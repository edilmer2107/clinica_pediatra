<?php

namespace App\Http\Controllers\Farmacia;

use App\Http\Controllers\Controller;
use App\Models\Dispensation;
use App\Models\MedicationError;
use App\Models\Prescription;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ErrorMedicacionController extends Controller
{
    public const CATEGORIAS = [
        'dosis_incorrecta' => 'Dosis incorrecta',
        'medicamento_equivocado' => 'Medicamento equivocado',
        'paciente_equivocado' => 'Paciente equivocado',
        'via_incorrecta' => 'Vía de administración incorrecta',
        'frecuencia_incorrecta' => 'Frecuencia incorrecta',
        'omision' => 'Omisión',
        'lote_vencido' => 'Lote vencido o inadecuado',
        'interaccion' => 'Interacción no advertida',
        'otro' => 'Otro',
    ];

    public function index(Request $request): View
    {
        $errores = MedicationError::with(['patient', 'reportadoPor', 'prescription', 'dispensation'])
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->query('tipo')))
            ->latest('fecha')->paginate(15)->withQueryString();

        return view('farmacia.errores.index', ['errores' => $errores, 'categorias' => self::CATEGORIAS]);
    }

    public function create(): View
    {
        return view('farmacia.errores.create', [
            'categorias' => self::CATEGORIAS,
            'recetas' => Prescription::latest()->limit(100)->get(['id', 'codigo']),
            'dispensaciones' => Dispensation::latest()->limit(100)->get(['id', 'codigo']),
        ]);
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $datos = $request->validate([
            'tipo' => ['required', Rule::in(['prescripcion', 'dispensacion'])],
            'categoria' => ['required', Rule::in(array_keys(self::CATEGORIAS))],
            'prescription_id' => ['nullable', 'exists:prescriptions,id'],
            'dispensation_id' => ['nullable', 'exists:dispensations,id'],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'gravedad' => ['required', Rule::in(['sin_dano', 'leve', 'moderado', 'grave'])],
            'descripcion' => ['required', 'string', 'max:2000'],
            'medida_correctiva' => ['required', 'string', 'max:2000'],
            // Evidencia: solo PDF o imágenes, máximo 5 MB (se valida el contenido real del archivo)
            'evidencia' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:5120'],
        ]);

        $patientId = null;
        if (! empty($datos['prescription_id'])) {
            $patientId = Prescription::find($datos['prescription_id'])->patient_id;
        } elseif (! empty($datos['dispensation_id'])) {
            $patientId = Dispensation::find($datos['dispensation_id'])->patient_id;
        }

        if ($request->hasFile('evidencia')) {
            $archivo = $request->file('evidencia');
            $datos['evidencia_path'] = $archivo->store('evidencias', 'local');
            $datos['evidencia_nombre'] = $archivo->getClientOriginalName();
        }
        unset($datos['evidencia']);

        $error = MedicationError::create($datos + ['patient_id' => $patientId, 'reportado_por' => $request->user()->id]);
        $audit->registrar('registro_error_medicacion', 'farmacia', "Registro de error de {$error->tipo}: ".self::CATEGORIAS[$error->categoria].'.', $error);

        return redirect()->route('errores.index')->with('success', 'Error de medicación registrado.');
    }

    public function evidencia(MedicationError $error): StreamedResponse
    {
        abort_unless($error->evidencia_path && Storage::disk('local')->exists($error->evidencia_path), 404);

        return Storage::disk('local')->download($error->evidencia_path, $error->evidencia_nombre);
    }
}
