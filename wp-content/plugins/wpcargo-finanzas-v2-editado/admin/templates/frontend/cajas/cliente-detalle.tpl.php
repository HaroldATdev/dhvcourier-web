<?php if ( ! defined('ABSPATH') ) exit;
$metodos_pago = ['transferencia'=>'Transferencia Bancaria','yape_plin'=>'YAPE / PLIN','efectivo'=>'Efectivo','deposito'=>'Depósito en cuenta'];
?>

<div class="mb-3">
    <a href="<?php echo esc_url(wcfin_frontend_url(['wcfin_vista' => 'caja-clientes'])); ?>"
       class="btn btn-sm btn-outline-secondary">← Clientes</a>
</div>
<h4><i class="fa fa-user mr-2"></i>👤 Caja: <?php echo esc_html($cliente->display_name); ?>
    <small class="text-muted"> — <?php echo esc_html($cliente->user_email); ?></small>
</h4>

<!-- Resumen balances -->
<div style="display:flex;gap:16px;margin:20px 0;flex-wrap:wrap">
    <div style="flex:1;min-width:160px;background:<?php echo $dhv_debe>0?'#fce0a8':'#f0f0f0'; ?>;border-left:4px solid <?php echo $dhv_debe>0?'#9c5700':'#ccc'; ?>;border-radius:6px;padding:14px 18px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:<?php echo $dhv_debe>0?'#9c5700':'#666'; ?>;margin-bottom:6px">DHV debe → <?php echo esc_html($cliente->display_name); ?></div>
        <div style="font-size:1.7rem;font-weight:700;color:<?php echo $dhv_debe>0?'#9c5700':'#666'; ?>">S/ <?php echo number_format($dhv_debe,2); ?></div>
        <div style="font-size:11px;color:#888;margin-top:4px"><?php echo $dhv_debe>0?'⚠ Monto que DHV le debe al cliente':'✓ Sin deuda de DHV'; ?></div>
    </div>
    <div style="flex:1;min-width:160px;background:<?php echo $cliente_debe>0?'#fce9e9':'#d7f7c2'; ?>;border-left:4px solid <?php echo $cliente_debe>0?'#d63638':'#00a32a'; ?>;border-radius:6px;padding:14px 18px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:<?php echo $cliente_debe>0?'#d63638':'#00a32a'; ?>;margin-bottom:6px"><?php echo esc_html($cliente->display_name); ?> → debe a DHV</div>
        <div style="font-size:1.7rem;font-weight:700;color:<?php echo $cliente_debe>0?'#d63638':'#00a32a'; ?>">S/ <?php echo number_format($cliente_debe,2); ?></div>
        <div style="font-size:11px;color:#888;margin-top:4px"><?php echo $cliente_debe>0?'⚠ Deuda pendiente del cliente':'✓ Sin deuda del cliente'; ?></div>
    </div>
</div>

<!-- Acción: DHV paga al cliente -->
<?php if ($dhv_debe > 0): ?>
<div class="card mb-4" style="border-left:4px solid #9c5700">
    <div class="card-header"><strong>📤 Registrar pago DHV → <?php echo esc_html($cliente->display_name); ?></strong></div>
    <div class="card-body">
        <p style="color:#9c5700"><strong>DHV le debe S/ <?php echo number_format($dhv_debe,2); ?> a este cliente.</strong>
        Sube el comprobante del pago para que el cliente pueda verlo en su panel.</p>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('wcfin_pago_dhv_nonce'); ?>
        <input type="hidden" name="action"  value="wcfin_pago_dhv_cliente">
        <input type="hidden" name="user_id" value="<?php echo intval($cliente->ID); ?>">
        <input type="hidden" name="_wcfin_redirect" value="<?php echo esc_url(wcfin_frontend_url(['wcfin_vista' => 'caja-clientes', 'cliente' => $cliente->ID])); ?>">

        <div class="form-group">
            <label>Monto S/ <span class="text-danger">*</span></label>
            <input name="monto" type="number" step="0.01" min="0.01" max="<?php echo esc_attr($dhv_debe); ?>"
                   class="form-control" style="max-width:200px"
                   value="<?php echo esc_attr(number_format($dhv_debe,2,'.','')); ?>" required>
        </div>
        <div class="form-group">
            <label>Método <span class="text-danger">*</span></label>
            <select name="metodo" class="form-control" style="max-width:250px">
                <?php foreach ($metodos_pago as $k => $v): ?>
                <option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($v); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Referencia / N° operación</label>
            <input name="referencia" type="text" class="form-control" style="max-width:350px" placeholder="N° de operación, CCI destino...">
        </div>
        <div class="form-group">
            <label>Comprobante <span class="text-danger">*</span></label><br>
            <input type="hidden" name="comprobante_url" id="pd_comp_url">
            <input type="file" id="pd_comp_file" accept="image/*,.pdf" style="display:none">
            <button type="button" class="btn btn-outline-secondary btn-sm"
                    onclick="document.getElementById('pd_comp_file').click()">📎 Subir comprobante</button>
            <span id="pd_comp_nombre" style="margin-left:8px;color:#888;font-size:12px">Obligatorio</span>
        </div>
        <div class="form-group">
            <label>Notas</label>
            <textarea name="notas" rows="2" class="form-control" style="max-width:500px"></textarea>
        </div>
        <button type="submit" class="btn btn-warning" id="pd_submit" disabled>📤 Registrar pago DHV → Cliente</button>
    </form>
    <script>
    (function(){
        var fi = document.getElementById('pd_comp_file');
        fi.addEventListener('change', function(){
            var file = this.files[0]; if (!file) return;
            document.getElementById('pd_comp_nombre').textContent = 'Subiendo...';
            var fd = new FormData();
            fd.append('action', 'wcfin_subir_comprobante');
            fd.append('nonce', '<?php echo esc_js(wp_create_nonce('wcfin_subir_comp')); ?>');
            fd.append('file', file);
            fetch('<?php echo esc_js(admin_url('admin-ajax.php')); ?>', {method:'POST', body:fd, credentials:'same-origin'})
                .then(r => r.json())
                .then(function(resp){
                    if (resp.success) {
                        document.getElementById('pd_comp_url').value = resp.data.url;
                        document.getElementById('pd_comp_nombre').textContent = '✓ ' + file.name;
                        document.getElementById('pd_comp_nombre').style.color = '#00a32a';
                        document.getElementById('pd_submit').disabled = false;
                    }
                });
        });
    })();
    </script>
    </div>
</div>
<?php endif; ?>

<!-- Pagos pendientes de revisión -->
<?php $pendientes_rev = array_filter($pagos, fn($p) => $p->estado === 'pendiente' && $p->direccion === 'cliente_a_dhv');
if (!empty($pendientes_rev)): ?>
<div class="card mb-4" style="border-left:4px solid #2271b1">
    <div class="card-header"><strong>⏳ Pagos del cliente pendientes de revisión</strong></div>
    <div class="table-responsive">
    <table class="table table-striped mb-0">
        <thead class="thead-light"><tr><th>Fecha</th><th>Monto</th><th>Método</th><th>Referencia</th><th>Comprobante</th><th>Notas</th><th>Acción</th></tr></thead>
        <tbody>
        <?php foreach ($pendientes_rev as $p): ?>
        <tr style="background:#e8f4fd">
            <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($p->fecha_envio))); ?></td>
            <td><strong style="color:#2271b1">S/ <?php echo number_format(floatval($p->monto), 2); ?></strong></td>
            <td><?php echo esc_html($p->metodo); ?></td>
            <td><?php echo esc_html($p->referencia ?: '—'); ?></td>
            <td><?php if ($p->comprobante_url): ?><a href="<?php echo esc_url($p->comprobante_url); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Ver 🖼</a><?php else: ?>—<?php endif; ?></td>
            <td><?php echo esc_html($p->notas_emisor ?: '—'); ?></td>
            <td>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block">
                    <?php wp_nonce_field('wcfin_revisar_nonce'); ?>
                    <input type="hidden" name="action"  value="wcfin_revisar_pago">
                    <input type="hidden" name="pago_id" value="<?php echo intval($p->id); ?>">
                    <input type="hidden" name="user_id" value="<?php echo intval($p->user_id); ?>">
                    <input type="hidden" name="estado"  value="aprobado">
                    <input type="hidden" name="notas_admin" value="">
                    <input type="hidden" name="_wcfin_redirect" value="<?php echo esc_url(wcfin_frontend_url(['wcfin_vista' => 'caja-clientes', 'cliente' => $cliente->ID])); ?>">
                    <button type="submit" class="btn btn-sm btn-success">✅ Aprobar</button>
                </form>
                <button type="button" class="btn btn-sm btn-outline-danger ml-1"
                    onclick="wcfinRechazar(<?php echo intval($p->id); ?>, <?php echo intval($cliente->ID); ?>)">✗ Rechazar</button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<script>
function wcfinRechazar(pagoId, userId) {
    var motivo = prompt('Motivo del rechazo (opcional):', '');
    if (motivo === null) return;
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?php echo esc_js(admin_url('admin-post.php')); ?>';
    var campos = {
        action: 'wcfin_revisar_pago', pago_id: pagoId, user_id: userId,
        estado: 'rechazado', notas_admin: motivo,
        _wpnonce: '<?php echo esc_js(wp_create_nonce('wcfin_revisar_nonce')); ?>',
        _wcfin_redirect: '<?php echo esc_js(wcfin_frontend_url(['wcfin_vista' => 'caja-clientes', 'cliente' => $cliente->ID])); ?>'
    };
    Object.keys(campos).forEach(function(k){ var i=document.createElement('input'); i.type='hidden'; i.name=k; i.value=campos[k]; form.appendChild(i); });
    document.body.appendChild(form); form.submit();
}
</script>
<?php endif; ?>

<!-- Envíos con balance pendiente -->
<div class="card mb-4">
    <div class="card-header"><strong>📦 Envíos con balance pendiente</strong></div>
    <div class="table-responsive">
    <table class="table table-striped mb-0">
        <thead class="thead-light"><tr><th>Tracking</th><th>Condición</th><th class="text-right">Total envío</th><th class="text-right">DHV debe</th><th class="text-right">Cliente debe</th><th>Fecha</th></tr></thead>
        <tbody>
        <?php if (empty($envios)): ?>
            <tr><td colspan="6" class="text-center text-muted py-3">Sin envíos con balance pendiente.</td></tr>
        <?php else: foreach ($envios as $e): ?>
            <tr>
                <td><strong><?php echo esc_html($e->tracking); ?></strong></td>
                <td>
                    <span style="padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700;
                          background:<?php echo $e->cobrar_a==='destinatario'?'#e8f4fd':'#f0fff4'; ?>;
                          color:<?php echo $e->cobrar_a==='destinatario'?'#1a6891':'#135d3e'; ?>">
                        <?php echo esc_html($e->condicion ?: '—'); ?>
                    </span>
                </td>
                <td class="text-right">S/ <?php echo number_format(floatval($e->monto_total),2); ?></td>
                <td class="text-right">
                    <?php if (floatval($e->dhv_debe)>0): ?>
                        <strong style="color:#9c5700">S/ <?php echo number_format(floatval($e->dhv_debe),2); ?></strong>
                    <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                </td>
                <td class="text-right">
                    <?php if (floatval($e->cliente_debe)>0): ?>
                        <strong style="color:#d63638">S/ <?php echo number_format(floatval($e->cliente_debe),2); ?></strong>
                    <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                </td>
                <td><?php echo esc_html($e->fecha ? date_i18n('d/m/Y', strtotime($e->fecha)) : '—'); ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Historial completo de pagos -->
<div class="card">
    <div class="card-header"><strong>📋 Historial de Pagos</strong></div>
    <div class="table-responsive">
    <table class="table table-striped mb-0">
        <thead class="thead-light"><tr><th>Fecha</th><th>Dirección</th><th>Monto</th><th>Método</th><th>Ref.</th><th>Comprobante</th><th>Estado</th><th>Notas</th></tr></thead>
        <tbody>
        <?php if (empty($pagos)): ?>
            <tr><td colspan="8" class="text-center text-muted py-3">Sin pagos registrados.</td></tr>
        <?php else: foreach ($pagos as $p):
            $dir_label = $p->direccion === 'dhv_a_cliente' ? '📤 DHV → Cliente' : '📥 Cliente → DHV';
            $dir_color = $p->direccion === 'dhv_a_cliente' ? '#9c5700' : '#2271b1';
            $est_bg    = $p->estado === 'aprobado' ? '#d7f7c2' : ($p->estado === 'rechazado' ? '#fce9e9' : '#fff3cd');
            $est_color = $p->estado === 'aprobado' ? '#135d3e' : ($p->estado === 'rechazado' ? '#8a1a1a' : '#856404');
        ?>
            <tr>
                <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($p->fecha_envio))); ?></td>
                <td><strong style="color:<?php echo esc_attr($dir_color); ?>"><?php echo esc_html($dir_label); ?></strong></td>
                <td><strong>S/ <?php echo number_format(floatval($p->monto),2); ?></strong></td>
                <td><?php echo esc_html($p->metodo); ?></td>
                <td><?php echo esc_html($p->referencia ?: '—'); ?></td>
                <td><?php if ($p->comprobante_url): ?><a href="<?php echo esc_url($p->comprobante_url); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Ver 🖼</a><?php else: ?>—<?php endif; ?></td>
                <td>
                    <span style="padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700;background:<?php echo esc_attr($est_bg); ?>;color:<?php echo esc_attr($est_color); ?>">
                        <?php echo esc_html(ucfirst($p->estado)); ?>
                    </span>
                </td>
                <td><small><?php echo esc_html($p->notas_emisor ?: ''); ?><?php echo $p->notas_admin ? ' | Admin: '.esc_html($p->notas_admin) : ''; ?></small></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>
