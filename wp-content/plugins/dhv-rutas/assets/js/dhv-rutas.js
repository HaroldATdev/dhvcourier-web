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

    function normalizeText(value) {
        var text = (value || '').toString().toLowerCase().trim();
        if (typeof text.normalize === 'function') {
            text = text.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }
        return text;
    }

    function onlyDigits(value) {
        return (value || '').toString().replace(/\D+/g, '');
    }

    function applyCardSearch(input) {
        var group = input.dataset.group;
        if (!group) return;

        var termText = normalizeText(input.value);
        var termDigits = onlyDigits(input.value);
        var rows = document.querySelectorAll('.dhv-pedido-check[data-group="' + group + '"]');
        var visible = 0;

        rows.forEach(function (cb) {
            var row = cb.closest('.dhv-pedido-row');
            if (!row) return;

            var tracking = normalizeText(row.dataset.tracking || (row.querySelector('.dhv-tracking-num') ? row.querySelector('.dhv-tracking-num').textContent : ''));
            var phoneDigits = onlyDigits(row.dataset.telefono || (row.querySelector('.dhv-tel-link') ? row.querySelector('.dhv-tel-link').textContent : ''));
            var phoneText = normalizeText(phoneDigits);

            var match = !termText;
            if (!match) {
                match = tracking.indexOf(termText) !== -1;
            }
            if (!match && termDigits) {
                match = phoneDigits.indexOf(termDigits) !== -1;
            }
            if (!match && termText) {
                match = phoneText.indexOf(termText) !== -1;
            }

            if (match) {
                row.classList.remove('dhv-row-hidden');
                visible++;
            } else {
                row.classList.add('dhv-row-hidden');
                cb.checked = false;
                row.classList.remove('is-selected');
            }
        });

        var all = document.querySelector('.dhv-select-all[data-group="' + group + '"]');
        if (all) {
            var visibleChecks = Array.from(document.querySelectorAll('.dhv-pedido-check[data-group="' + group + '"]')).filter(function (cb) {
                var row = cb.closest('.dhv-pedido-row');
                return row && !row.classList.contains('dhv-row-hidden');
            });
            var selectedVisible = visibleChecks.filter(function (cb) { return cb.checked; }).length;
            all.checked = (visibleChecks.length > 0 && visibleChecks.length === selectedVisible);
        }

        var empty = document.querySelector('.dhv-card-search-empty[data-group="' + group + '"]');
        if (empty) {
            empty.style.display = visible === 0 ? 'block' : 'none';
        }

        updateCount(group);
    }

    function doAjax(data, callback) {
        var body = new URLSearchParams();
        Object.keys(data).forEach(function (k) {
            var v = data[k];
            if (Array.isArray(v)) { v.forEach(function (i) { body.append(k + '[]', i); }); }
            else { body.append(k, v); }
        });
        body.append('nonce', dhvRutas.nonce);
        fetch(dhvRutas.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        })
            .then(function (r) { return r.json(); })
            .then(callback)
            .catch(function () { showToast('Error de conexión.', 'error'); });
    }

    /** Detecta si un elemento pertenece a la sección de entrega */
    function isEntrega(el) {
        return !!el.closest('.dhv-entrega-wrap');
    }

    document.addEventListener('click', function (e) {

        // ── Toggle header ──────────────────────────────────────────────────
        var header = e.target.closest('.dhv-cliente-header');
        if (header) {
            header.closest('.dhv-cliente-card').classList.toggle('is-open');
            return;
        }

        // ── Aplicar estado individual ──────────────────────────────────────
        var sBtn = e.target.closest('.dhv-single-apply');
        if (sBtn) {
            var id     = sBtn.dataset.id;
            var row    = sBtn.closest('.dhv-pedido-row');
            var status = row.querySelector('.dhv-single-status').value;
            if (!status) { showToast('Selecciona un estado.', 'error'); return; }

            var action = isEntrega(sBtn)
                ? 'dhv_update_entrega_status'
                : 'dhv_update_recojo_status';

            sBtn.classList.add('loading');
            sBtn.textContent = '...';

            doAjax({ action: action, shipment_id: id, status: status }, function (res) {
                sBtn.classList.remove('loading');
                sBtn.textContent = 'Aplicar';
                if (res.success) {
                    var slug  = status.toLowerCase().replace(/ /g, '-');
                    var badge = row.querySelector('.dhv-status-badge');
                    if (badge) {
                        badge.textContent = status;
                        badge.className   = 'dhv-status-badge dhv-estado-' + slug;
                    }
                    showToast('Actualizado: ' + status, 'success');
                } else {
                    showToast((res.data && res.data.message) || 'Error.', 'error');
                }
            });
            return;
        }

        // ── Aplicar estado masivo ──────────────────────────────────────────
        var bBtn = e.target.closest('.dhv-bulk-apply');
        if (bBtn) {
            var group   = bBtn.dataset.group;
            var status2 = document.querySelector('.dhv-bulk-status[data-group="' + group + '"]').value;
            var ids     = Array.from(
                document.querySelectorAll('.dhv-pedido-check[data-group="' + group + '"]:checked')
            ).map(function (c) { return c.dataset.id; });

            if (!status2) { showToast('Selecciona un estado.', 'error'); return; }
            if (!ids.length) { showToast('Selecciona al menos un pedido.', 'error'); return; }

            var actionBulk = isEntrega(bBtn)
                ? 'dhv_bulk_update_entrega_status'
                : 'dhv_bulk_update_recojo_status';

            bBtn.classList.add('loading');
            bBtn.textContent = 'Aplicando...';

            doAjax({ action: actionBulk, shipment_ids: ids, status: status2 }, function (res) {
                bBtn.classList.remove('loading');
                bBtn.textContent = '⚡ Aplicar a seleccionados';
                if (res.success) {
                    var slug2 = status2.toLowerCase().replace(/ /g, '-');
                    res.data.updated.forEach(function (uid) {
                        var r = document.querySelector('.dhv-pedido-row[data-id="' + uid + '"]');
                        if (!r) return;
                        var b = r.querySelector('.dhv-status-badge');
                        var s = r.querySelector('.dhv-single-status');
                        if (b) { b.textContent = status2; b.className = 'dhv-status-badge dhv-estado-' + slug2; }
                        if (s) s.value = status2;
                    });
                    showToast(res.data.message, 'success');
                } else {
                    showToast((res.data && res.data.message) || 'Error.', 'error');
                }
            });
        }
    });

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('dhv-select-all')) {
            var group = e.target.dataset.group;
            document.querySelectorAll('.dhv-pedido-check[data-group="' + group + '"]').forEach(function (cb) {
                if (cb.closest('.dhv-pedido-row').classList.contains('dhv-row-hidden')) {
                    return;
                }
                cb.checked = e.target.checked;
                cb.closest('.dhv-pedido-row').classList.toggle('is-selected', e.target.checked);
            });
            updateCount(group);
        }
        if (e.target.classList.contains('dhv-pedido-check')) {
            var group2 = e.target.dataset.group;
            e.target.closest('.dhv-pedido-row').classList.toggle('is-selected', e.target.checked);
            var visibleChecks2 = Array.from(document.querySelectorAll('.dhv-pedido-check[data-group="' + group2 + '"]')).filter(function (cb) {
                var row = cb.closest('.dhv-pedido-row');
                return row && !row.classList.contains('dhv-row-hidden');
            });
            var total = visibleChecks2.length;
            var sel = visibleChecks2.filter(function (cb) { return cb.checked; }).length;
            var all   = document.querySelector('.dhv-select-all[data-group="' + group2 + '"]');
            if (all) all.checked = (total === sel && total > 0);
            updateCount(group2);
        }
    });

    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('dhv-card-search')) {
            applyCardSearch(e.target);
        }
    });
}());
