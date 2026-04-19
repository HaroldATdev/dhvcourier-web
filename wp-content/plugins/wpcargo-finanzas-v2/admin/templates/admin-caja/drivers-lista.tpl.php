<?php if ( ! defined('ABSPATH') ) exit; ?>
<div class="wrap">
<h1><span class="dashicons dashicons-car" style="vertical-align:middle;margin-right:8px"></span>Caja de Motorizados</h1>
<hr class="wp-header-end">
<div class="postbox" style="margin-top:16px">
    <div class="inside" style="padding:0">
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Motorizado</th>
                <th>N° Envíos</th>
                <th style="text-align:right">Balance bruto</th>
                <th style="text-align:right">Liquidado a DHV</th>
                <th style="text-align:right">Saldo pendiente</th>
                <th style="width:90px">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if(empty($drivers)): ?>
            <tr><td colspan="6" style="text-align:center;padding:20px;color:#888">No hay motorizados con movimientos financieros aún.</td></tr>
        <?php else: foreach($drivers as $d):
            $saldo_class = $d['saldo'] > 0 ? '#d63638' : '#00a32a';
        ?>
            <tr>
                <td>
                    <strong><?php echo esc_html($d['user']->display_name); ?></strong>
                    <br><small style="color:#888"><?php echo esc_html($d['user']->user_email); ?></small>
                </td>
                <td><?php echo intval($d['n_envios']); ?></td>
                <td style="text-align:right">S/ <?php echo number_format($d['balance'],2); ?></td>
                <td style="text-align:right;color:#00a32a">S/ <?php echo number_format($d['liquidado'],2); ?></td>
                <td style="text-align:right">
                    <strong style="color:<?php echo esc_attr($saldo_class); ?>;font-size:1.05em">
                        S/ <?php echo number_format(abs($d['saldo']),2); ?>
                    </strong>
                    <span style="color:<?php echo esc_attr($saldo_class); ?>;font-size:11px;display:block">
                        <?php echo $d['saldo'] > 0 ? '↑ Debe a DHV' : '✓ Al día'; ?>
                    </span>
                </td>
                <td>
                    <a href="<?php echo esc_url(wcfin_url('wcfin-caja-drivers',['driver'=>$d['user']->ID])); ?>" class="button button-primary button-small">Detalle</a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>
</div>
