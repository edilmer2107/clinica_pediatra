<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal de tutores') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
<header class="no-print bg-brand-900 text-white">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
        <a href="{{ route('portal.index') }}" class="flex items-center gap-2"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white font-bold text-brand-700">+</span><span class="font-semibold">Portal de tutores</span></a>
        <div class="flex items-center gap-3 text-sm"><span class="hidden sm:inline">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-lg border border-white/30 px-3 py-1 hover:bg-white/10">Cerrar sesión</button></form></div>
    </div>
</header>
<main class="mx-auto max-w-6xl p-4">
    @include('partials.flash')
    @yield('content')
</main>
@stack('scripts')
</body>
</html>
