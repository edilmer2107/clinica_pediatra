<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $usuarios = User::with('roles')
            ->when($request->filled('rol'), fn ($q) => $q->role($request->query('rol')))
            ->when($request->filled('buscar'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'ilike', '%'.$request->query('buscar').'%')
                ->orWhere('email', 'ilike', '%'.$request->query('buscar').'%')))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.usuarios.index', ['usuarios' => $usuarios, 'roles' => User::ROLES]);
    }

    public function create(): View
    {
        return view('admin.usuarios.form', ['usuario' => new User(['activo' => true]), 'roles' => $this->rolesInternos()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $usuario = User::create(collect($datos)->except('rol')->all() + ['email_verified_at' => now()]);
        $usuario->syncRoles([$datos['rol']]);
        $this->audit->registrar('crear_usuario', 'administracion', "Creación del usuario {$usuario->email} con rol {$datos['rol']}.", $usuario);

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado.');
    }

    public function edit(User $usuario): View
    {
        abort_if($usuario->esTutor(), 404);

        return view('admin.usuarios.form', ['usuario' => $usuario, 'roles' => $this->rolesInternos()]);
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        abort_if($usuario->esTutor(), 404);
        $datos = $this->validar($request, $usuario);
        if ($usuario->is($request->user()) && ($datos['rol'] !== 'administrador' || ! $datos['activo'])) {
            return back()->with('error', 'No puede quitarse el rol de administrador ni desactivar su propia cuenta.');
        }

        $antes = ['rol' => $usuario->getRoleNames()->first(), 'activo' => $usuario->activo];
        if (empty($datos['password'])) {
            unset($datos['password']);
        }
        $usuario->update(collect($datos)->except('rol')->all());
        $usuario->syncRoles([$datos['rol']]);
        $this->audit->registrar('editar_usuario', 'administracion', "Edición del usuario {$usuario->email}.", $usuario, $antes, ['rol' => $datos['rol'], 'activo' => $datos['activo']]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado.');
    }

    private function validar(Request $request, ?User $usuario = null): array
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users')->ignore($usuario)],
            'dni' => ['nullable', 'regex:/^\d{8}$/', Rule::unique('users')->ignore($usuario)],
            'telefono' => ['nullable', 'regex:/^9\d{8}$/'],
            'especialidad' => ['nullable', 'string', 'max:60'],
            'colegiatura' => ['nullable', 'string', 'max:20'],
            'rol' => ['required', Rule::in(array_keys($this->rolesInternos()))],
            'password' => [$usuario ? 'nullable' : 'required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ]);
        $datos['email'] = Str::lower($datos['email']);
        $datos['activo'] = $request->boolean('activo');

        return $datos;
    }

    private function rolesInternos(): array
    {
        return collect(User::ROLES)->except('tutor')->only(Role::pluck('name')->all())->all();
    }
}
