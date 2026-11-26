<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Inicio') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased" x-data="{ menu: false }">
@php($menu = require resource_path('views/partials/menu.php'))
<div class="flex min-h-screen">
    {{-- Menú lateral --}}
    <aside class="no-print fixed inset-y-0 left-0 z-30 w-64 -translate-x-full overflow-y-auto bg-brand-900 px-3 py-4 transition lg:static lg:translate-x-0"
           :class="menu && 'translate-x-0'">
        <a href="{{ route('dashboard') }}" class="mb-4 flex items-center gap-2 px-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-lg font-bold text-brand-700">+</span>
            <span class="leading-tight text-white"><span class="block text-sm font-semibold">Clínica Pediátrica</span><span class="block text-xs text-slate-300">con Farmacia Integrada</span></span>
        </a>
        <nav>
            @foreach ($menu as $seccion => $items)
                @php($visibles = collect($items)->filter(fn ($i) => Route::has($i[1]) && ($i[2] === null || auth()->user()->can($i[2]))))
                @if ($visibles->isNotEmpty())
                    <div class="nav-section">{{ $seccion }}</div>
                    @foreach ($visibles as [$etiqueta, $ruta, $permiso, $patron])
                        <a href="{{ route($ruta) }}" class="nav-link {{ request()->routeIs($patron) ? 'nav-link-active' : '' }}">{{ $etiqueta }}</a>
                    @endforeach
                @endif
            @endforeach
        </nav>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        {{-- Barra superior --}}
        <header class="no-print sticky top-0 z-20 flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 lg:px-6">
            <div class="flex items-center gap-3">
                <button type="button" class="btn-secondary btn-sm lg:hidden" @click="menu = !menu" aria-label="Abrir menú">☰</button>
                <h1 class="text-lg font-semibold text-slate-800">@yield('header', 'Panel principal')</h1>
            </div>
            <div class="flex items-center gap-3">
                <div class="hidden text-right sm:block">
                    <div class="text-sm font-medium">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-slate-500">{{ auth()->user()->rolPrincipal() }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-secondary btn-sm">Cerrar sesión</button>
                </form>
            </div>
        </header>

        <main class="print-full flex-1 p-4 lg:p-6">
            @include('partials.flash')
            @yield('content')
        </main>

        <footer class="no-print border-t border-slate-200 bg-white px-6 py-3 text-xs text-slate-500">
            {{ config('app.name') }} · Versión {{ config('clinica.version') }} · {{ now()->year }}
        </footer>
    </div>
</div>
@stack('scripts')
</body>
</html>
