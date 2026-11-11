@php
    $texto = trim((string) ($valor ?? ''));
    $t = mb_strtolower($texto);
    $clase = match (true) {
        $t === '' => 'tag-neutral',
        str_starts_with($t, 'no') || str_contains($t, 'observ') || str_contains($t, 'rechaz')
            || str_contains($t, 'anul') || str_contains($t, 'inactiv') => 'tag-bad',
        str_contains($t, 'pend') || str_contains($t, 'proceso') || str_contains($t, 'revis') => 'tag-warn',
        str_contains($t, 'conform') || str_contains($t, 'conclu') || str_contains($t, 'complet')
            || str_contains($t, 'activo') || str_contains($t, 'entreg')
            || in_array($t, ['si', 'sí', '1', 'efectivo', 'efectiva', 'visitado']) => 'tag-ok',
        default => 'tag-info',
    };
@endphp
<span class="tag {{ $clase }}">{{ $texto !== '' ? $texto : 'Sin dato' }}</span>
