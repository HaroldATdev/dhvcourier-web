<?php if ( ! defined( 'ABSPATH' ) ) exit;
global $wpcargo;
$statuses = $wpcargo->status ?? [];
$drivers  = get_users([
    'role'    => 'wpcargo_driver',
    'orderby' => 'display_name',
    'order'   => 'ASC',
    'fields'  => [ 'ID', 'display_name', 'user_email' ],
]);
?>

<style>
.le-wrap{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#1E293B;max-width:900px}
.le-wrap *{box-sizing:border-box}

/* Header */
.le-header{display:flex;align-items:center;gap:12px;margin-bottom:24px;padding-bottom:16px;border-bottom:2px solid #E2E8F0}
.le-header .fa{font-size:32px!important;color:#2563EB}
.le-title{font-size:20px;font-weight:700;margin:0 0 2px;color:#1E293B}
.le-subtitle{margin:0;font-size:13px;color:#64748B}

/* Sección asignar */
.le-assign-section{background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:20px 24px;margin-bottom:16px;box-shadow:0 1px 4px rgba(0,0,0,.06)}
.le-section-title{font-size:13px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.06em;margin:0 0 14px;display:flex;align-items:center;gap:7px}
.le-section-title .fa{font-size:13px!important;color:#2563EB}
.le-two-col{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.le-field label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
.le-field select{
    display:block!important;width:100%!important;
    padding:11px 14px!important;
    border:2px solid #D1D5DB!important;
    border-radius:8px!important;
    font-size:14px!important;
    color:#1E293B!important;
    background:#fff!important;
    appearance:auto!important;
    -webkit-appearance:auto!important;
    height:auto!important;
    min-height:44px!important;
    cursor:pointer;
    transition:border-color .15s;
}
.le-field select:focus{border-color:#2563EB!important;outline:none!important;box-shadow:0 0 0 3px rgba(37,99,235,.12)!important}
.le-field-hint{font-size:11px;color:#9CA3AF;margin:5px 0 0}

/* Badge activo */
.le-active-badge{margin-top:14px;padding:10px 14px;background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;font-size:13px;color:#1D4ED8;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.le-active-badge .fa{font-size:12px!important}
.le-active-badge strong{font-weight:700}

/* Sección escanear */
.le-scan-section{background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:20px 24px;margin-bottom:16px;box-shadow:0 1px 4px rgba(0,0,0,.06)}
.le-scan-row{display:flex;gap:10px;align-items:stretch}
.le-scan-row input{
    flex:1;
    padding:13px 16px!important;
    border:2px solid #D1D5DB!important;
    border-radius:8px!important;
    font-size:16px!important;
    font-weight:600!important;
    color:#1E293B!important;
    background:#fff!important;
    height:auto!important;
    transition:border-color .15s;
}
.le-scan-row input:focus{border-color:#2563EB!important;outline:none!important;box-shadow:0 0 0 3px rgba(37,99,235,.12)!important}
.le-scan-row input::placeholder{font-weight:400!important;color:#9CA3AF!important}
.le-btn-scan{
    padding:0 24px!important;
    background:#2563EB!important;
    color:#fff!important;
    border:none!important;
    border-radius:8px!important;
    font-size:15px!important;
    font-weight:700!important;
    cursor:pointer!important;
    display:flex!important;
    align-items:center!important;
    gap:7px!important;
    white-space:nowrap!important;
    transition:background .15s!important;
    height:50px!important;
}
.le-btn-scan:hover{background:#1D4ED8!important}
.le-btn-scan .fa{font-size:15px!important}

/* Checkboxes */
.le-checks{display:flex;gap:20px;margin-top:12px;flex-wrap:wrap}
.le-checks label{display:flex;align-items:center;gap:7px;font-size:13px;color:#64748B;cursor:pointer}
.le-checks input[type=checkbox]{width:16px;height:16px;cursor:pointer;accent-color:#2563EB}

/* Resultado */
.le-result{margin-top:14px;padding:13px 16px;border-radius:8px;font-size:14px;display:none}
.le-result--success{background:#DCFCE7;border:1px solid #86EFAC;color:#166534}
.le-result--error{background:#FEE2E2;border:1px solid #FCA5A5;color:#991B1B}
.le-result--info{background:#DBEAFE;border:1px solid #93C5FD;color:#1E40AF}
.le-result .fa{font-size:14px!important;margin-right:4px}

/* Historial */
.le-history-section{background:#fff;border:1px solid #E2E8F0;border-radius:12px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.06)}
.le-history-header{padding:12px 18px;background:#F8FAFC;border-bottom:1px solid #E2E8F0;font-size:13px;font-weight:700;color:#374151;display:flex;align-items:center;gap:8px}
.le-history-header .fa{font-size:13px!important;color:#2563EB}
.le-count-badge{background:#2563EB;color:#fff;font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;margin-left:auto}
.le-btn-clear{background:none!important;border:none!important;color:#9CA3AF!important;cursor:pointer!important;padding:4px 8px!important;border-radius:6px!important;font-size:12px!important}
.le-btn-clear:hover{color:#DC2626!important;background:#FEE2E2!important}
.le-history-body{max-height:320px;overflow-y:auto;padding:8px}
.le-history-item{padding:9px 12px;border:1px solid #E2E8F0;border-radius:8px;margin-bottom:6px;background:#fff}
.le-history-item--success{border-color:#86EFAC;background:#F0FDF4}
.le-history-item--error{border-color:#FCA5A5;background:#FFF5F5}
.le-history-item--info{border-color:#93C5FD;background:#EFF6FF}
.le-history-tracking{font-size:14px;font-weight:700;color:#2563EB;margin-bottom:3px}
.le-history-meta{font-size:11px;color:#9CA3AF;display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.le-history-status{background:#DBEAFE;color:#1D4ED8;padding:1px 7px;border-radius:999px;font-weight:700;font-size:11px}
.le-history-driver{color:#6B7280}
.le-empty-msg{text-align:center;padding:24px;color:#9CA3AF;font-size:13px;margin:0}

/* Notas */
.le-notes{background:#FEFCE8;border:1px solid #FDE68A;border-radius:10px;padding:14px 16px;font-size:13px;color:#78350F;margin-top:16px}
.le-notes strong{display:block;margin-bottom:8px}
.le-notes ol{margin:0;padding-left:18px;line-height:1.8}
kbd{background:#fff;border:1px solid #D1D5DB;border-radius:4px;padding:1px 6px;font-size:11px;font-family:monospace;color:#1E293B;}

@media(max-width:640px){.le-two-col{grid-template-columns:1fr}.le-scan-row{flex-direction:column}.le-btn-scan{width:100%!important;justify-content:center!important}}
</style>

<div class="le-wrap">

    <div class="le-header">
        <i class="fa fa-barcode"></i>
        <div>
            <h1 class="le-title">Escáner de Envíos</h1>
            <p class="le-subtitle">Selecciona el estado y/o motorizado, luego escanea los envíos uno por uno.</p>
        </div>
    </div>

    <!-- Asignar estado y motorizado -->
    <div class="le-assign-section">
        <p class="le-section-title"><i class="fa fa-sliders"></i> Pedido asignado a motorizado</p>
        <div class="le-two-col">
            <div class="le-field">
                <label><i class="fa fa-tag" style="color:#2563EB;font-size:12px!important;"></i> Estado</label>
                <select id="le-status-select">
                    <option value="">— Seleccionar Tipo —</option>
                    <?php foreach ( $statuses as $s ) : ?>
                        <option value="<?php echo esc_attr($s); ?>"><?php echo esc_html($s); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="le-field-hint">Si no seleccionas, el estado actual no cambia.</p>
            </div>
            <div class="le-field">
                <label><i class="fa fa-truck" style="color:#2563EB;font-size:12px!important;"></i> Conductor de Entrega</label>
                <select id="le-delivery-driver-select">
                    <option value="">— Sin cambiar —</option>
                    <?php foreach ( $drivers as $d ) : ?>
                        <option value="<?php echo esc_attr($d->ID); ?>">
                            <?php echo esc_html( $d->display_name ?: $d->user_email ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="le-field-hint">Si no seleccionas, el conductor de entrega no cambia.</p>
            </div>
        </div>
        <div id="le-active-badge" class="le-active-badge" style="display:none;"></div>
    </div>

    <!-- Escanear -->
    <div class="le-scan-section">
        <p class="le-section-title"><i class="fa fa-barcode"></i> Introduzca el número de envío</p>
        <div class="le-scan-row">
            <input
                type="text"
                id="le-tracking-input"
                placeholder="Escanee su código de barras o introduzca el número de seguimiento y pulse ENTER"
                autocomplete="off"
            />
            <button class="le-btn-scan" id="le-btn-scan">
                <i class="fa fa-bolt"></i> Actualizar
            </button>
        </div>
        <div class="le-checks">
            <label><input type="checkbox" id="le-clear-after" checked /> Borre todos los campos después de escanearlos.</label>
            <label><input type="checkbox" id="le-beep-sound" checked /> Sonido al escanear</label>
        </div>
        <div id="le-result" class="le-result"></div>
    </div>

    <!-- Historial -->
    <div class="le-history-section">
        <div class="le-history-header">
            <i class="fa fa-history"></i> Historial de sesión
            <span id="le-count-badge" class="le-count-badge">0</span>
            <button class="le-btn-clear" id="le-clear-history" title="Limpiar historial">
                <i class="fa fa-trash"></i>
            </button>
        </div>
        <div class="le-history-body">
            <div id="le-scan-history">
                <p class="le-empty-msg">Los envíos escaneados aparecerán aquí.</p>
            </div>
        </div>
    </div>

    <!-- Notas -->
    <div class="le-notes">
        <strong>Notas:</strong>
        <ol>
            <li>Si ha conectado su escáner de código de barras, escanee directamente al código de barras y actualizará automáticamente el estado del envío.</li>
            <li>Si no tiene un escáner de código de barras, introduzca el número de seguimiento y pulse <kbd>Enter</kbd> en su teclado.</li>
        </ol>
    </div>

</div>

<script>
(function($){
    var scanCount = 0;
    var $input    = $('#le-tracking-input');
    var $result   = $('#le-result');
    var $history  = $('#le-scan-history');
    var $badge    = $('#le-active-badge');
    var $count    = $('#le-count-badge');

    // Foco al cargar
    setTimeout(function(){ $input.focus(); }, 300);

    // Badge configuración activa
    function updateBadge(){
        var status      = $('#le-status-select').val();
        var deliveryVal = $('#le-delivery-driver-select').val();
        var deliveryTxt = $('#le-delivery-driver-select option:selected').text();
        var parts = [];
        if(status)      parts.push('<i class="fa fa-tag"></i> Estado: <strong>'+status+'</strong>');
        if(deliveryVal) parts.push('<i class="fa fa-truck"></i> Entrega: <strong>'+deliveryTxt+'</strong>');
        if(parts.length){
            $badge.html('Se aplicará → '+parts.join(' &nbsp;+&nbsp; ')).show();
        } else {
            $badge.html('<i class="fa fa-eye"></i> Solo se consultará el envío sin realizar cambios.').show();
        }
    }
    $('#le-status-select, #le-delivery-driver-select').on('change', updateBadge);
    updateBadge();

    // Escanear con Enter
    $input.on('keypress', function(e){ if(e.which===13){ e.preventDefault(); doScan(); } });
    $input.on('paste', function(){ setTimeout(doScan, 50); });
    $('#le-btn-scan').on('click', doScan);

    // Limpiar historial
    $('#le-clear-history').on('click', function(){
        scanCount=0; $count.text('0');
        $history.html('<p class="le-empty-msg">Los envíos escaneados aparecerán aquí.</p>');
    });

    function doScan(){
        var tracking = $input.val().trim();
        if(!tracking){ $input.focus(); return; }

        var status      = $('#le-status-select').val();
        var deliveryId  = $('#le-delivery-driver-select').val();
        var deliveryTxt = $('#le-delivery-driver-select option:selected').text();
        var clearAfter= $('#le-clear-after').is(':checked');
        var beepOn    = $('#le-beep-sound').is(':checked');

        showResult('info','<i class="fa fa-spinner fa-spin"></i> Procesando <strong>'+tracking+'</strong>...');
        $('#le-btn-scan').prop('disabled',true);

        $.post(DHV_Config.ajax_url,{
            action:'dhv_scan_shipment',
            nonce:DHV_Config.nonce,
            tracking_number:tracking,
            status:status,
            delivery_driver_id:deliveryId,
        },function(res){
            $('#le-btn-scan').prop('disabled',false);
            if(res.success){
                var d = res.data;
                if(d.type==='updated'){
                    var msg='<i class="fa fa-check-circle"></i> <strong>#'+d.tracking+'</strong> actualizado';
                    if(d.status_updated)          msg+=' &nbsp;·&nbsp; Estado: <strong>'+d.new_status+'</strong>';
                    if(d.delivery_driver_updated) msg+=' &nbsp;·&nbsp; Entrega: <strong>'+deliveryTxt+'</strong>';
                    if(d.receiver_name)           msg+='<br><small style="opacity:.8;">'+d.receiver_name+(d.receiver_addr?' · '+d.receiver_addr:'')+'</small>';
                    showResult('success',msg);
                    if(beepOn) playBeep(false);
                    addHistory(d.tracking,status,deliveryId?deliveryTxt:'','success');
                } else {
                    var msg='<i class="fa fa-info-circle"></i> <strong>#'+d.tracking+'</strong> — Estado actual: <strong>'+(d.status||'Sin estado')+'</strong>';
                    if(d.receiver_name) msg+='<br><small style="opacity:.8;">'+d.receiver_name+(d.receiver_addr?' · '+d.receiver_addr:'')+'</small>';
                    showResult('info',msg);
                    addHistory(d.tracking,'','','info');
                }
                scanCount++; $count.text(scanCount);
                if(clearAfter){ $input.val('').focus(); } else { $input.select(); }
            } else {
                var err=(res.data&&res.data.message)?res.data.message:'Error al procesar.';
                showResult('error','<i class="fa fa-times-circle"></i> '+err);
                addHistory(tracking,'','error');
                if(beepOn) playBeep(true);
                $input.select();
            }
        }).fail(function(){
            $('#le-btn-scan').prop('disabled',false);
            showResult('error','<i class="fa fa-times-circle"></i> Error de conexión.');
            $input.select();
        });
    }

    function showResult(type,html){
        $result.removeClass('le-result--success le-result--error le-result--info')
               .addClass('le-result--'+type).html(html).show();
    }

    function addHistory(tracking,status,deliveryDriver,type){
        var now=new Date();
        var time=now.toLocaleTimeString('es-PE',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
        var $e=$('#le-scan-history .le-empty-msg');
        if($e.length) $e.remove();
        var meta='<span>'+time+'</span>';
        if(status) meta+='<span class="le-history-status">'+status+'</span>';
        if(deliveryDriver&&deliveryDriver.indexOf('Sin cambiar')===-1) meta+='<span class="le-history-driver"><i class="fa fa-truck" style="font-size:11px!important"></i> '+deliveryDriver+'</span>';
        var item='<div class="le-history-item le-history-item--'+type+'">'+
            '<div class="le-history-tracking"># '+tracking+'</div>'+
            '<div class="le-history-meta">'+meta+'</div></div>';
        $history.prepend(item);
    }

    function playBeep(error){
        try{
            var ctx=new(window.AudioContext||window.webkitAudioContext)();
            var osc=ctx.createOscillator();
            var g=ctx.createGain();
            osc.connect(g); g.connect(ctx.destination);
            osc.frequency.value=error?280:920; osc.type='sine';
            g.gain.setValueAtTime(0.3,ctx.currentTime);
            g.gain.exponentialRampToValueAtTime(0.001,ctx.currentTime+(error?.5:.12));
            osc.start(ctx.currentTime); osc.stop(ctx.currentTime+(error?.5:.12));
        }catch(e){}
    }
})(jQuery);
</script>

