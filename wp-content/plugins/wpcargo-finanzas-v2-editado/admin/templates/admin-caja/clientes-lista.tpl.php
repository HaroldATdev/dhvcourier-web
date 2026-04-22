<?php if ( ! defined('ABSPATH') ) exit; ?>
<div class="wrap">
<h1><span class="dashicons dashicons-groups" style="vertical-align:middle;margin-right:8px"></span>Caja de Clientes</h1>
<hr class="wp-header-end">

<?php if (!empty($pendientes)): ?>
<div style="background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:12px 16px;margin:16px 0;display:flex;align-items:center;gap:12px">
    <span style="font-size:20px">⏳</span>
    <div>
        <strong><?php echo count($pendientes); ?> pago(s) de clientes pendientes de revisión.</strong>
        <span style="color:#856404"> Revísalos en el detalle de cada cliente.</span>
    </div>
</div>
<?php endif; ?>

<div class="postbox" style="margin-top:16px">
    <div class="inside" style="padding:0">
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>N° Envíos</th>
                <th style="text-align:right">DHV debe al cliente</th>
                <th style="text-align:right">Cliente debe a DHV</th>
                <th style="text-align:right">Saldo neto</th>
                <th style="width:90px">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if(empty($clientes)): ?>
            <tr><td colspan="6" style="text-align:center;padding:20px;color:#888">No hay clientes con balances pendientes.</td></tr>
        <?php else: foreach($clientes as $cl):
            $saldo = $cl['saldo_neto'];
            $saldo_label = $saldo > 0.01 ? 'Cliente debe' : ($saldo < -0.01 ? 'DHV debe' : 'Balanceado');
            $saldo_color = $saldo > 0.01 ? '#d63638' : ($saldo < -0.01 ? '#9c5700' : '#00a32a');
        ?>
            <tr>
                <td>
                    <strong><?php echo esc_html($cl['user']->display_name); ?></strong>
                    <br><small style="color:#888"><?php echo esc_html($cl['user']->user_email); ?></small>
                </td>
                <td><?php echo intval($cl['n_envios']); ?></td>
                <td style="text-align:right">
                    <?php if($cl['dhv_debe']>0): ?>
                    <strong style="color:#9c5700">S/ <?php echo number_format($cl['dhv_debe'],2); ?></strong>
                    <?php else: ?><span style="color:#aaa">—</span><?php endif; ?>
                </td>
                <td style="text-align:right">
                    <?php if($cl['cliente_debe']>0): ?>
                    <strong style="color:#d63638">S/ <?php echo number_format($cl['cliente_debe'],2); ?></strong>
                    <?php else: ?><span style="color:#aaa">—</span><?php endif; ?>
                </td>
                <td style="text-align:right">
                    <strong style="color:<?php echo esc_attr($saldo_color); ?>">
                        S/ <?php echo number_format(abs($saldo),2); ?>
                    </strong>
                    <span style="color:<?php echo esc_attr($saldo_color); ?>;font-size:11px;display:block"><?php echo esc_html($saldo_label); ?></span>
                </td>
                <td><a href="<?php echo esc_url(wcfin_url('wcfin-caja-clientes',['cliente'=>$cl['user']->ID])); ?>" class="button button-primary button-small">Detalle</a></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>
</div>
