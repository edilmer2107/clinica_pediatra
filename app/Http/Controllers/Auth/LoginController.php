<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /** Intentos permitidos antes de bloquear temporalmente (protección contra fuerza bruta). */
    public const MAX_INTENTOS = 5;

    public const SEGUNDOS_BLOQUEO = 300;

    public function __construct(private readonly AuditService $audit) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $clave = Str::lower($credenciales['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($clave, self::MAX_INTENTOS)) {
            $segundos = RateLimiter::availableIn($clave);
            $this->audit->registrar('login_bloqueado', 'seguridad', "Acceso bloqueado temporalmente para {$credenciales['email']} por exceso de intentos.");

            throw ValidationException::withMessages([
                'email' => "Demasiados intentos fallidos. Intente nuevamente en ".ceil($segundos / 60).' minuto(s).',
            ]);
        }

        $credenciales['activo'] = true;

        if (! Auth::attempt($credenciales, $request->boolean('remember'))) {
            RateLimiter::hit($clave, self::SEGUNDOS_BLOQUEO);
            $this->audit->registrar('login_fallido', 'seguridad', "Intento de acceso fallido para {$credenciales['email']}.");

            throw ValidationException::withMessages([
                'email' => 'Las credenciales no son válidas o la cuenta está inactiva.',
            ]);
        }

        RateLimiter::clear($clave);
        $request->session()->regenerate();

        $usuario = $request->user();
        $usuario->forceFill(['ultimo_acceso_at' => now()])->save();
        $this->audit->registrar('login', 'seguridad', "Inicio de sesión de {$usuario->email}.", $usuario);

        return redirect()->intended($usuario->esTutor() ? route('portal.index') : route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $usuario = $request->user();
        if ($usuario) {
            $this->audit->registrar('logout', 'seguridad', "Cierre de sesión de {$usuario->email}.", $usuario);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
