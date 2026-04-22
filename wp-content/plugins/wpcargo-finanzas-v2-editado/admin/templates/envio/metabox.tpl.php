<?php if ( ! defined( 'ABSPATH' ) ) exit;
$bloqueado = $trans && $trans->estado === 'confirmado';

// Badge visual según condicion_pago del formulario de envíos
$cp_labels = [
    'remitente'    => ['🧾 Paga el Remitente',    '#d7f7c2', '#135d3e', 'El remitente pagó previamente. El conductor solo entrega el paquete (no cobra al destinatario). DHV recibe el servicio y le debe el valor del producto al remitente.'],
    'destinatario' => ['💵 Paga el Destinatario',  '#e8f4fd', '#1a6891', 'El conductor cobra el monto total al destinatario al momento de entregar. Luego entrega todo a DHV, quien divide: servicio para DHV, valor del producto para el remitente.'],
];
$cp_info = $cp_labels[$condicion_pago] ?? null;
?>

<style>
.wcfin-tooltip-icon { cursor:help; color:#646970; font-size:12px; border-bottom:1px dashed #646970; }
.wcfin-badge        { display:inline-block; padding:3px 10px; border-radius:12px; font-size:11px; font-weight:700; letter-spacing:.3px; }
.wcfin-section-title{ font-weight:700; font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:#646970; margin:16px 0 8px; padding-top:12px; border-top:1px solid #f0f0f1; }
.wcfin-hint-box     { background:#f0f6fc; border-left:3px solid #2271b1; border-radius:4px; padding:8px 12px; font-size:12px; color:#1a6891; margin-bottom:10px; }
.wcfin-warn-box     { background:#fffbf0; border-left:3px solid #dba617; border-radius:4px; padding:8px 12px; font-size:12px; color:#856404; margin-bottom:10px; }
</style>

<!-- ─── Banner de estado ──────────────────────────────────────────────────── -->
<?php if ($trans): ?>
<div class="notice notice-<?php echo $trans->estado==='confirmado'?'success':'warning'; ?> inline" style="margin:8px 0">
    <p>
        <strong>Estado:</strong>
        <?php echo $trans->estado==='confirmado' ? '✅ Pago confirmado' : '⏳ Pendiente de confirmación'; ?>
        — <strong><?php echo esc_html($trans->metodo_nombre); ?></strong>
        — Total: <strong>S/ <?php echo number_format(floatval($trans->monto_total),2); ?></strong>
        <?php if ($trans->estado==='pendiente'): ?>
        <a href="<?php echo esc_url(wp_nonce_url(admin_url("admin-post.php?action=wcfin_confirmar_pago&trans_id={$trans->id}&shipment_id={$post->ID}"),'wcfin_confirmar_'.$trans->id)); ?>"
           class="button button-small button-primary" style="margin-left:8px">✔ Confirmar pago</a>
        <?php endif; ?>
    </p>
</div>
<?php endif; ?>

<!-- ─── Indicador de condicion_pago del formulario de envíos ─────────────── -->
<?php if ($cp_info): ?>
<div style="margin:8px 0 12px;padding:10px 14px;border-radius:6px;background:<?php echo esc_attr($cp_info[1]); ?>;border:1px solid <?php echo esc_attr($cp_info[2]); ?>30">
    <div style="font-weight:700;color:<?php echo esc_attr($cp_info[2]); ?>;font-size:13px;margin-bottom:4px">
        <?php echo esc_html($cp_info[0]); ?>
        <span style="font-size:11px;font-weight:400;color:#646970"> — detectado en el formulario de envío</span>
    </div>
    <div style="font-size:12px;color:#50575e;line-height:1.5"><?php echo esc_html($cp_info[3]); ?></div>
    <?php if (!$trans && $condicion_sugerida): ?>
    <div style="margin-top:6px;font-size:11px;color:<?php echo esc_attr($cp_info[2]); ?>;font-weight:600">
        ↓ La condición de pago se ha preseleccionado automáticamente abajo.
    </div>
    <?php endif; ?>
</div>
<?php elseif (!$trans): ?>
<div class="wcfin-warn-box">
    ⚠️ Este envío no tiene una <strong>condición de pago</strong> definida en el formulario. Selecciona manualmente la condición y el método de pago.
</div>
<?php endif; ?>

<?php wp_nonce_field('wcfin_pago_'.$post->ID,'wcfin_nonce'); ?>

<!-- ─── Condición y Método ────────────────────────────────────────────────── -->
<p class="wcfin-section-title">① Condición de pago y método</p>

<table class="form-table" role="presentation" style="margin-top:0">
    <tr>
        <th scope="row" style="width:160px">
            <label for="wcfin_condicion_id">
                ¿Quién paga?
                <span class="wcfin-tooltip-icon" title="Define si el remitente pagó previamente o si el destinatario paga al recibir el paquete. Esto determina cómo se distribuye el dinero.">(?)</span>
            </label>
        </th>
        <td>
            <select id="wcfin_condicion_id" name="wcfin_condicion_id" class="regular-text" <?php echo $bloqueado?'disabled':''; ?>>
                <option value="">— Elige quién paga —</option>
                <?php foreach($condiciones as $c):
                    $es_principal = in_array($c->slug, [WCFIN_Database::CONDICION_SLUG_REMITENTE, WCFIN_Database::CONDICION_SLUG_DESTINATARIO], true);
                    $seleccionado = ((int)$c->id === $condicion_preselect);
                ?>
                <option value="<?php echo intval($c->id); ?>"
                        data-comps="<?php echo esc_attr(wp_json_encode($comp_map[$c->id]??[])); ?>"
                        data-cobrar="<?php echo esc_attr($c->cobrar_a); ?>"
                        <?php selected($seleccionado); ?>>
                    <?php echo $es_principal ? '★ ' : ''; ?><?php echo esc_html($c->nombre); ?>
                    (cobra a: <?php echo esc_html($actores[$c->cobrar_a]??$c->cobrar_a); ?>)
                </option>
                <?php endforeach; ?>
            </select>
            <p id="wcfin-condicion-hint" class="description" style="margin-top:4px;color:#1a6891"></p>
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="wcfin_metodo_id">
                Método de pago
                <span class="wcfin-tooltip-icon" title="¿Cómo pagó o pagará el cliente? El destino del dinero depende del método elegido.">(?)</span>
            </label>
        </th>
        <td>
            <select id="wcfin_metodo_id" name="wcfin_metodo_id" class="regular-text" <?php echo $bloqueado?'disabled':''; ?>>
                <option value="">— Elige el método —</option>
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
            <p id="wcfin-metodo-hint" class="description" style="margin-top:4px"></p>
        </td>
    </tr>
</table>

<!-- ─── Variables de monto ───────────────────────────────────────────────── -->
<p class="wcfin-section-title">② Montos del envío</p>

<div id="wcfin-vars-wrap">
<p id="wcfin-vars-ph" class="description" style="<?php echo ($trans&&$vars_ex)?'display:none':''; ?>">
    <em>Selecciona una condición de pago para ver los campos de monto.</em>
</p>
<table class="form-table" role="presentation" id="wcfin-vars-tabla" style="<?php echo ($trans&&$vars_ex)?'':'display:none'; ?>margin-top:0">
<?php if ($trans && $vars_ex):
    foreach($comp_map[$trans->condicion_id]??[] as $comp): ?>
    <tr>
        <th scope="row" style="width:220px"><label for="wcfin_var_<?php echo esc_attr($comp['var']); ?>"><?php echo esc_html($comp['label']); ?></label></th>
        <td>
            <input id="wcfin_var_<?php echo esc_attr($comp['var']); ?>"
                   name="wcfin_var_<?php echo esc_attr($comp['var']); ?>"
                   type="number" step="0.01" min="0" class="small-text"
                   value="<?php echo esc_attr($vars_ex[$comp['var']]??'0'); ?>"
                   <?php echo $comp['req']?'required':''; ?>
                   <?php echo $bloqueado?'disabled':''; ?>
                   onchange="wcfinRecalc()"> <strong>S/</strong>
            <?php if(!$comp['req']): ?><span class="description">(opcional)</span><?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>
    <tr>
        <th scope="row"><strong>Total a cobrar</strong></th>
        <td>
            <strong id="wcfin-total" style="font-size:1.2em;color:#2271b1">S/ <?php echo number_format(floatval($trans->monto_total??0),2); ?></strong>
            <span id="wcfin-cobrador-badge" class="wcfin-badge" style="margin-left:8px;background:#e8f4fd;color:#1a6891"></span>
        </td>
    </tr>
<?php endif; ?>
</table>
</div>

<!-- ─── Notas ─────────────────────────────────────────────────────────────── -->
<table class="form-table" role="presentation" style="margin-top:0">
    <tr>
        <th scope="row"><label for="wcfin_notas">Notas internas</label></th>
        <td>
            <textarea id="wcfin_notas" name="wcfin_notas" rows="2" class="large-text"
                      placeholder="Observaciones sobre este pago (solo visible para el admin)..."
                      <?php echo $bloqueado?'disabled':''; ?>><?php echo esc_textarea($trans->notas??''); ?></textarea>
        </td>
    </tr>
</table>

<!-- ─── Penalidades ───────────────────────────────────────────────────────── -->
<?php if ($penalidades && !$bloqueado): ?>
<p class="wcfin-section-title">③ Penalidades (opcional)</p>
<div class="wcfin-hint-box" style="margin-bottom:10px">
    💡 Marca una penalidad solo si aplica a este envío. El monto se restará del balance del motorizado o de la caja empresa según corresponda.
</div>
<table class="wp-list-table widefat striped" style="font-size:13px">
    <thead>
        <tr>
            <th style="width:36px" title="¿Aplicar esta penalidad?">Aplic.</th>
            <th>Penalidad</th>
            <th>¿A quién afecta?</th>
            <th style="width:110px">Monto</th>
            <th>Motivo del caso</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($penalidades as $p): ?>
    <tr>
        <td style="text-align:center">
            <input type="checkbox" name="wcfin_pen[<?php echo intval($p->id); ?>][aplicar]" value="1"
                   title="Marcar para aplicar esta penalidad">
        </td>
        <td>
            <strong><?php echo esc_html($p->nombre); ?></strong>
            <?php if($p->descripcion): ?><br><small class="description"><?php echo esc_html($p->descripcion); ?></small><?php endif; ?>
        </td>
        <td>
            <span class="wcfin-badge" style="background:#f0f0f1;color:#2c3338">
                <?php echo esc_html($actores[$p->aplica_a]??$p->aplica_a); ?>
            </span>
        </td>
        <td>
            <input type="number" step="0.01" min="0" class="small-text"
                   name="wcfin_pen[<?php echo intval($p->id); ?>][monto]"
                   value="<?php echo esc_attr($p->monto_default); ?>">
            <strong><?php echo $p->tipo_monto==='porcentaje'?'%':'S/'; ?></strong>
        </td>
        <td>
            <input type="text" class="regular-text"
                   name="wcfin_pen[<?php echo intval($p->id); ?>][nota]"
                   placeholder="Describe brevemente el caso...">
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php elseif ($bloqueado && $penalidades): ?>
<p class="wcfin-section-title">Penalidades</p>
<p class="description">El pago está confirmado. Las penalidades no pueden editarse.</p>
<?php endif; ?>

<!-- ─── Movimientos registrados ───────────────────────────────────────────── -->
<?php if ($movimientos): ?>
<p class="wcfin-section-title">📋 Movimientos contables generados</p>
<table class="wp-list-table widefat striped" style="font-size:13px">
    <thead>
        <tr>
            <th>Cuenta afectada</th>
            <th>Monto</th>
            <th>Tipo</th>
            <th>Descripción</th>
            <th>Fecha</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($movimientos as $mov): ?>
    <tr>
        <td><?php echo esc_html($cuentas[$mov->cuenta]??$mov->cuenta); ?></td>
        <td>
            <strong style="color:<?php echo $mov->signo>0?'#00a32a':'#d63638'; ?>">
                <?php echo $mov->signo>0?'+':'−'; ?> S/ <?php echo number_format(abs(floatval($mov->monto)),2); ?>
            </strong>
        </td>
        <td>
            <span class="wcfin-badge" style="background:<?php echo $mov->tipo==='penalidad'?'#fce9e9':'#e8f4fd'; ?>;color:<?php echo $mov->tipo==='penalidad'?'#8a1a1a':'#1a6891'; ?>">
                <?php echo esc_html(ucfirst($mov->tipo)); ?>
            </span>
        </td>
        <td class="description"><?php echo esc_html($mov->descripcion); ?></td>
        <td class="description"><?php echo esc_html(date_i18n('d/m/Y H:i',strtotime($mov->fecha))); ?></td>
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
    var mHint    = document.getElementById('wcfin-metodo-hint');
    var cHint    = document.getElementById('wcfin-condicion-hint');
    var actores  = <?php echo wp_json_encode(WCFIN_Database::ACTORES); ?>;
    var savedVars= <?php echo wp_json_encode($vars_ex); ?>;
    var badgeCob = document.getElementById('wcfin-cobrador-badge');

    var cobrarTexts = {
        'remitente':    '🧾 El remitente pagó previamente',
        'destinatario': '💵 El destinatario paga al recibir',
        'ninguno':      '✓ Prepago — sin cobro al entregar',
        'motorizado':   '📦 El motorizado cobra'
    };

    window.wcfinRecalc = function(){
        var total=0;
        tabla.querySelectorAll('input[type=number]').forEach(function(i){
            total+=parseFloat(i.value||0);
        });
        var el=document.getElementById('wcfin-total');
        if(el) el.textContent='S/ '+total.toFixed(2);
    };

    function buildVars(comps){
        if(!comps||!comps.length){ tabla.style.display='none'; ph.style.display=''; return; }
        ph.style.display='none';
        tabla.innerHTML='';
        comps.forEach(function(c){
            var val=savedVars[c.var]||'0';
            var tr=document.createElement('tr');
            tr.innerHTML='<th scope="row" style="width:220px"><label>'+c.label+'</label></th>'
                +'<td><input name="wcfin_var_'+c.var+'" type="number" step="0.01" min="0" class="small-text"'
                +' value="'+val+'"'+(c.req?' required':'')+' onchange="wcfinRecalc()"> <strong>S/</strong>'
                +(c.req?'':' <span class="description">(opcional)</span>')+'</td>';
            tabla.appendChild(tr);
        });
        var trTot=document.createElement('tr');
        trTot.innerHTML='<th scope="row"><strong>Total a cobrar</strong></th>'
            +'<td><strong id="wcfin-total" style="font-size:1.2em;color:#2271b1">S/ 0.00</strong>'
            +' <span id="wcfin-cobrador-badge" class="wcfin-badge" style="margin-left:8px;background:#e8f4fd;color:#1a6891"></span></td>';
        tabla.appendChild(trTot);
        tabla.style.display='';
        badgeCob=document.getElementById('wcfin-cobrador-badge');
        wcfinRecalc();
    }

    function updateCobBadge(cobrar){
        badgeCob=document.getElementById('wcfin-cobrador-badge');
        if(badgeCob&&cobrar&&cobrarTexts[cobrar]){
            badgeCob.textContent=cobrarTexts[cobrar];
            badgeCob.style.display='';
        }
    }

    condSel&&condSel.addEventListener('change',function(){
        var opt=this.options[this.selectedIndex];
        var comps=[];
        try{comps=JSON.parse(opt.getAttribute('data-comps')||'[]');}catch(e){}
        var cobrar=opt.getAttribute('data-cobrar')||'';
        if(cHint) cHint.textContent = cobrar ? (cobrarTexts[cobrar]||'Cobra a: '+cobrar) : '';
        savedVars={};
        buildVars(comps);
        updateCobBadge(cobrar);
    });

    metSel&&metSel.addEventListener('change',function(){
        var opt=this.options[this.selectedIndex];
        var actor=opt.getAttribute('data-actor')||'';
        var conf=opt.getAttribute('data-conf')||'0';
        if(actor&&actor!=='ninguno'){
            mHint.textContent='💳 El dinero va a: '+(actores[actor]||actor)+(conf==='1'?' · Requiere confirmación manual':'');
            mHint.style.color='#2271b1';
        } else {
            mHint.textContent = actor==='ninguno' ? '✓ Sin cobro (prepago)' : '';
            mHint.style.color='#646970';
        }
    });

    if(condSel&&condSel.value) condSel.dispatchEvent(new Event('change'));
    if(metSel&&metSel.value)   metSel.dispatchEvent(new Event('change'));
    <?php if (!$trans && $condicion_preselect): ?>
    if(condSel){ condSel.value='<?php echo intval($condicion_preselect); ?>'; condSel.dispatchEvent(new Event('change')); }
    <?php endif; ?>
})();
</script>
