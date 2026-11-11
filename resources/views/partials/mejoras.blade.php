<script>
document.addEventListener('DOMContentLoaded', function () {
    var page = document.querySelector('.page');
    if (!page) return;
    var CTRL = 'input:not([type=hidden]):not([type=checkbox]):not([type=radio]):not([type=submit]):not([type=button]), select, textarea';
    var PROPIO = '.field, .form-grid, .filtros, .search, .opcion, .ficha, .info-grid, .panel, .vacio-box, .page-actions, .acciones, .form-actions';
    var esCtrl = function (el) { return el && el.matches && el.matches(CTRL); };

    page.querySelectorAll('form br').forEach(function (b) { b.remove(); });

    // Botones
    page.querySelectorAll('button:not(.btn):not(#btn-tema), input[type=submit]:not(.btn), input[type=button]:not(.btn)').forEach(function (b) {
        var t = (b.textContent || b.value || '').trim().toLowerCase();
        b.classList.add('btn');
        if (b.closest('td')) b.classList.add('btn-sm');
        if (/eliminar|borrar/.test(t)) b.classList.add('btn-danger');
        else if (/^(filtrar|guardar|importar|registrar|crear|actualizar|procesar)/.test(t)) b.classList.add('btn-primary');
    });

    // Enlaces de acción como botones
    page.querySelectorAll('a:not(.btn)').forEach(function (a) {
        if (a.closest(PROPIO)) return;
        var td = a.closest('td');
        if (td) { if (td.querySelectorAll('a, button').length > 1) a.classList.add('btn', 'btn-sm'); return; }
        var p = a.parentElement;
        var textoSuelto = Array.from(p.childNodes).some(function (n) { return n.nodeType === 3 && n.textContent.trim(); });
        if (textoSuelto || /^(P|LI|SPAN|SMALL)$/.test(p.tagName)) return;
        a.classList.add('btn');
        if (/^(nuevo|nueva|\+)/i.test(a.textContent.trim())) a.classList.add('btn-primary');
    });

    // Etiqueta + campo
    page.querySelectorAll('label').forEach(function (lb) {
        if (lb.closest(PROPIO + ', .field-auto, td')) return;
        if (lb.querySelector(CTRL)) { lb.classList.add('field-auto', 'field-wrap'); return; }
        var sig = lb.nextElementSibling;
        if (!esCtrl(sig)) return;
        var padre = lb.parentElement;
        if (padre.tagName !== 'FORM' && padre.children.length === 2) { padre.classList.add('field-auto'); return; }
        var d = document.createElement('div'); d.className = 'field-auto';
        lb.before(d); d.append(lb, sig);
    });

    // Campos sin etiqueta
    page.querySelectorAll(CTRL).forEach(function (c) {
        if (c.closest(PROPIO + ', .field-auto, td')) return;
        var d = document.createElement('div'); d.className = 'field-auto';
        c.before(d); d.append(c);
    });

    // Agrupar campos y botones consecutivos
    var agrupar = function (selector, clase, excluir) {
        var padres = new Set();
        page.querySelectorAll(selector).forEach(function (el) { if (!el.closest(excluir)) padres.add(el.parentElement); });
        padres.forEach(function (padre) {
            var run = [];
            var cerrar = function () {
                if (run.length > 1) {
                    var g = document.createElement('div'); g.className = clase;
                    run[0].before(g); run.forEach(function (x) { g.append(x); });
                }
                run = [];
            };
            Array.from(padre.children).forEach(function (h) { if (h.matches(selector)) run.push(h); else cerrar(); });
            cerrar();
        });
    };
    agrupar('.field-auto', 'grid-auto', 'td, .grid-auto');
    agrupar('.btn', 'actions-row', 'td, ' + PROPIO + ', .actions-row, .topbar');

    // Tarjetas para formularios y tablas con scroll
    page.querySelectorAll('form').forEach(function (f) {
        if (f.closest('td, .panel, .card-auto') || f.classList.contains('panel')) return;
        if (f.querySelector('.field-auto, input[type=file]')) f.classList.add('card-auto');
    });
    page.querySelectorAll('table').forEach(function (t) {
        if (t.closest('.table-wrap')) return;
        var w = document.createElement('div'); w.className = 'table-wrap';
        t.before(w); w.append(t);
    });

    // Cabecera institucional (EMPRESA, RUC, ...) como tarjetas
    var ETQ = ['EMPRESA', 'RUC', 'CONTRATO', 'DIVISION', 'DIVISIÓN', 'NOMBRE DEL SERVICIO CONTRATADO', 'RESPONSABLE',
               'PERIODO DE EJECUCION CONTRACTUAL', 'PERIODO DE EJECUCIÓN CONTRACTUAL', 'FECHA CONTRACTUAL PARA INGRESO DE ENTREGABLE'];
    var porPadre = new Map();
    page.querySelectorAll('p, div, span, dt, strong, label, h4, small').forEach(function (el) {
        if (el.closest('table, form, .ficha, .info-grid, .info-auto') || el.children.length) return;
        if (ETQ.indexOf(el.textContent.trim().toUpperCase()) === -1) return;
        var v = el.nextElementSibling;
        if (!v) return;
        if (!porPadre.has(el.parentElement)) porPadre.set(el.parentElement, []);
        porPadre.get(el.parentElement).push([el, v]);
    });
    porPadre.forEach(function (pares) {
        var grid = document.createElement('div'); grid.className = 'info-grid info-auto';
        pares[0][0].before(grid);
        pares.forEach(function (par) {
            var etq = par[0].textContent.trim();
            var card = document.createElement('div'); card.className = 'info';
            if (/SERVICIO|PERIODO/i.test(etq)) card.classList.add('wide');
            var s = document.createElement('span');
            s.textContent = etq.length <= 3 ? etq : etq.charAt(0) + etq.slice(1).toLowerCase();
            var st = document.createElement('strong');
            st.innerHTML = par[1].innerHTML;
            if (etq.toUpperCase() === 'RUC') st.classList.add('accent');
            card.append(s, st); grid.append(card);
            par[0].remove(); par[1].remove();
        });
    });

    // Estados como etiquetas de color
    var EST = /^(activo|inactivo|conforme|no conforme|pendiente|en proceso|observado|concluido|completado|entregado|si|sí|no|efectivo|no efectivo|efectiva|no efectiva|visitado|no visitado)$/i;
    page.querySelectorAll('td').forEach(function (td) {
        if (td.children.length) return;
        var t = td.textContent.trim();
        if (!EST.test(t)) return;
        var cls = /^(no|inactivo|observado)/i.test(t) ? 'tag-bad' : /pend|proceso/i.test(t) ? 'tag-warn' : 'tag-ok';
        td.innerHTML = '<span class="tag ' + cls + '"></span>';
        td.firstChild.textContent = t;
    });
});
</script>
