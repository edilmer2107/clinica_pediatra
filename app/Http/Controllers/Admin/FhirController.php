<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\AuditService;
use App\Services\Interoperabilidad\FhirService;
use App\Services\Pacientes\PercentilOmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FhirController extends Controller
{
    public function index(): View
    {
        return view('admin.fhir', ['pacientes' => Patient::orderBy('apellidos')->get(['id', 'nombres', 'apellidos', 'historia_clinica'])]);
    }

    public function exportar(Patient $paciente, FhirService $fhir, AuditService $audit): JsonResponse
    {
        $bundle = $fhir->exportarPaciente($paciente);
        $audit->registrar('exportar_fhir', 'interoperabilidad', "Exportación HL7 FHIR del paciente {$paciente->historia_clinica} ({$bundle['total']} recursos).", $paciente);

        return response()->json($bundle, 200, [
            'Content-Type' => 'application/fhir+json',
            'Content-Disposition' => "attachment; filename=\"fhir_{$paciente->historia_clinica}.json\"",
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function importar(Request $request, FhirService $fhir, PercentilOmsService $oms, AuditService $audit): RedirectResponse
    {
        $this->authorize('fhir.importar');
        $request->validate(['archivo' => ['required', 'file', 'mimes:json,txt', 'max:2048']]);

        $bundle = json_decode((string) file_get_contents($request->file('archivo')->getRealPath()), true);
        if (! is_array($bundle)) {
            return back()->with('error', 'El archivo no contiene JSON válido.');
        }

        $r = $fhir->importar($bundle, $oms);
        $audit->registrar('importar_fhir', 'interoperabilidad', "Importación HL7 FHIR: paciente {$r['paciente']->historia_clinica} ".($r['creado'] ? '(nuevo)' : '(existente)').", {$r['observaciones']} mediciones.", $r['paciente']);

        return redirect()->route('pacientes.show', [$r['paciente'], 'tab' => 'crecimiento'])->with('success',
            'Importación FHIR completada: paciente '.($r['creado'] ? 'creado' : 'existente')." {$r['paciente']->historia_clinica}, {$r['observaciones']} mediciones importadas, {$r['omitidas']} omitidas."
            .($r['creado'] ? ' Registre el consentimiento informado del tutor antes de atenderlo.' : ''));
    }
}
