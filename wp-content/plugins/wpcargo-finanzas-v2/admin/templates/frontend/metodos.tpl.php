<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="d-flex align-items-center mb-3 border-bottom pb-3">
    <h5 class="mb-0 mr-auto"><i class="fa fa-credit-card mr-2 text-primary"></i>Métodos de Pago</h5>
    <div>
        <a href="<?php echo esc_url($page_url); ?>" class="btn btn-outline-secondary btn-sm mr-1"><i class="fa fa-arrow-left mr-1"></i>Reportes</a>
        <a href="<?php echo esc_url(add_query_arg('wcfin_vista','condiciones',$page_url)); ?>" class="btn btn-outline-secondary btn-sm mr-1"><i class="fa fa-list-alt mr-1"></i>Condiciones</a>
        <a href="<?php echo esc_url(add_query_arg('wcfin_vista','penalidades',$page_url)); ?>" class="btn btn-outline-secondary btn-sm mr-1"><i class="fa fa-exclamation-triangle mr-1"></i>Penalidades</a>
        <?php if(!($edit_id||isset($_GET['editar']))): ?>
        <a href="<?php echo esc_url(add_query_arg(['wcfin_vista'=>'metodos','editar'=>'nuevo'],$page_url)); ?>" class="btn btn-primary btn-sm"><i class="fa fa-plus mr-1"></i>Nuevo método</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($edit_id || isset($_GET['editar'])): ?>
<div style="background:#fff;border:1px solid #dee2e6;border-radius:6px;overflow:hidden">
    <div style="padding:12px 16px;border-bottom:1px solid #dee2e6;background:#f8f9fa">
        <strong><?php echo $metodo ? 'Editar: '.esc_html($metodo->nombre) : 'Nuevo método de pago'; ?></strong>
    </div>
    <div style="padding:20px">
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('wcfin_metodo_nonce'); ?>
        <input type="hidden" name="action"    value="wcfin_guardar_metodo">
        <input type="hidden" name="metodo_id" value="<?php echo intval($metodo->id??0); ?>">

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="font-weight-bold">Nombre <span class="text-danger">*</span></label>
                    <input id="wcfin-nombre" name="nombre" type="text" class="form-control" value="<?php echo esc_attr($metodo->nombre??''); ?>" required>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="font-weight-bold">Slug</label>
                    <input id="wcfin-slug" name="slug" type="text" class="form-control" value="<?php echo esc_attr($metodo->slug??''); ?>" <?php echo $metodo?'readonly':'required'; ?>>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="font-weight-bold">¿A quién va el dinero? <span class="text-danger">*</span></label>
                    <select name="actor_destino" class="form-control browser-default">
                        <?php foreach($actores as $v=>$l): ?>
                        <option value="<?php echo esc_attr($v); ?>" <?php selected($metodo->actor_destino??'empresa',$v); ?>><?php echo esc_html($l); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="font-weight-bold">Tipo de medio</label>
                    <select name="tipo" class="form-control browser-default">
                        <?php foreach($tipos_medio as $v=>$l): ?>
                        <option value="<?php echo esc_attr($v); ?>" <?php selected($metodo->tipo??'efectivo',$v); ?>><?php echo esc_html($l); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-12">
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="wcfin-reqconf" name="requiere_conf" value="1" <?php checked($metodo->requiere_conf??0,1); ?>>
                        <label class="custom-control-label" for="wcfin-reqconf">
                            Requiere confirmación manual — el admin debe confirmar antes de liquidar
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <hr>
        <h6 class="font-weight-bold mb-1">Reglas de balance</h6>
        <p class="text-muted small mb-3">Define cómo afecta este método a cada cuenta contable. Si no eliges condición, la regla aplica a todas.</p>
        <div class="table-responsive">
        <table class="table table-sm table-bordered" id="wcfin-reglas-tabla">
            <thead class="thead-light">
                <tr><th>Cuenta afectada</th><th>Base de cálculo</th><th style="width:100px">Efecto</th><th>Condición</th><th>Descripción</th><th style="width:60px"></th></tr>
            </thead>
            <tbody id="wcfin-reglas-body">
            <?php foreach($reglas as $r): ?>
            <tr class="wcfin-regla-row">
                <td><select name="regla_cuenta[]" class="form-control form-control-sm browser-default">
                    <?php foreach($cuentas as $v=>$l): ?><option value="<?php echo esc_attr($v); ?>" <?php selected($r->cuenta_afectada,$v); ?>><?php echo esc_html($l); ?></option><?php endforeach; ?>
                </select></td>
                <td><select name="regla_base[]" class="form-control form-control-sm browser-default">
                    <?php foreach($vars_base as $v=>$l): ?><option value="<?php echo esc_attr($v); ?>" <?php selected($r->base_calculo,$v); ?>><?php echo esc_html($l); ?></option><?php endforeach; ?>
                </select></td>
                <td><select name="regla_signo[]" class="form-control form-control-sm browser-default">
                    <option value="1"  <?php selected($r->signo,1); ?>>+ Suma</option>
                    <option value="-1" <?php selected($r->signo,-1); ?>>− Resta</option>
                </select></td>
                <td><select name="regla_condicion[]" class="form-control form-control-sm browser-default">
                    <option value="">Todas</option>
                    <?php foreach($condiciones as $cond): ?><option value="<?php echo intval($cond->id); ?>" <?php selected($r->condicion_id,$cond->id); ?>><?php echo esc_html($cond->nombre); ?></option><?php endforeach; ?>
                </select></td>
                <td><input type="text" name="regla_descripcion[]" class="form-control form-control-sm" value="<?php echo esc_attr($r->descripcion_tpl); ?>" placeholder="ej: Ingreso S/ %s"></td>
                <td style="text-align:center;vertical-align:middle"><button type="button" class="btn btn-outline-danger btn-sm wcfin-del-regla"><i class="fa fa-times"></i></button></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="wcfin-add-regla">
            <i class="fa fa-plus mr-1"></i>Añadir regla
        </button>

        <div class="d-flex" style="gap:8px">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save mr-1"></i>Guardar</button>
            <a href="<?php echo esc_url(add_query_arg('wcfin_vista','metodos',$page_url)); ?>" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
    </div>
</div>
<script>
var wcfinCuentas=<?php echo wp_json_encode(array_map(fn($v,$k)=>['v'=>$k,'l'=>$v],$cuentas,array_keys($cuentas))); ?>;
var wcfinBases  =<?php echo wp_json_encode(array_map(fn($v,$k)=>['v'=>$k,'l'=>$v],$vars_base,array_keys($vars_base))); ?>;
var wcfinConds  =<?php echo wp_json_encode(array_map(fn($c)=>['v'=>$c->id,'l'=>$c->nombre],$condiciones)); ?>;
function wcfinSel(name,opts,blank){
    var s='<select name="'+name+'" class="form-control form-control-sm browser-default">';
    if(blank)s+='<option value="">'+blank+'</option>';
    opts.forEach(function(o){s+='<option value="'+o.v+'">'+o.l+'</option>';});
    return s+'</select>';
}
document.getElementById('wcfin-add-regla').onclick=function(){
    var tr=document.createElement('tr');tr.className='wcfin-regla-row';
    tr.innerHTML='<td>'+wcfinSel('regla_cuenta[]',wcfinCuentas)+'</td>'
        +'<td>'+wcfinSel('regla_base[]',wcfinBases)+'</td>'
        +'<td><select name="regla_signo[]" class="form-control form-control-sm browser-default"><option value="1">+ Suma</option><option value="-1">− Resta</option></select></td>'
        +'<td>'+wcfinSel('regla_condicion[]',wcfinConds,'Todas')+'</td>'
        +'<td><input type="text" name="regla_descripcion[]" class="form-control form-control-sm" placeholder="ej: Ingreso S/ %s"></td>'
        +'<td style="text-align:center;vertical-align:middle"><button type="button" class="btn btn-outline-danger btn-sm wcfin-del-regla"><i class="fa fa-times"></i></button></td>';
    document.getElementById('wcfin-reglas-body').appendChild(tr);
    tr.querySelector('.wcfin-del-regla').onclick=function(){tr.remove();};
};
document.querySelectorAll('.wcfin-del-regla').forEach(function(b){b.onclick=function(){b.closest('tr').remove();};});
document.getElementById('wcfin-nombre').addEventListener('input',function(){
    var s=document.getElementById('wcfin-slug');if(!s.readOnly)s.value=this.value.toLowerCase().replace(/\s+/g,'_').replace(/[^a-z0-9_]/g,'');
});
</script>

<?php else: ?>
<div style="background:#fff;border:1px solid #dee2e6;border-radius:6px;overflow:hidden">
    <div class="table-responsive">
    <table class="table table-hover table-sm mb-0">
        <thead class="thead-light">
            <tr><th>Nombre</th><th>Destino del dinero</th><th>Tipo</th><th>Conf. manual</th><th>Reglas</th><th style="width:110px">Acciones</th></tr>
        </thead>
        <tbody>
        <?php if($lista): foreach($lista as $m): ?>
            <tr>
                <td><strong><?php echo esc_html($m->nombre); ?></strong><br><code class="small"><?php echo esc_html($m->slug); ?></code></td>
                <td><?php echo esc_html($actores[$m->actor_destino]??$m->actor_destino); ?></td>
                <td><?php echo esc_html($tipos_medio[$m->tipo]??$m->tipo); ?></td>
                <td><?php echo $m->requiere_conf?'<span class="badge badge-warning">Sí</span>':'<span class="badge badge-light">No</span>'; ?></td>
                <td><?php echo intval($m->num_reglas); ?> regla(s)</td>
                <td>
                    <a href="<?php echo esc_url(add_query_arg(['wcfin_vista'=>'metodos','editar'=>$m->id],$page_url)); ?>" class="btn btn-outline-primary btn-sm mr-1"><i class="fa fa-pencil"></i></a>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline" onsubmit="return confirm('¿Eliminar?')">
                        <?php wp_nonce_field('wcfin_metodo_nonce'); ?>
                        <input type="hidden" name="action"    value="wcfin_eliminar_metodo">
                        <input type="hidden" name="metodo_id" value="<?php echo intval($m->id); ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fa fa-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="6" class="text-center text-muted py-4"><i class="fa fa-inbox fa-2x d-block mb-2"></i>No hay métodos configurados.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>
