<?php if ( ! defined( 'ABSPATH' ) ) exit;
$balances = $datos['balances'] ?? [];
$trans    = $datos['trans']    ?? [];
$pens     = $datos['pens']     ?? [];
$total_facturado   = array_sum(array_map(fn($t)=>floatval($t->monto_total),$trans));
$total_pendientes  = count(array_filter($trans,fn($t)=>$t->estado==='pendiente'));
$total_penalidades = array_sum(array_map(fn($p)=>floatval($p->monto_aplicado),$pens));
?>

<!-- Navegación interna -->
<div class="d-flex align-items-center mb-3 border-bottom pb-3">
    <h5 class="mb-0 mr-auto"><i class="fa fa-line-chart mr-2 text-primary"></i>Reportes Financieros</h5>
    <div>
        <a href="<?php echo esc_url(add_query_arg('wcfin_vista','condiciones',$page_url)); ?>" class="btn btn-outline-secondary btn-sm mr-1">
            <i class="fa fa-list-alt mr-1"></i>Condiciones
        </a>
        <a href="<?php echo esc_url(add_query_arg('wcfin_vista','metodos',$page_url)); ?>" class="btn btn-outline-secondary btn-sm mr-1">
            <i class="fa fa-credit-card mr-1"></i>Métodos de Pago
        </a>
        <a href="<?php echo esc_url(add_query_arg('wcfin_vista','penalidades',$page_url)); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fa fa-exclamation-triangle mr-1"></i>Penalidades
        </a>
    </div>
</div>

<!-- Filtro de fechas -->
<div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:6px;padding:14px 18px;margin-bottom:20px">
    <form method="get" action="<?php echo esc_url($page_url); ?>" class="d-flex align-items-end flex-wrap" style="gap:10px">
        <?php foreach($_GET as $k=>$v): if(in_array($k,['desde','hasta'],true)) continue; ?>
        <input type="hidden" name="<?php echo esc_attr($k); ?>" value="<?php echo esc_attr($v); ?>">
        <?php endforeach; ?>
        <div>
            <label class="d-block mb-1" style="font-size:11px;font-weight:700;color:#666;text-transform:uppercase">Desde</label>
            <input type="date" name="desde" class="form-control form-control-sm" value="<?php echo esc_attr($desde); ?>">
        </div>
        <div>
            <label class="d-block mb-1" style="font-size:11px;font-weight:700;color:#666;text-transform:uppercase">Hasta</label>
            <input type="date" name="hasta" class="form-control form-control-sm" value="<?php echo esc_attr($hasta); ?>">
        </div>
        <div class="d-flex" style="gap:6px">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter mr-1"></i>Filtrar</button>
            <a href="<?php echo esc_url(add_query_arg(['desde'=>date('Y-m-01'),'hasta'=>date('Y-m-d')],$page_url)); ?>" class="btn btn-outline-secondary btn-sm">Este mes</a>
            <a href="<?php echo esc_url(add_query_arg(['desde'=>date('Y-m-01',strtotime('first day of last month')),'hasta'=>date('Y-m-t',strtotime('first day of last month'))],$page_url)); ?>" class="btn btn-outline-secondary btn-sm">Mes anterior</a>
        </div>
    </form>
</div>

<!-- KPIs -->
<div class="row mb-4" style="row-gap:12px">
    <?php foreach([
        ['Total facturado',           'S/ '.number_format($total_facturado,2),  count($trans).' transacciones','#00a32a','fa-money'],
        ['Pendientes confirmación',    (string)$total_pendientes,               'transacciones pendientes',    '#b45309','fa-clock-o'],
        ['Total en penalidades',       'S/ '.number_format($total_penalidades,2),count($pens).' penalidad(es)','#dc3545','fa-exclamation-triangle'],
    ] as [$lbl,$val,$sub,$color,$icon]): ?>
    <div class="col-md-4 col-12">
        <div style="background:#fff;border:1px solid #dee2e6;border-radius:6px;padding:16px;text-align:center">
            <i class="fa <?php echo esc_attr($icon); ?> fa-lg mb-2 d-block" style="color:<?php echo esc_attr($color); ?>"></i>
            <div style="font-size:10px;color:#888;text-transform:uppercase;font-weight:700;letter-spacing:.5px"><?php echo esc_html($lbl); ?></div>
            <div style="font-size:1.6rem;font-weight:700;color:<?php echo esc_attr($color); ?>;margin:4px 0"><?php echo esc_html($val); ?></div>
            <div class="text-muted small"><?php echo esc_html($sub); ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Balance por cuenta -->
<div style="background:#fff;border:1px solid #dee2e6;border-radius:6px;margin-bottom:20px;overflow:hidden">
    <div style="padding:12px 16px;border-bottom:1px solid #dee2e6;background:#f8f9fa">
        <strong style="font-size:.9rem"><i class="fa fa-balance-scale mr-1 text-primary"></i>Balance por cuenta — <?php echo esc_html($desde); ?> al <?php echo esc_html($hasta); ?></strong>
    </div>
    <div class="table-responsive">
    <table class="table table-hover table-sm mb-0">
        <thead class="thead-light"><tr><th>Cuenta</th><th>Movimientos</th><th>Balance del período</th></tr></thead>
        <tbody>
        <?php if ($balances): foreach($balances as $b):
            $total = floatval($b->total); ?>
            <tr>
                <td><?php echo esc_html($cuentas[$b->cuenta]??$b->cuenta); ?></td>
                <td><?php echo intval($b->n); ?></td>
                <td><strong style="color:<?php echo $total>=0?'#28a745':'#dc3545'; ?>;font-size:1.05em">
                    <?php echo ($total>=0?'+':'').'S/ '.number_format($total,2); ?>
                </strong></td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="3" class="text-center text-muted py-4">
                <i class="fa fa-inbox fa-2x d-block mb-2"></i>Sin movimientos en este período.
            </td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Transacciones -->
<div style="background:#fff;border:1px solid #dee2e6;border-radius:6px;margin-bottom:20px;overflow:hidden">
    <div style="padding:12px 16px;border-bottom:1px solid #dee2e6;background:#f8f9fa">
        <strong style="font-size:.9rem"><i class="fa fa-exchange mr-1 text-secondary"></i>Transacciones (<?php echo count($trans); ?>)</strong>
    </div>
    <div class="table-responsive">
    <table class="table table-hover table-sm mb-0">
        <thead class="thead-light">
            <tr><th>Envío</th><th>Condición</th><th>Método</th><th>Servicio S/</th><th>Total S/</th><th>Estado</th><th>Fecha</th><th>Operador</th></tr>
        </thead>
        <tbody>
        <?php if ($trans): foreach($trans as $t):
            $vars=json_decode($t->variables_json??'{}',true); ?>
            <tr>
                <td><a href="<?php echo esc_url(get_edit_post_link($t->shipment_id)); ?>"><?php echo esc_html($t->envio_titulo??'#'.$t->shipment_id); ?></a></td>
                <td><span class="badge badge-light"><?php echo esc_html($t->condicion_nombre); ?></span></td>
                <td><?php echo esc_html($t->metodo_nombre); ?></td>
                <td>S/ <?php echo number_format(floatval($vars['monto_servicio']??$t->monto_servicio),2); ?></td>
                <td><strong>S/ <?php echo number_format(floatval($t->monto_total),2); ?></strong></td>
                <td><?php echo $t->estado==='confirmado'
                    ? '<span class="badge badge-success">✓ Confirmado</span>'
                    : '<span class="badge badge-warning">⏳ Pendiente</span>'; ?>
                </td>
                <td class="small"><?php echo esc_html(date_i18n('d/m/Y H:i',strtotime($t->fecha_creacion))); ?></td>
                <td class="small"><?php echo esc_html($t->operador??'—'); ?></td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="8" class="text-center text-muted py-4">
                <i class="fa fa-inbox fa-2x d-block mb-2"></i>Sin transacciones en este período.
            </td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Penalidades -->
<div style="background:#fff;border:1px solid #dee2e6;border-radius:6px;margin-bottom:20px;overflow:hidden">
    <div style="padding:12px 16px;border-bottom:1px solid #dee2e6;background:#f8f9fa">
        <strong style="font-size:.9rem"><i class="fa fa-exclamation-triangle mr-1 text-danger"></i>Penalidades aplicadas (<?php echo count($pens); ?>)</strong>
    </div>
    <div class="table-responsive">
    <table class="table table-hover table-sm mb-0">
        <thead class="thead-light">
            <tr><th>Envío</th><th>Penalidad</th><th>Aplica a</th><th>Monto S/</th><th>Notas</th><th>Fecha</th></tr>
        </thead>
        <tbody>
        <?php if ($pens): foreach($pens as $p): ?>
            <tr>
                <td><a href="<?php echo esc_url(get_edit_post_link($p->shipment_id)); ?>"><?php echo esc_html($p->envio_titulo??'#'.$p->shipment_id); ?></a></td>
                <td><strong><?php echo esc_html($p->tipo_nombre); ?></strong></td>
                <td><?php echo esc_html($actores[$p->aplica_a]??$p->aplica_a); ?></td>
                <td class="font-weight-bold text-danger">− S/ <?php echo number_format(floatval($p->monto_aplicado),2); ?></td>
                <td class="small text-muted"><?php echo esc_html($p->notas??'—'); ?></td>
                <td class="small"><?php echo esc_html(date_i18n('d/m/Y',strtotime($p->fecha))); ?></td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="6" class="text-center text-muted py-4">
                <i class="fa fa-inbox fa-2x d-block mb-2"></i>Sin penalidades en este período.
            </td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
