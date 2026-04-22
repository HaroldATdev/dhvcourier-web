<?php if ( ! defined('ABSPATH') ) exit; ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong><i class="fa fa-motorcycle mr-2"></i>Caja de Motorizados</strong>
        <a href="<?php echo esc_url(wcfin_frontend_url(['wcfin_vista' => 'cajas'])); ?>" class="btn btn-sm btn-outline-secondary">← Volver</a>
    </div>
    <div class="table-responsive">
    <table class="table table-striped table-hover mb-0">
        <thead class="thead-light">
            <tr>
                <th>Motorizado</th>
                <th>N° Envíos</th>
                <th class="text-right">Balance bruto</th>
                <th class="text-right">Liquidado a DHV</th>
                <th class="text-right">Saldo pendiente</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($drivers)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No hay motorizados con movimientos financieros aún.</td></tr>
        <?php else: foreach ($drivers as $d):
            $saldo_color = $d['saldo'] > 0 ? '#d63638' : '#00a32a';
        ?>
            <tr>
                <td>
                    <strong><?php echo esc_html($d['user']->display_name); ?></strong>
                    <br><small class="text-muted"><?php echo esc_html($d['user']->user_email); ?></small>
                </td>
                <td><?php echo intval($d['n_envios']); ?></td>
                <td class="text-right">S/ <?php echo number_format($d['balance'], 2); ?></td>
                <td class="text-right text-success">S/ <?php echo number_format($d['liquidado'], 2); ?></td>
                <td class="text-right">
                    <strong style="color:<?php echo esc_attr($saldo_color); ?>;font-size:1.05em">
                        S/ <?php echo number_format(abs($d['saldo']), 2); ?>
                    </strong>
                    <span style="color:<?php echo esc_attr($saldo_color); ?>;font-size:11px;display:block">
                        <?php echo $d['saldo'] > 0 ? '↑ Debe a DHV' : '✓ Al día'; ?>
                    </span>
                </td>
                <td>
                    <a href="<?php echo esc_url(wcfin_frontend_url(['wcfin_vista' => 'caja-drivers', 'driver' => $d['user']->ID])); ?>"
                       class="btn btn-sm btn-primary">Detalle</a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>
