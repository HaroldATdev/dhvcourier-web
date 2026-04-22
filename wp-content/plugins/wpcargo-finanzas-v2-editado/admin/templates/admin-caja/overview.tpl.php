<?php if ( ! defined('ABSPATH') ) exit;
$cta = WCFIN_Database::CUENTAS;
$caja_emp   = $resumen['por_cuenta']['caja_empresa']         ?? 0;
$bal_mot    = $resumen['por_cuenta']['balance_motorizado']    ?? 0;
$dhv_debe   = $resumen['por_cuenta']['deuda_a_remitente']     ?? 0;
$cli_debe   = $resumen['por_cuenta']['deuda_de_remitente']    ?? 0;
?>
<div class="wrap">
<h1><span class="dashicons dashicons-chart-line" style="font-size:24px;vertical-align:middle;margin-right:8px"></span>Panel de Finanzas DHV</h1>
<hr class="wp-header-end">
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin:20px 0">
    <?php
    $cards = [
        ['Caja Empresa',       number_format($caja_emp,2),  '#00a32a','#d7f7c2','dashicons-building'],
        ['Balance Motorizados',number_format($bal_mot,2),   '#2271b1','#e8f4fd','dashicons-car'],
        ['DHV debe → Clientes',number_format($dhv_debe,2),  '#9c5700','#fce0a8','dashicons-arrow-left-alt'],
        ['Clientes deben → DHV',number_format($cli_debe,2), '#d63638','#fce9e9','dashicons-arrow-right-alt'],
    ];
    foreach ($cards as [$titulo,$valor,$color,$bg,$icon]):
    ?>
    <div style="background:<?php echo esc_attr($bg); ?>;border-left:4px solid <?php echo esc_attr($color); ?>;border-radius:6px;padding:16px 20px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:<?php echo esc_attr($color); ?>;margin-bottom:6px">
            <span class="dashicons <?php echo esc_attr($icon); ?>" style="font-size:14px;vertical-align:middle;margin-right:4px"></span>
            <?php echo esc_html($titulo); ?>
        </div>
        <div style="font-size:1.8rem;font-weight:700;color:<?php echo esc_attr($color); ?>">S/ <?php echo esc_html($valor); ?></div>
    </div>
    <?php endforeach; ?>
    <?php if ($resumen['pendientes_revision'] > 0): ?>
    <div style="background:#fff3cd;border-left:4px solid #ffc107;border-radius:6px;padding:16px 20px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#856404;margin-bottom:6px">
            <span class="dashicons dashicons-clock" style="font-size:14px;vertical-align:middle;margin-right:4px"></span>
            Pagos por revisar
        </div>
        <div style="font-size:1.8rem;font-weight:700;color:#856404"><?php echo intval($resumen['pendientes_revision']); ?></div>
        <a href="<?php echo esc_url(wcfin_url('wcfin-caja-clientes')); ?>" class="button button-small" style="margin-top:6px">Ver ahora</a>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($pendientes)): ?>
<div class="postbox" style="margin-bottom:20px">
    <div class="postbox-header"><h2 class="hndle">⏳ Pagos de clientes pendientes de revisión (<?php echo count($pendientes); ?>)</h2></div>
    <div class="inside" style="padding:0">
    <table class="wp-list-table widefat striped">
        <thead><tr><th>Cliente</th><th>Monto</th><th>Método</th><th>Ref.</th><th>Comprobante</th><th>Fecha</th><th>Acción</th></tr></thead>
        <tbody>
        <?php foreach($pendientes as $p): ?>
        <tr>
            <td><strong><?php echo esc_html($p->cliente_nombre); ?></strong></td>
            <td><strong style="color:#2271b1">S/ <?php echo number_format(floatval($p->monto),2); ?></strong></td>
            <td><?php echo esc_html($p->metodo); ?></td>
            <td><?php echo esc_html($p->referencia ?: '—'); ?></td>
            <td><?php if($p->comprobante_url): ?><a href="<?php echo esc_url($p->comprobante_url); ?>" target="_blank" class="button button-small">Ver 🖼</a><?php else: ?>—<?php endif; ?></td>
            <td><?php echo esc_html(date_i18n('d/m/Y H:i',strtotime($p->fecha_envio))); ?></td>
            <td>
                <a href="<?php echo esc_url(wcfin_url('wcfin-caja-clientes',['cliente'=>$p->user_id])); ?>" class="button button-small button-primary">Revisar</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

<!-- Top drivers por saldo pendiente -->
<div class="postbox">
    <div class="postbox-header">
        <h2 class="hndle">🚗 Motorizados con saldo pendiente
            <a href="<?php echo esc_url(wcfin_url('wcfin-caja-drivers')); ?>" class="page-title-action" style="font-size:11px">Ver todos</a>
        </h2>
    </div>
    <div class="inside" style="padding:0">
    <table class="wp-list-table widefat striped">
        <thead><tr><th>Driver</th><th>Balance</th><th>Liquidado</th><th>Saldo</th><th></th></tr></thead>
        <tbody>
        <?php if(empty($drivers)): ?>
            <tr><td colspan="5" style="text-align:center;padding:12px;color:#888">Sin datos</td></tr>
        <?php else: foreach(array_slice($drivers,0,5) as $d): ?>
        <tr>
            <td><strong><?php echo esc_html($d['user']->display_name); ?></strong></td>
            <td>S/ <?php echo number_format($d['balance'],2); ?></td>
            <td class="text-success">S/ <?php echo number_format($d['liquidado'],2); ?></td>
            <td><strong style="color:<?php echo $d['saldo']>0?'#d63638':'#00a32a'; ?>">
                S/ <?php echo number_format($d['saldo'],2); ?></strong>
                <small style="color:#888"><?php echo $d['saldo']>0?'debe':'ok'; ?></small>
            </td>
            <td><a href="<?php echo esc_url(wcfin_url('wcfin-caja-drivers',['driver'=>$d['user']->ID])); ?>" class="button button-small">Ver</a></td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Top clientes por balance -->
<div class="postbox">
    <div class="postbox-header">
        <h2 class="hndle">👥 Clientes con balance pendiente
            <a href="<?php echo esc_url(wcfin_url('wcfin-caja-clientes')); ?>" class="page-title-action" style="font-size:11px">Ver todos</a>
        </h2>
    </div>
    <div class="inside" style="padding:0">
    <table class="wp-list-table widefat striped">
        <thead><tr><th>Cliente</th><th>DHV debe</th><th>Cliente debe</th><th></th></tr></thead>
        <tbody>
        <?php if(empty($clientes)): ?>
            <tr><td colspan="4" style="text-align:center;padding:12px;color:#888">Sin datos</td></tr>
        <?php else: foreach(array_slice($clientes,0,5) as $cl): ?>
        <tr>
            <td><strong><?php echo esc_html($cl['user']->display_name); ?></strong></td>
            <td><?php if($cl['dhv_debe']>0): ?><strong style="color:#9c5700">S/ <?php echo number_format($cl['dhv_debe'],2); ?></strong><?php else: ?><span style="color:#888">—</span><?php endif; ?></td>
            <td><?php if($cl['cliente_debe']>0): ?><strong style="color:#d63638">S/ <?php echo number_format($cl['cliente_debe'],2); ?></strong><?php else: ?><span style="color:#888">—</span><?php endif; ?></td>
            <td><a href="<?php echo esc_url(wcfin_url('wcfin-caja-clientes',['cliente'=>$cl['user']->ID])); ?>" class="button button-small">Ver</a></td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>

</div><!-- /grid -->
</div><!-- /wrap -->
