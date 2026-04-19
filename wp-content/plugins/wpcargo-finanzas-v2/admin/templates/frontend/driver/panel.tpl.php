<?php if ( ! defined('ABSPATH') ) exit;
$msg_map = [
    'solicitud_enviada' => ['success', '✅ Se notificó al equipo de DHV. Te contactarán para coordinar la liquidación.'],
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

<!-- Si tiene saldo pendiente: notificar al admin -->
<?php if ($saldo > 0): ?>
<div style="background:#fff;border:1px solid #f0c040;border-radius:8px;padding:16px 20px;margin-bottom:20px">
    <div class="d-flex align-items-start" style="gap:12px">
        <div style="background:#ffc107;color:#fff;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:16px">
            <i class="fa fa-bell"></i>
        </div>
        <div style="flex:1">
            <strong>Tienes S/ <?php echo number_format($saldo,2); ?> pendiente de entregar</strong>
            <p class="text-muted small mb-2">Este dinero fue cobrado a destinatarios y debe ser entregado a DHV. Si ya lo entregaste o necesitas coordinarlo, notifica al equipo.</p>
            <button type="button" class="btn btn-warning btn-sm" data-toggle="collapse" data-target="#wcfin-notif-form">
                <i class="fa fa-paper-plane mr-1"></i>Notificar a DHV
            </button>
        </div>
    </div>
    <div id="wcfin-notif-form" class="collapse" style="margin-top:14px;padding-top:14px;border-top:1px solid #f0c040">
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
                <th>Comprobante</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($liquidaciones as $l): ?>
        <tr>
            <td><?php echo esc_html(date_i18n('d/m/Y H:i',strtotime($l->fecha))); ?></td>
            <td class="text-right"><strong style="color:#00a32a">S/ <?php echo number_format(floatval($l->monto),2); ?></strong></td>
            <td><?php echo esc_html($l->metodo); ?></td>
            <td class="small text-muted"><?php echo esc_html($l->notas ?: '—'); ?></td>
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
