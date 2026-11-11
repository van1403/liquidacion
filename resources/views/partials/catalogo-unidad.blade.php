<script>
document.addEventListener('DOMContentLoaded', function () {
    var datos = window.CATALOGO_UNIDADES || [];
    if (!datos.length) { console.warn('SANTRIX: el catálogo de CODIGO UNIDAD está vacío'); return; }

    var input = document.querySelector('[name="codigo_unidad"], #codigo_unidad');
    if (!input) {
        document.querySelectorAll('label').forEach(function (lb) {
            if (input) return;
            if (lb.textContent.trim().toUpperCase().replace(/_/g, ' ') !== 'CODIGO UNIDAD') return;
            input = lb.htmlFor ? document.getElementById(lb.htmlFor)
                  : (lb.querySelector('input') || lb.nextElementSibling);
        });
    }
    if (!input || input.tagName === 'SELECT') { console.warn('SANTRIX: no encontré el campo CODIGO UNIDAD'); return; }

    var actual = (input.value || '').trim();
    var select = document.createElement('select');
    select.name = input.name;
    if (input.id) select.id = input.id;
    select.className = input.className;
    select.dataset.ancho = '2';
    if (input.required) select.required = true;
    select.add(new Option('Selecciona la acción específica', ''));

    var corto = function (t) { return t.replace(/\s*\(Acción de Fiscalización:[^)]*\)\s*$/i, ''); };
    var encontrado = false;
    datos.forEach(function (d) {
        var op = new Option(d.codigo + ' - ' + corto(d.nombre), d.codigo);
        op.title = d.nombre;
        if (d.codigo === actual) { op.selected = true; encontrado = true; }
        select.add(op);
    });
    if (actual && !encontrado) {
        var extra = new Option(actual + ' (valor anterior)', actual);
        extra.selected = true;
        select.add(extra, 1);
    }

    var ayuda = document.createElement('small');
    ayuda.className = 'ayuda-unidad';
    var pintar = function () {
        var d = datos.find(function (x) { return x.codigo === select.value; });
        ayuda.textContent = d ? d.nombre : '';
    };
    select.addEventListener('change', pintar);
    input.replaceWith(select);
    select.after(ayuda);
    pintar();
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        var select = document.querySelector('select[name="codigo_unidad"]');
        if (!select || document.getElementById('buscar-unidad')) return;

        var buscar = document.createElement('input');
        buscar.type = 'search';
        buscar.id = 'buscar-unidad';
        buscar.className = select.className;
        buscar.placeholder = 'Buscar acción: código o palabra (ej. GPS, 25.3, GLP)';
        buscar.style.marginBottom = '6px';
        select.before(buscar);

        var opciones = Array.from(select.options);
        var quitarTildes = function (t) { return t.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase(); };

        buscar.addEventListener('input', function () {
            var q = quitarTildes(buscar.value.trim());
            var visibles = 0;
            opciones.forEach(function (op, i) {
                if (i === 0) return;
                var ok = !q || quitarTildes(op.text + ' ' + (op.title || '')).includes(q);
                op.hidden = !ok;
                if (ok) visibles++;
            });
            select.options[0].text = q ? visibles + ' resultado(s), elige uno' : 'Selecciona la acción específica';
            if (q && visibles === 1) {
                var unica = opciones.find(function (op, i) { return i > 0 && !op.hidden; });
                select.value = unica.value;
                select.dispatchEvent(new Event('change'));
            }
        });
    }, 0);
});
</script>
