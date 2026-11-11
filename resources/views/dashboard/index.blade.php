@extends('layouts.app')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('subheading', 'Resumen del Anexo A y de los entregables registrados.')

@section('actions')
    <a class="btn" href="{{ url('registros/exportar-hoy') }}">Exportar registros de hoy</a>
    <a class="btn" href="{{ url('registros/exportar-todo') }}">Exportar todo</a>
    <a class="btn btn-primary" href="{{ url('registros/create') }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
        Nuevo registro
    </a>
@endsection

@php
    $DB = \Illuminate\Support\Facades\DB::class;
    $cfg = $DB::table('configuracion_anexo')->orderByDesc('id')->first();

    $anexosLista = $DB::table('anexos')->select('anexos.*')
        ->selectSub($DB::table('registros_anexo')->selectRaw('count(*)')->whereColumn('registros_anexo.anexo_id', 'anexos.id'), 'filas')
        ->orderBy('id')->get();

    $regs = $DB::table('registros_anexo')->get(['visitado', 'efectividad', 'estado_entregable', 'created_at']);
    $norm = fn ($v) => mb_strtolower(trim((string) $v));
    $esSi = fn ($v) => in_array($norm($v), ['si', 'sí', '1', 'true', 'x', 'visitado', 'efectivo', 'efectiva']);
    $tieneDato = fn ($v) => $norm($v) !== '';

    $hoy = now()->toDateString();
    $total = $regs->count();
    $deHoy = $regs->filter(fn ($r) => substr((string) $r->created_at, 0, 10) === $hoy)->count();
    $visitados = $regs->filter(fn ($r) => $esSi($r->visitado))->count();
    $noVisitados = $regs->filter(fn ($r) => $tieneDato($r->visitado) && !$esSi($r->visitado))->count();
    $efectivos = $regs->filter(fn ($r) => $esSi($r->efectividad))->count();
    $noEfectivos = $regs->filter(fn ($r) => $tieneDato($r->efectividad) && !$esSi($r->efectividad))->count();
    $porEstado = $regs->groupBy(fn ($r) => trim((string) $r->estado_entregable) ?: 'Sin estado')
                      ->map->count()->sortDesc();

    $pct = fn ($a, $b) => ($a + $b) > 0 ? round($a * 100 / ($a + $b)) : 0;
    $ultimos = $DB::table('registros_anexo')->orderByDesc('id')->limit(10)->get();
    $fecha = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d/m/Y') : '';
@endphp

@section('content')
<div class="stack">

    <section class="ficha" aria-label="Cabecera del Anexo A">
        <div class="ficha-title">
            <span>Resumen de los servicios y/o actividades de supervisión y/o fiscalización</span>
            <a href="{{ url('anexos') }}">Editar cabecera</a>
        </div>
        <div class="ficha-grid">
            <div class="ficha-cell" style="grid-column:span 2"><span>Empresa</span><strong>{{ $cfg->empresa ?? 'Sin definir' }}</strong></div>
            <div class="ficha-cell"><span>RUC</span><strong style="color:var(--accent)">{{ $cfg->ruc ?? 'Sin definir' }}</strong></div>
            <div class="ficha-cell"><span>Contrato</span><strong>{{ $cfg->contrato ?? 'Sin definir' }}</strong></div>
            <div class="ficha-cell"><span>División</span><strong>@if(!empty($cfg->division))<span class="tag tag-info">{{ $cfg->division }}</span>@else Sin definir @endif</strong></div>
            <div class="ficha-cell" style="grid-column:span 3"><span>Nombre del servicio contratado</span><strong>{{ $cfg->nombre_servicio_contratado ?? 'Sin definir' }}</strong></div>
        </div>
    </section>

    <section class="cifras" aria-label="Totales">
        <div class="cifra"><span>Entregables (anexos)</span><b>{{ $anexosLista->count() }}</b></div>
        <div class="cifra ok"><span>Registros en total</span><b>{{ $total }}</b></div>
        <div class="cifra warn"><span>Registrados hoy</span><b>{{ $deHoy }}</b></div>
    </section>

    <section class="balances" aria-label="Distribución de registros">
        @foreach ([['Visitas en campo', 'Visitados', $visitados, 'No visitados', $noVisitados],
                   ['Efectividad', 'Efectivos', $efectivos, 'No efectivos', $noEfectivos]] as [$tit, $la, $a, $lb, $b])
            <div class="panel balance">
                <h3>{{ $tit }}</h3>
                <div class="bar" role="img" aria-label="{{ $la }}: {{ $a }}, {{ $lb }}: {{ $b }}">
                    @if ($a + $b > 0)<i class="a" style="width:{{ $pct($a, $b) }}%"></i><i class="b" style="width:{{ 100 - $pct($a, $b) }}%"></i>@endif
                </div>
                <div class="leyenda">
                    <span><i class="dot a"></i><b>{{ $a }}</b>{{ $la }}</span>
                    <span><i class="dot b"></i><b>{{ $b }}</b>{{ $lb }}</span>
                </div>
            </div>
        @endforeach
        <div class="panel balance">
            <h3>Estado de los entregables</h3>
            @forelse ($porEstado->take(4) as $estado => $cant)
                <div style="display:flex;justify-content:space-between;align-items:center;padding:4px 0">
                    @include('partials.badge', ['valor' => $estado])<b>{{ $cant }}</b>
                </div>
            @empty
                <p style="margin:0;color:var(--muted)">Aún no hay registros.</p>
            @endforelse
        </div>
    </section>

    <section>
        <div class="panel-head"><h2>Entregables del Anexo A</h2><a class="btn btn-sm" href="{{ url('anexos') }}">Gestionar</a></div>
        @if ($anexosLista->isEmpty())
            <div class="vacio-box">
                <h3>No hay entregables configurados</h3>
                <p>Crea el primer entregable para empezar a asignarle registros.</p>
                <a class="btn btn-primary" href="{{ url('anexos/create') }}">Nuevo anexo</a>
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr><th>ID</th><th>Título</th><th>Periodo de ejecución</th><th>Ingreso del entregable</th><th>Filas</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($anexosLista as $a)
                        <tr>
                            <td class="num">#{{ $a->id }}</td>
                            <td><a class="code" href="{{ url('anexos/'.$a->id) }}">{{ $a->titulo }}</a></td>
                            <td style="max-width:280px">{{ $a->periodo_ejecucion_contractual }}</td>
                            <td>{{ $a->fecha_contractual_ingreso_entregable }}</td>
                            <td><b style="color:var(--accent)">{{ $a->filas }}</b></td>
                            <td>@include('partials.badge', ['valor' => $a->estado])</td>
                            <td>
                                <div class="acciones">
                                    <a class="btn btn-sm" href="{{ url('anexos/'.$a->id.'/exportar-excel') }}">Excel</a>
                                    <a class="btn btn-sm" href="{{ url('anexos/'.$a->id.'/exportar-pdf') }}">PDF</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section>
        <div class="panel-head"><h2>Últimos 10 registros</h2><a class="btn btn-sm" href="{{ url('registros') }}">Ver todos</a></div>
        @if ($ultimos->isEmpty())
            <div class="vacio-box">
                <h3>Todavía no hay registros</h3>
                <p>Agrega la primera fila del Anexo A a mano o impórtalas desde Excel.</p>
                <a class="btn btn-primary" href="{{ url('registros/create') }}">Nuevo registro</a>
                <a class="btn" href="{{ url('importaciones/anexo-a') }}">Importar Excel</a>
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Ítem</th><th>Código Osinergmin</th><th>Agente supervisado</th><th>Tipo de supervisión</th><th>Fecha de visita</th><th>Estado</th></tr></thead>
                    <tbody>
                    @foreach ($ultimos as $r)
                        <tr>
                            <td class="num">{{ $r->item }}</td>
                            <td><a class="code" href="{{ url('registros/'.$r->id.'/edit') }}">{{ $r->codigo_osinergmin ?: $r->codigo_unidad ?: '#'.$r->id }}</a></td>
                            <td>{{ $r->razon_social_agente }}</td>
                            <td>{{ $r->tipo_supervision }}</td>
                            <td>{{ $fecha($r->fecha_visita) }}</td>
                            <td>@include('partials.badge', ['valor' => $r->estado_entregable])</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
