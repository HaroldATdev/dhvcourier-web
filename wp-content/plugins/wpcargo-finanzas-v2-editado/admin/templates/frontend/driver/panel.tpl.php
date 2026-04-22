<?php if ( ! defined('ABSPATH') ) exit;
$msg_map = [
    'solicitud_enviada'   => ['success', '✅ Se notificó al equipo de DHV. Te contactarán para coordinar la liquidación.'],
    'comprobante_enviado' => ['success', '✅ Comprobante enviado correctamente. DHV lo revisará y actualizará tu saldo.'],
    'error_archivo'       => ['danger',  '❌ Error al subir el archivo. Verifica que sea una imagen o PDF válido.'],
    'error_monto'         => ['danger',  '❌ El monto debe ser mayor a 0.'],
];
$msg = sanitize_key($_GET['wcfin_msg'] ?? '');
?>

<!-- Encabezado -->
<div class="d-flex align-items-center mb-3 border-bottom pb-3">
    <h5 class="mb-0 mr-auto">
        <i class="fa fa-money mr-2 text-primary"></i>Mi Caja
    </h5>
    <small class="text-muted">Solo tú puedes ver esta información</small>
</div>

<?php if ($msg && isset($msg_map[$msg])): [$mt,$mm] = $msg_map[$msg]; ?>
<div class="alert alert-<?php echo esc_attr($mt); ?> alert-dismissible fade show mb-3">
    <?php echo esc_html($mm); ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<!-- Tarjetas de resumen -->
<div class="row mb-4" style="row-gap:12px">

    <!-- Cobrado total -->
    <div class="col-sm-4">
        <div style="background:#e8f4fd;border-left:4px solid #2271b1;border-radius:8px;padding:16px 18px">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#1a6891;letter-spacing:.5px;margin-bottom:6px">
                <i class="fa fa-arrow-circle-down mr-1"></i>Total cobrado
            </div>
            <div style="font-size:2rem;font-weight:700;color:#1a6891">S/ <?php echo number_format($balance,2); ?></div>
            <div style="font-size:11px;color:#888;margin-top:4px">Recaudado de destinatarios</div>
        </div>
    </div>

    <!-- Entregado a DHV -->
    <div class="col-sm-4">
        <div style="background:#d7f7c2;border-left:4px solid #00a32a;border-radius:8px;padding:16px 18px">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#135d3e;letter-spacing:.5px;margin-bottom:6px">
                <i class="fa fa-arrow-circle-up mr-1"></i>Entregado a DHV
            </div>
            <div style="font-size:2rem;font-weight:700;color:#135d3e">S/ <?php echo number_format($liquidado,2); ?></div>
            <div style="font-size:11px;color:#888;margin-top:4px">Liquidaciones registradas</div>
        </div>
    </div>

    <!-- Saldo pendiente -->
    <div class="col-sm-4">
        <div style="background:<?php echo $saldo>0?'#fce9e9':'#d7f7c2'; ?>;border-left:4px solid <?php echo $saldo>0?'#d63638':'#00a32a'; ?>;border-radius:8px;padding:16px 18px">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:<?php echo $saldo>0?'#8a1a1a':'#135d3e'; ?>;letter-spacing:.5px;margin-bottom:6px">
                <i class="fa fa-<?php echo $saldo>0?'exclamation-circle':'check-circle'; ?> mr-1"></i>
                <?php echo $saldo>0 ? 'Saldo a entregar' : 'Estado'; ?>
            </div>
            <div style="font-size:2rem;font-weight:700;color:<?php echo $saldo>0?'#8a1a1a':'#135d3e'; ?>">
                S/ <?php echo number_format(abs($saldo),2); ?>
            </div>
            <div style="font-size:11px;color:#888;margin-top:4px">
                <?php echo $saldo > 0 ? 'Debes entregar este monto a DHV' : '✓ Al día, sin saldo pendiente'; ?>
            </div>
        </div>
    </div>
</div>

<!-- ─── SECCIÓN: Subir comprobante de liquidación ─────────────────────────── -->
<?php if ($saldo > 0): ?>
<div style="background:#fff;border:1px solid #f0c040;border-radius:8px;overflow:hidden;margin-bottom:20px">
    <div style="padding:14px 18px;border-bottom:1px solid #f0e08080;background:#fffbf0;display:flex;align-items:center;gap:12px">
        <div style="background:#ffc107;color:#fff;border-radius:50%;width:38px;height:38px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:16px">
            <i class="fa fa-bell"></i>
        </div>
        <div style="flex:1">
            <strong>Tienes S/ <?php echo number_format($saldo,2); ?> pendiente de entregar a DHV</strong>
            <p class="text-muted small mb-0">Este dinero fue cobrado a destinatarios. Puedes notificar a DHV o subir el comprobante de tu depósito/transferencia directamente aquí.</p>
        </div>
    </div>

    <div style="padding:16px 18px">
        <!-- Tabs -->
        <ul class="nav nav-pills mb-3" style="font-size:13px;gap:6px" id="wcfin-tabs">
            <li class="nav-item">
                <a class="nav-link active" href="#" data-target="tab-voucher" onclick="wcfinTab(this,'tab-voucher');return false;">
                    <i class="fa fa-upload mr-1"></i>Subir comprobante
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-target="tab-notif" onclick="wcfinTab(this,'tab-notif');return false;">
                    <i class="fa fa-paper-plane mr-1"></i>Solo notificar a DHV
                </a>
            </li>
        </ul>

        <!-- Tab: subir comprobante de liquidación (motorizado lo sube) -->
        <div id="tab-voucher">
            <p class="text-muted small mb-3">
                <i class="fa fa-info-circle mr-1"></i>
                Si ya hiciste el depósito o transferencia, sube aquí el comprobante. DHV lo revisará y marcará como liquidado.
            </p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data"
                  id="wcfin-voucher-form">
                <?php wp_nonce_field('wcfin_driver_sube_voucher_nonce'); ?>
                <input type="hidden" name="action" value="wcfin_driver_sube_voucher">
                <input type="hidden" name="_wcfin_redirect" value="<?php echo esc_attr(wcfin_driver_url()); ?>">

                <div class="row" style="row-gap:10px">
                    <div class="col-sm-4 form-group mb-0">
                        <label class="small font-weight-bold">
                            Monto que depositaste S/ <span class="text-danger">*</span>
                        </label>
                        <input name="monto" type="number" step="0.01" min="0.01"
                               max="<?php echo esc_attr(number_format($saldo,2,'.','')); ?>"
                               class="form-control form-control-sm" required
                               value="<?php echo esc_attr(number_format($saldo,2,'.','')); ?>"
                               placeholder="0.00">
                    </div>
                    <div class="col-sm-4 form-group mb-0">
                        <label class="small font-weight-bold">
                            Método <span class="text-danger">*</span>
                        </label>
                        <select name="metodo" class="form-control form-control-sm browser-default" required>
                            <option value="transferencia">Transferencia bancaria</option>
                            <option value="yape_plin">YAPE / PLIN</option>
                            <option value="deposito">Depósito en cuenta</option>
                            <option value="efectivo">Efectivo en mano</option>
                        </select>
                    </div>
                    <div class="col-sm-4 form-group mb-0">
                        <label class="small font-weight-bold">N° operación / referencia</label>
                        <input name="referencia" type="text" class="form-control form-control-sm"
                               placeholder="Código de operación...">
                    </div>

                    <!-- Uploader de comprobante -->
                    <div class="col-12 form-group mb-0">
                        <label class="small font-weight-bold">
                            Comprobante <span class="text-danger">*</span>
                            <span class="text-muted font-weight-normal">(imagen JPG/PNG o PDF, máx. 5MB)</span>
                        </label>
                        <div id="wcfin-drop-area"
                             style="border:2px dashed #c3c4c7;border-radius:8px;padding:24px;text-align:center;cursor:pointer;transition:.2s;background:#f9f9f9"
                             onclick="document.getElementById('wcfin-driver-file').click()"
                             ondragover="event.preventDefault();this.style.borderColor='#2271b1';this.style.background='#f0f6fc'"
                             ondragleave="this.style.borderColor='#c3c4c7';this.style.background='#f9f9f9'"
                             ondrop="wcfinHandleDrop(event)">
                            <i class="fa fa-cloud-upload fa-2x d-block mb-2" style="color:#aaa"></i>
                            <p class="mb-1 small" style="color:#555">Arrastra tu comprobante aquí o haz clic para seleccionar</p>
                            <p class="mb-0" style="font-size:11px;color:#aaa">JPG, PNG, PDF · Máx. 5 MB</p>
                        </div>
                        <input type="file" id="wcfin-driver-file" name="comprobante"
                               accept="image/jpeg,image/png,image/webp,application/pdf"
                               required style="display:none"
                               onchange="wcfinPreviewFile(this)">
                        <!-- Preview del archivo seleccionado -->
                        <div id="wcfin-driver-preview" style="display:none;margin-top:10px;align-items:center;gap:10px;padding:10px;background:#f0f6fc;border-radius:6px;border:1px solid #b3d4f5">
                            <i class="fa fa-file-image-o fa-lg" id="wcfin-file-icon" style="color:#2271b1;flex-shrink:0"></i>
                            <div style="flex:1;min-width:0">
                                <div id="wcfin-file-name" class="small font-weight-bold text-truncate" style="color:#1a6891"></div>
                                <div id="wcfin-file-size" class="small text-muted"></div>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm flex-shrink-0"
                                    onclick="wcfinClearFile()">
                                <i class="fa fa-times mr-1"></i>Quitar
                            </button>
                        </div>
                        <img id="wcfin-img-preview" src="" alt="Preview"
                             style="display:none;margin-top:8px;max-height:140px;max-width:100%;border-radius:6px;border:1px solid #dee2e6">
                    </div>

                    <div class="col-12 form-group mb-0">
                        <label class="small font-weight-bold">Notas adicionales</label>
                        <textarea name="notas" rows="2" class="form-control form-control-sm"
                            placeholder="Ej: Deposité el viernes a las 3pm, referencia XYZ..."></textarea>
                    </div>
                </div>

                <div class="mt-3 d-flex align-items-center flex-wrap" style="gap:10px">
                    <button type="submit" class="btn btn-warning btn-sm px-4" id="wcfin-submit-btn">
                        <i class="fa fa-upload mr-1"></i>Enviar comprobante
                    </button>
                    <small class="text-muted">
                        <i class="fa fa-lock mr-1"></i>DHV revisará y confirmará tu liquidación. Tu saldo se actualizará.
                    </small>
                </div>
            </form>
        </div>

        <!-- Tab: solo notificar -->
        <div id="tab-notif" style="display:none">
            <p class="text-muted small mb-3">
                <i class="fa fa-info-circle mr-1"></i>
                Si prefieres coordinar personalmente con DHV, envía una notificación para que te contacten.
            </p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('wcfin_driver_solicita_nonce'); ?>
                <input type="hidden" name="action" value="wcfin_driver_solicita">
                <div class="form-group mb-2">
                    <label class="small font-weight-bold">Mensaje para DHV (opcional)</label>
                    <textarea name="notas" rows="2" class="form-control form-control-sm"
                        placeholder="Ej: Puedo entregar el viernes, o ya lo deposité en tal cuenta..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa fa-send mr-1"></i>Enviar notificación
                </button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tabla de envíos -->
<div style="background:#fff;border:1px solid #dee2e6;border-radius:8px;overflow:hidden;margin-bottom:16px">
    <div style="padding:12px 16px;border-bottom:1px solid #dee2e6;background:#f8f9fa;display:flex;align-items:center;justify-content:space-between">
        <strong><i class="fa fa-list mr-1 text-primary"></i>Mis envíos con cobro</strong>
        <span class="badge badge-primary"><?php echo count($envios); ?></span>
    </div>
    <?php if (empty($envios)): ?>
    <div class="text-center text-muted py-4">
        <i class="fa fa-inbox fa-2x d-block mb-2" style="opacity:.3"></i>
        No tienes envíos con cobros registrados aún.
    </div>
    <?php else: ?>
    <div class="table-responsive">
    <table class="table table-sm table-hover mb-0" style="font-size:13px">
        <thead class="thead-light">
            <tr>
                <th>Tracking</th>
                <th>Condición</th>
                <th class="text-right">Monto cobrado</th>
                <th>Fecha</th>
                <th>Estado pago</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($envios as $e):
            $ec = $e->estado_pago;
            $ec_bg    = $ec==='confirmado'?'#d7f7c2':($ec==='pendiente'?'#fff3cd':'#f0f0f0');
            $ec_color = $ec==='confirmado'?'#135d3e':($ec==='pendiente'?'#856404':'#666');
            $ec_label = $ec==='confirmado'?'✓ Confirmado':($ec==='pendiente'?'⏳ Pendiente':'Sin registro');
        ?>
        <tr>
            <td><strong><?php echo esc_html($e->tracking); ?></strong></td>
            <td><span style="font-size:11px;color:#666"><?php echo esc_html($e->condicion ?: '—'); ?></span></td>
            <td class="text-right">
                <strong style="color:#2271b1">S/ <?php echo number_format(floatval($e->monto_driver),2); ?></strong>
            </td>
            <td class="small text-muted"><?php echo esc_html($e->fecha ? date_i18n('d/m/Y',strtotime($e->fecha)) : '—'); ?></td>
            <td>
                <span style="padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700;background:<?php echo esc_attr($ec_bg); ?>;color:<?php echo esc_attr($ec_color); ?>">
                    <?php echo esc_html($ec_label); ?>
                </span>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- Historial de liquidaciones -->
<div style="background:#fff;border:1px solid #dee2e6;border-radius:8px;overflow:hidden;margin-bottom:16px">
    <div style="padding:12px 16px;border-bottom:1px solid #dee2e6;background:#f8f9fa;display:flex;align-items:center;justify-content:space-between">
        <strong><i class="fa fa-history mr-1 text-success"></i>Historial de liquidaciones</strong>
        <?php if (!empty($liquidaciones)): ?>
        <span class="badge badge-success"><?php echo count($liquidaciones); ?> registros</span>
        <?php endif; ?>
    </div>
    <?php if (empty($liquidaciones)): ?>
    <div class="text-center text-muted py-4">
        <i class="fa fa-clock-o fa-2x d-block mb-2" style="opacity:.3"></i>
        Aún no hay liquidaciones registradas.
    </div>
    <?php else: ?>
    <div class="table-responsive">
    <table class="table table-sm table-hover mb-0" style="font-size:13px">
        <thead class="thead-light">
            <tr>
                <th>Fecha</th>
                <th class="text-right">Monto</th>
                <th>Método</th>
                <th>Notas</th>
                <th>Estado</th>
                <th>Comprobante</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($liquidaciones as $l):
            $estado_liq = $l->estado ?? 'aprobado';
            $liq_bg    = $estado_liq==='pendiente' ? '#fff3cd' : '#d7f7c2';
            $liq_color = $estado_liq==='pendiente' ? '#856404' : '#135d3e';
            $liq_label = $estado_liq==='pendiente' ? '⏳ Revisando' : '✓ Confirmado';
        ?>
        <tr>
            <td><?php echo esc_html(date_i18n('d/m/Y H:i',strtotime($l->fecha))); ?></td>
            <td class="text-right"><strong style="color:#00a32a">S/ <?php echo number_format(floatval($l->monto),2); ?></strong></td>
            <td><?php echo esc_html($l->metodo); ?></td>
            <td class="small text-muted"><?php echo esc_html($l->notas ?: '—'); ?></td>
            <td>
                <span style="padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700;background:<?php echo esc_attr($liq_bg); ?>;color:<?php echo esc_attr($liq_color); ?>">
                    <?php echo esc_html($liq_label); ?>
                </span>
            </td>
            <td>
                <?php if ($l->comprobante_url): ?>
                <a href="<?php echo esc_url($l->comprobante_url); ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                    <i class="fa fa-download mr-1"></i>Ver
                </a>
                <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<script>
function wcfinTab(link, targetId) {
    document.querySelectorAll('#wcfin-tabs .nav-link').forEach(function(l){ l.classList.remove('active'); });
    link.classList.add('active');
    ['tab-voucher','tab-notif'].forEach(function(id){
        var el = document.getElementById(id);
        if (el) el.style.display = (id === targetId) ? '' : 'none';
    });
}

function wcfinPreviewFile(input) {
    var file = input.files[0];
    if (!file) return;
    var dropArea = document.getElementById('wcfin-drop-area');
    var preview  = document.getElementById('wcfin-driver-preview');
    var imgPrev  = document.getElementById('wcfin-img-preview');
    var nameEl   = document.getElementById('wcfin-file-name');
    var sizeEl   = document.getElementById('wcfin-file-size');
    var iconEl   = document.getElementById('wcfin-file-icon');

    // Validar tipo
    var allowed = ['image/jpeg','image/png','image/webp','application/pdf'];
    if (!allowed.includes(file.type)) {
        alert('Tipo de archivo no permitido. Usa JPG, PNG, WEBP o PDF.');
        input.value = '';
        return;
    }
    // Validar tamaño (5MB)
    if (file.size > 5 * 1024 * 1024) {
        alert('El archivo supera los 5MB. Elige un archivo más pequeño.');
        input.value = '';
        return;
    }

    nameEl.textContent = file.name;
    sizeEl.textContent = (file.size / 1024).toFixed(0) + ' KB';
    preview.style.display = 'flex';
    dropArea.style.borderColor = '#00a32a';
    dropArea.style.background  = '#f0fff4';

    if (file.type.startsWith('image/')) {
        iconEl.className = 'fa fa-file-image-o fa-lg';
        var r = new FileReader();
        r.onload = function(e) {
            imgPrev.src = e.target.result;
            imgPrev.style.display = '';
        };
        r.readAsDataURL(file);
    } else {
        iconEl.className = 'fa fa-file-pdf-o fa-lg';
        imgPrev.style.display = 'none';
    }
}

function wcfinHandleDrop(event) {
    event.preventDefault();
    var dropArea = document.getElementById('wcfin-drop-area');
    dropArea.style.borderColor = '#c3c4c7';
    dropArea.style.background  = '#f9f9f9';
    var input = document.getElementById('wcfin-driver-file');
    if (event.dataTransfer.files.length) {
        // Asignar al input via DataTransfer
        try {
            input.files = event.dataTransfer.files;
        } catch(e) {
            // Fallback para browsers sin soporte directo
        }
        wcfinPreviewFile({ files: event.dataTransfer.files });
    }
}

function wcfinClearFile() {
    document.getElementById('wcfin-driver-file').value = '';
    document.getElementById('wcfin-driver-preview').style.display = 'none';
    document.getElementById('wcfin-img-preview').style.display = 'none';
    document.getElementById('wcfin-drop-area').style.borderColor = '#c3c4c7';
    document.getElementById('wcfin-drop-area').style.background  = '#f9f9f9';
}

// Feedback al enviar formulario
document.getElementById('wcfin-voucher-form') && document.getElementById('wcfin-voucher-form').addEventListener('submit', function(){
    var btn = document.getElementById('wcfin-submit-btn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-1"></i>Enviando...'; }
});
</script>
