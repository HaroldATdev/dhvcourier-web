(function () {
    'use strict';
    function showToast(msg, type) {
        var t = document.getElementById('dhvToast');
        if (!t) return;
        t.textContent = msg;
        t.className = 'dhv-toast ' + type + ' show';
        setTimeout(function () { t.className = 'dhv-toast'; }, 3200);
    }
    function updateCount(group) {
        var n = document.querySelectorAll('.dhv-pedido-check[data-group="' + group + '"]:checked').length;
        var c = document.querySelector('.dhv-selected-count[data-group="' + group + '"]');
        if (c) c.textContent = n + ' seleccionado(s)';
    }
    function doAjax(data, callback) {
        var body = new URLSearchParams();
        Object.keys(data).forEach(function(k) {
            var v = data[k];
            if (Array.isArray(v)) { v.forEach(function(i) { body.append(k + '[]', i); }); }
            else { body.append(k, v); }
        });
        body.append('nonce', dhvRutas.nonce);
        fetch(dhvRutas.ajax_url, { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: body.toString() })
            .then(function(r){ return r.json(); }).then(callback)
            .catch(function(){ showToast('Error de conexión.', 'error'); });
    }
    document.addEventListener('click', function(e) {
        var header = e.target.closest('.dhv-cliente-header');
        if (header) { header.closest('.dhv-cliente-card').classList.toggle('is-open'); return; }
        var sBtn = e.target.closest('.dhv-single-apply');
        if (sBtn) {
            var id = sBtn.dataset.id;
            var row = sBtn.closest('.dhv-pedido-row');
            var status = row.querySelector('.dhv-single-status').value;
            if (!status) { showToast('Selecciona un estado.', 'error'); return; }
            sBtn.classList.add('loading'); sBtn.textContent = '...';
            doAjax({ action: 'dhv_update_recojo_status', shipment_id: id, status: status }, function(res) {
                sBtn.classList.remove('loading'); sBtn.textContent = 'Aplicar';
                if (res.success) {
                    var slug = status.toLowerCase().replace(/ /g,'-');
                    var badge = row.querySelector('.dhv-status-badge');
                    if (badge) { badge.textContent = status; badge.className = 'dhv-status-badge dhv-estado-'+slug; }
                    showToast('Actualizado: ' + status, 'success');
                } else { showToast((res.data&&res.data.message)||'Error.', 'error'); }
            });
            return;
        }
        var bBtn = e.target.closest('.dhv-bulk-apply');
        if (bBtn) {
            var group = bBtn.dataset.group;
            var status2 = document.querySelector('.dhv-bulk-status[data-group="'+group+'"]').value;
            var ids = Array.from(document.querySelectorAll('.dhv-pedido-check[data-group="'+group+'"]:checked')).map(function(c){return c.dataset.id;});
            if (!status2) { showToast('Selecciona un estado.', 'error'); return; }
            if (!ids.length) { showToast('Selecciona al menos un pedido.', 'error'); return; }
            bBtn.classList.add('loading'); bBtn.textContent = 'Aplicando...';
            doAjax({ action: 'dhv_bulk_update_recojo_status', shipment_ids: ids, status: status2 }, function(res) {
                bBtn.classList.remove('loading'); bBtn.textContent = '⚡ Aplicar a seleccionados';
                if (res.success) {
                    var slug2 = status2.toLowerCase().replace(/ /g,'-');
                    res.data.updated.forEach(function(uid) {
                        var r = document.querySelector('.dhv-pedido-row[data-id="'+uid+'"]');
                        if (!r) return;
                        var b = r.querySelector('.dhv-status-badge');
                        var s = r.querySelector('.dhv-single-status');
                        if (b) { b.textContent = status2; b.className = 'dhv-status-badge dhv-estado-'+slug2; }
                        if (s) s.value = status2;
                    });
                    showToast(res.data.message, 'success');
                } else { showToast((res.data&&res.data.message)||'Error.', 'error'); }
            });
        }
    });
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('dhv-select-all')) {
            var group = e.target.dataset.group;
            document.querySelectorAll('.dhv-pedido-check[data-group="'+group+'"]').forEach(function(cb) {
                cb.checked = e.target.checked;
                cb.closest('.dhv-pedido-row').classList.toggle('is-selected', e.target.checked);
            });
            updateCount(group);
        }
        if (e.target.classList.contains('dhv-pedido-check')) {
            var group2 = e.target.dataset.group;
            e.target.closest('.dhv-pedido-row').classList.toggle('is-selected', e.target.checked);
            var total = document.querySelectorAll('.dhv-pedido-check[data-group="'+group2+'"]').length;
            var sel = document.querySelectorAll('.dhv-pedido-check[data-group="'+group2+'"]:checked').length;
            var all = document.querySelector('.dhv-select-all[data-group="'+group2+'"]');
            if (all) all.checked = (total===sel && total>0);
            updateCount(group2);
        }
    });
}());
