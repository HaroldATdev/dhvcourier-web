<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wpcte_render_frontend_page( string $wpcfe ): void {
    $es_admin  = current_user_can('manage_options');
    $tarifario = wpcte_tarifario();
    $tar_js    = wp_json_encode( $tarifario );
    $lug_js    = wp_json_encode( wpcte_lugares() );
    $nonce     = wp_create_nonce('wpcte_admin_nonce');
    $ajax      = admin_url('admin-ajax.php');
    $url_tar   = wpcte_page_url( 'tarifario_dhv' );
    $url_cot   = wpcte_page_url( 'cotizador_dhv' );

    ?>
<!-- ══ WPCTE FRONTEND PAGES ══════════════════════════════════════ -->
<style>
#wpcte-fe-wrap{display:block;padding:1.5rem;background:#f4f7fb;min-height:500px;font-family:inherit;}

/* Header */
.wpcte-fe-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;}
.wpcte-fe-header h2{margin:0;font-size:1.3rem;color:#0077b6;display:flex;align-items:center;gap:.5rem;}

/* Tabs tarifario */
.wpcte-fe-tabs{display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1.25rem;}
.wpcte-fe-tab{background:#fff;border:1.5px solid #d0e8f5;color:#0077b6;padding:.4rem 1rem;border-radius:20px;cursor:pointer;font-size:.82rem;font-weight:600;transition:all .15s;}
.wpcte-fe-tab.active,.wpcte-fe-tab:hover{background:#0077b6;color:#fff;border-color:#0077b6;}
.wpcte-fe-tab-content{display:none;}
.wpcte-fe-tab-content.active{display:block;animation:wpcte-fe-in .2s;}
@keyframes wpcte-fe-in{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}
/* Cards / secciones */
.wpcte-fe-card{background:#fff;border:1.5px solid #d0e8f5;border-radius:12px;padding:1.25rem 1.5rem;margin-bottom:1.25rem;box-shadow:0 2px 8px rgba(0,119,182,.06);}
.wpcte-fe-card h3{font-size:1rem;color:#0077b6;margin:0 0 1rem;display:flex;align-items:center;gap:.5rem;}
/* Tabla tarifario */
.wpcte-fe-table{width:100%;border-collapse:collapse;font-size:.85rem;}
.wpcte-fe-table th{background:#f0f7ff;color:#0077b6;padding:.5rem .75rem;text-align:left;font-weight:700;border-bottom:2px solid #d0e8f5;}
.wpcte-fe-table td{padding:.45rem .75rem;border-bottom:1px solid #edf4ff;vertical-align:middle;}
.wpcte-fe-table tr:hover td{background:#f8fbff;}
.wpcte-fe-table tr:last-child td{border-bottom:none;}
.wpcte-fe-badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:.75rem;font-weight:700;background:#e8f5fd;color:#0077b6;}
.wpcte-fe-badge.per{background:#fff0e8;color:#e76f51;}
/* Formulario cotizador inline */
.wpcte-fe-cot-form{display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-end;margin-bottom:1rem;}
.wpcte-fe-cot-group{display:flex;flex-direction:column;flex:1;min-width:150px;}
.wpcte-fe-cot-group label{font-size:.78rem;font-weight:600;color:#555;margin-bottom:3px;}
.wpcte-fe-cot-group select,.wpcte-fe-cot-group input{padding:6px 8px;border:1px solid #ccc;border-radius:8px;font-size:.88rem;background:#fff;width:100%;}
.wpcte-fe-calc-btn{background:#0077b6;color:#fff;border:none;padding:.5rem 1.4rem;border-radius:8px;font-weight:600;cursor:pointer;font-size:.9rem;white-space:nowrap;}
.wpcte-fe-calc-btn:hover{background:#005f99;}
.wpcte-fe-resultado{margin-top:.75rem;padding:.75rem 1rem;border-radius:8px;background:#f0f9ff;border:1px solid #b3ddf5;font-size:.9rem;line-height:1.8;display:none;}
.wpcte-fe-resultado.ok{display:block;}
.wpcte-desglose-fe{margin-top:.5rem;border-top:1px solid #d0e8f5;padding-top:.5rem;font-size:.83rem;color:#555;}
.wpcte-desglose-row-fe{display:flex;justify-content:space-between;padding:1px 0;}
.wpcte-total-row-fe{display:flex;justify-content:space-between;font-weight:700;color:#0077b6;border-top:1px solid #b3ddf5;margin-top:3px;padding-top:3px;}
/* Admin-only: tabla editable */
.wpcte-fe-editable input{width:100%;padding:3px 5px;border:1px solid #ccc;border-radius:5px;font-size:.83rem;}
.wpcte-fe-editable input.p{width:70px;}
.wpcte-fe-btn-del{background:#fee;border:1px solid #fcc;color:#c0392b;padding:2px 8px;border-radius:5px;cursor:pointer;font-size:.78rem;}
.wpcte-fe-btn-add{background:#e8f5fd;border:1.5px dashed #0077b6;color:#0077b6;padding:.35rem 1rem;border-radius:8px;cursor:pointer;font-size:.82rem;font-weight:600;margin-top:.5rem;}
.wpcte-fe-btn-add:hover{background:#d0eaf8;}
.wpcte-fe-save-row{margin-top:1rem;display:flex;gap:.75rem;align-items:center;}
.wpcte-fe-save-btn{background:#2a9d8f;color:#fff;border:none;padding:.45rem 1.3rem;border-radius:8px;font-weight:600;cursor:pointer;}
.wpcte-fe-save-btn:hover{background:#21867a;}
.wpcte-fe-notice{padding:.5rem 1rem;border-radius:8px;font-size:.85rem;margin-top:.75rem;display:none;}
.wpcte-fe-notice.ok{background:#ecfdf5;border:1px solid #2a9d8f;color:#165a52;display:block;}
.wpcte-fe-notice.err{background:#fff0f0;border:1px solid #c0392b;color:#922;display:block;}
/* Permisos: ocultar edición a no-admins */
<?php if ( ! $es_admin ): ?>.wpcte-admin-only{display:none!important;}<?php endif; ?>
</style>

<div id="wpcte-fe-wrap">

    <!-- Cabecera + nav pills -->
    <div class="wpcte-fe-header">
        <h2>
            📋 Tarifario DHV
        </h2>

    </div>

    <?php if ( $wpcfe === 'tarifario_dhv' ): ?>
    <!-- ═══════════════════ TARIFARIO FRONTEND ═══════════════════ -->
    <div class="wpcte-fe-tabs">
        <button class="wpcte-fe-tab active" data-tab="fe-lima">🏙️ Dentro de Lima</button>
        <button class="wpcte-fe-tab" data-tab="fe-carga">📦 Carga General</button>
        <button class="wpcte-fe-tab" data-tab="fe-merc">🛋️ Mercadería</button>
        <button class="wpcte-fe-tab" data-tab="fe-aereo">✈️ Aéreos</button>
        <button class="wpcte-fe-tab" data-tab="fe-sobres">✉️ Sobres</button>
    </div>

    <!-- DENTRO DE LIMA -->
    <div class="wpcte-fe-tab-content active" id="fe-lima">
        <div class="wpcte-fe-card">
            <h3>🚗 Vehículos y Precios Base</h3>
            <table class="wpcte-fe-table" id="fet-vehs">
                <thead><tr><th>Vehículo</th><th>Precio Base (S/)</th><?php if($es_admin): ?><th class="wpcte-admin-only"></th><?php endif; ?></tr></thead>
                <tbody></tbody>
            </table>
            <?php if($es_admin): ?><button class="wpcte-fe-btn-add wpcte-admin-only" id="fe-add-veh">+ Añadir vehículo</button><?php endif; ?>
        </div>
        <div class="wpcte-fe-card">
            <h3>🏘️ Distritos Normales <small style="font-weight:400;font-size:.8rem;color:#888">(precio base + adicional por distrito)</small></h3>
            <input type="text" id="fe-search-dist" placeholder="🔍 Buscar distrito..." style="width:100%;max-width:280px;padding:5px 10px;border:1px solid #ccc;border-radius:8px;margin-bottom:.75rem;font-size:.85rem">
            <div style="overflow-x:auto">
            <table class="wpcte-fe-table" id="fet-distritos">
                <thead><tr id="fet-dist-head"><th>Distrito</th></tr></thead>
                <tbody></tbody>
            </table>
            </div>
            <?php if($es_admin): ?><button class="wpcte-fe-btn-add wpcte-admin-only" id="fe-add-dist">+ Añadir distrito</button><?php endif; ?>
        </div>
        <div class="wpcte-fe-card" style="border-color:#f4a261">
            <h3 style="color:#e76f51">🔶 Zonas Periféricas <small style="font-weight:400;font-size:.8rem;color:#888">(precio fijo total)</small></h3>
            <div style="overflow-x:auto">
            <table class="wpcte-fe-table" id="fet-perifericas">
                <thead><tr id="fet-per-head"><th>Zona</th></tr></thead>
                <tbody></tbody>
            </table>
            </div>
            <?php if($es_admin): ?><button class="wpcte-fe-btn-add wpcte-admin-only" id="fe-add-per">+ Añadir zona</button><?php endif; ?>
        </div>
        <?php if($es_admin): ?>
        <div class="wpcte-fe-save-row wpcte-admin-only">
            <button class="wpcte-fe-save-btn" id="fe-save-lima">💾 Guardar cambios Lima</button>
            <span class="wpcte-fe-notice" id="fe-notice-lima"></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- CARGA GENERAL -->
    <div class="wpcte-fe-tab-content" id="fe-carga">
        <div class="wpcte-fe-card">
            <h3>📦 Tarifas de Carga General</h3>
            <p style="font-size:.82rem;color:#666;margin-bottom:.75rem">Fórmula: <strong>Base + (kg × precio/kg)</strong>. Agencia solo aplica para envíos Agencia/Almacén.</p>
            <div id="fe-carga-contenido"></div>
            <?php if($es_admin): ?>
            <button class="wpcte-fe-btn-add wpcte-admin-only" id="fe-add-ruta-cg">+ Añadir ruta/origen</button>
            <div class="wpcte-fe-save-row wpcte-admin-only">
                <button class="wpcte-fe-save-btn" id="fe-save-cg">💾 Guardar cambios Carga</button>
                <span class="wpcte-fe-notice" id="fe-notice-cg"></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MERCADERÍA -->
    <div class="wpcte-fe-tab-content" id="fe-merc">
        <?php if($es_admin): ?>
        <div class="wpcte-fe-card" style="display:flex;gap:1.5rem;flex-wrap:wrap">
            <div style="flex:1;min-width:200px">
                <strong style="color:#0077b6">🏙️ Lugares en Lima</strong>
                <p style="font-size:.8rem;color:#666;margin:.2rem 0 .4rem">Agrega los lugares de Lima para mercadería.</p>
                <div id="fe-merc-lima-lista" style="margin-bottom:.4rem"></div>
                <button class="wpcte-fe-btn-add wpcte-admin-only" id="fe-add-merc-lima">+ Añadir lugar Lima</button>
            </div>
            <div style="flex:1;min-width:200px">
                <strong style="color:#0077b6">🌎 Lugares en Provincia</strong>
                <p style="font-size:.8rem;color:#666;margin:.2rem 0 .4rem">Agrega las ciudades de provincia para mercadería.</p>
                <div id="fe-merc-prov-lista" style="margin-bottom:.4rem"></div>
                <button class="wpcte-fe-btn-add wpcte-admin-only" id="fe-add-merc-prov">+ Añadir lugar Provincia</button>
            </div>
        </div>
        <?php endif; ?>
        <div class="wpcte-fe-card">
            <h3>🛋️ Mercadería Frecuente</h3>
            <p style="font-size:.82rem;color:#666;margin-bottom:.75rem">Precio fijo por producto.</p>
            <div id="fe-merc-contenido"></div>
            <?php if($es_admin): ?>
            <button class="wpcte-fe-btn-add wpcte-admin-only" id="fe-add-cat-merc">+ Añadir categoría</button>
            <div class="wpcte-fe-save-row wpcte-admin-only">
                <button class="wpcte-fe-save-btn" id="fe-save-merc">💾 Guardar Mercadería</button>
                <span class="wpcte-fe-notice" id="fe-notice-merc"></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- AÉREOS -->
    <div class="wpcte-fe-tab-content" id="fe-aereo">
        <div class="wpcte-fe-card">
            <h3>✈️ Rutas Aéreas</h3>
            <p style="font-size:.82rem;color:#666;margin-bottom:.75rem">Cada ruta tiene un origen (<strong>Desde</strong>) y sus destinos con precios.</p>
            <div id="fe-aereo-contenido"></div>
            <?php if($es_admin): ?>
            <button class="wpcte-fe-btn-add wpcte-admin-only" id="fe-add-ruta-aereo-fe">+ Añadir ruta (Desde)</button>
            <div class="wpcte-fe-save-row wpcte-admin-only">
                <button class="wpcte-fe-save-btn" id="fe-save-aereo">💾 Guardar Aéreos</button>
                <span class="wpcte-fe-notice" id="fe-notice-aereo"></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SOBRES -->
    <div class="wpcte-fe-tab-content" id="fe-sobres">
        <?php if($es_admin): ?>
        <div class="wpcte-fe-card" style="display:flex;gap:1.5rem;flex-wrap:wrap">
            <div style="flex:1;min-width:200px">
                <strong style="color:#0077b6">🏙️ Distritos de Lima</strong>
                <p style="font-size:.8rem;color:#666;margin:.2rem 0 .4rem">Lugares para envíos Lima→Lima y Lima→Provincia.</p>
                <div id="fe-sobres-lima-lista" style="margin-bottom:.4rem"></div>
                <button class="wpcte-fe-btn-add wpcte-admin-only" id="fe-add-sobre-lima">+ Añadir lugar Lima</button>
            </div>
            <div style="flex:1;min-width:200px">
                <strong style="color:#0077b6">🌎 Ciudades de Provincia</strong>
                <p style="font-size:.8rem;color:#666;margin:.2rem 0 .4rem">Lugares para Lima→Provincia y Provincia→Provincia.</p>
                <div id="fe-sobres-prov-lista" style="margin-bottom:.4rem"></div>
                <button class="wpcte-fe-btn-add wpcte-admin-only" id="fe-add-sobre-prov">+ Añadir lugar Provincia</button>
            </div>
        </div>
        <?php endif; ?>
        <div class="wpcte-fe-card">
            <h3>✉️ Tarifas de Sobres</h3>
            <table class="wpcte-fe-table" id="fet-sobres">
                <thead><tr>
                    <th>Tipo</th><th>Agencia (S/)</th><th>Domicilio (S/)</th><th>Devolución (S/)</th>
                    <?php if($es_admin): ?><th class="wpcte-admin-only">Estado</th><?php endif; ?>
                </tr></thead>
                <tbody></tbody>
            </table>
            <?php if($es_admin): ?>
            <div class="wpcte-fe-save-row wpcte-admin-only">
                <button class="wpcte-fe-save-btn" id="fe-save-sobres">💾 Guardar Sobres</button>
                <span class="wpcte-fe-notice" id="fe-notice-sobres"></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php else: ?>
    <!-- ═══════════════════ COTIZADOR FRONTEND ══════════════════ -->
    <div class="wpcte-fe-card">
        <h3>🧮 Simula el precio de tu envío</h3>

        <!-- Tipo de envío filter -->
        <div style="margin-bottom:1rem">
            <label style="font-size:.82rem;font-weight:600;color:#555;display:block;margin-bottom:3px">Tipo de Envío (filtra modalidades)</label>
            <select id="fe-cot-tipo" style="padding:6px 8px;border:1px solid #ccc;border-radius:8px;font-size:.88rem;width:100%;max-width:260px">
                <option value="">-- Todos --</option>
                <option value="puerta_puerta">Puerta a Puerta</option>
                <option value="agencia">Agencia</option>
                <option value="almacen">Almacén</option>
            </select>
        </div>

        <!-- Reutilizar el cotizador existente (se renderiza por wpcte_render_cotizador_html) -->
        <?php echo wpcte_render_cotizador_html( $tarifario, '', 'crear' ); ?>
    </div>
    <?php endif; ?>

</div><!-- #wpcte-fe-wrap -->

<script>
(function(){
// Esperar a que jQuery esté disponible antes de inicializar
function wpcteInit($){
'use strict';
var T  = <?php echo $tar_js; ?>;
var L  = <?php echo $lug_js; ?>;
var IS_ADMIN = <?php echo $es_admin ? 'true' : 'false'; ?>;
var AJAX     = '<?php echo esc_js($ajax); ?>';
var NONCE    = '<?php echo esc_js($nonce); ?>';
var WPCFE    = '<?php echo esc_js($wpcfe); ?>';


/* ── Tabs frontend ── */
$(document).on('click','.wpcte-fe-tab',function(){
    $('.wpcte-fe-tab').removeClass('active');
    $('.wpcte-fe-tab-content').removeClass('active');
    $(this).addClass('active');
    $('#'+$(this).data('tab')).addClass('active');
});

/* ── Helpers render ── */
function esc(v){return (v===null||v===undefined)?'':String(v).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');}
function num(v){return (v===null||v===undefined)?'':v;}
function inp(type,val,cls,extra){cls=cls||'';extra=extra||'';return '<input type="'+type+'" value="'+esc(val)+'" class="'+cls+'" '+extra+'>';}
function noAdm(html){return IS_ADMIN?html:'';}

/* ════════════════════════════════════════════════════════
   RENDER: Dentro de Lima
════════════════════════════════════════════════════════ */
function renderFeLimaVehs(){
    var $b=$('#fet-vehs tbody');$b.empty();
    var vehs=T.lima_lima.vehiculos||{};
    $.each(vehs,function(k,v){
        var r='<tr data-vkey="'+esc(k)+'">';
        if(IS_ADMIN){
            r+='<td>'+inp('text',v.label,'','data-f="vlabel"')+'<br><small style="color:#888">key: '+inp('text',k,'','data-f="vkey" style="width:100px;font-size:.78rem"')+'</small></td>';
            r+='<td>'+inp('number',v.precio_base,'p','data-f="vbase" min="0" step="1"')+'</td>';
            r+='<td class="wpcte-admin-only"><button class="wpcte-fe-btn-del fe-del-veh">✕</button></td>';
        } else {
            r+='<td><strong>'+esc(v.label)+'</strong></td>';
            r+='<td><span class="wpcte-fe-badge">S/ '+Number(v.precio_base).toFixed(2)+'</span></td>';
        }
        r+='</tr>';
        $b.append(r);
    });
}

function renderFeLimaHead(){
    var vehs=Object.keys(T.lima_lima.vehiculos||{});
    var $h=$('#fet-dist-head');$h.empty();$h.append('<th>Distrito</th>');
    vehs.forEach(function(v){
        var lbl=(T.lima_lima.vehiculos[v]&&T.lima_lima.vehiculos[v].label)||v;
        $h.append('<th>+'+esc(lbl)+' (S/)</th>');
    });
    if(IS_ADMIN)$h.append('<th class="wpcte-admin-only"></th>');
    var $hp=$('#fet-per-head');$hp.empty();$hp.append('<th>Zona</th>');
    vehs.forEach(function(v){
        var lbl=(T.lima_lima.vehiculos[v]&&T.lima_lima.vehiculos[v].label)||v;
        $hp.append('<th>Fijo '+esc(lbl)+' (S/)</th>');
    });
    if(IS_ADMIN)$hp.append('<th class="wpcte-admin-only"></th>');
}

function renderFeDist(filter){
    var $b=$('#fet-distritos tbody');$b.empty();
    var vehs=Object.keys(T.lima_lima.vehiculos||{});
    $.each(T.lima_lima.distritos||{},function(dist,precios){
        if(filter&&dist.toLowerCase().indexOf(filter.toLowerCase())===-1)return;
        var r='<tr data-dist="'+esc(dist)+'">';
        if(IS_ADMIN){
            r+='<td>'+inp('text',dist,'','data-f="dist"')+'</td>';
            vehs.forEach(function(v){r+='<td>'+inp('number',num(precios[v]),'p','data-veh="'+esc(v)+'" data-f="dp" min="0" step="0.5" placeholder="-"')+'</td>';});
            r+='<td class="wpcte-admin-only"><button class="wpcte-fe-btn-del fe-del-dist">✕</button></td>';
        } else {
            r+='<td><strong>'+esc(dist)+'</strong></td>';
            vehs.forEach(function(v){
                var val=precios[v];
                r+='<td>'+(val===null||val===undefined?'<span style="color:#ccc">—</span>':'<span class="wpcte-fe-badge">+S/ '+Number(val).toFixed(2)+'</span>')+'</td>';
            });
        }
        r+='</tr>';$b.append(r);
    });
}

function renderFePer(){
    var $b=$('#fet-perifericas tbody');$b.empty();
    var vehs=Object.keys(T.lima_lima.vehiculos||{});
    $.each(T.lima_lima.perifericas||{},function(zona,precios){
        var r='<tr data-zona="'+esc(zona)+'">';
        if(IS_ADMIN){
            r+='<td>'+inp('text',zona,'','data-f="zona"')+'</td>';
            vehs.forEach(function(v){
                var val=precios[v];
                r+='<td>'+inp('number',(val===null||val===undefined)?'':''+val,'p','data-veh="'+esc(v)+'" data-f="pp" min="0" step="0.5" placeholder="-"')+'</td>';
            });
            r+='<td class="wpcte-admin-only"><button class="wpcte-fe-btn-del fe-del-per">✕</button></td>';
        } else {
            r+='<td><strong>'+esc(zona)+'</strong> <span class="wpcte-fe-badge per">Periférica</span></td>';
            vehs.forEach(function(v){
                var val=precios[v];
                r+='<td>'+(val===null||val===undefined?'<span style="color:#ccc">—</span>':'<span class="wpcte-fe-badge per">S/ '+Number(val).toFixed(2)+'</span>')+'</td>';
            });
        }
        r+='</tr>';$b.append(r);
    });
}

function renderFeLima(){renderFeLimaVehs();renderFeLimaHead();renderFeDist();renderFePer();}

/* ════════════════════════════════════════════════════════
   RENDER: Carga General
════════════════════════════════════════════════════════ */
function renderFeCG(){
    var $w=$('#fe-carga-contenido');$w.empty();
    $.each(T.carga_general.rutas||{},function(origen,ruta){
        var b='<div style="margin-bottom:1.25rem" data-cg-origen="'+esc(origen)+'">';
        b+='<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem">';
        if(IS_ADMIN){
            b+='<strong style="color:#0077b6">Desde:</strong> <input type="text" value="'+esc(origen)+'" data-f="cg-origen" style="padding:3px 6px;border:1px solid #ccc;border-radius:6px;font-size:.85rem;width:140px">';
            b+='<button class="wpcte-fe-btn-del wpcte-admin-only fe-del-ruta-cg" style="margin-left:auto">✕ Quitar ruta</button>';
        } else {
            b+='<strong style="color:#0077b6;font-size:.95rem">Desde '+esc(origen)+'</strong>';
        }
        b+='</div>';
        b+='<div style="overflow-x:auto"><table class="wpcte-fe-table"><thead><tr>';
        b+='<th>Destino</th><th>Base (S/)</th><th>Agencia/kg</th><th>Domicilio/kg</th><th>x kilo 101-500</th><th>Lead</th>';
        if(IS_ADMIN) b+='<th class="wpcte-admin-only"></th>';
        b+='</tr></thead><tbody data-cg-tbody="'+esc(origen)+'">';
        $.each(ruta.destinos||{},function(dest,d){
            b+='<tr data-cg-dest="'+esc(dest)+'">';
            if(IS_ADMIN){
                b+='<td>'+inp('text',dest,'','data-f="cgd" style="width:140px"')+'</td>';
                b+='<td>'+inp('number',d.base,'p','data-f="base" min="0" step="0.5"')+'</td>';
                b+='<td>'+inp('number',d.agencia,'p','data-f="agencia" min="0" step="0.01"')+'</td>';
                b+='<td>'+inp('number',d.domicilio,'p','data-f="domicilio" min="0" step="0.01"')+'</td>';
                b+='<td>'+inp('number',d.x_kilo_101_500,'p','data-f="x101" min="0" step="0.01"')+'</td>';
                b+='<td>'+inp('text',d.lead,'','data-f="lead" style="width:80px"')+'</td>';
                b+='<td class="wpcte-admin-only"><button class="wpcte-fe-btn-del fe-del-cg-dest">✕</button></td>';
            } else {
                b+='<td><strong>'+esc(dest)+'</strong></td>';
                b+='<td>S/ '+Number(d.base).toFixed(2)+'</td>';
                b+='<td>S/ '+Number(d.agencia).toFixed(2)+'/kg</td>';
                b+='<td>S/ '+Number(d.domicilio).toFixed(2)+'/kg</td>';
                b+='<td>S/ '+Number(d.x_kilo_101_500).toFixed(2)+'/kg</td>';
                b+='<td><span class="wpcte-fe-badge">'+esc(d.lead)+'</span></td>';
            }
            b+='</tr>';
        });
        b+='</tbody></table></div>';
        if(IS_ADMIN) b+='<button class="wpcte-fe-btn-add wpcte-admin-only fe-add-cg-dest" data-origen="'+esc(origen)+'">+ Añadir destino</button>';
        b+='</div>';
        $w.append(b);
    });
}

/* ════════════════════════════════════════════════════════
   RENDER: Mercadería
════════════════════════════════════════════════════════ */
function renderFeMerc(){
    var $w=$('#fe-merc-contenido');$w.empty();
    $.each(T.mercaderia.categorias||{},function(catKey,cat){
        var b='<div style="margin-bottom:1.25rem" data-cat="'+esc(catKey)+'">';
        b+='<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem">';
        if(IS_ADMIN){
            b+='<strong style="color:#0077b6">Cat:</strong> <input type="text" value="'+esc(catKey)+'" data-f="cat-key" style="padding:3px 6px;border:1px solid #ccc;border-radius:6px;font-size:.83rem;width:120px;font-family:monospace">';
            b+='<input type="text" value="'+esc(cat.label)+'" data-f="cat-label" style="padding:3px 6px;border:1px solid #ccc;border-radius:6px;font-size:.85rem;width:180px" placeholder="Etiqueta">';
            b+='<button class="wpcte-fe-btn-del wpcte-admin-only fe-del-cat-merc" style="margin-left:auto">✕ Quitar</button>';
        } else {
            b+='<strong style="color:#0077b6;font-size:.95rem">'+esc(cat.label)+'</strong>';
        }
        b+='</div>';
        b+='<table class="wpcte-fe-table"><thead><tr><th>Producto</th><th>Precio (S/)</th>';
        if(IS_ADMIN) b+='<th class="wpcte-admin-only"></th>';
        b+='</tr></thead><tbody data-merc-tbody="'+esc(catKey)+'">';
        $.each(cat.items||{},function(prod,precio){
            b+='<tr data-prod="'+esc(prod)+'">';
            if(IS_ADMIN){
                b+='<td>'+inp('text',prod,'','data-f="pname" style="min-width:220px"')+'</td>';
                b+='<td>'+inp('number',precio,'p','data-f="pprice" min="0" step="1"')+'</td>';
                b+='<td class="wpcte-admin-only"><button class="wpcte-fe-btn-del fe-del-prod-merc">✕</button></td>';
            } else {
                b+='<td>'+esc(prod)+'</td><td><span class="wpcte-fe-badge">S/ '+Number(precio).toFixed(2)+'</span></td>';
            }
            b+='</tr>';
        });
        b+='</tbody></table>';
        if(IS_ADMIN) b+='<button class="wpcte-fe-btn-add wpcte-admin-only fe-add-prod-merc" data-cat="'+esc(catKey)+'">+ Añadir producto</button>';
        b+='</div>';
        $w.append(b);
    });
}

/* ════════════════════════════════════════════════════════
   RENDER: Aéreos
════════════════════════════════════════════════════════ */
function renderFeAereo(){
    var $w=$('#fe-aereo-contenido');$w.empty();
    var rutas=T.aereo.rutas||{};
    /* Fallback: si aún usa estructura vieja T.aereo.destinos, no mostrar nada */
    if(!Object.keys(rutas).length&&T.aereo.destinos&&Object.keys(T.aereo.destinos).length){
        $w.html('<p style="color:#888;font-size:.85rem">Migra los destinos aéreos al nuevo formato (Desde → Destinos) desde el admin de WordPress.</p>');
        return;
    }
    $.each(rutas,function(desde,ruta){
        var b='<div style="margin-bottom:1.25rem" data-aereo-desde="'+esc(desde)+'">';
        b+='<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem">';
        if(IS_ADMIN){
            b+='<strong style="color:#0077b6">Desde:</strong> <input type="text" value="'+esc(desde)+'" data-f="aer-desde" style="padding:3px 6px;border:1px solid #ccc;border-radius:6px;font-size:.85rem;width:140px">';
            b+='<button class="wpcte-fe-btn-del wpcte-admin-only fe-del-ruta-aereo" style="margin-left:auto">✕ Quitar ruta</button>';
        } else {
            b+='<strong style="color:#0077b6;font-size:.95rem">Desde '+esc(desde)+'</strong>';
        }
        b+='</div>';
        b+='<div style="overflow-x:auto"><table class="wpcte-fe-table"><thead><tr>';
        b+='<th>Destino</th><th>Zona</th><th>Base (S/)</th><th>Exceso/kg</th><th>1kg (S/)</th><th>Lead</th>';
        if(IS_ADMIN) b+='<th class="wpcte-admin-only"></th>';
        b+='</tr></thead><tbody data-aereo-tbody="'+esc(desde)+'">';
        $.each(ruta.destinos||{},function(dest,d){
            b+='<tr data-aer-dest="'+esc(dest)+'">';
            if(IS_ADMIN){
                b+='<td>'+inp('text',dest,'','data-f="aer-dest" style="width:130px"')+'</td>';
                b+='<td>'+inp('text',d.zona||'','','data-f="zona" style="width:110px"')+'</td>';
                b+='<td>'+inp('number',d.base_kg,'p','data-f="base_kg" min="0" step="0.5"')+'</td>';
                b+='<td>'+inp('number',d.exceso_kg,'p','data-f="exceso_kg" min="0" step="0.5"')+'</td>';
                b+='<td>'+inp('number',d.precio_1kg,'p','data-f="precio_1kg" min="0" step="0.5"')+'</td>';
                b+='<td>'+inp('text',d.lead||'','','data-f="lead" style="width:80px"')+'</td>';
                b+='<td class="wpcte-admin-only"><button class="wpcte-fe-btn-del fe-del-aer-dest">✕</button></td>';
            } else {
                b+='<td><strong>'+esc(dest)+'</strong></td>';
                b+='<td><span class="wpcte-fe-badge">'+esc(d.zona||'')+'</span></td>';
                b+='<td>S/ '+Number(d.base_kg).toFixed(2)+'</td>';
                b+='<td>S/ '+Number(d.exceso_kg).toFixed(2)+'/kg</td>';
                b+='<td><span class="wpcte-fe-badge">S/ '+Number(d.precio_1kg).toFixed(2)+'</span></td>';
                b+='<td><span class="wpcte-fe-badge">'+esc(d.lead||'')+'</span></td>';
            }
            b+='</tr>';
        });
        b+='</tbody></table></div>';
        if(IS_ADMIN) b+='<button class="wpcte-fe-btn-add wpcte-admin-only fe-add-aer-dest">+ Añadir destino</button>';
        b+='</div>';
        $w.append(b);
    });
}

/* ════════════════════════════════════════════════════════
   RENDER: Sobres
════════════════════════════════════════════════════════ */
/* ════════════════════════════════════════════════════════
   RENDER: Listas de lugares para Sobres (admin)
════════════════════════════════════════════════════════ */
function renderFeSobreLugar(tipo){
    var arr=tipo==='lima'?(T.sobres.lugares_lima||[]):(T.sobres.lugares_provincia||[]);
    var wrapId=tipo==='lima'?'fe-sobres-lima-lista':'fe-sobres-prov-lista';
    var $w=$('#'+wrapId);if(!$w.length)return;$w.empty();
    arr.forEach(function(lugar,i){
        $w.append('<div style="display:flex;gap:.35rem;align-items:center;margin-bottom:.3rem">'
            +'<input type="text" value="'+esc(lugar)+'" class="wpcte-fe-input-sm" style="flex:1;padding:4px 8px;border:1px solid #ccc;border-radius:6px;font-size:.86rem" data-sobre-lugar-tipo="'+tipo+'" data-sobre-lugar-idx="'+i+'">'
            +'<button class="wpcte-fe-btn-del fe-del-sobre-lugar" data-tipo="'+tipo+'" data-idx="'+i+'">✕</button>'
            +'</div>');
    });
}
function renderFeSobreLugares(){renderFeSobreLugar('lima');renderFeSobreLugar('provincia');}

var SOBRE_TIPOS_FE=[
    {key:'Lima - Lima',           label:'Lima → Lima',           orig:'lima',     dest:'lima'},
    {key:'Lima - Provincia',      label:'Lima → Provincia',      orig:'lima',     dest:'provincia'},
    {key:'Provincia - Provincia', label:'Provincia → Provincia', orig:'provincia',dest:'provincia'}
];

function renderFeSobres(){
    /* Listas de lugares */
    renderFeSobreLugares();
    /* 3 tipos fijos */
    var $b=$('#fet-sobres tbody');$b.empty();
    SOBRE_TIPOS_FE.forEach(function(meta){
        var d=(T.sobres.tarifas&&T.sobres.tarifas[meta.key])||{activo:true,agencia:0,domicilio:0,devolucion:0};
        var activo=d.activo!==false;
        if(!activo&&!IS_ADMIN)return;
        var r='<tr data-stipo="'+esc(meta.key)+'" style="'+(activo?'':'opacity:.5')+'">';
        if(IS_ADMIN){
            r+='<td><strong>'+esc(meta.label)+'</strong>'+(activo?'':' <em style="color:#aaa;font-size:.75rem">(inactivo)</em>')+'</td>';
            r+='<td>'+inp('number',d.agencia,'p','data-f="agencia" min="0" step="0.5"')+'</td>';
            r+='<td>'+inp('number',d.domicilio,'p','data-f="domicilio" min="0" step="0.5"')+'</td>';
            r+='<td>'+inp('number',d.devolucion,'p','data-f="devolucion" min="0" step="0.5"')+'</td>';
            r+='<td class="wpcte-admin-only"><label style="cursor:pointer;font-size:.8rem">'
             +'<input type="checkbox" class="fe-sobre-activo" data-tipo="'+esc(meta.key)+'"'+(activo?' checked':'')+' style="margin-right:.2rem">'
             +(activo?'Activo':'Inactivo')+'</label></td>';
        } else {
            r+='<td><strong>'+esc(meta.label)+'</strong></td>';
            r+='<td><span class="wpcte-fe-badge">S/ '+Number(d.agencia).toFixed(2)+'</span></td>';
            r+='<td><span class="wpcte-fe-badge">S/ '+Number(d.domicilio).toFixed(2)+'</span></td>';
            r+='<td><span class="wpcte-fe-badge">S/ '+Number(d.devolucion).toFixed(2)+'</span></td>';
        }
        r+='</tr>';$b.append(r);
    });
}

/* ════════════════════════════════════════════════════════
   HELPERS: leer T desde DOM antes de mutarlo
   (evita que al añadir/eliminar se pierdan ediciones previas)
════════════════════════════════════════════════════════ */
function sincronizarT(){
    /* igual que recopilarFeT pero sin limpiar el buscador */
    var $srch=$('#fe-search-dist');
    var filtroActivo=$srch.val();
    if(filtroActivo){$srch.val('');renderFeDist();}

    var nv={};
    $('#fet-vehs tbody tr').each(function(){
        var k=(IS_ADMIN?$('input[data-f="vkey"]',this).val():$(this).data('vkey'))||'';
        k=k.trim().replace(/\s+/g,'_');if(!k)return;
        nv[k]={label:($('input[data-f="vlabel"]',this).val()||'').trim()||k,precio_base:parseFloat($('input[data-f="vbase"]',this).val())||0};
    });
    if(Object.keys(nv).length||$('#fet-vehs tbody tr').length)T.lima_lima.vehiculos=nv;

    var nd={};
    $('#fet-distritos tbody tr').each(function(){
        var d=($('input[data-f="dist"]',this).val()||$(this).data('dist')||'').trim().toUpperCase();if(!d)return;
        var p={};$('input[data-f="dp"]',this).each(function(){var v=$(this).val();p[$(this).data('veh')]=(v===''||v==='-')?null:parseFloat(v);});
        nd[d]=p;
    });
    T.lima_lima.distritos=nd;

    var np={};
    $('#fet-perifericas tbody tr').each(function(){
        var z=($('input[data-f="zona"]',this).val()||$(this).data('zona')||'').trim().toUpperCase();if(!z)return;
        var p={};$('input[data-f="pp"]',this).each(function(){var v=$(this).val();p[$(this).data('veh')]=(v===''||v==='-')?null:parseFloat(v);});
        np[z]=p;
    });
    T.lima_lima.perifericas=np;

    var nr={};
    $('[data-cg-origen]').each(function(){
        var on=$(this).data('cg-origen');
        var nn=($('input[data-f="cg-origen"]',this).val()||on||'').trim().toUpperCase();if(!nn)return;
        var tituloOrig=T.carga_general.rutas[on]?T.carga_general.rutas[on].titulo:'';
        var titulo=($('input[data-f="cg-titulo"]',this).val()||tituloOrig).trim();
        var ds={};
        $('[data-cg-tbody] tr',this).each(function(){
            var dn=($('input[data-f="cgd"]',this).val()||$(this).data('cg-dest')||'').trim().toUpperCase();if(!dn)return;
            ds[dn]={base:parseFloat($('[data-f="base"]',this).val())||0,agencia:parseFloat($('[data-f="agencia"]',this).val())||0,domicilio:parseFloat($('[data-f="domicilio"]',this).val())||0,vol_0_100:parseFloat($('[data-f="vol_0_100"]',this).val())||0,x_kilo_101_500:parseFloat($('[data-f="x101"]',this).val())||0,vol_101_500:parseFloat($('[data-f="vol_101_500"]',this).val())||0,lead:($('[data-f="lead"]',this).val()||'').trim()||'24 HORAS'};
        });
        nr[nn]={titulo:titulo,destinos:ds};
    });
    T.carga_general.rutas=nr;

    /* Mercadería — 2 pasos para evitar selector ambiguo */
    /* PASO 1: sync DOM → T para nombres/precios de productos existentes */
    $('[data-cat]').each(function(){
        var $blk=$(this);
        var ko=$blk.data('cat');
        if(!T.mercaderia||!T.mercaderia.categorias||!T.mercaderia.categorias[ko])return;
        /* Asegurar que items es objeto, no array */
        if(Array.isArray(T.mercaderia.categorias[ko].items))T.mercaderia.categorias[ko].items={};
        var newItems={};
        $blk.find('tbody[data-merc-tbody] tr').each(function(){
            var pn=IS_ADMIN?($('input[data-f="pname"]',this).val()||'').trim():$(this).data('prod')||'';
            var pp=parseFloat($('input[data-f="pprice"]',this).val())||0;
            if(pn)newItems[pn]=pp;
        });
        /* Solo sobreescribir si hay items en el DOM (evitar borrar al guardar sin haber visto el tab) */
        if(Object.keys(newItems).length){
            T.mercaderia.categorias[ko].items=newItems;
        }
    });
    /* PASO 2: construir nc desde T (ya actualizado) */
    var nc={};
    $('[data-cat]').each(function(){
        var $blk=$(this);
        var ko=$blk.data('cat');
        var kn=($('input[data-f="cat-key"]',$blk).val()||ko||'').trim();if(!kn)return;
        var cat=T.mercaderia&&T.mercaderia.categorias?T.mercaderia.categorias[ko]||{}:{};
        var lblOrig=cat.label||'';
        var lbl=($('input[data-f="cat-label"]',$blk).val()||lblOrig).trim();
        nc[kn]={label:lbl,items:cat.items||{},rutas:cat.rutas||[]};
    });
    T.mercaderia.categorias=nc;
    /* Leer inputs individuales de lugares */
    if($('#fe-merc-lima-lista').length){
        T.mercaderia.lugares_lima=[];
        $('#fe-merc-lima-lista input').each(function(){var v=$(this).val().trim().toUpperCase();if(v)T.mercaderia.lugares_lima.push(v);});
        T.mercaderia.lugares_provincia=[];
        $('#fe-merc-prov-lista input').each(function(){var v=$(this).val().trim().toUpperCase();if(v)T.mercaderia.lugares_provincia.push(v);});
    }

    /* Aereo: sincronizar SOLO si el tab está visible (evita borrar ediciones) */
    if(IS_ADMIN&&$('#fe-aereo').hasClass('active')&&$('#fe-aereo-contenido').length){
        var newRutas={};
        $('#fe-aereo-contenido [data-aereo-desde]').each(function(){
            var $blk=$(this);
            var desdeOrig=$blk.data('aereo-desde');
            var desdeNew=($('input[data-f="aer-desde"]',$blk).val()||desdeOrig).trim().toUpperCase();
            if(!desdeNew)return;
            var destinos={};
            $blk.find('tbody tr').each(function(){
                var dn=($('input[data-f="aer-dest"]',this).val()||$(this).data('aer-dest')||'').trim().toUpperCase();
                if(!dn)return;
                destinos[dn]={
                    zona:($('input[data-f="zona"]',this).val()||'').trim(),
                    base_kg:parseFloat($('input[data-f="base_kg"]',this).val())||0,
                    exceso_kg:parseFloat($('input[data-f="exceso_kg"]',this).val())||0,
                    precio_1kg:parseFloat($('input[data-f="precio_1kg"]',this).val())||0,
                    lead:($('input[data-f="lead"]',this).val()||'').trim()||'48 HORAS'
                };
            });
            newRutas[desdeNew]={destinos:destinos};
        });
        if(Object.keys(newRutas).length)T.aereo.rutas=newRutas;
    }

    var ns={};
    $('#fet-sobres tbody tr').each(function(){
        var tn=($('input[data-f="stipo"]',this).val()||$(this).data('stipo')||'').trim();if(!tn)return;
        ns[tn]={origen:($('[data-f="sorigen"]',this).val()||'').trim(),destino:($('[data-f="sdestino"]',this).val()||'').trim(),agencia:parseFloat($('[data-f="agencia"]',this).val())||0,domicilio:parseFloat($('[data-f="domicilio"]',this).val())||0,devolucion:parseFloat($('[data-f="devolucion"]',this).val())||0};
    });
    T.sobres.tarifas=ns;

    if(filtroActivo){$srch.val(filtroActivo);renderFeDist(filtroActivo);}
}

/* ════════════════════════════════════════════════════════
   Actualizar selects del cotizador cuando T cambia
════════════════════════════════════════════════════════ */
function refrescarSelectsCotizador(){
    /* Vehículos */
    var vehSel=document.getElementById('wpcte-veh');
    if(vehSel){
        var vAct=vehSel.value;
        while(vehSel.options.length)vehSel.remove(0);
        Object.keys(T.lima_lima.vehiculos||{}).forEach(function(k){
            var op=document.createElement('option');op.value=k;op.textContent=T.lima_lima.vehiculos[k].label||k;vehSel.appendChild(op);
        });
        if(Array.from(vehSel.options).some(function(o){return o.value===vAct;}))vehSel.value=vAct;
    }
    /* Origen/Destino lima (según modalidad activa) */
    var modActual=document.getElementById('wpcte-mod');
    if(modActual&&modActual.value==='lima_lima'){
        repoblarSelect('wpcte-orig',getLimaOpts(),true);
        repoblarSelect('wpcte-dest',getLimaOpts(),true);
    }
    /* CG origen */
    repoblarSelect('wpcte-orig-cg',getCGOrigenes(),true);
    /* Aéreo destinos */
    if(modActual&&modActual.value==='aereo') repoblarSelect('wpcte-dest',getAereoDestinos(),true);
    /* Mercadería categorías */
    var catSel2=document.getElementById('wpcte-merc-cat');
    if(catSel2){
        var cAct=catSel2.value;
        while(catSel2.options.length>1)catSel2.remove(1);
        Object.keys(T.mercaderia.categorias||{}).forEach(function(k){
            var op=document.createElement('option');op.value=k;op.textContent=T.mercaderia.categorias[k].label||k;catSel2.appendChild(op);
        });
        if(Array.from(catSel2.options).some(function(o){return o.value===cAct;}))catSel2.value=cAct;
    }
    /* Sobres */
    poblarSobreOrigenes();
}

/* ════════════════════════════════════════════════════════
   BOTONES ADMIN — Añadir / Eliminar
   Siempre sincronizar T desde DOM primero, luego mutar, luego re-render
════════════════════════════════════════════════════════ */
if(IS_ADMIN){
    /* Lima - Vehículos */
    $(document).on('click','#fe-add-veh',function(){
        sincronizarT();
        var base=T.lima_lima.vehiculos||{};
        var k='nuevo_veh_'+(Object.keys(base).length+1);
        while(base[k])k+='_';
        base[k]={label:'Nuevo Vehículo',precio_base:20};
        T.lima_lima.vehiculos=base;
        renderFeLima();refrescarSelectsCotizador();
    });
    $(document).on('click','.fe-del-veh',function(){
        sincronizarT();
        var k=$(this).closest('tr').data('vkey');
        delete T.lima_lima.vehiculos[k];
        renderFeLima();refrescarSelectsCotizador();
    });
    /* Lima - Distritos */
    $(document).on('click','#fe-add-dist',function(){
        sincronizarT();
        var base=T.lima_lima.distritos||{};
        var k='NUEVO DISTRITO';var n=1;
        while(base[k])k='NUEVO DISTRITO '+(++n);
        base[k]={};T.lima_lima.distritos=base;
        renderFeLima();
    });
    $(document).on('click','.fe-del-dist',function(){
        sincronizarT();
        var d=$(this).closest('tr').data('dist');
        delete T.lima_lima.distritos[d];
        renderFeDist();
    });
    /* Lima - Periféricas */
    $(document).on('click','#fe-add-per',function(){
        sincronizarT();
        var base=T.lima_lima.perifericas||{};
        var k='NUEVA ZONA';var n=1;
        while(base[k])k='NUEVA ZONA '+(++n);
        base[k]={};T.lima_lima.perifericas=base;
        renderFeLima();
    });
    $(document).on('click','.fe-del-per',function(){
        sincronizarT();
        var z=$(this).closest('tr').data('zona');
        delete T.lima_lima.perifericas[z];
        renderFePer();
    });
    /* Búsqueda distritos */
    $(document).on('input','#fe-search-dist',function(){renderFeDist($(this).val());});
    /* CG - Añadir ruta */
    $(document).on('click','#fe-add-ruta-cg',function(){
        sincronizarT();
        var base=T.carga_general.rutas||{};
        var k='NUEVO ORIGEN';var n=1;
        while(base[k])k='NUEVO ORIGEN '+(++n);
        base[k]={titulo:'Nueva ruta',destinos:{}};
        T.carga_general.rutas=base;
        renderFeCG();refrescarSelectsCotizador();
    });
    $(document).on('click','.fe-del-ruta-cg',function(){
        sincronizarT();
        var o=$(this).closest('[data-cg-origen]').data('cg-origen');
        delete T.carga_general.rutas[o];
        renderFeCG();refrescarSelectsCotizador();
    });
    /* CG - Añadir destino */
    $(document).on('click','.fe-add-cg-dest',function(){
        sincronizarT();
        /* Leer origen actual del input (puede haber cambiado) */
        var $bloque=$(this).closest('[data-cg-origen]');
        var origenActual=($('input[data-f="cg-origen"]',$bloque).val()||'').trim().toUpperCase()||$bloque.data('cg-origen');
        var rutas=T.carga_general.rutas;
        if(!rutas[origenActual]){
            /* Si renombró, buscar por posición */
            origenActual=$bloque.data('cg-origen');
            if(!rutas[origenActual])return;
        }
        var destinos=rutas[origenActual].destinos||{};
        var dk='NUEVO DESTINO';var n=1;
        while(destinos[dk])dk='NUEVO DESTINO '+(++n);
        destinos[dk]={base:10,agencia:1,domicilio:1.5,vol_0_100:2,x_kilo_101_500:1,vol_101_500:2,lead:'24 HORAS'};
        rutas[origenActual].destinos=destinos;
        renderFeCG();
    });
    $(document).on('click','.fe-del-cg-dest',function(){
        sincronizarT();
        var o=$(this).closest('[data-cg-tbody]').data('cg-tbody');
        var d=$(this).closest('tr').data('cg-dest');
        if(T.carga_general.rutas[o])delete T.carga_general.rutas[o].destinos[d];
        renderFeCG();
    });
    /* Lugares de Mercadería — inputs individuales */
    function renderFeMercLugar(tipo){
        var arr=tipo==='lima'?(T.mercaderia.lugares_lima||[]):(T.mercaderia.lugares_provincia||[]);
        var wrapId=tipo==='lima'?'fe-merc-lima-lista':'fe-merc-prov-lista';
        var $w=$('#'+wrapId);if(!$w.length)return;$w.empty();
        arr.forEach(function(lugar,i){
            $w.append('<div style="display:flex;gap:.35rem;align-items:center;margin-bottom:.3rem" data-fe-ml-tipo="'+tipo+'" data-fe-ml-idx="'+i+'">'
                +'<input type="text" value="'+lugar+'" class="wpcte-fe-input-sm" style="flex:1;padding:4px 8px;border:1px solid #ccc;border-radius:6px;font-size:.86rem">'
                +'<button class="wpcte-fe-btn-del fe-del-merc-lugar" data-tipo="'+tipo+'" data-idx="'+i+'">✕</button>'
                +'</div>');
        });
    }
    function renderFeMercLugares(){renderFeMercLugar('lima');renderFeMercLugar('provincia');}
    renderFeMercLugares();

    $(document).on('click','#fe-add-merc-lima',function(){
        sincronizarT();
        if(!T.mercaderia.lugares_lima)T.mercaderia.lugares_lima=[];
        T.mercaderia.lugares_lima.push('');renderFeMercLugar('lima');
        $('#fe-merc-lima-lista input').last().focus();
    });
    $(document).on('click','#fe-add-merc-prov',function(){
        sincronizarT();
        if(!T.mercaderia.lugares_provincia)T.mercaderia.lugares_provincia=[];
        T.mercaderia.lugares_provincia.push('');renderFeMercLugar('provincia');
        $('#fe-merc-prov-lista input').last().focus();
    });
    $(document).on('click','.fe-del-merc-lugar',function(){
        sincronizarT();
        var tipo=$(this).data('tipo');
        var idx=$(this).data('idx');
        if(tipo==='lima')T.mercaderia.lugares_lima.splice(idx,1);
        else T.mercaderia.lugares_provincia.splice(idx,1);
        renderFeMercLugar(tipo);
    });

    /* Mercadería - Añadir categoría */
    $(document).on('click','#fe-add-cat-merc',function(){
        sincronizarT();
        var base=T.mercaderia.categorias||{};
        var k='NUEVA_CAT';var n=1;
        while(base[k])k='NUEVA_CAT_'+(++n);
        base[k]={label:'Nueva Categoría',items:{}};
        T.mercaderia.categorias=base;
        renderFeMerc();refrescarSelectsCotizador();
    });
    $(document).on('click','.fe-del-cat-merc',function(){
        sincronizarT();
        var k=$(this).closest('[data-cat]').data('cat');
        delete T.mercaderia.categorias[k];
        renderFeMerc();refrescarSelectsCotizador();
    });
    /* Mercadería - Añadir producto */
    $(document).on('click','.fe-add-prod-merc',function(){
        var $bloque=$(this).closest('[data-cat]');
        var catOrig=$bloque.data('cat');
        /* Primero leer DOM del tbody de ESTA categoría → actualizar T */
        var newItems={};
        $bloque.find('tbody[data-merc-tbody] tr').each(function(){
            var pn=($('input[data-f="pname"]',this).val()||$(this).data('prod')||'').trim();
            var pp=parseFloat($('input[data-f="pprice"]',this).val())||0;
            if(pn)newItems[pn]=pp;
        });
        if(!T.mercaderia.categorias[catOrig])return;
        if(Object.keys(newItems).length)T.mercaderia.categorias[catOrig].items=newItems;
        /* Ahora agregar el nuevo producto */
        var items=T.mercaderia.categorias[catOrig].items;
        if(!items||Array.isArray(items))items={};
        var pk='Nuevo producto';var n=1;
        while(items[pk]!==undefined)pk='Nuevo producto '+(++n);
        items[pk]=0;
        T.mercaderia.categorias[catOrig].items=items;
        renderFeMerc();
    });
    $(document).on('click','.fe-del-prod-merc',function(){
        sincronizarT();
        var k=$(this).closest('[data-merc-tbody]').data('merc-tbody');
        var p=$(this).closest('tr').data('prod');
        if(T.mercaderia.categorias[k])delete T.mercaderia.categorias[k].items[p];
        renderFeMerc();
    });
    /* Aéreos — nueva estructura rutas */
    $(document).on('click','#fe-add-ruta-aereo-fe',function(){
        sincronizarT();
        if(!T.aereo.rutas)T.aereo.rutas={};
        var k='NUEVO ORIGEN';var n=1;
        while(T.aereo.rutas[k])k='NUEVO ORIGEN '+(++n);
        T.aereo.rutas[k]={destinos:{}};
        renderFeAereo();
    });
    $(document).on('click','.fe-del-ruta-aereo',function(){
        var $bloque=$(this).closest('[data-aereo-desde]');
        var desdeKey=$('input[data-f="aer-desde"]',$bloque).val().trim().toUpperCase()||$bloque.data('aereo-desde');
        var desdeOrig=$bloque.data('aereo-desde');
        if(T.aereo.rutas){
            if(desdeOrig)delete T.aereo.rutas[desdeOrig];
            if(desdeKey&&desdeKey!==desdeOrig)delete T.aereo.rutas[desdeKey];
        }
        renderFeAereo();
    });
    $(document).on('click','.fe-add-aer-dest',function(){
        var $bloque=$(this).closest('[data-aereo-desde]');
        /* Leer nombre actual del input — sin sincronizarT */
        var desdeKey=$('input[data-f="aer-desde"]',$bloque).val().trim().toUpperCase()||$bloque.data('aereo-desde');
        if(!T.aereo.rutas)T.aereo.rutas={};
        /* Leer destinos actuales del DOM de este bloque */
        var destinos={};
        $bloque.find('tbody tr').each(function(){
            var dn=($('input[data-f="aer-dest"]',this).val()||$(this).data('aer-dest')||'').trim().toUpperCase();
            if(!dn)return;
            destinos[dn]={
                zona:($('input[data-f="zona"]',this).val()||'').trim(),
                base_kg:parseFloat($('input[data-f="base_kg"]',this).val())||0,
                exceso_kg:parseFloat($('input[data-f="exceso_kg"]',this).val())||0,
                precio_1kg:parseFloat($('input[data-f="precio_1kg"]',this).val())||0,
                lead:($('input[data-f="lead"]',this).val()||'').trim()||'48 HORAS'
            };
        });
        /* Añadir nuevo destino */
        var dk='NUEVO';var n=1;
        while(destinos[dk]!==undefined)dk='NUEVO_'+(++n);
        destinos[dk]={zona:'',base_kg:15,exceso_kg:6,precio_1kg:21,lead:'48 HORAS'};
        /* Si el desde cambió de nombre, eliminar la key vieja */
        var desdeOrig=$bloque.data('aereo-desde');
        if(desdeOrig&&desdeOrig!==desdeKey)delete T.aereo.rutas[desdeOrig];
        T.aereo.rutas[desdeKey]={destinos:destinos};
        renderFeAereo();
    });
    $(document).on('click','.fe-del-aer-dest',function(){
        var $bloque=$(this).closest('[data-aereo-desde]');
        var desdeKey=$('input[data-f="aer-desde"]',$bloque).val().trim().toUpperCase()||$bloque.data('aereo-desde');
        var dest=$(this).closest('tr').data('aer-dest');
        /* Leer destinos actuales del DOM, quitar este */
        var destinos={};
        $bloque.find('tbody tr').each(function(){
            var dn=($('input[data-f="aer-dest"]',this).val()||$(this).data('aer-dest')||'').trim().toUpperCase();
            if(!dn||dn===dest.toUpperCase())return;
            destinos[dn]={
                zona:($('input[data-f="zona"]',this).val()||'').trim(),
                base_kg:parseFloat($('input[data-f="base_kg"]',this).val())||0,
                exceso_kg:parseFloat($('input[data-f="exceso_kg"]',this).val())||0,
                precio_1kg:parseFloat($('input[data-f="precio_1kg"]',this).val())||0,
                lead:($('input[data-f="lead"]',this).val()||'').trim()||'48 HORAS'
            };
        });
        var desdeOrig=$bloque.data('aereo-desde');
        if(desdeOrig&&desdeOrig!==desdeKey)delete T.aereo.rutas[desdeOrig];
        if(!T.aereo.rutas)T.aereo.rutas={};
        T.aereo.rutas[desdeKey]={destinos:destinos};
        renderFeAereo();
    });
    /* Sobres — lugares Lima */
    $(document).on('click','#fe-add-sobre-lima',function(){
        sincronizarT();
        if(!T.sobres.lugares_lima)T.sobres.lugares_lima=[];
        T.sobres.lugares_lima.push('');renderFeSobreLugar('lima');
        $('#fe-sobres-lima-lista input').last().focus();
    });
    $(document).on('click','#fe-add-sobre-prov',function(){
        sincronizarT();
        if(!T.sobres.lugares_provincia)T.sobres.lugares_provincia=[];
        T.sobres.lugares_provincia.push('');renderFeSobreLugar('provincia');
        $('#fe-sobres-prov-lista input').last().focus();
    });
    $(document).on('click','.fe-del-sobre-lugar',function(){
        sincronizarT();
        var tipo=$(this).data('tipo');var idx=$(this).data('idx');
        if(tipo==='lima')T.sobres.lugares_lima.splice(idx,1);
        else T.sobres.lugares_provincia.splice(idx,1);
        renderFeSobreLugar(tipo);
    });
    /* Sobres — activo/inactivo */
    $(document).on('change','.fe-sobre-activo',function(){
        var key=$(this).data('tipo');
        if(!T.sobres.tarifas)T.sobres.tarifas={};
        if(!T.sobres.tarifas[key])T.sobres.tarifas[key]={};
        T.sobres.tarifas[key].activo=$(this).prop('checked');
        renderFeSobres();
    });
}

/* ════════════════════════════════════════════════════════
   GUARDAR — recopilar DOM → T → AJAX
════════════════════════════════════════════════════════ */
function recopilarFeT(){
    /* Reutilizar sincronizarT (incluye limpieza del buscador) */
    var $srch=$('#fe-search-dist');
    if($srch.val()){$srch.val('');renderFeDist();}

    var nv={};
    $('#fet-vehs tbody tr').each(function(){
        var k=(IS_ADMIN?$('input[data-f="vkey"]',this).val():$(this).data('vkey'))||'';
        k=k.trim().replace(/\s+/g,'_');if(!k)return;
        var lbl=IS_ADMIN?($('input[data-f="vlabel"]',this).val()||k).trim():(T.lima_lima.vehiculos[k]?T.lima_lima.vehiculos[k].label:k);
        var base=IS_ADMIN?parseFloat($('input[data-f="vbase"]',this).val())||0:(T.lima_lima.vehiculos[k]?T.lima_lima.vehiculos[k].precio_base:0);
        nv[k]={label:lbl,precio_base:base};
    });
    T.lima_lima.vehiculos=nv;

    var nd={};
    $('#fet-distritos tbody tr').each(function(){
        var d=(IS_ADMIN?$('input[data-f="dist"]',this).val():$(this).data('dist'))||'';
        d=d.trim().toUpperCase();if(!d)return;
        var p={};$('input[data-f="dp"]',this).each(function(){var v=$(this).val();p[$(this).data('veh')]=(v===''||v==='-')?null:parseFloat(v);});
        nd[d]=p;
    });
    T.lima_lima.distritos=nd;

    var np={};
    $('#fet-perifericas tbody tr').each(function(){
        var z=(IS_ADMIN?$('input[data-f="zona"]',this).val():$(this).data('zona'))||'';
        z=z.trim().toUpperCase();if(!z)return;
        var p={};$('input[data-f="pp"]',this).each(function(){var v=$(this).val();p[$(this).data('veh')]=(v===''||v==='-')?null:parseFloat(v);});
        np[z]=p;
    });
    T.lima_lima.perifericas=np;

    var nr={};
    $('[data-cg-origen]').each(function(){
        var on=$(this).data('cg-origen');
        var nn=IS_ADMIN?($('input[data-f="cg-origen"]',this).val()||on||'').trim().toUpperCase():on;
        if(!nn)return;
        var tituloOrig=T.carga_general.rutas[on]?T.carga_general.rutas[on].titulo:'';
        var titulo=IS_ADMIN?($('input[data-f="cg-titulo"]',this).val()||tituloOrig).trim():tituloOrig;
        var ds={};
        $('[data-cg-tbody] tr',this).each(function(){
            var dn=IS_ADMIN?($('input[data-f="cgd"]',this).val()||'').trim().toUpperCase():$(this).data('cg-dest');
            if(!dn)return;
            ds[dn]={base:parseFloat($('[data-f="base"]',this).val())||0,agencia:parseFloat($('[data-f="agencia"]',this).val())||0,domicilio:parseFloat($('[data-f="domicilio"]',this).val())||0,vol_0_100:parseFloat($('[data-f="vol_0_100"]',this).val())||0,x_kilo_101_500:parseFloat($('[data-f="x101"]',this).val())||0,vol_101_500:parseFloat($('[data-f="vol_101_500"]',this).val())||0,lead:($('[data-f="lead"]',this).val()||'').trim()||'24 HORAS'};
        });
        nr[nn]={titulo:titulo,destinos:ds};
    });
    T.carga_general.rutas=nr;

    /* Mercadería — 2 pasos para evitar selector ambiguo */
    /* PASO 1: sync DOM → T para nombres/precios de productos existentes */
    $('[data-cat]').each(function(){
        var $blk=$(this);
        var ko=$blk.data('cat');
        if(!T.mercaderia||!T.mercaderia.categorias||!T.mercaderia.categorias[ko])return;
        /* Asegurar que items es objeto, no array */
        if(Array.isArray(T.mercaderia.categorias[ko].items))T.mercaderia.categorias[ko].items={};
        var newItems={};
        $blk.find('tbody[data-merc-tbody] tr').each(function(){
            var pn=IS_ADMIN?($('input[data-f="pname"]',this).val()||'').trim():$(this).data('prod')||'';
            var pp=parseFloat($('input[data-f="pprice"]',this).val())||0;
            if(pn)newItems[pn]=pp;
        });
        /* Solo sobreescribir si hay items en el DOM (evitar borrar al guardar sin haber visto el tab) */
        if(Object.keys(newItems).length){
            T.mercaderia.categorias[ko].items=newItems;
        }
    });
    /* PASO 2: construir nc desde T */
    var nc={};
    $('[data-cat]').each(function(){
        var $blk=$(this);
        var ko=$blk.data('cat');
        var kn=($('input[data-f="cat-key"]',$blk).val()||ko||'').trim();if(!kn)return;
        var cat=T.mercaderia&&T.mercaderia.categorias?T.mercaderia.categorias[ko]||{}:{};
        var lblOrig=cat.label||'';
        var lbl=($('input[data-f="cat-label"]',$blk).val()||lblOrig).trim();
        nc[kn]={label:lbl,items:cat.items||{},rutas:cat.rutas||[]};
    });
    T.mercaderia.categorias=nc;
    /* Leer inputs individuales de lugares (recopilarFeT) */
    if($('#fe-merc-lima-lista').length){
        T.mercaderia.lugares_lima=[];
        $('#fe-merc-lima-lista input').each(function(){var v=$(this).val().trim().toUpperCase();if(v)T.mercaderia.lugares_lima.push(v);});
        T.mercaderia.lugares_provincia=[];
        $('#fe-merc-prov-lista input').each(function(){var v=$(this).val().trim().toUpperCase();if(v)T.mercaderia.lugares_provincia.push(v);});
    }

    /* Aereo: leer del DOM para guardar */
    if(IS_ADMIN&&$('#fe-aereo-contenido').length){
        var newRutasG={};
        $('#fe-aereo-contenido [data-aereo-desde]').each(function(){
            var $blk=$(this);
            var desdeOrig=$blk.data('aereo-desde');
            var desdeNew=($('input[data-f="aer-desde"]',$blk).val()||desdeOrig).trim().toUpperCase();
            if(!desdeNew)return;
            var destinos={};
            $blk.find('tbody tr').each(function(){
                var dn=($('input[data-f="aer-dest"]',this).val()||$(this).data('aer-dest')||'').trim().toUpperCase();
                if(!dn)return;
                destinos[dn]={
                    zona:($('input[data-f="zona"]',this).val()||'').trim(),
                    base_kg:parseFloat($('input[data-f="base_kg"]',this).val())||0,
                    exceso_kg:parseFloat($('input[data-f="exceso_kg"]',this).val())||0,
                    precio_1kg:parseFloat($('input[data-f="precio_1kg"]',this).val())||0,
                    lead:($('input[data-f="lead"]',this).val()||'').trim()||'48 HORAS'
                };
            });
            newRutasG[desdeNew]={destinos:destinos};
        });
        if(Object.keys(newRutasG).length)T.aereo.rutas=newRutasG;
    }

    /* Sobres — 3 tipos fijos */
    if(!T.sobres.tarifas||Array.isArray(T.sobres.tarifas))T.sobres.tarifas={};
    SOBRE_TIPOS_FE.forEach(function(meta){
        var $row=$('#fet-sobres tbody tr[data-stipo]').filter(function(){return $(this).data('stipo')===meta.key;});
        if(!$row.length)return;
        if(!T.sobres.tarifas[meta.key])T.sobres.tarifas[meta.key]={};
        T.sobres.tarifas[meta.key].activo=$row.find('.fe-sobre-activo').prop('checked');
        T.sobres.tarifas[meta.key].agencia=parseFloat($row.find('[data-f="agencia"]').val())||0;
        T.sobres.tarifas[meta.key].domicilio=parseFloat($row.find('[data-f="domicilio"]').val())||0;
        T.sobres.tarifas[meta.key].devolucion=parseFloat($row.find('[data-f="devolucion"]').val())||0;
    });
    /* Lugares de sobres desde inputs */
    if($('#fe-sobres-lima-lista').length){
        T.sobres.lugares_lima=[];
        $('#fe-sobres-lima-lista input').each(function(){var v=$(this).val().trim().toUpperCase();if(v)T.sobres.lugares_lima.push(v);});
        T.sobres.lugares_provincia=[];
        $('#fe-sobres-prov-lista input').each(function(){var v=$(this).val().trim().toUpperCase();if(v)T.sobres.lugares_provincia.push(v);});
    }
}

function guardarFe(btnId,noticeId){
    if(!IS_ADMIN)return;
    recopilarFeT();
    var $btn=$('#'+btnId);
    $btn.prop('disabled',true).text('Guardando...');
    var ajaxEndpoint=(typeof ajaxurl!=='undefined')?ajaxurl:AJAX;
    $.post(ajaxEndpoint,{action:'wpcte_save_tarifario',nonce:NONCE,tarifario:JSON.stringify(T)},function(res){
        $btn.prop('disabled',false).text('💾 Guardar');
        if(res&&res.success){
            $('.wpcte-fe-notice').attr('class','wpcte-fe-notice ok').text('✅ Tarifario completo guardado').show();
            setTimeout(function(){$('.wpcte-fe-notice').fadeOut();},3500);
        } else {
            var msg=(res&&res.data)?('❌ '+res.data):'❌ Error al guardar';
            $('#'+noticeId).attr('class','wpcte-fe-notice err').text(msg).show();
        }
    },'json').fail(function(xhr){
        $btn.prop('disabled',false).text('💾 Guardar');
        $('#'+noticeId).attr('class','wpcte-fe-notice err').text('❌ Error de red: '+xhr.status).show();
    });
}

if(IS_ADMIN){
    $(document).on('click','#fe-save-lima',function(){guardarFe('fe-save-lima','fe-notice-lima');});
    $(document).on('click','#fe-save-cg',function(){guardarFe('fe-save-cg','fe-notice-cg');});
    $(document).on('click','#fe-save-merc',function(){guardarFe('fe-save-merc','fe-notice-merc');});
    $(document).on('click','#fe-save-aereo',function(){guardarFe('fe-save-aereo','fe-notice-aereo');});
    $(document).on('click','#fe-save-sobres',function(){guardarFe('fe-save-sobres','fe-notice-sobres');});
}

/* ════════════════════════════════════════════════════════
   COTIZADOR: filtrar por tipo de envío
════════════════════════════════════════════════════════ */
var MOD_RULES={
    puerta_puerta:['lima_lima','mercaderia','sobres','carga_general'],
    agencia:['lima_lima','aereo','sobres','carga_general'],
    almacen:['lima_lima','carga_general']
};
$(document).on('change','#fe-cot-tipo',function(){
    var tipo=$(this).val();
    var modSel=document.getElementById('wpcte-mod');if(!modSel)return;
    Array.from(modSel.options).forEach(function(op){
        if(!op.value){op.style.display='';return;}
        op.style.display=(!tipo||MOD_RULES[tipo]&&MOD_RULES[tipo].indexOf(op.value)>-1)?'':'none';
    });
    modSel.value='';$(modSel).trigger('change');
});

/* ════════════════════════════════════════════════════════
   INIT
════════════════════════════════════════════════════════ */
$(document).ready(function(){
    // Renderizar según qué página es
    if(WPCFE==='tarifario_dhv'){
        renderFeLima();renderFeCG();renderFeMerc();renderFeAereo();renderFeSobres();
    }

});

}
// Inicializar cuando jQuery esté listo
if(typeof jQuery !== 'undefined'){
    wpcteInit(jQuery);
} else {
    document.addEventListener('DOMContentLoaded', function(){
        var t=setInterval(function(){
            if(typeof jQuery!=='undefined'){clearInterval(t);wpcteInit(jQuery);}
        },50);
    });
}
})();
</script>
    <?php
}
