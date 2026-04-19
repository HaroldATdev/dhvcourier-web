(function ($) {
    'use strict';

    var scanCount = 0;

    $(document).ready(function () {

        var $input   = $('#le-tracking-input');
        var $result  = $('#le-result');
        var $history = $('#le-scan-history');
        var $btnScan = $('#le-btn-scan');
        var $count   = $('#le-scan-count');

        // ── Foco automático ───────────────────────────────────────────────
        $input.focus();

        // ── Toggle campos adicionales ─────────────────────────────────────
        $('#le-toggle-extra').on('click', function () {
            var $content = $('#le-extra-content');
            var $icon    = $(this).find('.fa');
            $content.slideToggle(200);
            $icon.toggleClass('fa-plus-circle fa-minus-circle');
        });

        // ── Actualizar badge de configuración activa ──────────────────────
        function updateConfigBadge() {
            var status      = $('#le-status-select').val();
            var deliveryVal = $('#le-delivery-driver-select').val();
            var deliveryTxt = $('#le-delivery-driver-select option:selected').text();
            var parts  = [];

            if (status)      parts.push('<i class="fa fa-tag"></i> <strong>' + status + '</strong>');
            if (deliveryVal) parts.push('<i class="fa fa-truck"></i> Entrega: <strong>' + deliveryTxt + '</strong>');

            var $badge = $('#le-config-badge');
            if (parts.length) {
                $badge.html('Se aplicará: ' + parts.join(' &nbsp;+&nbsp; ')).show();
            } else {
                $badge.html('Solo se consultará el envío, sin cambios.').show();
            }
        }

        $('#le-status-select, #le-delivery-driver-select').on('change', updateConfigBadge);
        updateConfigBadge();

        // ── Escanear al presionar Enter ───────────────────────────────────
        $input.on('keypress', function (e) {
            if (e.which === 13) { e.preventDefault(); doScan(); }
        });

        // ── Escanear al pegar (lector de barras) ──────────────────────────
        $input.on('paste', function () { setTimeout(doScan, 50); });

        // ── Botón ─────────────────────────────────────────────────────────
        $btnScan.on('click', doScan);

        // ── Limpiar historial ─────────────────────────────────────────────
        $('#le-clear-history').on('click', function () {
            scanCount = 0;
            $count.text('0');
            $history.html('<p class="le-empty-history">Los envíos escaneados aparecerán aquí.</p>');
        });

        // ── FUNCIÓN PRINCIPAL DE ESCANEO ──────────────────────────────────
        function doScan() {
            var tracking  = $input.val().trim();
            if (!tracking) { $input.focus(); return; }

            var status       = $('#le-status-select').val();
            var deliveryId   = $('#le-delivery-driver-select').val();
            var deliveryName = $('#le-delivery-driver-select option:selected').text();
            var location     = $('#le-location-input').val().trim();
            var remarks      = $('#le-remarks-input').val().trim();
            var clearAfter   = $('#le-clear-after').is(':checked');
            var beepOn       = $('#le-beep-sound').is(':checked');

            showResult('info', '<i class="fa fa-spinner fa-spin"></i> Procesando <strong>' + tracking + '</strong>...');
            $btnScan.prop('disabled', true);

            $.post(DHV_Config.ajax_url, {
                action:             'dhv_scan_shipment',
                nonce:              DHV_Config.nonce,
                tracking_number:    tracking,
                status:             status,
                delivery_driver_id: deliveryId,
                location:           location,
                remarks:            remarks,
            }, function (res) {
                $btnScan.prop('disabled', false);

                if (res.success) {
                    var data = res.data;
                    var msg  = '';

                    if (data.type === 'updated') {
                        msg = '<i class="fa fa-check-circle"></i> <strong>#' + data.tracking + '</strong> actualizado';
                        if (data.status_updated)          msg += ' · Estado: <strong>' + data.new_status + '</strong>';
                        if (data.delivery_driver_updated) msg += ' · Entrega: <strong>' + deliveryName + '</strong>';
                        msg += buildInfoSnippet(data);
                        showResult('success', msg);
                        if (beepOn) playBeep(false);
                        addToHistory(data, status, deliveryName, 'success');
                    } else {
                        msg = '<i class="fa fa-info-circle"></i> <strong>#' + data.tracking + '</strong>';
                        msg += ' — Estado actual: <strong>' + (data.status || 'Sin estado') + '</strong>';
                        msg += buildInfoSnippet(data);
                        showResult('info', msg);
                        addToHistory(data, '', '', 'info');
                    }

                    scanCount++;
                    $count.text(scanCount);

                    if (clearAfter) { $input.val('').focus(); }
                    else            { $input.select(); }

                } else {
                    var errMsg = (res.data && res.data.message) ? res.data.message : DHV_Config.txt_error;
                    showResult('error', '<i class="fa fa-times-circle"></i> ' + errMsg);
                    addToHistory({ tracking: tracking }, '', '', 'error');
                    if (beepOn) playBeep(true);
                    $input.select();
                }

            }).fail(function () {
                $btnScan.prop('disabled', false);
                showResult('error', '<i class="fa fa-times-circle"></i> ' + DHV_Config.txt_error);
                $input.select();
            });
        }

        // ── Helpers ───────────────────────────────────────────────────────
        function showResult(type, html) {
            $result
                .removeClass('le-result--success le-result--error le-result--info')
                .addClass('le-result--' + type)
                .html(html)
                .show();
        }

        function buildInfoSnippet(data) {
            if (!data.receiver_name) return '';
            var s = '<br><small style="opacity:.75;">';
            s += data.receiver_name;
            if (data.receiver_addr) s += ' · ' + data.receiver_addr;
            s += '</small>';
            return s;
        }

        function addToHistory(data, status, deliveryName, type) {
            var now  = new Date();
            var time = now.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

            var $empty = $history.find('.le-empty-history');
            if ($empty.length) $empty.remove();

            var meta = '<span>' + time + '</span>';
            if (status) meta += '<span class="le-history-item__status">' + status + '</span>';
            if (deliveryName && deliveryName.indexOf('Sin cambiar') === -1)
                meta += '<span class="le-history-item__driver"><i class="fa fa-truck"></i> ' + deliveryName + '</span>';

            var $item = $('<div class="le-history-item le-history-item--' + type + '">' +
                '<div class="le-history-item__tracking"># ' + (data.tracking || '—') + '</div>' +
                '<div class="le-history-item__meta">' + meta + '</div>' +
            '</div>');

            $history.prepend($item);
        }

        function playBeep(error) {
            try {
                var ctx = new (window.AudioContext || window.webkitAudioContext)();
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.frequency.value = error ? 280 : 920;
                osc.type = 'sine';
                gain.gain.setValueAtTime(0.3, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + (error ? 0.5 : 0.12));
                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + (error ? 0.5 : 0.12));
            } catch(e) {}
        }

    });

})(jQuery);
