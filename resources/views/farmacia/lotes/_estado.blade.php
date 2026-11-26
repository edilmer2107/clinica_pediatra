@php($clases = ['activo' => 'badge-green', 'cuarentena' => 'badge-yellow', 'retirado' => 'badge-red', 'agotado' => 'badge-gray'])
<span class="{{ $clases[$lote->estado] ?? 'badge-gray' }}">{{ \App\Models\Lot::ESTADOS[$lote->estado] ?? $lote->estado }}</span>
