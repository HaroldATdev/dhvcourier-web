<?php if ( ! defined('ABSPATH') ) exit;
$msg_map = [
    'solicitud_enviada'  => ['success', '✅ Se notificó al equipo de DHV. Te contactarán para coordinar la liquidación.'],
    'comp_enviado'       => ['success', '✅ Comprobante enviado. DHV lo revisará y registrará tu liquidación.'],
    'comp_error'         => ['danger',  '❌ Error al subir el comprobante. Intenta con JPG, PNG o PDF menor a 5 MB.'],
    'comp_sin_archivo'   => ['warning', '⚠️ Debes seleccionar un comprobante (imagen o PDF) para continuar.'],
    'comp_monto_invalido'=> ['danger',  '❌ El monto debe ser mayor a 0.'],
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
<div style="background:#fff;border:1px solid #2271b1;border-radius:8px;overflow:hidden;margin-bottom:20px">
    <div style="padding:14px 18px;background:#e8f4fd;border-bottom:1px solid #c0dcf3;display:flex;align-items:center;gap:10px">
        <div style="background:#2271b1;color:#fff;border-radius:50%;width:38px;height:38px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:16px">
            <i class="fa fa-upload"></i>
        </div>
        <div style="flex:1">
            <strong style="color:#1a6891">Enviar dinero a DHV — S/ <?php echo number_format($saldo,2); ?> pendiente</strong>
            <div style="font-size:12px;color:#555;margin-top:2px">
                Realiza la transferencia o depósito y sube tu comprobante aquí. DHV lo revisará y registrará la liquidación.
            </div>
        </div>
        <button type="button" class="btn btn-primary btn-sm" data-toggle="collapse" data-target="#wcfin-driver-comp-form">
            <i class="fa fa-camera mr-1"></i>Subir comprobante
        </button>
    </div>

    <div id="wcfin-driver-comp-form" class="collapse">
    <div style="padding:18px">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" id="wcfin-driver-form">
            <?php wp_nonce_field('wcfin_driver_comp_nonce'); ?>
            <input type="hidden" name="action" value="wcfin_driver_sube_comprobante">
            <input type="hidden" name="_wcfin_redirect" value="<?php echo esc_attr(wcfin_driver_url()); ?>">

            <div class="row" style="row-gap:12px">
                <!-- Monto -->
                <div class="col-sm-4 form-group mb-0">
                    <label class="small font-weight-bold">
                        Monto que entregas S/ <span class="text-danger">*</span>
                    </label>
                    <input name="monto" type="number" step="0.01" min="0.01"
                           max="<?php echo esc_attr(number_format($saldo,2,'.','')); ?>"
                           class="form-control form-control-sm" required
                           value="<?php echo esc_attr(number_format($saldo,2,'.','')); ?>">
                    <small class="text-muted">Puedes entregar parcialmente</small>
                </div>
                <!-- Método -->
                <div class="col-sm-4 form-group mb-0">
                    <label class="small font-weight-bold">Método <span class="text-danger">*</span></label>
                    <select name="metodo" class="form-control form-control-sm browser-default">
                        <option value="efectivo">💵 Efectivo en mano</option>
                        <option value="transferencia">🏦 Transferencia bancaria</option>
                        <option value="yape_plin">📱 YAPE / PLIN</option>
                        <option value="deposito">🏧 Depósito en cuenta</option>
                    </select>
                </div>
                <!-- Referencia -->
                <div class="col-sm-4 form-group mb-0">
                    <label class="small font-weight-bold">N° operación / referencia</label>
                    <input name="referencia" type="text" class="form-control form-control-sm" placeholder="Código de transferencia...">
                </div>
                <!-- Comprobante -->
                <div class="col-12 form-group mb-0">
                    <label class="small font-weight-bold">
                        Comprobante <span class="text-danger">*</span>
                        <span class="text-muted font-weight-normal">(foto del depósito, captura de YAPE, etc. — JPG, PNG o PDF)</span>
                    </label>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                        <label class="btn btn-outline-primary btn-sm mb-0" for="wcfin-drv-file" style="cursor:pointer">
                            <i class="fa fa-image mr-1"></i>Seleccionar archivo
                        </label>
                        <input type="file" id="wcfin-drv-file" name="comprobante"
                               accept="image/jpeg,image/png,image/webp,application/pdf"
                               required style="display:none">
                        <span id="wcfin-drv-nombre" class="small text-muted">Ningún archivo seleccionado</span>
                        <span id="wcfin-drv-size-warn" class="small text-danger" style="display:none">⚠️ Archivo demasiado grande (máx 5 MB)</span>
                    </div>
                    <!-- Preview imagen -->
                    <div id="wcfin-drv-preview-wrap" style="display:none;margin-top:8px">
                        <img id="wcfin-drv-preview" src="" alt="Preview"
                             style="max-height:140px;max-width:100%;border-radius:6px;border:1px solid #dee2e6;box-shadow:0 2px 6px rgba(0,0,0,.1)">
                    </div>
                </div>
                <!-- Notas -->
                <div class="col-12 form-group mb-0">
                    <label class="small font-weight-bold">Notas adicionales (opcional)</label>
                    <textarea name="notas" rows="2" class="form-control form-control-sm"
                        placeholder="Ej: Depósito realizado el lunes, referencia tal..."></textarea>
                </div>
            </div>

            <div class="mt-3 d-flex align-items-center" style="gap:10px">
                <button type="submit" class="btn btn-primary btn-sm px-4" id="wcfin-drv-submit">
                    <i class="fa fa-paper-plane mr-1"></i>Enviar comprobante
                </button>
                <small class="text-muted">DHV lo revisará y marcará como liquidado.</small>
            </div>
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
            $est = $l->estado ?? 'aprobado';
            $est_color = $est==='pendiente'?'#856404':($est==='rechazado'?'#8a1a1a':'#135d3e');
            $est_bg    = $est==='pendiente'?'#fff3cd':($est==='rechazado'?'#fce9e9':'#d7f7c2');
            $est_label = $est==='pendiente'?'⏳ Revisando':($est==='rechazado'?'❌ Rechazado':'✓ Aprobado');
        ?>
        <tr>
            <td><?php echo esc_html(date_i18n('d/m/Y H:i',strtotime($l->fecha))); ?></td>
            <td class="text-right"><strong style="color:#00a32a">S/ <?php echo number_format(floatval($l->monto),2); ?></strong></td>
            <td><?php echo esc_html($l->metodo); ?></td>
            <td class="small text-muted"><?php echo esc_html($l->notas ?: '—'); ?></td>
            <td>
                <span style="padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700;background:<?php echo esc_attr($est_bg); ?>;color:<?php echo esc_attr($est_color); ?>">
                    <?php echo esc_html($est_label); ?>
                </span>
                <?php if (!empty($l->notas_admin) && $est === 'rechazado'): ?>
                <div class="small text-danger mt-1"><?php echo esc_html($l->notas_admin); ?></div>
                <?php endif; ?>
            </td>
            <td>
                <?php if (!empty($l->comprobante_url)): ?>
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
(function(){
    var fileInput = document.getElementById('wcfin-drv-file');
    if (!fileInput) return;
    fileInput.addEventListener('change', function(){
        var file = this.files[0];
        if (!file) return;
        var maxBytes = 5 * 1024 * 1024;
        var warn = document.getElementById('wcfin-drv-size-warn');
        var submit = document.getElementById('wcfin-drv-submit');
        if (file.size > maxBytes) {
            if (warn) warn.style.display = 'inline';
            if (submit) submit.disabled = true;
            document.getElementById('wcfin-drv-nombre').textContent = file.name;
            return;
        }
        if (warn) warn.style.display = 'none';
        if (submit) submit.disabled = false;
        document.getElementById('wcfin-drv-nombre').textContent = file.name;
        document.getElementById('wcfin-drv-nombre').style.color = '#2271b1';
        if (file.type.startsWith('image/')) {
            var r = new FileReader();
            r.onload = function(e) {
                document.getElementById('wcfin-drv-preview').src = e.target.result;
                document.getElementById('wcfin-drv-preview-wrap').style.display = '';
            };
            r.readAsDataURL(file);
        } else {
            document.getElementById('wcfin-drv-preview-wrap').style.display = 'none';
        }
    });

    <?php if ($saldo > 0): ?>
    // Auto-abrir form si hay saldo pendiente por primera vez
    if (!document.cookie.match('wcfin_drv_seen')) {
        // Solo mostrar el badge, no abrir automáticamente
    }
    <?php endif; ?>
})();
</script>
