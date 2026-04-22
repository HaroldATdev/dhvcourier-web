<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
<h1 class="wp-heading-inline">Condiciones de Pago</h1>
<?php if ( ! ($edit_id || isset($_GET['editar'])) ): ?>
<a href="<?php echo esc_url(wcfin_url('wcfin-condiciones',['editar'=>'nuevo'])); ?>" class="page-title-action">Añadir nueva</a>
<?php endif; ?>
<hr class="wp-header-end">

<?php if ( $edit_id || isset($_GET['editar']) ): ?>
<!-- ═══ FORMULARIO ══════════════════════════════════════════════════════ -->
<h2><?php echo $condicion ? 'Editar condición' : 'Nueva condición'; ?></h2>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('wcfin_condicion_nonce'); ?>
    <input type="hidden" name="action"       value="wcfin_guardar_condicion">
    <input type="hidden" name="condicion_id" value="<?php echo intval($condicion->id ?? 0); ?>">

    <table class="form-table" role="presentation">
        <tr>
            <th><label for="nombre">Nombre</label></th>
            <td><input id="nombre" name="nombre" type="text" class="regular-text" value="<?php echo esc_attr($condicion->nombre ?? ''); ?>" required></td>
        </tr>
        <tr>
            <th><label for="slug">Identificador (slug)</label></th>
            <td>
                <input id="slug" name="slug" type="text" class="regular-text" value="<?php echo esc_attr($condicion->slug ?? ''); ?>" <?php echo $condicion ? 'readonly' : 'required'; ?>>
                <?php if ($condicion): ?><p class="description">No se puede modificar después de creado.</p><?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><label for="cobrar_a">¿A quién se cobra?</label></th>
            <td>
                <select id="cobrar_a" name="cobrar_a" class="regular-text">
                    <?php foreach($actores as $v => $l): ?>
                    <option value="<?php echo esc_attr($v); ?>" <?php selected($condicion->cobrar_a??'remitente',$v); ?>><?php echo esc_html($l); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="descripcion">Descripción</label></th>
            <td><textarea id="descripcion" name="descripcion" class="large-text" rows="2"><?php echo esc_textarea($condicion->descripcion ?? ''); ?></textarea></td>
        </tr>
    </table>

    <h3>Variables del monto</h3>
    <p class="description">El monto total = suma de todas estas variables. El operador rellenará estos campos en cada envío.</p>
    <table class="wp-list-table widefat fixed striped" id="wcfin-comp-tabla">
        <thead><tr><th>Variable (slug)</th><th>Etiqueta visible</th><th style="width:110px;text-align:center">¿Obligatoria?</th><th style="width:80px">Quitar</th></tr></thead>
        <tbody id="wcfin-comp-body">
        <?php $rows = $componentes ?: [(object)['variable'=>'monto_servicio','label'=>'Costo del servicio','obligatorio'=>1]];
        foreach($rows as $i=>$c): ?>
            <tr class="wcfin-comp-row">
                <td><input type="text" name="comp_variable[]" class="regular-text" value="<?php echo esc_attr($c->variable); ?>" placeholder="ej: monto_producto"></td>
                <td><input type="text" name="comp_label[]"    class="regular-text" value="<?php echo esc_attr($c->label); ?>"    placeholder="ej: Valor del producto"></td>
                <td style="text-align:center"><input type="checkbox" name="comp_obligatorio[<?php echo $i; ?>]" value="1" <?php checked($c->obligatorio,1); ?>></td>
                <td><button type="button" class="button button-small wcfin-del-comp">Quitar</button></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p><button type="button" class="button" id="wcfin-add-comp">+ Añadir variable</button></p>

    <p class="submit">
        <button type="submit" class="button button-primary">Guardar</button>
        <a href="<?php echo esc_url(wcfin_url('wcfin-condiciones')); ?>" class="button">Cancelar</a>
    </p>
</form>
<script>
var wcfinI = document.querySelectorAll('.wcfin-comp-row').length;
document.getElementById('wcfin-add-comp').onclick = function(){
    var tr = document.createElement('tr'); tr.className='wcfin-comp-row';
    tr.innerHTML='<td><input type="text" name="comp_variable[]" class="regular-text" placeholder="ej: monto_extra"></td>'
        +'<td><input type="text" name="comp_label[]" class="regular-text" placeholder="ej: Cargo adicional"></td>'
        +'<td style="text-align:center"><input type="checkbox" name="comp_obligatorio['+wcfinI+']" value="1"></td>'
        +'<td><button type="button" class="button button-small wcfin-del-comp">Quitar</button></td>';
    document.getElementById('wcfin-comp-body').appendChild(tr);
    tr.querySelector('.wcfin-del-comp').onclick=function(){tr.remove();};
    wcfinI++;
};
document.querySelectorAll('.wcfin-del-comp').forEach(function(b){b.onclick=function(){b.closest('tr').remove();};});
document.getElementById('nombre').addEventListener('input',function(){
    var s=document.getElementById('slug'); if(!s.readOnly) s.value=this.value.toLowerCase().replace(/\s+/g,'_').replace(/[^a-z0-9_]/g,'');
});
</script>

<?php else: ?>
<!-- ═══ LISTA ════════════════════════════════════════════════════════════ -->
<table class="wp-list-table widefat fixed striped">
    <thead><tr><th>Nombre</th><th>Slug</th><th>Cobrar al</th><th>Variables</th><th>Activo</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php if($lista): foreach($lista as $c): ?>
        <tr>
            <td><strong><?php echo esc_html($c->nombre); ?></strong></td>
            <td><code><?php echo esc_html($c->slug); ?></code></td>
            <td><?php echo esc_html($actores[$c->cobrar_a]??$c->cobrar_a); ?></td>
            <td><?php echo intval($c->num_comp); ?> campo(s)</td>
            <td><?php echo $c->activo ? '<span style="color:#00a32a">✓</span>' : '<span style="color:#d63638">✗</span>'; ?></td>
            <td>
                <a href="<?php echo esc_url(wcfin_url('wcfin-condiciones',['editar'=>$c->id])); ?>" class="button button-small">Editar</a>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline" onsubmit="return confirm('¿Eliminar?')">
                    <?php wp_nonce_field('wcfin_condicion_nonce'); ?>
                    <input type="hidden" name="action"       value="wcfin_eliminar_condicion">
                    <input type="hidden" name="condicion_id" value="<?php echo intval($c->id); ?>">
                    <button type="submit" class="button button-small button-link-delete">Eliminar</button>
                </form>
            </td>
        </tr>
    <?php endforeach; else: ?>
        <tr><td colspan="6" style="text-align:center;padding:20px;color:#888">No hay condiciones configuradas.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<?php endif; ?>
</div>
