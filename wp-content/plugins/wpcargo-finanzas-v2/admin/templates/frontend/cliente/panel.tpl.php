<?php if ( ! defined('ABSPATH') ) exit;
$metodos_pago = [
    'transferencia' => 'Transferencia Bancaria',
    'yape_plin'     => 'YAPE / PLIN',
    'efectivo'      => 'Efectivo',
    'deposito'      => 'Depósito en cuenta',
];
$msg_map = [
    'pago_enviado'  => ['success','✅ Tu comprobante fue enviado. DHV lo revisará pronto y actualizará tu saldo.'],
    'error_req'     => ['danger', '❌ Debes adjuntar un comprobante para registrar el pago.'],
    'error_monto'   => ['danger', '❌ El monto debe ser mayor a 0.'],
];
$msg = sanitize_key($_GET['wcfin_msg'] ?? '');
$pagos_dhv_a_mi = array_filter($pagos, fn($p) => $p->direccion === 'dhv_a_cliente');
$mis_pagos      = array_filter($pagos, fn($p) => $p->direccion === 'cliente_a_dhv');
?>

<!-- Encabezado -->
<div class="d-flex align-items-center mb-3 border-bottom pb-3">
    <h5 class="mb-0 mr-auto">
        <i class="fa fa-money mr-2 text-primary"></i>Mi Cuenta Financiera
    </h5>
    <small class="text-muted">Resumen de tu balance con DHV</small>
</div>

<?php if ($msg && isset($msg_map[$msg])): [$mt,$mm] = $msg_map[$msg]; ?>
<div class="alert alert-<?php echo esc_attr($mt); ?> alert-dismissible fade show mb-3">
    <?php echo esc_html($mm); ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<!-- Tarjetas de balance -->
<div class="row mb-4" style="row-gap:12px">

    <!-- DHV te debe -->
    <div class="col-md-6">
        <div style="background:<?php echo $dhv_debe>0?'#fce0a8':'#f8f9fa'; ?>;border-left:4px solid <?php echo $dhv_debe>0?'#9c5700':'#ccc'; ?>;border-radius:8px;padding:16px 18px;height:100%">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:<?php echo $dhv_debe>0?'#9c5700':'#888'; ?>;letter-spacing:.5px;margin-bottom:6px">
                <i class="fa fa-arrow-circle-left mr-1"></i>DHV te debe
            </div>
            <div style="font-size:2rem;font-weight:700;color:<?php echo $dhv_debe>0?'#9c5700':'#aaa'; ?>">
                S/ <?php echo number_format($dhv_debe,2); ?>
            </div>
            <div style="font-size:12px;color:#888;margin-top:4px">
                <?php if ($dhv_debe > 0): ?>
                <i class="fa fa-info-circle mr-1"></i>Pendiente que DHV te transfiera
                <?php else: ?>
                Sin deuda pendiente de DHV hacia ti
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tú debes a DHV -->
    <div class="col-md-6">
        <div style="background:<?php echo $yo_debo>0?'#fce9e9':'#d7f7c2'; ?>;border-left:4px solid <?php echo $yo_debo>0?'#d63638':'#00a32a'; ?>;border-radius:8px;padding:16px 18px;height:100%">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:<?php echo $yo_debo>0?'#d63638':'#00a32a'; ?>;letter-spacing:.5px;margin-bottom:6px">
                <i class="fa fa-arrow-circle-right mr-1"></i>Tú debes a DHV
            </div>
            <div style="font-size:2rem;font-weight:700;color:<?php echo $yo_debo>0?'#d63638':'#00a32a'; ?>">
                S/ <?php echo number_format($yo_debo,2); ?>
            </div>
            <div style="font-size:12px;color:#888;margin-top:4px">
                <?php if ($yo_debo > 0): ?>
                <i class="fa fa-exclamation-circle mr-1"></i>Pendiente de pago
                <?php else: ?>
                <i class="fa fa-check-circle mr-1"></i>¡Al día! Sin deuda pendiente
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($yo_debo <= 0 && $dhv_debe <= 0 && empty($envios)): ?>
<!-- Sin actividad -->
<div class="text-center py-5 text-muted">
    <i class="fa fa-check-circle fa-3x d-block mb-3" style="color:#00a32a;opacity:.6"></i>
    <h6>Todo al día</h6>
    <p class="small">No tienes deudas pendientes ni saldos a favor por el momento.</p>
</div>
<?php else: ?>

<!-- ─── SECCIÓN: Registrar mi pago → DHV ─────────────────────────────────── -->
<?php if ($yo_debo > 0): ?>
<div style="background:#fff;border:1px solid #f0a0a0;border-radius:8px;overflow:hidden;margin-bottom:16px">
    <div style="padding:12px 16px;border-bottom:1px solid #f0a0a0;background:#fff5f5;display:flex;align-items:center;gap:8px">
        <i class="fa fa-credit-card" style="color:#d63638"></i>
        <div>
            <strong style="color:#d63638">Pagar a DHV — S/ <?php echo number_format($yo_debo,2); ?> pendiente</strong>
            <div class="small text-muted">Realiza la transferencia y sube el comprobante aquí</div>
        </div>
        <button class="btn btn-outline-danger btn-sm ml-auto" type="button"
                data-toggle="collapse" data-target="#wcfin-pago-form" aria-expanded="false">
            <i class="fa fa-upload mr-1"></i>Subir comprobante
        </button>
    </div>
    <div id="wcfin-pago-form" class="collapse">
    <div style="padding:16px">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
            <?php wp_nonce_field('wcfin_fe_pago_nonce'); ?>
            <input type="hidden" name="action" value="wcfin_fe_cliente_paga">
            <div class="row" style="row-gap:10px">
                <div class="col-sm-4 form-group mb-0">
                    <label class="small font-weight-bold">Monto que pagas S/ <span class="text-danger">*</span></label>
                    <input name="monto" type="number" step="0.01" min="0.01" max="<?php echo esc_attr($yo_debo); ?>"
                           class="form-control form-control-sm" required
                           value="<?php echo esc_attr(number_format($yo_debo,2,'.','')); ?>">
                </div>
                <div class="col-sm-4 form-group mb-0">
                    <label class="small font-weight-bold">Método <span class="text-danger">*</span></label>
                    <select name="metodo" class="form-control form-control-sm browser-default">
                        <?php foreach ($metodos_pago as $k => $v): ?>
                        <option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($v); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-4 form-group mb-0">
                    <label class="small font-weight-bold">N° operación / referencia</label>
                    <input name="referencia" type="text" class="form-control form-control-sm" placeholder="Código de operación...">
                </div>
                <div class="col-12 form-group mb-0">
                    <label class="small font-weight-bold">
                        Comprobante <span class="text-danger">*</span>
                        <span class="text-muted font-weight-normal">(imagen o PDF)</span>
                    </label>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                        <label class="btn btn-outline-secondary btn-sm mb-0" for="wcfin-comp-file" style="cursor:pointer">
                            <i class="fa fa-upload mr-1"></i>Seleccionar archivo
                        </label>
                        <input type="file" id="wcfin-comp-file" name="comprobante" accept="image/*,.pdf" required style="display:none">
                        <span id="wcfin-comp-nombre" class="small text-muted">Ningún archivo seleccionado</span>
                    </div>
                    <div id="wcfin-preview-wrap" style="display:none;margin-top:8px">
                        <img id="wcfin-comp-preview" src="" alt="Preview" style="max-height:120px;max-width:100%;border-radius:4px;border:1px solid #dee2e6">
                    </div>
                </div>
                <div class="col-12 form-group mb-0">
                    <label class="small font-weight-bold">Notas adicionales</label>
                    <textarea name="notas" rows="2" class="form-control form-control-sm"
                        placeholder="Observaciones sobre el pago..."></textarea>
                </div>
            </div>
            <div class="mt-3 d-flex align-items-center" style="gap:10px">
                <button type="submit" class="btn btn-danger btn-sm px-4">
                    <i class="fa fa-paper-plane mr-1"></i>Enviar comprobante
                </button>
                <small class="text-muted">DHV revisará y confirmará tu pago.</small>
            </div>
        </form>
    </div>
    </div>
</div>
<script>
document.getElementById('wcfin-comp-file').addEventListener('change', function(){
    var file = this.files[0];
    if (!file) return;
    document.getElementById('wcfin-comp-nombre').textContent = file.name;
    document.getElementById('wcfin-comp-nombre').style.color = '#2271b1';
    if (file.type.startsWith('image/')) {
        var r = new FileReader();
        r.onload = function(e) {
            document.getElementById('wcfin-comp-preview').src = e.target.result;
            document.getElementById('wcfin-preview-wrap').style.display = '';
        };
        r.readAsDataURL(file);
    } else {
        document.getElementById('wcfin-preview-wrap').style.display = 'none';
    }
});
// Auto-expand si hay mensaje de error
<?php if (in_array($msg, ['error_req','error_monto'])): ?>
document.getElementById('wcfin-pago-form').classList.add('show');
<?php endif; ?>
</script>
<?php endif; ?>

<!-- ─── SECCIÓN: Pagos que DHV te ha enviado ─────────────────────────────── -->
<?php if (!empty($pagos_dhv_a_mi) || $dhv_debe > 0): ?>
<div style="background:#fff;border:1px solid <?php echo !empty($pagos_dhv_a_mi)?'#f0c040':'#dee2e6'; ?>;border-radius:8px;overflow:hidden;margin-bottom:16px">
    <div style="padding:12px 16px;border-bottom:1px solid <?php echo !empty($pagos_dhv_a_mi)?'#f0c040':'#dee2e6'; ?>;background:<?php echo !empty($pagos_dhv_a_mi)?'#fffbf0':'#f8f9fa'; ?>">
        <strong><i class="fa fa-inbox mr-1" style="color:#9c5700"></i>Pagos de DHV hacia ti</strong>
        <?php if ($dhv_debe > 0): ?>
        <span class="badge badge-warning ml-2">S/ <?php echo number_format($dhv_debe,2); ?> pendiente</span>
        <?php endif; ?>
    </div>
    <?php if (empty($pagos_dhv_a_mi)): ?>
    <div class="text-center text-muted py-4">
        <i class="fa fa-clock-o fa-2x d-block mb-2" style="opacity:.3"></i>
        <p class="small mb-0">DHV procesará el pago pronto. El comprobante aparecerá aquí.</p>
    </div>
    <?php else: ?>
    <div style="padding:12px">
    <?php foreach ($pagos_dhv_a_mi as $p): ?>
    <div style="display:flex;align-items:center;gap:12px;padding:12px;background:#fffbf0;border:1px solid #f0e080;border-radius:6px;margin-bottom:8px">
        <div style="background:#9c5700;color:#fff;border-radius:50%;width:38px;height:38px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="fa fa-money"></i>
        </div>
        <div style="flex:1">
            <div>
                <strong>S/ <?php echo number_format(floatval($p->monto),2); ?></strong>
                <span class="small text-muted ml-2">via <?php echo esc_html($p->metodo); ?></span>
                <?php if ($p->referencia): ?>
                <span class="small text-muted"> · Ref: <?php echo esc_html($p->referencia); ?></span>
                <?php endif; ?>
            </div>
            <div class="small text-muted"><?php echo esc_html(date_i18n('d/m/Y H:i',strtotime($p->fecha_envio))); ?></div>
            <?php if ($p->notas_emisor): ?>
            <div class="small text-muted"><i class="fa fa-comment-o mr-1"></i><?php echo esc_html($p->notas_emisor); ?></div>
            <?php endif; ?>
        </div>
        <?php if ($p->comprobante_url): ?>
        <a href="<?php echo esc_url($p->comprobante_url); ?>" target="_blank" class="btn btn-outline-secondary btn-sm flex-shrink-0">
            <i class="fa fa-download mr-1"></i>Comprobante
        </a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ─── SECCIÓN: Detalle por envío ───────────────────────────────────────── -->
<?php if (!empty($envios)): ?>
<div style="background:#fff;border:1px solid #dee2e6;border-radius:8px;overflow:hidden;margin-bottom:16px">
    <div style="padding:12px 16px;border-bottom:1px solid #dee2e6;background:#f8f9fa">
        <strong><i class="fa fa-list mr-1 text-primary"></i>Detalle por envío</strong>
        <span class="badge badge-secondary ml-2"><?php echo count($envios); ?></span>
    </div>
    <div class="table-responsive">
    <table class="table table-sm table-hover mb-0" style="font-size:13px">
        <thead class="thead-light">
            <tr>
                <th>Tracking</th>
                <th>Condición</th>
                <th class="text-right">Total</th>
                <th class="text-right">DHV te debe</th>
                <th class="text-right">Tú debes</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($envios as $e): ?>
        <tr>
            <td><strong class="small"><?php echo esc_html($e->tracking); ?></strong></td>
            <td>
                <span style="padding:2px 7px;border-radius:10px;font-size:11px;font-weight:700;
                      background:<?php echo $e->cobrar_a==='destinatario'?'#e8f4fd':'#f0fff4'; ?>;
                      color:<?php echo $e->cobrar_a==='destinatario'?'#1a6891':'#135d3e'; ?>">
                    <?php echo esc_html($e->condicion ?: '—'); ?>
                </span>
            </td>
            <td class="text-right small">S/ <?php echo number_format(floatval($e->monto_total),2); ?></td>
            <td class="text-right">
                <?php if (floatval($e->dhv_debe) > 0): ?>
                <strong style="color:#9c5700">S/ <?php echo number_format(floatval($e->dhv_debe),2); ?></strong>
                <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
            </td>
            <td class="text-right">
                <?php if (floatval($e->cliente_debe) > 0): ?>
                <strong style="color:#d63638">S/ <?php echo number_format(floatval($e->cliente_debe),2); ?></strong>
                <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
            </td>
            <td class="small text-muted"><?php echo esc_html($e->fecha ? date_i18n('d/m/Y',strtotime($e->fecha)) : '—'); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<!-- ─── SECCIÓN: Mis pagos enviados ──────────────────────────────────────── -->
<?php if (!empty($mis_pagos)): ?>
<div style="background:#fff;border:1px solid #dee2e6;border-radius:8px;overflow:hidden;margin-bottom:16px">
    <div style="padding:12px 16px;border-bottom:1px solid #dee2e6;background:#f8f9fa">
        <strong><i class="fa fa-history mr-1 text-primary"></i>Mis pagos enviados</strong>
    </div>
    <div class="table-responsive">
    <table class="table table-sm mb-0" style="font-size:13px">
        <thead class="thead-light">
            <tr><th>Fecha</th><th>Monto</th><th>Método</th><th>Referencia</th><th>Comprobante</th><th>Estado</th><th>Respuesta DHV</th></tr>
        </thead>
        <tbody>
        <?php foreach ($mis_pagos as $p):
            $est_data = [
                'aprobado'  => ['#d7f7c2','#135d3e','✅ Aprobado'],
                'rechazado' => ['#fce9e9','#8a1a1a','❌ Rechazado'],
                'pendiente' => ['#fff3cd','#856404','⏳ Revisando'],
            ][$p->estado] ?? ['#f0f0f0','#666','—'];
        ?>
        <tr>
            <td class="small"><?php echo esc_html(date_i18n('d/m/Y H:i',strtotime($p->fecha_envio))); ?></td>
            <td><strong>S/ <?php echo number_format(floatval($p->monto),2); ?></strong></td>
            <td class="small"><?php echo esc_html($p->metodo); ?></td>
            <td class="small text-muted"><?php echo esc_html($p->referencia ?: '—'); ?></td>
            <td>
                <?php if ($p->comprobante_url): ?>
                <a href="<?php echo esc_url($p->comprobante_url); ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="fa fa-eye mr-1"></i>Ver</a>
                <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
            </td>
            <td>
                <span style="padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700;background:<?php echo esc_attr($est_data[0]); ?>;color:<?php echo esc_attr($est_data[1]); ?>">
                    <?php echo esc_html($est_data[2]); ?>
                </span>
            </td>
            <td class="small text-muted"><?php echo esc_html($p->notas_admin ?: '—'); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<?php endif; // fin del bloque "sin actividad" ?>
