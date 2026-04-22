<?php if ( ! defined('ABSPATH') ) exit;
$caja_emp   = $resumen['por_cuenta']['caja_empresa']         ?? 0;
$bal_mot    = $resumen['por_cuenta']['balance_motorizado']    ?? 0;
$dhv_debe   = $resumen['por_cuenta']['deuda_a_remitente']     ?? 0;
$cli_debe   = $resumen['por_cuenta']['deuda_de_remitente']    ?? 0;

$url_cajas    = wcfin_frontend_url(['wcfin_vista' => 'cajas']);
$url_drivers  = wcfin_frontend_url(['wcfin_vista' => 'caja-drivers']);
$url_clientes = wcfin_frontend_url(['wcfin_vista' => 'caja-clientes']);

// Liquidaciones de drivers pendientes de revisión
$liqs_pendientes = WCFIN_Caja::liquidaciones_pendientes_revision();

// Mensajes de feedback
$msg_map = [
    'liq_aprobada'  => ['success','✅ Liquidación aprobada correctamente.'],
    'liq_rechazada' => ['warning','⚠️ Liquidación rechazada. Se notificó al motorizado.'],
];
$msg = sanitize_key($_GET['wcfin_msg'] ?? '');
?>

<?php if ($msg && isset($msg_map[$msg])): [$mt,$mm] = $msg_map[$msg]; ?>
<div class="alert alert-<?php echo esc_attr($mt); ?> alert-dismissible fade show mb-3">
    <?php echo esc_html($mm); ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<!-- Tarjetas resumen -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin:0 0 24px">
    <?php
    $cards = [
        ['Caja Empresa',        number_format($caja_emp, 2),  '#00a32a', '#d7f7c2', 'fa-building'],
        ['Balance Motorizados', number_format($bal_mot, 2),   '#2271b1', '#e8f4fd', 'fa-motorcycle'],
        ['DHV debe → Clientes', number_format($dhv_debe, 2),  '#9c5700', '#fce0a8', 'fa-arrow-left'],
        ['Clientes deben → DHV',number_format($cli_debe, 2),  '#d63638', '#fce9e9', 'fa-arrow-right'],
    ];
    foreach ($cards as [$titulo, $valor, $color, $bg, $icon]):
    ?>
    <div style="background:<?php echo esc_attr($bg); ?>;border-left:4px solid <?php echo esc_attr($color); ?>;border-radius:6px;padding:16px 20px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:<?php echo esc_attr($color); ?>;margin-bottom:6px">
            <i class="fa <?php echo esc_attr($icon); ?>" style="margin-right:4px"></i>
            <?php echo esc_html($titulo); ?>
        </div>
        <div style="font-size:1.8rem;font-weight:700;color:<?php echo esc_attr($color); ?>">S/ <?php echo esc_html($valor); ?></div>
    </div>
    <?php endforeach; ?>

    <?php if ( ($resumen['pendientes_revision'] ?? 0) > 0 ): ?>
    <div style="background:#fff3cd;border-left:4px solid #ffc107;border-radius:6px;padding:16px 20px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#856404;margin-bottom:6px">
            <i class="fa fa-clock-o" style="margin-right:4px"></i> Pagos por revisar
        </div>
        <div style="font-size:1.8rem;font-weight:700;color:#856404"><?php echo intval($resumen['pendientes_revision']); ?></div>
        <a href="<?php echo esc_url($url_clientes); ?>" class="btn btn-sm btn-warning mt-2">Ver ahora</a>
    </div>
    <?php endif; ?>

    <?php if ( count($liqs_pendientes) > 0 ): ?>
    <div style="background:#e8f4fd;border-left:4px solid #2271b1;border-radius:6px;padding:16px 20px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#1a6891;margin-bottom:6px">
            <i class="fa fa-upload" style="margin-right:4px"></i> Comprobantes drivers
        </div>
        <div style="font-size:1.8rem;font-weight:700;color:#1a6891"><?php echo count($liqs_pendientes); ?></div>
        <small style="color:#555">pendientes de revisión</small>
    </div>
    <?php endif; ?>
</div>

<!-- ─── COMPROBANTES DE LIQUIDACIÓN DE DRIVERS ────────────────────────────── -->
<?php if ( ! empty($liqs_pendientes) ): ?>
<div style="background:#fff;border:1px solid #2271b1;border-radius:8px;overflow:hidden;margin-bottom:20px">
    <div style="padding:12px 18px;background:#e8f4fd;border-bottom:1px solid #c0dcf3;display:flex;align-items:center;gap:8px">
        <i class="fa fa-upload" style="color:#2271b1;font-size:16px"></i>
        <strong style="color:#1a6891">Comprobantes de liquidación enviados por motorizados (<?php echo count($liqs_pendientes); ?> pendientes)</strong>
        <span class="badge badge-primary ml-auto"><?php echo count($liqs_pendientes); ?></span>
    </div>
    <div class="table-responsive">
    <table class="table table-hover mb-0" style="font-size:13px">
        <thead class="thead-light">
            <tr>
                <th>Motorizado</th>
                <th class="text-right">Monto</th>
                <th>Método</th>
                <th>Notas</th>
                <th>Comprobante</th>
                <th>Fecha</th>
                <th style="width:200px">Acción</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($liqs_pendientes as $liq): ?>
        <tr>
            <td><strong><?php echo esc_html($liq->driver_nombre ?? '—'); ?></strong></td>
            <td class="text-right"><strong style="color:#2271b1">S/ <?php echo number_format(floatval($liq->monto), 2); ?></strong></td>
            <td><?php echo esc_html($liq->metodo); ?></td>
            <td class="small text-muted"><?php echo esc_html($liq->notas ?: '—'); ?></td>
            <td>
                <?php if ($liq->comprobante_url): ?>
                <a href="<?php echo esc_url($liq->comprobante_url); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-eye mr-1"></i>Ver
                </a>
                <?php else: ?><span class="text-muted">—</span><?php endif; ?>
            </td>
            <td class="small text-muted"><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($liq->fecha))); ?></td>
            <td>
                <!-- Aprobar -->
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="d-inline">
                    <?php wp_nonce_field('wcfin_revisar_liq_'.intval($liq->id)); ?>
                    <input type="hidden" name="action" value="wcfin_revisar_liquidacion">
                    <input type="hidden" name="liq_id" value="<?php echo intval($liq->id); ?>">
                    <input type="hidden" name="estado" value="aprobado">
                    <input type="hidden" name="_wcfin_redirect" value="<?php echo esc_attr($url_cajas); ?>">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fa fa-check mr-1"></i>Aprobar
                    </button>
                </form>
                <!-- Rechazar con nota -->
                <button type="button" class="btn btn-danger btn-sm ml-1"
                        data-toggle="collapse" data-target="#liq-rechazar-<?php echo intval($liq->id); ?>">
                    <i class="fa fa-times mr-1"></i>Rechazar
                </button>
                <div id="liq-rechazar-<?php echo intval($liq->id); ?>" class="collapse mt-2">
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('wcfin_revisar_liq_'.intval($liq->id)); ?>
                        <input type="hidden" name="action" value="wcfin_revisar_liquidacion">
                        <input type="hidden" name="liq_id" value="<?php echo intval($liq->id); ?>">
                        <input type="hidden" name="estado" value="rechazado">
                        <input type="hidden" name="_wcfin_redirect" value="<?php echo esc_attr($url_cajas); ?>">
                        <textarea name="notas_admin" rows="2" class="form-control form-control-sm mb-1"
                                  placeholder="Motivo del rechazo (se enviará al motorizado)..." required></textarea>
                        <button type="submit" class="btn btn-danger btn-sm btn-block">
                            <i class="fa fa-paper-plane mr-1"></i>Confirmar rechazo
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<!-- ─── PAGOS DE CLIENTES PENDIENTES ─────────────────────────────────────── -->
<?php if ( ! empty($pendientes) ): ?>
<div class="card mb-4">
    <div class="card-header"><strong>⏳ Pagos de clientes pendientes de revisión (<?php echo count($pendientes); ?>)</strong></div>
    <div class="table-responsive">
    <table class="table table-striped table-hover mb-0">
        <thead class="thead-light"><tr>
            <th>Cliente</th><th>Monto</th><th>Método</th><th>Ref.</th><th>Comprobante</th><th>Fecha</th><th>Acción</th>
        </tr></thead>
        <tbody>
        <?php foreach ($pendientes as $p): ?>
        <tr>
            <td><strong><?php echo esc_html($p->cliente_nombre); ?></strong></td>
            <td><strong style="color:#2271b1">S/ <?php echo number_format(floatval($p->monto), 2); ?></strong></td>
            <td><?php echo esc_html($p->metodo); ?></td>
            <td><?php echo esc_html($p->referencia ?: '—'); ?></td>
            <td>
                <?php if ($p->comprobante_url): ?>
                    <a href="<?php echo esc_url($p->comprobante_url); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Ver 🖼</a>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($p->fecha_envio))); ?></td>
            <td>
                <a href="<?php echo esc_url(wcfin_frontend_url(['wcfin_vista' => 'caja-clientes', 'cliente' => $p->user_id])); ?>"
                   class="btn btn-sm btn-primary">Revisar</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

<!-- Motorizados con saldo pendiente -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>🚗 Motorizados con saldo pendiente</strong>
        <a href="<?php echo esc_url($url_drivers); ?>" class="btn btn-sm btn-outline-primary">Ver todos</a>
    </div>
    <div class="table-responsive">
    <table class="table table-striped table-hover mb-0">
        <thead class="thead-light"><tr><th>Driver</th><th>Balance</th><th>Liquidado</th><th>Saldo</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($drivers)): ?>
            <tr><td colspan="5" class="text-center text-muted py-3">Sin datos</td></tr>
        <?php else: foreach (array_slice($drivers, 0, 5) as $d): ?>
        <tr>
            <td><strong><?php echo esc_html($d['user']->display_name); ?></strong></td>
            <td>S/ <?php echo number_format($d['balance'], 2); ?></td>
            <td class="text-success">S/ <?php echo number_format($d['liquidado'], 2); ?></td>
            <td>
                <strong style="color:<?php echo $d['saldo'] > 0 ? '#d63638' : '#00a32a'; ?>">
                    S/ <?php echo number_format($d['saldo'], 2); ?>
                </strong>
                <small class="text-muted"><?php echo $d['saldo'] > 0 ? 'debe' : '✓ ok'; ?></small>
            </td>
            <td>
                <a href="<?php echo esc_url(wcfin_frontend_url(['wcfin_vista' => 'caja-drivers', 'driver' => $d['user']->ID])); ?>"
                   class="btn btn-sm btn-outline-secondary">Ver</a>
            </td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Clientes con balance pendiente -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>👥 Clientes con balance pendiente</strong>
        <a href="<?php echo esc_url($url_clientes); ?>" class="btn btn-sm btn-outline-primary">Ver todos</a>
    </div>
    <div class="table-responsive">
    <table class="table table-striped table-hover mb-0">
        <thead class="thead-light"><tr><th>Cliente</th><th>DHV debe</th><th>Cliente debe</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($clientes)): ?>
            <tr><td colspan="4" class="text-center text-muted py-3">Sin datos</td></tr>
        <?php else: foreach (array_slice($clientes, 0, 5) as $cl): ?>
        <tr>
            <td><strong><?php echo esc_html($cl['user']->display_name); ?></strong></td>
            <td>
                <?php if ($cl['dhv_debe'] > 0): ?>
                    <strong style="color:#9c5700">S/ <?php echo number_format($cl['dhv_debe'], 2); ?></strong>
                <?php else: ?><span class="text-muted">—</span><?php endif; ?>
            </td>
            <td>
                <?php if ($cl['cliente_debe'] > 0): ?>
                    <strong style="color:#d63638">S/ <?php echo number_format($cl['cliente_debe'], 2); ?></strong>
                <?php else: ?><span class="text-muted">—</span><?php endif; ?>
            </td>
            <td>
                <a href="<?php echo esc_url(wcfin_frontend_url(['wcfin_vista' => 'caja-clientes', 'cliente' => $cl['user']->ID])); ?>"
                   class="btn btn-sm btn-outline-secondary">Ver</a>
            </td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>

</div><!-- /grid -->
