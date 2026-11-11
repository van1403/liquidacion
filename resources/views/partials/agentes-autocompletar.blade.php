@php
    $rutaAgentes = database_path('data/agentes.json');
    $agentes = file_exists($rutaAgentes) ? (json_decode(file_get_contents($rutaAgentes), true) ?? []) : [];
@endphp
<datalist id="lista-agentes">
    @foreach ($agentes as $cod => $a)
        <option value="{{ $cod }}">{{ $a['r'] }}</option>
    @endforeach
</datalist>
<script>
window.AGENTES = @json($agentes);
document.addEventListener('DOMContentLoaded', function () {
    var A = window.AGENTES || {};
    var cod = document.querySelector('[name="codigo_osinergmin"]');
    if (!cod || !Object.keys(A).length) return;

    var act = document.querySelector('[name="codigo_actividad"]');
    var raz = document.querySelector('[name="razon_social_agente"]');

    cod.setAttribute('list', 'lista-agentes');
    cod.setAttribute('autocomplete', 'off');
    cod.placeholder = 'Código o nombre del agente';

    var aviso = document.createElement('small');
    aviso.className = 'ayuda-agente';
    cod.after(aviso);

    var resaltar = function (el) {
        if (!el) return;
        el.classList.add('autollenado');
        setTimeout(function () { el.classList.remove('autollenado'); }, 1500);
    };

    var buscar = function (rellenar) {
        var c = cod.value.trim().replace(/^0+/, '');
        if (!c) { aviso.textContent = ''; return; }
        var a = A[c];
        if (!a) {
            aviso.className = 'ayuda-agente sin-dato';
            aviso.textContent = 'Este código no está en la relación de agentes. Completa los datos a mano.';
            return;
        }
        if (rellenar) {
            if (act) { act.value = a.a.split(' - ')[0].trim(); resaltar(act); }
            if (raz) { raz.value = a.r; resaltar(raz); }
        }
        aviso.className = 'ayuda-agente';
        aviso.textContent = a.a + ' | RUC ' + a.ruc + ' | ' + a.u;
    };

    cod.addEventListener('input', function () { buscar(true); });
    cod.addEventListener('change', function () { buscar(true); });
    if (cod.value) buscar(false);
});
</script>
