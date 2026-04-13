<?php if ( ! defined( 'ABSPATH' ) ) exit;
$balances = $datos['balances'] ?? [];
$trans    = $datos['trans']    ?? [];
$pens     = $datos['pens']     ?? [];

$total_facturado  = 0;
$total_pendientes = 0;
$total_penalidades= 0;
foreach ( $trans as $t ) {
    $total_facturado += floatval($t->monto_total);
    if ( $t->estado === 'pendiente' ) $total_pendientes++;
}
foreach ( $pens as $p ) $total_penalidades += floatval($p->monto_aplicado);
?>
<div class="wrap">
<h1>Reportes Financieros</h1>
<hr class="wp-header-end">

<!-- Filtro -->
<form method="get" style="margin:16px 0">
    <input type="hidden" name="page" value="wcfin-reportes">
    Desde: <input type="date" name="desde" value="<?php echo esc_attr($desde); ?>" style="margin-right:8px">
    Hasta: <input type="date" name="hasta" value="<?php echo esc_attr($hasta); ?>" style="margin-right:8px">
    <button type="submit" class="button button-primary">Filtrar</button>
    <a href="<?php echo esc_url(wcfin_url('wcfin-reportes',['desde'=>date('Y-m-01'),'hasta'=>date('Y-m-d')])); ?>" class="button">Este mes</a>
    <a href="<?php echo esc_url(wcfin_url('wcfin-reportes',['desde'=>date('Y-m-01',strtotime('first day of last month')),'hasta'=>date('Y-m-t',strtotime('first day of last month'))])); ?>" class="button">Mes anterior</a>
</form>

<!-- KPIs -->
<div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap">
    <?php foreach([
        ['Total facturado',           'S/ '.number_format($total_facturado,2), count($trans).' transacciones',     '#00a32a'],
        ['Pendientes de confirmación', (string)$total_pendientes,              'transacciones',                    '#b45309'],
        ['Total en penalidades',       'S/ '.number_format($total_penalidades,2),count($pens).' penalidad(es)',    '#d63638'],
    ] as [$lbl,$val,$sub,$color]): ?>
    <div style="background:#fff;border:1px solid #ddd;border-radius:4px;padding:16px 24px;min-width:160px;text-align:center">
        <div style="font-size:11px;color:#666;text-transform:uppercase;font-weight:700;letter-spacing:.5px"><?php echo esc_html($lbl); ?></div>
        <div style="font-size:1.6rem;font-weight:700;color:<?php echo esc_attr($color); ?>;margin:6px 0"><?php echo esc_html($val); ?></div>
        <div style="font-size:11px;color:#888"><?php echo esc_html($sub); ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Balance por cuenta -->
<div style="background:#fff;border:1px solid #ddd;border-radius:4px;margin-bottom:20px">
    <div style="padding:10px 16px;border-bottom:1px solid #ddd;background:#f6f7f7">
        <strong>Balance por cuenta (<?php echo esc_html($desde); ?> al <?php echo esc_html($hasta); ?>)</strong>
    </div>
    <div style="padding:0">
    <table class="wp-list-table widefat fixed striped" style="border:none">
        <thead><tr><th>Cuenta</th><th>Movimientos</th><th>Balance del período</th></tr></thead>
        <tbody>
        <?php if ($balances): foreach($balances as $b):
            $total = floatval($b->total); ?>
            <tr>
                <td><?php echo esc_html($cuentas[$b->cuenta] ?? $b->cuenta); ?></td>
                <td><?php echo intval($b->n); ?></td>
                <td><strong style="color:<?php echo $total>=0?'#00a32a':'#d63638'; ?>;font-size:1.1em">
                    <?php echo ($total>=0?'+':'').'S/ '.number_format($total,2); ?>
                </strong></td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="3" style="text-align:center;padding:20px;color:#888">Sin movimientos en este período.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Transacciones -->
<div style="background:#fff;border:1px solid #ddd;border-radius:4px;margin-bottom:20px">
    <div style="padding:10px 16px;border-bottom:1px solid #ddd;background:#f6f7f7">
        <strong>Transacciones (<?php echo count($trans); ?>)</strong>
    </div>
    <div style="overflow-x:auto">
    <table class="wp-list-table widefat fixed striped" style="border:none">
        <thead><tr>
            <th>Envío</th><th>Condición</th><th>Método</th>
            <th>Servicio S/</th><th>Producto S/</th><th>Total S/</th>
            <th>Estado</th><th>Fecha</th><th>Operador</th>
        </tr></thead>
        <tbody>
        <?php if ($trans): foreach($trans as $t):
            $vars = json_decode($t->variables_json??'{}',true); ?>
            <tr>
                <td><a href="<?php echo esc_url(get_edit_post_link($t->shipment_id)); ?>">
                    <?php echo esc_html($t->envio_titulo ?: '#'.$t->shipment_id); ?>
                </a></td>
                <td><?php echo esc_html($t->condicion_nombre); ?></td>
                <td><?php echo esc_html($t->metodo_nombre); ?></td>
                <td>S/ <?php echo number_format(floatval($vars['monto_servicio']??$t->monto_servicio),2); ?></td>
                <td>S/ <?php echo number_format(floatval($vars['monto_producto']??0),2); ?></td>
                <td><strong>S/ <?php echo number_format(floatval($t->monto_total),2); ?></strong></td>
                <td><?php echo $t->estado==='confirmado'
                    ? '<span style="color:#00a32a">✅ Confirmado</span>'
                    : '<span style="color:#b45309">⏳ Pendiente</span>'; ?>
                </td>
                <td><?php echo esc_html(date_i18n('d/m/Y H:i',strtotime($t->fecha_creacion))); ?></td>
                <td><?php echo esc_html($t->operador??'—'); ?></td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="9" style="text-align:center;padding:20px;color:#888">Sin transacciones en este período.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Penalidades -->
<div style="background:#fff;border:1px solid #ddd;border-radius:4px;margin-bottom:20px">
    <div style="padding:10px 16px;border-bottom:1px solid #ddd;background:#f6f7f7">
        <strong>Penalidades aplicadas (<?php echo count($pens); ?>)</strong>
    </div>
    <table class="wp-list-table widefat fixed striped" style="border:none">
        <thead><tr><th>Envío</th><th>Penalidad</th><th>Aplica a</th><th>Monto S/</th><th>Notas</th><th>Fecha</th></tr></thead>
        <tbody>
        <?php if ($pens): foreach($pens as $p): ?>
            <tr>
                <td><a href="<?php echo esc_url(get_edit_post_link($p->shipment_id)); ?>"><?php echo esc_html($p->envio_titulo??'#'.$p->shipment_id); ?></a></td>
                <td><?php echo esc_html($p->tipo_nombre); ?></td>
                <td><?php echo esc_html($actores[$p->aplica_a]??$p->aplica_a); ?></td>
                <td><strong style="color:#d63638">− S/ <?php echo number_format(floatval($p->monto_aplicado),2); ?></strong></td>
                <td><?php echo esc_html($p->notas??'—'); ?></td>
                <td><?php echo esc_html(date_i18n('d/m/Y',strtotime($p->fecha))); ?></td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="6" style="text-align:center;padding:20px;color:#888">Sin penalidades en este período.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</div>
