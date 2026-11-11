@extends('layouts.app')

@section('title', 'Importar Excel')
@section('heading', 'Importar Excel')
@section('subheading', 'Carga las filas del Anexo A desde un Excel y elige en qué anexo guardarlas.')

@php
    $anexos = $anexos ?? collect();
    $sel = (string) old('anexo_id', $anexoSeleccionado ?? '');
@endphp

@section('content')
<div class="dos-col">
    <form class="panel" method="POST" action="{{ route('importaciones.store') }}" enctype="multipart/form-data" id="form-importar">
        @csrf

        <div class="field">
            <label for="anexo_id">¿En qué anexo se guardan las filas?</label>
            <select class="input" id="anexo_id" name="anexo_id">
                <option value="">Crear un anexo nuevo con la cabecera del Excel</option>
                @foreach ($anexos as $a)
                    <option value="{{ $a->id }}" data-filas="{{ $a->registros_count }}" @selected($sel === (string) $a->id)>
                        #{{ $a->id }} - {{ $a->titulo }} ({{ $a->registros_count }} filas)
                    </option>
                @endforeach
            </select>
            @error('anexo_id')<span class="error">{{ $message }}</span>@enderror
        </div>

        <fieldset id="bloque-modo" class="seccion" hidden>
            <legend>Este anexo ya tiene filas</legend>
            <div class="opciones">
                <label class="opcion"><input type="radio" name="modo" value="agregar" @checked(old('modo', 'agregar') === 'agregar')><span>Agregar al final</span></label>
                <label class="opcion"><input type="radio" name="modo" value="reemplazar" @checked(old('modo') === 'reemplazar')><span>Reemplazar todas las filas</span></label>
            </div>
            <small id="texto-modo" style="display:block;margin-top:8px;color:var(--muted)"></small>
        </fieldset>

        <div class="seccion">
            <label class="dropzone" id="dropzone" for="archivo">
                <input type="file" id="archivo" name="archivo" accept=".xlsx,.xls" required
                       style="position:absolute;width:1px;height:1px;opacity:0;padding:0;border:0">
                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2z"/><path d="M12 17v-6M9.5 13.5 12 11l2.5 2.5"/></svg>
                <strong>Arrastra tu Excel aquí o haz clic para elegirlo</strong>
                <span>Formatos .xlsx o .xls</span>
                <div class="archivo-elegido" id="archivo-elegido" aria-live="polite"></div>
            </label>
            @error('archivo')<p class="error">{{ $message }}</p>@enderror
        </div>

        <div class="form-actions">
            <a class="btn" href="{{ route('dashboard') }}">Volver</a>
            <button class="btn btn-primary" type="submit" id="btn-importar">Importar filas</button>
        </div>
    </form>

    <div class="stack">
        <section class="panel">
            <div class="panel-head"><h2>Cómo funciona</h2></div>
            <ol class="pasos">
                <li><div><strong>Elige el anexo</strong><p>Un entregable que ya existe o uno nuevo. Si es nuevo, el título y el periodo se toman de la cabecera del Excel.</p></div></li>
                <li><div><strong>Sube el Excel</strong><p>El sistema busca la fila donde aparece ITEM y lee las filas de detalle que siguen.</p></div></li>
                <li><div><strong>Revisa el resultado</strong><p>Al terminar se abre el anexo con las filas importadas.</p></div></li>
            </ol>
        </section>
        <section class="panel">
            <div class="panel-head"><h2>Exportar por anexo</h2></div>
            <p style="margin:0 0 12px;color:var(--muted)">La descarga del Excel se hace desde el detalle de cada anexo, para no mezclar registros.</p>
            <a class="btn" href="{{ route('anexos.index') }}">Ir a Anexos</a>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('form-importar');
    var sel = document.getElementById('anexo_id');
    var bloque = document.getElementById('bloque-modo');
    var txt = document.getElementById('texto-modo');
    var modo = function () { return (form.querySelector('[name=modo]:checked') || {}).value || 'agregar'; };
    var filas = function () { return parseInt(sel.options[sel.selectedIndex].dataset.filas || '0', 10); };

    var pintar = function () {
        bloque.hidden = !sel.value || filas() === 0;
        if (bloque.hidden) { txt.textContent = ''; return; }
        txt.textContent = modo() === 'reemplazar'
            ? 'Se borrarán las ' + filas() + ' filas actuales y se cargarán las del Excel.'
            : 'Las filas nuevas se agregarán después de las ' + filas() + ' existentes, continuando la numeración de ITEM.';
    };
    sel.addEventListener('change', pintar);
    form.querySelectorAll('[name=modo]').forEach(function (r) { r.addEventListener('change', pintar); });
    pintar();

    form.addEventListener('submit', function (e) {
        if (!bloque.hidden && modo() === 'reemplazar'
            && !confirm('Se borrarán las ' + filas() + ' filas del anexo #' + sel.value + '. ¿Continuar?')) {
            e.preventDefault();
            return;
        }
        var b = document.getElementById('btn-importar');
        b.disabled = true;
        b.textContent = 'Importando…';
    });

    var zona = document.getElementById('dropzone');
    var input = document.getElementById('archivo');
    var nombre = document.getElementById('archivo-elegido');
    var mostrar = function () { nombre.textContent = input.files.length ? 'Archivo elegido: ' + input.files[0].name : ''; };
    input.addEventListener('change', mostrar);
    ['dragenter', 'dragover'].forEach(function (ev) { zona.addEventListener(ev, function (e) { e.preventDefault(); zona.classList.add('over'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { zona.addEventListener(ev, function (e) { e.preventDefault(); zona.classList.remove('over'); }); });
    zona.addEventListener('drop', function (e) { if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; mostrar(); } });
})();
</script>
@endpush
