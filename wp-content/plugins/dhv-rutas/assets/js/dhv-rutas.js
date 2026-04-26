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

    function rowMatchesTerm(row, termText, termDigits) {
        if (!row) return false;
        if (!termText && !termDigits) return true;

        var card = row.closest('.dhv-cliente-card');
        var cardName = normalizeText(card ? (card.dataset.cliente || card.dataset.dest || '') : '');
        var tracking = normalizeText(row.dataset.tracking || (row.querySelector('.dhv-tracking-num') ? row.querySelector('.dhv-tracking-num').textContent : ''));
        var direccion = normalizeText(row.querySelector('.dhv-pedido-direccion') ? row.querySelector('.dhv-pedido-direccion').textContent : '');
        var phoneDigits = onlyDigits(row.dataset.telefono || (row.querySelector('.dhv-tel-link') ? row.querySelector('.dhv-tel-link').textContent : ''));
        var phoneText = normalizeText(phoneDigits);

        var match = !termText;
        if (!match) {
            match = tracking.indexOf(termText) !== -1 || cardName.indexOf(termText) !== -1 || direccion.indexOf(termText) !== -1;
        }
        if (!match && termDigits) {
            match = phoneDigits.indexOf(termDigits) !== -1;
        }
        if (!match && termText) {
            match = phoneText.indexOf(termText) !== -1;
        }

        return match;
    }

    function updateGlobalCardVisibility(wrap) {
        if (!wrap) return;
        var globalInput = wrap.querySelector('.dhv-global-search');
        var hasGlobal = !!(globalInput && normalizeText(globalInput.value));

        wrap.querySelectorAll('.dhv-cliente-card').forEach(function (card) {
            if (!hasGlobal) {
                card.classList.remove('dhv-card-hidden-global');
                return;
            }
            var hasVisibleRows = !!card.querySelector('.dhv-pedido-row:not(.dhv-row-hidden)');
            card.classList.toggle('dhv-card-hidden-global', !hasVisibleRows);
        });
    }

    function applyGlobalSearch(input) {
        var wrap = input.closest('.dhv-recojo-wrap, .dhv-entrega-wrap');
        if (!wrap) return;

        wrap.querySelectorAll('.dhv-card-search').forEach(function (cardSearchInput) {
            applyCardSearch(cardSearchInput);
        });

        updateGlobalCardVisibility(wrap);
    }

    function applyCardSearch(input) {
        var group = input.dataset.group;
        if (!group) return;

        var wrap = input.closest('.dhv-recojo-wrap, .dhv-entrega-wrap');
        var globalInput = wrap ? wrap.querySelector('.dhv-global-search') : null;
        var termText = normalizeText(input.value);
        var termDigits = onlyDigits(input.value);
        var globalTermText = normalizeText(globalInput ? globalInput.value : '');
        var globalTermDigits = onlyDigits(globalInput ? globalInput.value : '');
        var rows = document.querySelectorAll('.dhv-pedido-check[data-group="' + group + '"]');
        var visible = 0;

        rows.forEach(function (cb) {
            var row = cb.closest('.dhv-pedido-row');
            if (!row) return;

            var match = rowMatchesTerm(row, termText, termDigits) && rowMatchesTerm(row, globalTermText, globalTermDigits);

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

        updateGlobalCardVisibility(wrap);
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

    function ensurePodModal() {
        var modal = document.getElementById('wpc_pod_signature-modal');
        if (modal) return modal;

        var html = '' +
            '<div class="modal fade top" id="wpc_pod_signature-modal" tabindex="-1" role="dialog" aria-labelledby="podModalPreview" aria-hidden="true">' +
                '<div class="modal-dialog modal-lg modal-frame modal-top" role="document">' +
                    '<div class="modal-content">' +
                        '<div class="modal-header">' +
                            '<h5 class="modal-title" id="podModalPreview">Proof of Delivery</h5>' +
                            '<button type="button" class="close" data-dismiss="modal" aria-label="Close">' +
                                '<span aria-hidden="true">&times;</span>' +
                            '</button>' +
                        '</div>' +
                        '<div class="modal-body my-4">Loading...</div>' +
                    '</div>' +
                '</div>' +
            '</div>';

        document.body.insertAdjacentHTML('beforeend', html);
        return document.getElementById('wpc_pod_signature-modal');
    }

    function showPodModal($modal) {
        if ($modal && typeof $modal.modal === 'function') {
            $modal.modal('show');
            return;
        }
        if ($modal && $modal.length) {
            $modal.css('display', 'block').addClass('show');
        }
    }

    function hidePodModal($modal) {
        if ($modal && typeof $modal.modal === 'function') {
            $modal.modal('hide');
            return;
        }
        if ($modal && $modal.length) {
            $modal.css('display', 'none').removeClass('show');
        }
    }

    function lockDeliveredStatusInForm($form) {
        if (!$form || !$form.length) return;

        var $status = $form.find('#status');
        if ($status.length) {
            $status.val('Entregado').trigger('change');
            $status.prop('disabled', true).addClass('disabled');
        }

        var $hiddenStatus = $form.find('input[name="status"][data-autogenerated="1"]');
        if (!$hiddenStatus.length) {
            $hiddenStatus = jQuery('<input>', {
                type: 'hidden',
                name: 'status',
                value: 'Entregado',
                'data-autogenerated': '1'
            });
            $form.append($hiddenStatus);
        } else {
            $hiddenStatus.val('Entregado');
        }
    }

    function applyDeliveredBadge(row) {
        var slug = 'entregado';
        var badge = row.querySelector('.dhv-status-badge');

        if (badge) {
            badge.textContent = 'Entregado';
            badge.className = 'dhv-status-badge dhv-estado-' + slug;
        }

        if (row) {
            row.dataset.estado = 'Entregado';
        }

        lockDeliveredRow(row);
    }

    function lockDeliveredRow(row) {
        if (!row) return;

        var right = row.querySelector('.dhv-pedido-right');
        if (!right) return;

        var select = right.querySelector('.dhv-single-status');
        var readonly = right.querySelector('.dhv-status-readonly');
        if (select) {
            if (!readonly) {
                readonly = document.createElement('span');
                readonly.className = 'dhv-status-readonly';
                readonly.textContent = 'Entregado';
                right.insertBefore(readonly, select);
            }
            select.remove();
        }

        var btn = right.querySelector('.dhv-single-apply');
        if (btn) {
            btn.remove();
        }

        var cb = row.querySelector('.dhv-pedido-check');
        if (cb) {
            cb.checked = false;
            cb.disabled = true;
            cb.classList.add('is-locked');
        }
        row.classList.remove('is-selected');
    }

    function openPodModalForEntrega(shipmentId, row, sBtn) {
        var modalEl = ensurePodModal();
        var $modal = jQuery(modalEl);
        var $modalBody = $modal.find('.modal-body');

        jQuery.ajax({
            type: 'POST',
            url: dhvRutas.ajax_url,
            data: {
                action: 'show_signaturepad',
                sid: shipmentId
            },
            beforeSend: function () {
                sBtn.classList.add('loading');
                sBtn.textContent = '...';
                jQuery('body').append('<div class="wpcargo-loading">Cargando formulario de firma...</div>');
            },
            success: function (response) {
                jQuery('body .wpcargo-loading').remove();
                $modalBody.html(response);

                var $form = $modal.find('#wpc_pod_signature-form');
                lockDeliveredStatusInForm($form);
                showPodModal($modal);

                // El canvas se inicializa con dimensiones 0 porque el modal estaba oculto.
                // Disparar resize después de que el modal sea visible para que SignaturePad
                // redimensione correctamente el canvas.
                setTimeout(function () {
                    var canvas = modalEl.querySelector('#pod-canvas');
                    if (canvas) {
                        var ratio = Math.max(window.devicePixelRatio || 1, 1);
                        canvas.width  = canvas.offsetWidth  * ratio;
                        canvas.height = canvas.offsetHeight * ratio;
                        if (canvas.getContext) {
                            canvas.getContext('2d').scale(ratio, ratio);
                        }
                    }
                    window.dispatchEvent(new Event('resize'));
                }, 350);

                $form.off('submit.dhvPod').on('submit.dhvPod', function (e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    e.stopPropagation();

                    var podNonce = (window.dhvRutas && window.dhvRutas.pod_sign_nonce) ? window.dhvRutas.pod_sign_nonce : '';
                    if (!podNonce) {
                        showToast('No se encontró nonce de firma POD.', 'error');
                        return;
                    }

                    var formData = jQuery(this).serializeArray();

                    jQuery.ajax({
                        type: 'POST',
                        url: dhvRutas.ajax_url,
                        dataType: 'json',
                        data: {
                            action: 'pod_signed',
                            nonce: podNonce,
                            formData: formData
                        },
                        beforeSend: function () {
                            jQuery('body').append('<div class="wpcargo-loading">Guardando POD...</div>');
                        },
                        success: function (res) {
                            jQuery('body .wpcargo-loading').remove();
                            if (res && res.status === 'error') {
                                showToast(res.message || 'Error al guardar POD.', 'error');
                                return;
                            }

                            applyDeliveredBadge(row);
                            showToast('Actualizado: Entregado', 'success');
                            hidePodModal($modal);
                        },
                        error: function () {
                            jQuery('body .wpcargo-loading').remove();
                            showToast('Error al guardar POD.', 'error');
                        }
                    });
                });
            },
            error: function () {
                jQuery('body .wpcargo-loading').remove();
                showToast('Error al cargar el formulario de firma.', 'error');
            },
            complete: function () {
                sBtn.classList.remove('loading');
                sBtn.textContent = 'Aplicar';
            }
        });
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
            var statusSelect = row.querySelector('.dhv-single-status');
            if (!statusSelect) {
                showToast('Este pedido ya esta en estado entregado.', 'error');
                return;
            }

            var status = statusSelect.value;
            if (!status) { showToast('Selecciona un estado.', 'error'); return; }

            // Entrega + Entregado => abrir modal POD y no guardar estado directo
            if (isEntrega(sBtn) && normalizeText(status) === 'entregado') {
                openPodModalForEntrega(id, row, sBtn);
                return;
            }

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
                        if (normalizeText(status2) === 'entregado') {
                            lockDeliveredRow(r);
                        } else if (s) {
                            s.value = status2;
                        }
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
            return;
        }

        if (e.target.classList.contains('dhv-global-search')) {
            applyGlobalSearch(e.target);
        }
    });
}());
