<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('codigo') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 p-4 text-slate-800">
    <div class="card max-w-md text-center"><div class="card-body">
        <div class="text-5xl font-bold text-brand-700">@yield('codigo')</div>
        <h1 class="mt-2 text-lg font-semibold">@yield('titulo')</h1>
        <p class="mt-2 text-sm text-slate-600">@yield('mensaje')</p>
        <a href="{{ url('/') }}" class="btn-primary mt-4">Volver al inicio</a>
    </div></div>
</body>
</html>
