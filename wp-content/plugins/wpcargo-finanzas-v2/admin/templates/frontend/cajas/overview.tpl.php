<?php if ( ! defined('ABSPATH') ) exit;
$caja_emp   = $resumen['por_cuenta']['caja_empresa']         ?? 0;
$bal_mot    = $resumen['por_cuenta']['balance_motorizado']    ?? 0;
$dhv_debe   = $resumen['por_cuenta']['deuda_a_remitente']     ?? 0;
$cli_debe   = $resumen['por_cuenta']['deuda_de_remitente']    ?? 0;

// URL base de la página de finanzas frontend
$url_cajas    = wcfin_frontend_url(['wcfin_vista' => 'cajas']);
$url_drivers  = wcfin_frontend_url(['wcfin_vista' => 'caja-drivers']);
$url_clientes = wcfin_frontend_url(['wcfin_vista' => 'caja-clientes']);
?>

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
</div>

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
                <small class="text-muted"><?php echo $d['saldo'] > 0 ? 'debe' : 'ok'; ?></small>
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
