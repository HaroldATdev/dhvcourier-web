<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
<h1 class="wp-heading-inline">Métodos de Pago</h1>
<?php if ( ! ($edit_id || isset($_GET['editar'])) ): ?>
<a href="<?php echo esc_url(wcfin_url('wcfin-metodos',['editar'=>'nuevo'])); ?>" class="page-title-action">Añadir nuevo</a>
<?php endif; ?>
<hr class="wp-header-end">

<?php if ($edit_id || isset($_GET['editar'])): ?>
<!-- ═══ FORMULARIO ══════════════════════════════════════════════════════ -->
<h2><?php echo $metodo ? 'Editar método' : 'Nuevo método'; ?></h2>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('wcfin_metodo_nonce'); ?>
    <input type="hidden" name="action"    value="wcfin_guardar_metodo">
    <input type="hidden" name="metodo_id" value="<?php echo intval($metodo->id ?? 0); ?>">

    <table class="form-table" role="presentation">
        <tr>
            <th><label for="nombre">Nombre</label></th>
            <td><input id="nombre" name="nombre" type="text" class="regular-text" value="<?php echo esc_attr($metodo->nombre??''); ?>" required></td>
        </tr>
        <tr>
            <th><label for="slug">Identificador (slug)</label></th>
            <td><input id="slug" name="slug" type="text" class="regular-text" value="<?php echo esc_attr($metodo->slug??''); ?>" <?php echo $metodo?'readonly':'required'; ?>></td>
        </tr>
        <tr>
            <th><label for="actor_destino">¿A quién va el dinero?</label></th>
            <td>
                <select id="actor_destino" name="actor_destino" class="regular-text">
                    <?php foreach($actores as $v=>$l): ?>
                    <option value="<?php echo esc_attr($v); ?>" <?php selected($metodo->actor_destino??'empresa',$v); ?>><?php echo esc_html($l); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description">¿Quién recibe físicamente el dinero con este método?</p>
            </td>
        </tr>
        <tr>
            <th><label for="tipo">Tipo de medio</label></th>
            <td>
                <select id="tipo" name="tipo" class="regular-text">
                    <?php foreach($tipos_medio as $v=>$l): ?>
                    <option value="<?php echo esc_attr($v); ?>" <?php selected($metodo->tipo??'efectivo',$v); ?>><?php echo esc_html($l); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th>¿Requiere confirmación?</th>
            <td><label><input type="checkbox" name="requiere_conf" value="1" <?php checked($metodo->requiere_conf??0,1); ?>>
                Sí — el admin debe confirmar manualmente antes de liquidar</label></td>
        </tr>
    </table>

    <h3>Reglas de balance</h3>
    <p class="description">Define cómo afecta este método a cada cuenta contable según la condición del envío.<br>
    Si dejas "Condición" en <em>Todas</em>, la regla aplica sin importar la condición.</p>

    <table class="wp-list-table widefat fixed" id="wcfin-reglas-tabla">
        <thead><tr>
            <th>Cuenta afectada</th><th>Base de cálculo</th><th style="width:90px">Efecto</th>
            <th>Condición (opcional)</th><th>Descripción auto</th><th style="width:60px">Quitar</th>
        </tr></thead>
        <tbody id="wcfin-reglas-body">
        <?php foreach($reglas as $r): ?>
        <tr class="wcfin-regla-row">
            <td><select name="regla_cuenta[]" class="widefat">
                <?php foreach($cuentas as $v=>$l): ?><option value="<?php echo esc_attr($v); ?>" <?php selected($r->cuenta_afectada,$v); ?>><?php echo esc_html($l); ?></option><?php endforeach; ?>
            </select></td>
            <td><select name="regla_base[]" class="widefat">
                <?php foreach($vars_base as $v=>$l): ?><option value="<?php echo esc_attr($v); ?>" <?php selected($r->base_calculo,$v); ?>><?php echo esc_html($l); ?></option><?php endforeach; ?>
            </select></td>
            <td><select name="regla_signo[]">
                <option value="1"  <?php selected($r->signo,1); ?>>+ Suma</option>
                <option value="-1" <?php selected($r->signo,-1); ?>>− Resta</option>
            </select></td>
            <td><select name="regla_condicion[]">
                <option value="">Todas</option>
                <?php foreach($condiciones as $cond): ?><option value="<?php echo intval($cond->id); ?>" <?php selected($r->condicion_id,$cond->id); ?>><?php echo esc_html($cond->nombre); ?></option><?php endforeach; ?>
            </select></td>
            <td><input type="text" name="regla_descripcion[]" class="widefat" value="<?php echo esc_attr($r->descripcion_tpl); ?>" placeholder="ej: Ingreso S/ %s"></td>
            <td style="text-align:center"><button type="button" class="button button-small wcfin-del-regla">✕</button></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p><button type="button" class="button" id="wcfin-add-regla">+ Añadir regla</button></p>

    <p class="submit">
        <button type="submit" class="button button-primary">Guardar</button>
        <a href="<?php echo esc_url(wcfin_url('wcfin-metodos')); ?>" class="button">Cancelar</a>
    </p>
</form>
<script>
var wcfinCuentas = <?php echo wp_json_encode(array_map(fn($v,$k)=>['v'=>$k,'l'=>$v],$cuentas,array_keys($cuentas))); ?>;
var wcfinBases   = <?php echo wp_json_encode(array_map(fn($v,$k)=>['v'=>$k,'l'=>$v],$vars_base,array_keys($vars_base))); ?>;
var wcfinConds   = <?php echo wp_json_encode(array_map(fn($c)=>['v'=>$c->id,'l'=>$c->nombre],$condiciones)); ?>;
function wcfinSel(name,opts,blank){
    var s='<select name="'+name+'" class="widefat">';
    if(blank)s+='<option value="">'+blank+'</option>';
    opts.forEach(function(o){s+='<option value="'+o.v+'">'+o.l+'</option>';});
    return s+'</select>';
}
document.getElementById('wcfin-add-regla').onclick=function(){
    var tr=document.createElement('tr'); tr.className='wcfin-regla-row';
    tr.innerHTML='<td>'+wcfinSel('regla_cuenta[]',wcfinCuentas)+'</td>'
        +'<td>'+wcfinSel('regla_base[]',wcfinBases)+'</td>'
        +'<td><select name="regla_signo[]"><option value="1">+ Suma</option><option value="-1">− Resta</option></select></td>'
        +'<td>'+wcfinSel('regla_condicion[]',wcfinConds,'Todas')+'</td>'
        +'<td><input type="text" name="regla_descripcion[]" class="widefat" placeholder="ej: Ingreso S/ %s"></td>'
        +'<td style="text-align:center"><button type="button" class="button button-small wcfin-del-regla">✕</button></td>';
    document.getElementById('wcfin-reglas-body').appendChild(tr);
    tr.querySelector('.wcfin-del-regla').onclick=function(){tr.remove();};
};
document.querySelectorAll('.wcfin-del-regla').forEach(function(b){b.onclick=function(){b.closest('tr').remove();};});
document.getElementById('nombre').addEventListener('input',function(){
    var s=document.getElementById('slug'); if(!s.readOnly) s.value=this.value.toLowerCase().replace(/\s+/g,'_').replace(/[^a-z0-9_]/g,'');
});
</script>

<?php else: ?>
<!-- ═══ LISTA ════════════════════════════════════════════════════════════ -->
<table class="wp-list-table widefat fixed striped">
    <thead><tr><th>Nombre</th><th>Destino del dinero</th><th>Tipo</th><th>Conf. manual</th><th>Reglas</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php if($lista): foreach($lista as $m): ?>
        <tr>
            <td><strong><?php echo esc_html($m->nombre); ?></strong><br><code style="font-size:11px"><?php echo esc_html($m->slug); ?></code></td>
            <td><?php echo esc_html($actores[$m->actor_destino]??$m->actor_destino); ?></td>
            <td><?php echo esc_html($tipos_medio[$m->tipo]??$m->tipo); ?></td>
            <td><?php echo $m->requiere_conf ? '✓ Sí' : '—'; ?></td>
            <td><?php echo intval($m->num_reglas); ?> regla(s)</td>
            <td>
                <a href="<?php echo esc_url(wcfin_url('wcfin-metodos',['editar'=>$m->id])); ?>" class="button button-small">Editar</a>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline" onsubmit="return confirm('¿Eliminar?')">
                    <?php wp_nonce_field('wcfin_metodo_nonce'); ?>
                    <input type="hidden" name="action"    value="wcfin_eliminar_metodo">
                    <input type="hidden" name="metodo_id" value="<?php echo intval($m->id); ?>">
                    <button type="submit" class="button button-small button-link-delete">Eliminar</button>
                </form>
            </td>
        </tr>
    <?php endforeach; else: ?>
        <tr><td colspan="6" style="text-align:center;padding:20px;color:#888">No hay métodos configurados.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<?php endif; ?>
</div>
