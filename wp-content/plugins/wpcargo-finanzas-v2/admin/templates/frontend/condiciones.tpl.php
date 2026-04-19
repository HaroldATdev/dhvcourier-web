<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<!-- Barra superior con navegación -->
<div class="d-flex align-items-center mb-3 border-bottom pb-3">
    <h5 class="mb-0 mr-auto"><i class="fa fa-list-alt mr-2 text-primary"></i>Condiciones de Pago</h5>
    <div>
        <a href="<?php echo esc_url($page_url); ?>" class="btn btn-outline-secondary btn-sm mr-1">
            <i class="fa fa-arrow-left mr-1"></i>Reportes
        </a>
        <a href="<?php echo esc_url(add_query_arg('wcfin_vista','metodos',$page_url)); ?>" class="btn btn-outline-secondary btn-sm mr-1">
            <i class="fa fa-credit-card mr-1"></i>Métodos
        </a>
        <a href="<?php echo esc_url(add_query_arg('wcfin_vista','penalidades',$page_url)); ?>" class="btn btn-outline-secondary btn-sm mr-1">
            <i class="fa fa-exclamation-triangle mr-1"></i>Penalidades
        </a>
        <?php if (!($edit_id || isset($_GET['editar']))): ?>
        <a href="<?php echo esc_url(add_query_arg(['wcfin_vista'=>'condiciones','editar'=>'nuevo'],$page_url)); ?>" class="btn btn-primary btn-sm">
            <i class="fa fa-plus mr-1"></i>Nueva condición
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($edit_id || isset($_GET['editar'])): ?>
<!-- ═══ FORMULARIO ════════════════════════════════════════════ -->
<div style="background:#fff;border:1px solid #dee2e6;border-radius:6px;overflow:hidden">
    <div style="padding:12px 16px;border-bottom:1px solid #dee2e6;background:#f8f9fa">
        <strong><?php echo $condicion ? 'Editar condición: '.esc_html($condicion->nombre) : 'Nueva condición de pago'; ?></strong>
    </div>
    <div style="padding:20px">
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('wcfin_condicion_nonce'); ?>
        <input type="hidden" name="action"       value="wcfin_guardar_condicion">
        <input type="hidden" name="condicion_id" value="<?php echo intval($condicion->id ?? 0); ?>">
        <input type="hidden" name="wcfin_redirect_to" value="<?php echo esc_url(add_query_arg('wcfin_vista','condiciones',$page_url)); ?>">

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="font-weight-bold">Nombre <span class="text-danger">*</span></label>
                    <input id="wcfin-nombre" name="nombre" type="text" class="form-control" value="<?php echo esc_attr($condicion->nombre??''); ?>" required>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="font-weight-bold">Slug (identificador)</label>
                    <input id="wcfin-slug" name="slug" type="text" class="form-control" value="<?php echo esc_attr($condicion->slug??''); ?>" <?php echo $condicion?'readonly':'required'; ?>>
                    <?php if($condicion): ?><small class="text-muted">No se puede modificar.</small><?php endif; ?>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="font-weight-bold">¿A quién se cobra?</label>
                    <select name="cobrar_a" class="form-control browser-default">
                        <?php foreach($actores as $v=>$l): ?>
                        <option value="<?php echo esc_attr($v); ?>" <?php selected($condicion->cobrar_a??'remitente',$v); ?>><?php echo esc_html($l); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-12">
                <div class="form-group">
                    <label class="font-weight-bold">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="2"><?php echo esc_textarea($condicion->descripcion??''); ?></textarea>
                </div>
            </div>
        </div>

        <hr>
        <h6 class="font-weight-bold mb-1">Variables del monto</h6>
        <p class="text-muted small mb-3">Monto total = suma de todas estas variables. El operador las rellena en cada envío.</p>
        <div class="table-responsive">
        <table class="table table-sm table-bordered" id="wcfin-comp-tabla">
            <thead class="thead-light">
                <tr><th>Variable (slug)</th><th>Etiqueta visible</th><th style="width:120px;text-align:center">¿Obligatoria?</th><th style="width:80px"></th></tr>
            </thead>
            <tbody id="wcfin-comp-body">
            <?php $rows=$componentes?:[(object)['variable'=>'monto_servicio','label'=>'Costo del servicio','obligatorio'=>1]];
            foreach($rows as $i=>$c): ?>
                <tr class="wcfin-comp-row">
                    <td><input type="text" name="comp_variable[]" class="form-control form-control-sm" value="<?php echo esc_attr($c->variable); ?>" placeholder="ej: monto_producto"></td>
                    <td><input type="text" name="comp_label[]"    class="form-control form-control-sm" value="<?php echo esc_attr($c->label); ?>" placeholder="ej: Valor del producto"></td>
                    <td style="text-align:center;vertical-align:middle"><input type="checkbox" name="comp_obligatorio[<?php echo $i; ?>]" value="1" <?php checked($c->obligatorio,1); ?>></td>
                    <td><button type="button" class="btn btn-outline-danger btn-sm btn-block wcfin-del-comp">Quitar</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="wcfin-add-comp">
            <i class="fa fa-plus mr-1"></i>Añadir variable
        </button>

        <div class="d-flex" style="gap:8px">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save mr-1"></i>Guardar</button>
            <a href="<?php echo esc_url(add_query_arg('wcfin_vista','condiciones',$page_url)); ?>" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
    </div>
</div>

<script>
var wcfinI=document.querySelectorAll('.wcfin-comp-row').length;
document.getElementById('wcfin-add-comp').onclick=function(){
    var tr=document.createElement('tr');tr.className='wcfin-comp-row';
    tr.innerHTML='<td><input type="text" name="comp_variable[]" class="form-control form-control-sm" placeholder="ej: monto_extra"></td>'
        +'<td><input type="text" name="comp_label[]" class="form-control form-control-sm" placeholder="ej: Cargo adicional"></td>'
        +'<td style="text-align:center;vertical-align:middle"><input type="checkbox" name="comp_obligatorio['+wcfinI+']" value="1"></td>'
        +'<td><button type="button" class="btn btn-outline-danger btn-sm btn-block wcfin-del-comp">Quitar</button></td>';
    document.getElementById('wcfin-comp-body').appendChild(tr);
    tr.querySelector('.wcfin-del-comp').onclick=function(){tr.remove();};wcfinI++;
};
document.querySelectorAll('.wcfin-del-comp').forEach(function(b){b.onclick=function(){b.closest('tr').remove();};});
document.getElementById('wcfin-nombre').addEventListener('input',function(){
    var s=document.getElementById('wcfin-slug');if(!s.readOnly)s.value=this.value.toLowerCase().replace(/\s+/g,'_').replace(/[^a-z0-9_]/g,'');
});
</script>

<?php else: ?>
<!-- ═══ LISTA ════════════════════════════════════════════════ -->
<div style="background:#fff;border:1px solid #dee2e6;border-radius:6px;overflow:hidden">
    <div class="table-responsive">
    <table class="table table-hover table-sm mb-0">
        <thead class="thead-light"><tr><th>Nombre</th><th>Slug</th><th>Cobrar al</th><th>Variables</th><th>Activo</th><th style="width:130px">Acciones</th></tr></thead>
        <tbody>
        <?php if($lista): foreach($lista as $c): ?>
            <tr>
                <td><strong><?php echo esc_html($c->nombre); ?></strong></td>
                <td><code><?php echo esc_html($c->slug); ?></code></td>
                <td><?php echo esc_html($actores[$c->cobrar_a]??$c->cobrar_a); ?></td>
                <td><?php echo intval($c->num_comp); ?> campo(s)</td>
                <td><?php echo $c->activo?'<span class="badge badge-success">Sí</span>':'<span class="badge badge-secondary">No</span>'; ?></td>
                <td>
                    <a href="<?php echo esc_url(add_query_arg(['wcfin_vista'=>'condiciones','editar'=>$c->id],$page_url)); ?>" class="btn btn-outline-primary btn-sm mr-1">
                        <i class="fa fa-pencil"></i>
                    </a>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline" onsubmit="return confirm('¿Eliminar esta condición?')">
                        <?php wp_nonce_field('wcfin_condicion_nonce'); ?>
                        <input type="hidden" name="action"       value="wcfin_eliminar_condicion">
                        <input type="hidden" name="condicion_id" value="<?php echo intval($c->id); ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fa fa-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="6" class="text-center text-muted py-4">
                <i class="fa fa-inbox fa-2x d-block mb-2"></i>No hay condiciones configuradas.
            </td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>
