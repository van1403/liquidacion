@php $e = strtolower(str_replace(' ', '_', (string) $estado)); @endphp
@if ($e === 'concluido')
    <span class="tag tag-ok">Concluido</span>
@elseif ($e === 'en_proceso')
    <span class="tag tag-warn">En proceso</span>
@else
    <span class="tag tag-neutral">{{ $estado ?: 'Sin estado' }}</span>
@endif
