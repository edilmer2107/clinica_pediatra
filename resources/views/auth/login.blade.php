@extends('layouts.guest')

@section('title', 'Iniciar sesión')

@section('content')
<div class="w-full max-w-md">
    <div class="mb-6 text-center">
        <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-600 text-3xl font-bold text-white">+</div>
        <h1 class="text-2xl font-bold text-brand-900">Clínica Pediátrica</h1>
        <p class="text-sm text-slate-600">con Farmacia Integrada</p>
    </div>
    <div class="card">
        <div class="card-body">
            <h2 class="mb-4 text-lg font-semibold">Iniciar sesión</h2>
            @include('partials.flash')
            <form method="POST" action="{{ route('login.store') }}" class="space-y-4" novalidate>
                @csrf
                <div>
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" class="form-input" required autofocus autocomplete="username">
                </div>
                <div>
                    <label for="password" class="form-label">Contraseña</label>
                    <input id="password" name="password" type="password" class="form-input" required autocomplete="current-password">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="rounded border-slate-300"> Recordarme
                </label>
                <button type="submit" class="btn-primary w-full">Ingresar</button>
            </form>
        </div>
    </div>
    <p class="mt-4 text-center text-xs text-slate-500">Personal de la clínica y tutores registrados. Versión {{ config('clinica.version') }}</p>
</div>
@endsection
