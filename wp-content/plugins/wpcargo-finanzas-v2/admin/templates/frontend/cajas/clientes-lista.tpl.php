<?php if ( ! defined('ABSPATH') ) exit; ?>

<div class="mb-3">
    <a href="<?php echo esc_url(wcfin_frontend_url(['wcfin_vista' => 'cajas'])); ?>"
       class="btn btn-sm btn-outline-secondary">← Volver al Panel</a>
</div>

<?php if (!empty($pendientes)): ?>
<div class="alert alert-warning d-flex align-items-center mb-4">
    <span style="font-size:20px;margin-right:10px">⏳</span>
    <div>
        <strong><?php echo count($pendientes); ?> pago(s) de clientes pendientes de revisión.</strong>
        <span> Revísalos en el detalle de cada cliente.</span>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><strong><i class="fa fa-users mr-2"></i>Caja de Clientes</strong></div>
    <div class="table-responsive">
    <table class="table table-striped table-hover mb-0">
        <thead class="thead-light">
            <tr>
                <th>Cliente</th>
                <th>N° Envíos</th>
                <th class="text-right">DHV debe al cliente</th>
                <th class="text-right">Cliente debe a DHV</th>
                <th class="text-right">Saldo neto</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($clientes)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No hay clientes con balances pendientes.</td></tr>
        <?php else: foreach ($clientes as $cl):
            $saldo       = $cl['saldo_neto'];
            $saldo_label = $saldo > 0.01 ? 'Cliente debe' : ($saldo < -0.01 ? 'DHV debe' : 'Balanceado');
            $saldo_color = $saldo > 0.01 ? '#d63638' : ($saldo < -0.01 ? '#9c5700' : '#00a32a');
        ?>
            <tr>
                <td>
                    <strong><?php echo esc_html($cl['user']->display_name); ?></strong>
                    <br><small class="text-muted"><?php echo esc_html($cl['user']->user_email); ?></small>
                </td>
                <td><?php echo intval($cl['n_envios']); ?></td>
                <td class="text-right">
                    <?php if ($cl['dhv_debe'] > 0): ?>
                        <strong style="color:#9c5700">S/ <?php echo number_format($cl['dhv_debe'],2); ?></strong>
                    <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                </td>
                <td class="text-right">
                    <?php if ($cl['cliente_debe'] > 0): ?>
                        <strong style="color:#d63638">S/ <?php echo number_format($cl['cliente_debe'],2); ?></strong>
                    <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                </td>
                <td class="text-right">
                    <strong style="color:<?php echo esc_attr($saldo_color); ?>">
                        S/ <?php echo number_format(abs($saldo),2); ?>
                    </strong>
                    <span style="color:<?php echo esc_attr($saldo_color); ?>;font-size:11px;display:block"><?php echo esc_html($saldo_label); ?></span>
                </td>
                <td>
                    <a href="<?php echo esc_url(wcfin_frontend_url(['wcfin_vista' => 'caja-clientes', 'cliente' => $cl['user']->ID])); ?>"
                       class="btn btn-sm btn-primary">Detalle</a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>
