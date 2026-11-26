@extends('layouts.app')
@section('title', 'Reportes')
@section('header', 'Reportes')

@section('content')
<p class="mb-4 text-sm text-slate-600">Todos los reportes se pueden ver en pantalla, imprimir y exportar a PDF y Excel, con filtros por fechas y otros criterios.</p>
<div class="grid gap-4 md:grid-cols-2">
    @forelse ($reportes as $modulo => $lista)
        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ $modulo }}</h2></div>
            <ul class="divide-y divide-slate-100">
                @foreach ($lista as $r)
                    <li class="px-5 py-3">
                        <a href="{{ route('reportes.show', $r->clave()) }}" class="font-medium text-brand-700 hover:underline">{{ $r->titulo() }}</a>
                        <p class="text-xs text-slate-500">{{ $r->descripcion() }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    @empty
        <div class="alert-info">No tiene reportes disponibles para su rol.</div>
    @endforelse
</div>
@endsection
