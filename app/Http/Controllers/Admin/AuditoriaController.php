<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditoriaController extends Controller
{
    public function index(Request $request): View
    {
        $registros = AuditLog::query()
            ->when($request->filled('modulo'), fn ($q) => $q->where('modulo', $request->query('modulo')))
            ->when($request->filled('accion'), fn ($q) => $q->where('accion', $request->query('accion')))
            ->when($request->filled('usuario'), fn ($q) => $q->where('user_name', 'ilike', '%'.$request->query('usuario').'%'))
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('desde')))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('hasta')))
            ->latest('id')->paginate(30)->withQueryString();

        return view('admin.auditoria.index', [
            'registros' => $registros,
            'modulos' => AuditLog::distinct()->orderBy('modulo')->pluck('modulo'),
            'acciones' => AuditLog::distinct()->orderBy('accion')->pluck('accion'),
            'verificacion' => session('verificacion'),
        ]);
    }

    public function verificar(AuditService $audit): RedirectResponse
    {
        $resultado = $audit->verificarCadena();

        return back()->with('verificacion', $resultado)->with($resultado['valida'] ? 'success' : 'error', $resultado['valida']
            ? "Cadena de auditoría íntegra: {$resultado['total']} registros verificados."
            : "¡Cadena de auditoría ALTERADA! Primer registro inconsistente: #{$resultado['primer_error_id']}.");
    }
}
