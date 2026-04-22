<?php if ( ! defined( 'ABSPATH' ) ) exit;
$bloqueado = $trans && $trans->estado === 'confirmado';
$condicion_auto_id = $condicion_sugerida ?? 0;
$condicion_sel     = $trans ? intval($trans->condicion_id) : $condicion_auto_id;
?>

<?php if ( $condicion_pago && ! $trans ): ?>
<div class="notice notice-info inline" style="margin:8px 0;border-left-color:#2271b1">
    <p style="margin:6px 0">
        <span style="font-size:15px">🔍</span>
        <strong>Detección automática:</strong>
        El formulario indica <em><?php echo esc_html($condicion_pago); ?></em> —
        la condición de finanzas fue preseleccionada automáticamente.
        Puedes cambiarla si es necesario.
    </p>
</div>
<?php endif; ?>

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
        <th scope="row" style="width:160px">
            <label for="wcfin_condicion_id">
                Condición de pago
                <span class="wcfin-tooltip" title="¿Quién paga este envío? Elige 'Paga el Destinatario' si el conductor cobra al entregar, o 'Paga el Remitente' si ya fue pagado previamente.">
                    <span class="dashicons dashicons-editor-help" style="font-size:14px;color:#999;cursor:help;vertical-align:middle"></span>
                </span>
            </label>
        </th>
        <td>
            <select id="wcfin_condicion_id" name="wcfin_condicion_id" class="regular-text" <?php echo $bloqueado?'disabled':''; ?>>
                <option value="">— Seleccionar condición —</option>
                <?php foreach($condiciones as $c): ?>
                <option value="<?php echo intval($c->id); ?>"
                        data-comps="<?php echo esc_attr(wp_json_encode($comp_map[$c->id]??[])); ?>"
                        data-cobrar="<?php echo esc_attr($c->cobrar_a); ?>"
                        data-desc="<?php echo esc_attr($c->descripcion ?? ''); ?>"
                        <?php selected($condicion_sel, $c->id); ?>>
                    <?php echo esc_html($c->nombre); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <div id="wcfin-cond-desc" class="description" style="margin-top:4px;font-style:italic;color:#555"></div>
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="wcfin_metodo_id">
                Método de pago
                <span class="wcfin-tooltip" title="¿Cómo pagó? Elige el método y verás a dónde va el dinero.">
                    <span class="dashicons dashicons-editor-help" style="font-size:14px;color:#999;cursor:help;vertical-align:middle"></span>
                </span>
            </label>
        </th>
        <td>
            <select id="wcfin_metodo_id" name="wcfin_metodo_id" class="regular-text" <?php echo $bloqueado?'disabled':''; ?>>
                <option value="">— Seleccionar método —</option>
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
            <div id="wcfin-metodo-hint" class="description" style="margin-top:4px"></div>
        </td>
    </tr>
</table>

<!-- Variables del monto — dinámicas -->
<div id="wcfin-vars-wrap">
<p id="wcfin-vars-ph" class="description" style="<?php echo ($trans&&$vars_ex)?'display:none':''; ?>;background:#f0f6fc;padding:8px 12px;border-radius:4px;border-left:3px solid #2271b1">
    👆 Selecciona una condición de pago para ver los campos de monto.
</p>
<table class="form-table" role="presentation" id="wcfin-vars-tabla" style="<?php echo ($trans&&$vars_ex)?'':'display:none'; ?>margin-top:0">
<?php if ($trans && $vars_ex):
    foreach($comp_map[$trans->condicion_id]??[] as $comp): ?>
    <tr>
        <th scope="row" style="width:160px"><label for="wcfin_var_<?php echo esc_attr($comp['var']); ?>"><?php echo esc_html($comp['label']); ?></label></th>
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
        <td><strong id="wcfin-total" style="font-size:1.1em;color:#2271b1">S/ <?php echo number_format(floatval($trans->monto_total??0),2); ?></strong>
        <span class="description" style="margin-left:8px">→ <span id="wcfin-cobrar-a"></span></span></td>
    </tr>
<?php endif; ?>
</table>
</div>

<table class="form-table" role="presentation" style="margin-top:0">
    <tr>
        <th scope="row"><label for="wcfin_notas">Notas del pago</label></th>
        <td><textarea id="wcfin_notas" name="wcfin_notas" rows="2" class="large-text" placeholder="Observaciones sobre este pago..." <?php echo $bloqueado?'disabled':''; ?>><?php echo esc_textarea($trans->notas??''); ?></textarea></td>
    </tr>
</table>

<!-- ═══ PENALIDADES ══════════════════════════════════════════════════════════ -->
<?php if ($penalidades): ?>
<hr style="margin:16px 0">
<div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
    <span style="font-size:16px">⚠️</span>
    <strong>Penalidades</strong>
    <span class="description">— aplica sanciones a motorizados, remitentes u operaciones de este envío</span>
</div>

<?php if (!$bloqueado): ?>
<!-- Formulario penalidades al guardar -->
<table class="wp-list-table widefat striped" style="font-size:13px">
    <thead><tr>
        <th style="width:34px">Aplic.</th>
        <th>Penalidad</th>
        <th style="width:120px">Aplica a</th>
        <th style="width:120px">Monto S/</th>
        <th>Descripción del caso</th>
    </tr></thead>
    <tbody>
    <?php foreach($penalidades as $p): ?>
    <tr>
        <td style="text-align:center"><input type="checkbox" name="wcfin_pen[<?php echo intval($p->id); ?>][aplicar]" value="1" class="wcfin-pen-check" data-row="pen-<?php echo intval($p->id); ?>"></td>
        <td>
            <strong><?php echo esc_html($p->nombre); ?></strong>
            <?php if($p->descripcion): ?><br><small class="text-muted"><?php echo esc_html($p->descripcion); ?></small><?php endif; ?>
        </td>
        <td><span style="padding:2px 8px;background:#f0f0f0;border-radius:10px;font-size:11px"><?php echo esc_html($actores[$p->aplica_a]??$p->aplica_a); ?></span></td>
        <td><input type="number" step="0.01" min="0" class="small-text" name="wcfin_pen[<?php echo intval($p->id); ?>][monto]" value="<?php echo esc_attr($p->monto_default); ?>"> <?php echo $p->tipo_monto==='porcentaje'?'%':'S/'; ?></td>
        <td><input type="text" class="regular-text" name="wcfin_pen[<?php echo intval($p->id); ?>][nota]" placeholder="Describe el caso específico aquí..."></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<p class="description" style="margin-top:6px">💡 Marca las penalidades que apliquen y guarda el envío para ejecutarlas.</p>
<?php endif; ?>

<!-- Botón para aplicar penalidad inmediata (AJAX, sin guardar) -->
<div style="margin-top:10px">
    <button type="button" id="wcfin-btn-pen" class="button button-secondary" style="border-color:#d63638;color:#d63638">
        <span class="dashicons dashicons-warning" style="vertical-align:middle;margin-right:4px"></span>
        Aplicar penalidad ahora (inmediata)
    </button>
    <div id="wcfin-pen-form" style="display:none;background:#fff5f5;border:1px solid #f0a0a0;border-radius:6px;padding:14px;margin-top:10px">
        <div style="display:grid;grid-template-columns:1fr 1fr 100px;gap:10px;align-items:end">
            <div>
                <label style="font-size:12px;font-weight:700;display:block;margin-bottom:4px">Tipo de penalidad <span style="color:#d63638">*</span></label>
                <select id="wcfin-pen-tipo" class="regular-text">
                    <option value="">— Seleccionar —</option>
                    <?php foreach($penalidades as $p): ?>
                    <option value="<?php echo intval($p->id); ?>" data-monto="<?php echo esc_attr($p->monto_default); ?>">
                        <?php echo esc_html($p->nombre); ?> (<?php echo esc_html($actores[$p->aplica_a]??$p->aplica_a); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="font-size:12px;font-weight:700;display:block;margin-bottom:4px">Descripción del caso</label>
                <input type="text" id="wcfin-pen-nota" class="regular-text" placeholder="Ej: Llegó 2 horas tarde...">
            </div>
            <div>
                <label style="font-size:12px;font-weight:700;display:block;margin-bottom:4px">Monto S/</label>
                <input type="number" id="wcfin-pen-monto" step="0.01" min="0" class="small-text" value="0">
            </div>
        </div>
        <div style="margin-top:10px;display:flex;gap:8px;align-items:center">
            <button type="button" id="wcfin-pen-ejecutar" class="button button-primary" style="background:#d63638;border-color:#d63638">
                <span class="dashicons dashicons-yes-alt" style="vertical-align:middle"></span>
                Ejecutar penalidad
            </button>
            <span id="wcfin-pen-msg" style="font-size:13px;font-weight:600"></span>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ═══ MOVIMIENTOS CONTABLES ════════════════════════════════════════════════ -->
<?php if ($movimientos): ?>
<hr style="margin:16px 0">
<p><strong>📊 Movimientos contables de este envío</strong></p>
<table class="wp-list-table widefat striped" style="font-size:13px">
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
    var condDesc = document.getElementById('wcfin-cond-desc');
    var cobrarEl = document.getElementById('wcfin-cobrar-a');
    var actores  = <?php echo wp_json_encode(WCFIN_Database::ACTORES); ?>;
    var savedVars= <?php echo wp_json_encode($vars_ex); ?>;
    var shipId   = <?php echo intval($post->ID); ?>;

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
            tr.innerHTML='<th scope="row" style="width:160px"><label>'+c.label+'</label></th>'
                +'<td><input name="wcfin_var_'+c.var+'" type="number" step="0.01" min="0" class="small-text"'
                +' value="'+val+'"'+(c.req?' required':'')+' onchange="wcfinRecalc()"> S/'
                +(c.req?'':' <span class="description">(opcional)</span>')+'</td>';
            tabla.appendChild(tr);
        });
        var trTot=document.createElement('tr');
        trTot.innerHTML='<th scope="row"><strong>Total estimado</strong></th>'
            +'<td><strong id="wcfin-total" style="font-size:1.1em;color:#2271b1">S/ 0.00</strong>'
            +'<span class="description" style="margin-left:8px">→ <span id="wcfin-cobrar-a"></span></span></td>';
        tabla.appendChild(trTot);
        tabla.style.display='';
        wcfinRecalc();
    }

    condSel&&condSel.addEventListener('change',function(){
        var opt=this.options[this.selectedIndex];
        var comps=[];
        try{comps=JSON.parse(opt.getAttribute('data-comps')||'[]');}catch(e){}
        var cobrar=opt.getAttribute('data-cobrar')||'';
        var desc=opt.getAttribute('data-desc')||'';
        savedVars={};
        buildVars(comps);
        if(condDesc) condDesc.textContent=desc;
        var ca=document.getElementById('wcfin-cobrar-a');
        if(ca) ca.textContent=cobrar?(('Cobra: ')+(actores[cobrar]||cobrar)):'';
    });

    metSel&&metSel.addEventListener('change',function(){
        var opt=this.options[this.selectedIndex];
        var actor=opt.getAttribute('data-actor')||'';
        var conf=opt.getAttribute('data-conf')||'0';
        if(actor&&actor!=='ninguno'){
            hint.innerHTML='💰 El dinero va a: <strong>'+(actores[actor]||actor)+'</strong>'+(conf==='1'?' <em>(requiere confirmación manual)</em>':'');
        } else hint.textContent='';
    });

    if(condSel&&condSel.value) condSel.dispatchEvent(new Event('change'));
    if(metSel&&metSel.value)   metSel.dispatchEvent(new Event('change'));

    // ── Penalidad AJAX ────────────────────────────────────────────────────────
    var btnPen = document.getElementById('wcfin-btn-pen');
    var penForm= document.getElementById('wcfin-pen-form');
    var penTipo= document.getElementById('wcfin-pen-tipo');
    var penMonto=document.getElementById('wcfin-pen-monto');
    var penNota= document.getElementById('wcfin-pen-nota');
    var penMsg = document.getElementById('wcfin-pen-msg');
    var penExec= document.getElementById('wcfin-pen-ejecutar');

    if(btnPen) btnPen.addEventListener('click',function(){
        penForm.style.display=penForm.style.display==='none'?'block':'none';
    });
    if(penTipo) penTipo.addEventListener('change',function(){
        var m=this.options[this.selectedIndex].getAttribute('data-monto')||'0';
        if(penMonto) penMonto.value=m;
    });
    if(penExec) penExec.addEventListener('click',function(){
        var tipo=penTipo?penTipo.value:'';
        var monto=penMonto?penMonto.value:'0';
        var nota=penNota?penNota.value:'';
        if(!tipo){penMsg.textContent='⚠️ Selecciona un tipo.';penMsg.style.color='#d63638';return;}
        penExec.disabled=true;
        penMsg.textContent='Aplicando...';penMsg.style.color='#888';
        var fd=new FormData();
        fd.append('action','wcfin_aplicar_penalidad');
        fd.append('nonce','<?php echo wp_create_nonce('wcfin_ajax_pen'); ?>');
        fd.append('shipment_id',shipId);
        fd.append('tipo_id',tipo);
        fd.append('monto',monto);
        fd.append('notas',nota);
        fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(function(d){
            penExec.disabled=false;
            if(d.success){
                penMsg.textContent='✅ '+d.data.msg;penMsg.style.color='#00a32a';
                setTimeout(function(){location.reload();},1200);
            } else {
                penMsg.textContent='❌ '+(d.data||'Error desconocido');penMsg.style.color='#d63638';
            }
        }).catch(function(){penExec.disabled=false;penMsg.textContent='Error de red.';penMsg.style.color='#d63638';});
    });
})();
</script>
