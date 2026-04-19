<?php if ( ! defined('ABSPATH') ) exit; ?>

<div class="mb-3">
    <a href="<?php echo esc_url(wcfin_frontend_url(['wcfin_vista' => 'caja-drivers'])); ?>"
       class="btn btn-sm btn-outline-secondary">← Motorizados</a>
</div>
<h4><i class="fa fa-motorcycle mr-2"></i>🚗 Caja: <?php echo esc_html($driver->display_name); ?></h4>

<!-- Resumen de balance -->
<div style="display:flex;gap:16px;margin:20px 0;flex-wrap:wrap">
    <?php foreach ([
        ['Cobrado de destinatarios',          $balance,   '#2271b1', '#e8f4fd'],
        ['Liquidado a DHV',                   $liquidado, '#00a32a', '#d7f7c2'],
        ['Saldo pendiente (debe a DHV)',       $saldo,     $saldo > 0 ? '#d63638' : '#00a32a', $saldo > 0 ? '#fce9e9' : '#d7f7c2'],
    ] as [$titulo, $val, $color, $bg]): ?>
    <div style="flex:1;min-width:160px;background:<?php echo esc_attr($bg); ?>;border-left:4px solid <?php echo esc_attr($color); ?>;border-radius:6px;padding:14px 18px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:<?php echo esc_attr($color); ?>;margin-bottom:6px"><?php echo esc_html($titulo); ?></div>
        <div style="font-size:1.7rem;font-weight:700;color:<?php echo esc_attr($color); ?>">S/ <?php echo number_format($val, 2); ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Registrar liquidación -->
<?php if ($saldo > 0): ?>
<div class="card mb-4">
    <div class="card-header"><strong>💰 Registrar Liquidación (el motorizado entrega efectivo)</strong></div>
    <div class="card-body">
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('wcfin_liquidar_nonce'); ?>
        <input type="hidden" name="action"    value="wcfin_liquidar">
        <input type="hidden" name="driver_id" value="<?php echo intval($driver->ID); ?>">
        <input type="hidden" name="_wcfin_redirect" value="<?php echo esc_url(wcfin_frontend_url(['wcfin_vista' => 'caja-drivers', 'driver' => $driver->ID])); ?>">

        <div class="form-group">
            <label>Monto recibido S/ <span class="text-danger">*</span></label>
            <div class="input-group" style="max-width:300px">
                <input name="monto" type="number" step="0.01" min="0.01" max="<?php echo esc_attr($saldo); ?>"
                       class="form-control" required>
                <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                            onclick="this.closest('form').querySelector('[name=monto]').value='<?php echo esc_js(number_format($saldo,2,'.','')); ?>'">
                        Total S/ <?php echo number_format($saldo, 2); ?>
                    </button>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label>Método <span class="text-danger">*</span></label>
            <select name="metodo" class="form-control" style="max-width:250px">
                <option value="efectivo">Efectivo</option>
                <option value="yape_plin">YAPE / PLIN</option>
                <option value="transferencia">Transferencia bancaria</option>
                <option value="deposito">Depósito en cuenta</option>
            </select>
        </div>
        <div class="form-group">
            <label>Notas</label>
            <textarea name="notas" rows="2" class="form-control" style="max-width:500px"
                      placeholder="Observaciones de la liquidación..."></textarea>
        </div>
        <div class="form-group">
            <label>Comprobante</label><br>
            <input type="hidden" name="comprobante_url" id="liq_comp_url">
            <input type="file" id="liq_comp_file" accept="image/*,.pdf" style="display:none">
            <button type="button" class="btn btn-outline-secondary btn-sm"
                    onclick="document.getElementById('liq_comp_file').click()">📎 Subir comprobante</button>
            <span id="liq_comp_nombre" style="margin-left:8px;color:#888;font-size:12px"></span>
        </div>
        <button type="submit" class="btn btn-success">✅ Registrar liquidación</button>
    </form>
    <script>
    (function(){
        var fileInput = document.getElementById('liq_comp_file');
        fileInput.addEventListener('change', function(){
            var file = this.files[0];
            if (!file) return;
            document.getElementById('liq_comp_nombre').textContent = 'Subiendo...';
            var fd = new FormData();
            fd.append('action', 'wcfin_subir_comprobante');
            fd.append('nonce', '<?php echo esc_js(wp_create_nonce('wcfin_subir_comp')); ?>');
            fd.append('file', file);
            fetch('<?php echo esc_js(admin_url('admin-ajax.php')); ?>', {method:'POST', body:fd, credentials:'same-origin'})
                .then(r => r.json())
                .then(function(resp){
                    if (resp.success) {
                        document.getElementById('liq_comp_url').value = resp.data.url;
                        document.getElementById('liq_comp_nombre').textContent = '✓ ' + file.name;
                        document.getElementById('liq_comp_nombre').style.color = '#00a32a';
                    } else {
                        document.getElementById('liq_comp_nombre').textContent = '❌ Error: ' + (resp.data || 'desconocido');
                        document.getElementById('liq_comp_nombre').style.color = '#d63638';
                    }
                });
        });
    })();
    </script>
    </div>
</div>
<?php endif; ?>

<!-- Historial de envíos -->
<div class="card mb-4">
    <div class="card-header"><strong>📦 Envíos asignados con cobro</strong></div>
    <div class="table-responsive">
    <table class="table table-striped table-hover mb-0">
        <thead class="thead-light"><tr><th>Tracking</th><th>Condición</th><th>Monto cobrado</th><th>Fecha</th><th>Estado</th></tr></thead>
        <tbody>
        <?php if (empty($envios)): ?>
            <tr><td colspan="5" class="text-center text-muted py-3">Sin envíos con cobro registrado.</td></tr>
        <?php else: foreach ($envios as $e): ?>
            <tr>
                <td><strong><?php echo esc_html($e->tracking); ?></strong></td>
                <td><?php echo esc_html($e->condicion ?: '—'); ?></td>
                <td><strong>S/ <?php echo number_format(floatval($e->monto_driver), 2); ?></strong></td>
                <td><?php echo esc_html($e->fecha ? date_i18n('d/m/Y H:i', strtotime($e->fecha)) : '—'); ?></td>
                <td>
                    <?php $ec = $e->estado_pago; ?>
                    <span style="padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700;
                          background:<?php echo $ec === 'confirmado' ? '#d7f7c2' : ($ec === 'pendiente' ? '#fff3cd' : '#eee'); ?>;
                          color:<?php echo $ec === 'confirmado' ? '#135d3e' : ($ec === 'pendiente' ? '#856404' : '#666'); ?>">
                        <?php echo esc_html(ucfirst($ec ?: 'sin registro')); ?>
                    </span>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Historial de liquidaciones -->
<div class="card">
    <div class="card-header"><strong>💵 Historial de Liquidaciones</strong></div>
    <div class="table-responsive">
    <table class="table table-striped table-hover mb-0">
        <thead class="thead-light"><tr><th>Fecha</th><th>Monto</th><th>Método</th><th>Registrado por</th><th>Notas</th><th>Comprobante</th></tr></thead>
        <tbody>
        <?php if (empty($liquidaciones)): ?>
            <tr><td colspan="6" class="text-center text-muted py-3">Sin liquidaciones registradas.</td></tr>
        <?php else: foreach ($liquidaciones as $l): ?>
            <tr>
                <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($l->fecha))); ?></td>
                <td><strong style="color:#00a32a">S/ <?php echo number_format(floatval($l->monto), 2); ?></strong></td>
                <td><?php echo esc_html($l->metodo); ?></td>
                <td><?php echo esc_html($l->admin_nombre ?: 'Admin'); ?></td>
                <td><?php echo esc_html($l->notas ?: '—'); ?></td>
                <td>
                    <?php if ($l->comprobante_url): ?>
                        <a href="<?php echo esc_url($l->comprobante_url); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Ver 🖼</a>
                    <?php else: ?>—<?php endif; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>
