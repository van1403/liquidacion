<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | SANTRIX Anexo Local</title>
    <script>
        try { if (localStorage.getItem('tema') === 'dark') document.documentElement.dataset.theme = 'dark'; } catch (e) {}
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>{!! file_get_contents(public_path('css/santrix.css')) !!}</style>
</head>
<body>
@php
    $menu = [
        ['url' => 'dashboard', 'match' => 'dashboard*', 'texto' => 'Dashboard',
         'icono' => '<path d="M4 13h6V4H4zM14 20h6v-9h-6zM4 20h6v-3H4zM14 4v3h6V4z"/>'],
        ['url' => 'anexos', 'match' => 'anexos*', 'texto' => 'Configuración Anexo A',
         'icono' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M4.9 19.1 7 17M17 7l2.1-2.1"/>'],
        ['url' => 'registros', 'match' => 'registros*', 'texto' => 'Registros Anexo A',
         'icono' => '<path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/>'],
        ['url' => 'importaciones/anexo-a', 'match' => 'importaciones*', 'texto' => 'Importar Excel',
         'icono' => '<path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2z"/><path d="M12 17v-6M9.5 13.5 12 11l2.5 2.5"/>'],
    ];
@endphp
<div class="app">
    <aside class="sidebar">
        <a class="brand" href="{{ url('dashboard') }}">
            <small>SANTRIX</small>
            <strong>Anexo Local</strong>
            <p>Gestión local del Anexo A en Laravel + SQLite.</p>
        </a>
        <nav class="nav" aria-label="Menú principal">
            @foreach ($menu as $item)
                @php $activo = request()->is($item['match']); @endphp
                <a href="{{ url($item['url']) }}" class="{{ $activo ? 'active' : '' }}" @if($activo) aria-current="page" @endif>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icono'] !!}</svg>
                    {{ $item['texto'] }}
                </a>
            @endforeach
        </nav>
        <div class="sidebar-foot">Los datos se guardan en este equipo.</div>
    </aside>

    <div class="main">
        <header class="topbar">
            <div>
                <small>Sistema local</small>
                @php
                    $tituloAuto = match (true) {
                        request()->is('registros/create') => 'Nuevo registro',
                        request()->is('registros/*/edit') => 'Editar registro',
                        request()->is('registros/exportar*') => 'Registros Anexo A',
                        request()->is('registros/*') => 'Detalle del registro',
                        request()->is('registros*') => 'Registros Anexo A',
                        request()->is('anexos/create') => 'Nuevo anexo',
                        request()->is('anexos/*/edit') => 'Editar anexo',
                        request()->is('anexos/*') => 'Detalle del anexo',
                        request()->is('anexos*') => 'Configuración del Anexo',
                        request()->is('importaciones*') => 'Importar Excel',
                        default => 'Dashboard',
                    };
                @endphp
                <h1>@yield('heading', $tituloAuto)</h1>
                @hasSection('subheading')<p>@yield('subheading')</p>@endif
            </div>
            <div class="topbar-tools">
                <button class="pill" type="button" id="btn-tema" aria-pressed="false">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                    <span>Modo oscuro</span>
                </button>
                <span class="pill pill-date">{{ now()->format('d/m/Y H:i') }}</span>
            </div>
        </header>

        <main class="page">
            @hasSection('actions')<div class="page-actions">@yield('actions')</div>@endif

            @if (session('success'))
                <div class="alert alert-ok" role="status">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-bad" role="alert">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-bad" role="alert">
                    Revisa los campos marcados antes de guardar:
                    <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script>
    (function () {
        var btn = document.getElementById('btn-tema');
        var html = document.documentElement;
        var pintar = function () {
            var oscuro = html.dataset.theme === 'dark';
            btn.querySelector('span').textContent = oscuro ? 'Modo claro' : 'Modo oscuro';
            btn.setAttribute('aria-pressed', oscuro);
        };
        btn.addEventListener('click', function () {
            if (html.dataset.theme === 'dark') { delete html.dataset.theme; } else { html.dataset.theme = 'dark'; }
            try { localStorage.setItem('tema', html.dataset.theme || 'light'); } catch (e) {}
            pintar();
        });
        pintar();
    })();
</script>
@if (request()->is('registros/create') || request()->is('registros/*/edit'))
@php
    $catalogoUnidades = \Illuminate\Support\Facades\DB::table('catalogos')
        ->where('tipo', 'codigo_unidad')->where('activo', 1)
        ->orderBy('id')->get(['codigo', 'nombre']);
@endphp
<script>window.CATALOGO_UNIDADES = @json($catalogoUnidades);</script>
@include('partials.catalogo-unidad')
@include('partials.agentes-autocompletar')
@endif
@include('partials.mejoras')
@stack('scripts')
</body>
</html>
