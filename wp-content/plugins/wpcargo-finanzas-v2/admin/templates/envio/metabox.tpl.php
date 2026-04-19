<?php if ( ! defined( 'ABSPATH' ) ) exit;
$bloqueado = $trans && $trans->estado === 'confirmado';
?>

<?php if ($trans): ?>
<div class="notice notice-<?php echo $trans->estado==='confirmado'?'success':'warning'; ?> inline" style="margin:8px 0">
    <p>
        <strong>Estado:</strong>
        <?php echo $trans->estado==='confirmado' ? '✅ Pago confirmado' : '⏳ Pendiente de confirmación'; ?>
        — <strong><?php echo esc_html($trans->metodo_nombre); ?></strong>
        — Total: <strong>S/ <?php echo number_format(floatval($trans->monto_total),2); ?></strong>
        <?php if ($trans->estado==='pendiente'): ?>
        <a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=wcfin_confirmar_pago&trans_id={$trans->id}&shipment_id={$post->ID}"),'wcfin_confirmar_'.$trans->id)); ?>"
           class="button button-small" style="margin-left:8px">Confirmar pago</a>
        <?php endif; ?>
    </p>
</div>
<?php endif; ?>

<?php wp_nonce_field('wcfin_pago_'.$post->ID,'wcfin_nonce'); ?>

<table class="form-table" role="presentation" style="margin-top:0">
    <tr>
        <th scope="row" style="width:130px"><label for="wcfin_condicion_id">Condición de pago</label></th>
        <td>
            <select id="wcfin_condicion_id" name="wcfin_condicion_id" class="regular-text" <?php echo $bloqueado?'disabled':''; ?>>
                <option value="">— Seleccionar —</option>
                <?php foreach($condiciones as $c): ?>
                <option value="<?php echo intval($c->id); ?>"
                        data-comps="<?php echo esc_attr(wp_json_encode($comp_map[$c->id]??[])); ?>"
                        <?php selected($trans->condicion_id??0,$c->id); ?>>
                    <?php echo esc_html($c->nombre); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="wcfin_metodo_id">Método de pago</label></th>
        <td>
            <select id="wcfin_metodo_id" name="wcfin_metodo_id" class="regular-text" <?php echo $bloqueado?'disabled':''; ?>>
                <option value="">— Seleccionar —</option>
                <?php foreach($metodos as $m): ?>
                <option value="<?php echo intval($m->id); ?>"
                        data-actor="<?php echo esc_attr($m->actor_destino); ?>"
                        data-conf="<?php echo intval($m->requiere_conf); ?>"
                        <?php selected($trans->metodo_id??0,$m->id); ?>>
                    <?php echo esc_html($m->nombre); ?>
                    (→ <?php echo esc_html($actores[$m->actor_destino]??$m->actor_destino); ?>)
                </option>
                <?php endforeach; ?>
            </select>
            <span id="wcfin-metodo-hint" class="description" style="display:block;margin-top:4px"></span>
        </td>
    </tr>
</table>

<!-- Variables del monto — dinámicas -->
<div id="wcfin-vars-wrap">
<p id="wcfin-vars-ph" class="description" style="<?php echo ($trans&&$vars_ex)?'display:none':''; ?>">
    Selecciona una condición de pago para ver los campos del monto.
</p>
<table class="form-table" role="presentation" id="wcfin-vars-tabla" style="<?php echo ($trans&&$vars_ex)?'':'display:none'; ?>margin-top:0">
<?php if ($trans && $vars_ex):
    foreach($comp_map[$trans->condicion_id]??[] as $comp): ?>
    <tr>
        <th scope="row" style="width:130px"><label for="wcfin_var_<?php echo esc_attr($comp['var']); ?>"><?php echo esc_html($comp['label']); ?></label></th>
        <td>
            <input id="wcfin_var_<?php echo esc_attr($comp['var']); ?>"
                   name="wcfin_var_<?php echo esc_attr($comp['var']); ?>"
                   type="number" step="0.01" min="0" class="small-text"
                   value="<?php echo esc_attr($vars_ex[$comp['var']]??'0'); ?>"
                   <?php echo $comp['req']?'required':''; ?>
                   <?php echo $bloqueado?'disabled':''; ?>
                   onchange="wcfinRecalc()"> S/
            <?php if(!$comp['req']): ?><span class="description">(opcional)</span><?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>
    <tr>
        <th scope="row"><strong>Total estimado</strong></th>
        <td><strong id="wcfin-total" style="font-size:1.1em">S/ <?php echo number_format(floatval($trans->monto_total??0),2); ?></strong></td>
    </tr>
<?php endif; ?>
</table>
</div>

<table class="form-table" role="presentation" style="margin-top:0">
    <tr>
        <th scope="row"><label for="wcfin_notas">Notas</label></th>
        <td><textarea id="wcfin_notas" name="wcfin_notas" rows="2" class="large-text" <?php echo $bloqueado?'disabled':''; ?>><?php echo esc_textarea($trans->notas??''); ?></textarea></td>
    </tr>
</table>

<!-- Penalidades -->
<?php if ($penalidades && !$bloqueado): ?>
<hr>
<p><strong>Aplicar penalidades a este envío</strong></p>
<table class="wp-list-table widefat striped">
    <thead><tr><th style="width:34px">Aplic.</th><th>Penalidad</th><th>Aplica a</th><th style="width:100px">Monto S/</th><th>Nota del caso</th></tr></thead>
    <tbody>
    <?php foreach($penalidades as $p): ?>
    <tr>
        <td style="text-align:center"><input type="checkbox" name="wcfin_pen[<?php echo intval($p->id); ?>][aplicar]" value="1"></td>
        <td>
            <strong><?php echo esc_html($p->nombre); ?></strong>
            <?php if($p->descripcion): ?><br><small><?php echo esc_html($p->descripcion); ?></small><?php endif; ?>
        </td>
        <td><?php echo esc_html($actores[$p->aplica_a]??$p->aplica_a); ?></td>
        <td><input type="number" step="0.01" min="0" class="small-text" name="wcfin_pen[<?php echo intval($p->id); ?>][monto]" value="<?php echo esc_attr($p->monto_default); ?>"> <?php echo $p->tipo_monto==='porcentaje'?'%':'S/'; ?></td>
        <td><input type="text" class="regular-text" name="wcfin_pen[<?php echo intval($p->id); ?>][nota]" placeholder="Descripción del caso específico"></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- Movimientos registrados -->
<?php if ($movimientos): ?>
<hr>
<p><strong>Movimientos contables de este envío</strong></p>
<table class="wp-list-table widefat striped">
    <thead><tr><th>Cuenta</th><th>Monto</th><th>Tipo</th><th>Descripción</th><th>Fecha</th></tr></thead>
    <tbody>
    <?php foreach($movimientos as $mov): ?>
    <tr>
        <td><?php echo esc_html($cuentas[$mov->cuenta]??$mov->cuenta); ?></td>
        <td><strong style="color:<?php echo $mov->signo>0?'#00a32a':'#d63638'; ?>">
            <?php echo $mov->signo>0?'+':'−'; ?> S/ <?php echo number_format(abs(floatval($mov->monto)),2); ?>
        </strong></td>
        <td><?php echo esc_html(ucfirst($mov->tipo)); ?></td>
        <td><?php echo esc_html($mov->descripcion); ?></td>
        <td><?php echo esc_html(date_i18n('d/m/Y H:i',strtotime($mov->fecha))); ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<script>
(function(){
    var condSel  = document.getElementById('wcfin_condicion_id');
    var metSel   = document.getElementById('wcfin_metodo_id');
    var tabla    = document.getElementById('wcfin-vars-tabla');
    var ph       = document.getElementById('wcfin-vars-ph');
    var hint     = document.getElementById('wcfin-metodo-hint');
    var actores  = <?php echo wp_json_encode(WCFIN_Database::ACTORES); ?>;
    var savedVars= <?php echo wp_json_encode($vars_ex); ?>;

    window.wcfinRecalc = function(){
        var total=0;
        tabla.querySelectorAll('input[type=number]').forEach(function(i){total+=parseFloat(i.value||0);});
        var el=document.getElementById('wcfin-total');
        if(el) el.textContent='S/ '+total.toFixed(2);
    };

    function buildVars(comps){
        if(!comps||!comps.length){tabla.style.display='none';ph.style.display='';return;}
        ph.style.display='none';
        tabla.innerHTML='';
        comps.forEach(function(c){
            var val=savedVars[c.var]||'0';
            var tr=document.createElement('tr');
            tr.innerHTML='<th scope="row" style="width:130px"><label>'+c.label+'</label></th>'
                +'<td><input name="wcfin_var_'+c.var+'" type="number" step="0.01" min="0" class="small-text"'
                +' value="'+val+'"'+(c.req?' required':'')+' onchange="wcfinRecalc()"> S/'
                +(c.req?'':' <span class="description">(opcional)</span>')+'</td>';
            tabla.appendChild(tr);
        });
        var trTot=document.createElement('tr');
        trTot.innerHTML='<th scope="row"><strong>Total estimado</strong></th><td><strong id="wcfin-total" style="font-size:1.1em">S/ 0.00</strong></td>';
        tabla.appendChild(trTot);
        tabla.style.display='';
        wcfinRecalc();
    }

    condSel&&condSel.addEventListener('change',function(){
        var opt=this.options[this.selectedIndex];
        var comps=[];
        try{comps=JSON.parse(opt.getAttribute('data-comps')||'[]');}catch(e){}
        savedVars={};
        buildVars(comps);
    });

    metSel&&metSel.addEventListener('change',function(){
        var opt=this.options[this.selectedIndex];
        var actor=opt.getAttribute('data-actor')||'';
        var conf=opt.getAttribute('data-conf')||'0';
        if(actor&&actor!=='ninguno'){
            hint.textContent='El dinero va a: '+(actores[actor]||actor)+(conf==='1'?' (requiere confirmación manual)':'');
        } else hint.textContent='';
    });

    if(condSel&&condSel.value) condSel.dispatchEvent(new Event('change'));
    if(metSel&&metSel.value)   metSel.dispatchEvent(new Event('change'));
})();
</script>
