<?php if ( ! defined('ABSPATH') ) exit; ?>
<div class="wrap wpcte-admin-wrap">

<div class="wpcte-page-header">
    <div class="wpcte-page-header-left">
        <h1><span class="dashicons dashicons-editor-table"></span> Tarifario DHV</h1>
        <p class="wpcte-subtitle">Gestiona precios, destinos, vehículos y productos de todas las modalidades de envío.</p>
    </div>
    <div class="wpcte-page-header-right">
        <a href="<?php echo admin_url('admin.php?page=wpcte-cotizador'); ?>" class="button button-primary">
            🧮 Ir al Cotizador
        </a>
    </div>
</div>

<div id="wpcte-notice" class="notice" style="display:none;margin-bottom:1rem"></div>

<div class="wpcte-tabs">
    <button class="wpcte-tab active" data-tab="lima_lima">🏙️ Dentro de Lima</button>
    <button class="wpcte-tab" data-tab="carga_general">📦 Carga General</button>
    <button class="wpcte-tab" data-tab="mercaderia">🛋️ Mercadería Frecuente</button>
    <button class="wpcte-tab" data-tab="aereo">✈️ Aéreos</button>
    <button class="wpcte-tab" data-tab="sobres">✉️ Sobres</button>
</div>

<!-- ═══════════════════════════════════════════════════════════
     TAB: DENTRO DE LIMA
════════════════════════════════════════════════════════════ -->
<div class="wpcte-tab-content active" id="tab-lima_lima">
    <div class="wpcte-section">
        <h2>Vehículos y Precios Base</h2>
        <p class="desc">El precio final = Base del vehículo + Tarifa adicional por destino. En zonas periféricas el precio es fijo.</p>
        <table class="wpcte-table" id="tbl-vehiculos">
            <thead><tr><th>Vehículo (key)</th><th>Etiqueta</th><th>Precio Base (S/)</th><th></th></tr></thead>
            <tbody id="body-vehiculos"></tbody>
        </table>
        <button class="wpcte-btn-add" data-target="body-vehiculos" data-type="vehiculo">+ Añadir vehículo</button>
    </div>

    <div class="wpcte-section">
        <h2>Distritos Normales</h2>
        <p class="desc">Precio adicional por vehículo (se suma al precio base del vehículo). Dejar vacío o "-" = no disponible.</p>
        <div class="wpcte-toolbar">
            <input type="text" id="search-distritos" placeholder="🔍 Buscar distrito..." class="wpcte-search">
        </div>
        <table class="wpcte-table" id="tbl-distritos">
            <thead id="head-distritos"><tr><th>Distrito</th></tr></thead>
            <tbody id="body-distritos"></tbody>
        </table>
        <button class="wpcte-btn-add" data-target="body-distritos" data-type="distrito">+ Añadir distrito</button>
    </div>

    <div class="wpcte-section wpcte-section-periferico">
        <h2>🔶 Zonas Periféricas</h2>
        <p class="desc">Precio FIJO total por vehículo (no se suma base). Dejar vacío = vehículo no disponible en esa zona.</p>
        <table class="wpcte-table" id="tbl-perifericas">
            <thead id="head-perifericas"><tr><th>Zona</th></tr></thead>
            <tbody id="body-perifericas"></tbody>
        </table>
        <button class="wpcte-btn-add" data-target="body-perifericas" data-type="periferico">+ Añadir zona periférica</button>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     TAB: CARGA GENERAL
════════════════════════════════════════════════════════════ -->
<div class="wpcte-tab-content" id="tab-carga_general">
    <div class="wpcte-section">
        <h2>Rutas de Carga General</h2>
        <p class="desc">Organizado por origen. Fórmula: <strong>Base + (kg × precio/kg)</strong>. Agencia solo aplica para tipo envío Agencia/Almacén.</p>

        <div id="cg-rutas-wrap">
            <!-- Se genera dinámicamente -->
        </div>
        <button class="wpcte-btn-add" id="btn-add-ruta-cg">+ Añadir origen / ruta</button>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     TAB: MERCADERÍA FRECUENTE
════════════════════════════════════════════════════════════ -->
<div class="wpcte-tab-content" id="tab-mercaderia">
    <div class="wpcte-section" style="display:flex;gap:1.5rem;flex-wrap:wrap">
        <div style="flex:1;min-width:220px">
            <h2>🏙️ Lugares en Lima</h2>
            <p class="desc">Agrega los distritos/lugares de Lima disponibles para mercadería.</p>
            <div id="merc-lima-lista" style="margin-bottom:.5rem"></div>
            <button class="wpcte-btn-add" id="btn-add-merc-lima">+ Añadir lugar Lima</button>
        </div>
        <div style="flex:1;min-width:220px">
            <h2>🌎 Lugares en Provincia</h2>
            <p class="desc">Agrega las ciudades de provincia disponibles para mercadería.</p>
            <div id="merc-prov-lista" style="margin-bottom:.5rem"></div>
            <button class="wpcte-btn-add" id="btn-add-merc-prov">+ Añadir lugar Provincia</button>
        </div>
    </div>
    <div class="wpcte-section">
        <h2>Categorías y Productos</h2>
        <p class="desc">Precio fijo por producto. Indica en qué rutas aplica cada categoría.</p>
        <div id="merc-cats-wrap"></div>
        <button class="wpcte-btn-add" id="btn-add-cat">+ Añadir categoría</button>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     TAB: AÉREOS
════════════════════════════════════════════════════════════ -->
<div class="wpcte-tab-content" id="tab-aereo">
    <div class="wpcte-section">
        <h2>Rutas Aéreas</h2>
        <p class="desc">Cada ruta tiene un <strong>Desde</strong> (origen) y sus destinos con precios. Puedes tener múltiples orígenes.</p>
        <div id="aereo-rutas-wrap"><!-- generado dinámicamente --></div>
        <button class="wpcte-btn-add" id="btn-add-ruta-aereo">+ Añadir origen / ruta</button>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     TAB: SOBRES
════════════════════════════════════════════════════════════ -->
<div class="wpcte-tab-content" id="tab-sobres">
    <div class="wpcte-section" style="display:flex;gap:1.5rem;flex-wrap:wrap">
        <div style="flex:1;min-width:220px">
            <h2>🏙️ Distritos de Lima</h2>
            <p class="desc">Uno por línea. Origen/destino para Lima→Lima y Lima→Provincia.</p>
            <textarea id="sobres-lima-lugares" rows="8" style="width:100%;font-size:.83rem;padding:6px;border:1px solid #ccc;border-radius:6px;resize:vertical"></textarea>
        </div>
        <div style="flex:1;min-width:220px">
            <h2>🌎 Ciudades de Provincia</h2>
            <p class="desc">Uno por línea. Origen/destino para Lima→Provincia y Provincia→Provincia.</p>
            <textarea id="sobres-prov-lugares" rows="8" style="width:100%;font-size:.83rem;padding:6px;border:1px solid #ccc;border-radius:6px;resize:vertical"></textarea>
        </div>
    </div>
    <div class="wpcte-section">
        <h2>Tarifas de Sobres</h2>
        <p class="desc">3 tipos fijos. Activa/desactiva cada uno y edita precios.</p>
        <table class="wpcte-table" id="tbl-sobres">
            <thead><tr>
                <th>Tipo</th><th>Activo</th><th>Agencia (S/)</th><th>Domicilio (S/)</th><th>Devolución (S/)</th>
            </tr></thead>
            <tbody id="body-sobres"></tbody>
        </table>
    </div>
</div>

<!-- ═══ Acciones ═══════════════════════════════════════════ -->
<div class="wpcte-actions">
    <button id="btn-guardar" class="button button-primary button-large">💾 Guardar Tarifario</button>
    <button id="btn-reset" class="button button-secondary">↺ Restaurar valores por defecto</button>
</div>

</div><!-- .wpcte-admin-wrap -->

<style>
.wpcte-admin-wrap{max-width:1100px;margin-top:1.5rem;}
/* Cabecera */
.wpcte-page-header{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap;}
.wpcte-page-header h1{margin:0 0 .25rem;font-size:1.4rem;display:flex;align-items:center;gap:.5rem;}
.wpcte-page-header h1 .dashicons{color:#0077b6;font-size:1.6rem;width:1.6rem;height:1.6rem;}
.wpcte-page-header-right{display:flex;gap:.5rem;align-items:center;padding-top:.25rem;}
.wpcte-subtitle{color:#666;margin:0;}
.wpcte-tabs{display:flex;gap:.5rem;flex-wrap:wrap;border-bottom:2px solid #d0e8f5;margin-bottom:1.5rem;}
.wpcte-tab{background:#f0f7ff;border:1.5px solid #b3ddf5;border-bottom:none;color:#0077b6;padding:.5rem 1.2rem;border-radius:8px 8px 0 0;cursor:pointer;font-weight:600;font-size:.9rem;transition:background .15s;}
.wpcte-tab.active,.wpcte-tab:hover{background:#0077b6;color:#fff;}
.wpcte-tab-content{display:none;animation:wpcte-fadein .2s;}
.wpcte-tab-content.active{display:block;}
@keyframes wpcte-fadein{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}
.wpcte-section{background:#fff;border:1.5px solid #d0e8f5;border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;}
.wpcte-section h2{font-size:1.05rem;color:#0077b6;margin:0 0 .5rem;}
.wpcte-section-periferico{border-color:#f4a261;}
.wpcte-section-periferico h2{color:#e76f51;}
.wpcte-table{width:100%;border-collapse:collapse;margin:.75rem 0;}
.wpcte-table th{background:#f0f7ff;color:#0077b6;padding:.5rem .75rem;text-align:left;font-size:.82rem;font-weight:700;border-bottom:2px solid #d0e8f5;}
.wpcte-table td{padding:.4rem .6rem;border-bottom:1px solid #edf4ff;vertical-align:middle;}
.wpcte-table tr:last-child td{border-bottom:none;}
.wpcte-table input[type=text],.wpcte-table input[type=number]{width:100%;padding:4px 6px;border:1px solid #ccc;border-radius:6px;font-size:.88rem;}
.wpcte-table input.wpcte-price{width:80px;}
.wpcte-table input.wpcte-key{width:140px;font-family:monospace;background:#f8fbff;}
.wpcte-btn-del{background:#fee;border:1px solid #fcc;color:#c0392b;padding:3px 10px;border-radius:6px;cursor:pointer;font-size:.8rem;}
.wpcte-btn-del:hover{background:#fcc;}
.wpcte-btn-add{background:#e8f5fd;border:1.5px dashed #0077b6;color:#0077b6;padding:.45rem 1.1rem;border-radius:8px;cursor:pointer;font-size:.88rem;margin-top:.25rem;font-weight:600;}
.wpcte-btn-add:hover{background:#d0eaf8;}
.wpcte-actions{margin:2rem 0;display:flex;gap:1rem;align-items:center;}
.wpcte-search{padding:5px 10px;border:1px solid #ccc;border-radius:8px;font-size:.88rem;width:250px;}
.wpcte-toolbar{margin-bottom:.5rem;}
.wpcte-input-sm{padding:4px 8px;border:1px solid #ccc;border-radius:6px;font-size:.88rem;width:140px;}
/* CG ruta */
.cg-ruta-block{background:#f8fbff;border:1.5px solid #d0e8f5;border-radius:10px;padding:1rem;margin-bottom:1rem;}
.cg-ruta-title{display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem;}
.cg-ruta-title strong{font-size:.95rem;color:#0077b6;}
/* Merc cats */
.merc-cat-block{background:#f8fbff;border:1.5px solid #d0e8f5;border-radius:10px;padding:1rem;margin-bottom:1rem;}
.merc-cat-header{display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem;flex-wrap:wrap;}
.merc-cat-title{font-weight:700;color:#0077b6;font-size:.95rem;}
/* Notice */
.notice.updated{background:#ecfdf5;border-left:4px solid #2a9d8f;padding:.75rem 1rem;}
.notice.error{background:#fff0f0;border-left:4px solid #c0392b;padding:.75rem 1rem;}
</style>

<script>
(function($){
var T=JSON.parse(JSON.stringify(WPCTE_ADMIN.tarifario));
var nonce=WPCTE_ADMIN.nonce;
var ajax=WPCTE_ADMIN.ajax;

/* ── Tabs ────────────────────────────────────────────────── */
$('.wpcte-tab').on('click',function(){
    $('.wpcte-tab').removeClass('active');
    $('.wpcte-tab-content').removeClass('active');
    $(this).addClass('active');
    $('#tab-'+$(this).data('tab')).addClass('active');
});

/* ── Render helpers ─────────────────────────────────────── */
function inp(type,val,cls,extra){
    cls=cls||'';extra=extra||'';
    return '<input type="'+type+'" value="'+esc(val)+'" class="'+cls+'" '+extra+'>';
}
function esc(v){return (v===null||v===undefined)?'':String(v).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');}
function num(v){return (v===null||v===undefined)?'':v;}

/* ═══════════════════════════════════════════════════════════
   DENTRO DE LIMA — Vehículos
════════════════════════════════════════════════════════════ */
function renderVehiculos(){
    var $b=$('#body-vehiculos');$b.empty();
    $.each(T.lima_lima.vehiculos||{},function(key,veh){
        $b.append('<tr data-vkey="'+esc(key)+'">'
            +'<td>'+inp('text',key,'wpcte-key','data-field="vkey"')+'</td>'
            +'<td>'+inp('text',veh.label,'','data-field="vlabel"')+'</td>'
            +'<td>'+inp('number',veh.precio_base,'wpcte-price','data-field="vbase" min="0" step="1"')+'</td>'
            +'<td><button class="wpcte-btn-del btn-del-vehiculo">✕</button></td>'
        +'</tr>');
    });
}

function renderDistritosHead(){
    var vehs=Object.keys(T.lima_lima.vehiculos||{});
    var $h=$('#head-distritos tr');
    $h.empty();$h.append('<th>Distrito</th>');
    vehs.forEach(function(v){$h.append('<th>+'+esc((T.lima_lima.vehiculos[v]&&T.lima_lima.vehiculos[v].label)||v)+' (S/)</th>');});
    $h.append('<th></th>');
}
function renderPericHead(){
    var vehs=Object.keys(T.lima_lima.vehiculos||{});
    var $h=$('#head-perifericas tr');
    $h.empty();$h.append('<th>Zona</th>');
    vehs.forEach(function(v){$h.append('<th>Fijo '+esc((T.lima_lima.vehiculos[v]&&T.lima_lima.vehiculos[v].label)||v)+' (S/)</th>');});
    $h.append('<th></th>');
}

function renderDistritos(filter){
    var $b=$('#body-distritos');$b.empty();
    var vehs=Object.keys(T.lima_lima.vehiculos||{});
    $.each(T.lima_lima.distritos||{},function(dist,precios){
        if(filter&&dist.toLowerCase().indexOf(filter.toLowerCase())===-1)return;
        var row='<tr data-dist="'+esc(dist)+'">'
            +'<td>'+inp('text',dist,'','data-field="dist"')+'</td>';
        vehs.forEach(function(v){
            var val=precios[v];
            row+='<td>'+inp('number',num(val),'wpcte-price','data-veh="'+esc(v)+'" data-field="dprice" min="0" step="0.5" placeholder="-"')+'</td>';
        });
        row+='<td><button class="wpcte-btn-del btn-del-dist">✕</button></td></tr>';
        $b.append(row);
    });
}

function renderPerifericas(){
    var $b=$('#body-perifericas');$b.empty();
    var vehs=Object.keys(T.lima_lima.vehiculos||{});
    $.each(T.lima_lima.perifericas||{},function(zona,precios){
        var row='<tr data-zona="'+esc(zona)+'">'
            +'<td>'+inp('text',zona,'','data-field="zona"')+'</td>';
        vehs.forEach(function(v){
            var val=precios[v];
            row+='<td>'+inp('number',(val===null||val===undefined)?'':''+val,'wpcte-price','data-veh="'+esc(v)+'" data-field="pprice" min="0" step="0.5" placeholder="-"')+'</td>';
        });
        row+='<td><button class="wpcte-btn-del btn-del-per">✕</button></td></tr>';
        $b.append(row);
    });
}

function renderLimaLima(){
    renderVehiculos();
    renderDistritosHead();
    renderPericHead();
    renderDistritos();
    renderPerifericas();
}
renderLimaLima();

// Agregar vehículo
$('[data-target="body-vehiculos"]').on('click',function(){
    T.lima_lima.vehiculos['nuevo_veh']={label:'Nuevo',precio_base:20};
    renderLimaLima();
});
// Eliminar vehículo
$('#body-vehiculos').on('click','.btn-del-vehiculo',function(){
    var key=$(this).closest('tr').data('vkey');
    delete T.lima_lima.vehiculos[key];
    renderLimaLima();
});
// Agregar distrito
$('[data-target="body-distritos"]').on('click',function(){
    T.lima_lima.distritos['NUEVO DISTRITO']={};
    renderLimaLima();
});
// Eliminar distrito
$('#body-distritos').on('click','.btn-del-dist',function(){
    var d=$(this).closest('tr').data('dist');
    delete T.lima_lima.distritos[d];renderDistritos();
});
// Agregar periferico
$('[data-target="body-perifericas"]').on('click',function(){
    T.lima_lima.perifericas['NUEVA ZONA']={};
    renderLimaLima();
});
// Eliminar periférico
$('#body-perifericas').on('click','.btn-del-per',function(){
    var z=$(this).closest('tr').data('zona');
    delete T.lima_lima.perifericas[z];renderPerifericas();
});

// Buscar distritos
$('#search-distritos').on('input',function(){renderDistritos($(this).val());});

/* ═══════════════════════════════════════════════════════════
   CARGA GENERAL
════════════════════════════════════════════════════════════ */
function renderCG(){
    var $w=$('#cg-rutas-wrap');$w.empty();
    $.each(T.carga_general.rutas||{},function(origen,ruta){
        var b='<div class="cg-ruta-block" data-origen="'+esc(origen)+'">';
        b+='<div class="cg-ruta-title">';
        b+='<strong>Origen: </strong><input type="text" value="'+esc(origen)+'" data-field="cg-origen" class="wpcte-input-sm">';
        b+='<input type="text" value="'+esc(ruta.titulo)+'" data-field="cg-titulo" class="wpcte-input-sm" placeholder="Título ruta">';
        b+='<button class="wpcte-btn-del btn-del-ruta" style="margin-left:auto">✕ Eliminar ruta</button>';
        b+='</div>';
        b+='<table class="wpcte-table"><thead><tr>';
        b+='<th>Destino</th><th>Base (S/)</th><th>Agencia/kg (S/)</th><th>Domicilio/kg (S/)</th>';
        b+='<th>Vol 0-100 (S/)</th><th>x kilo 101-500 (S/)</th><th>Vol 101-500 (S/)</th><th>Lead</th><th></th>';
        b+='</tr></thead><tbody data-cg-origen="'+esc(origen)+'">';
        $.each(ruta.destinos||{},function(dest,d){
            b+='<tr data-dest="'+esc(dest)+'">';
            b+='<td>'+inp('text',dest,'','data-field="cg-dest"')+'</td>';
            b+='<td>'+inp('number',d.base,'wpcte-price','data-field="base" min="0" step="0.5"')+'</td>';
            b+='<td>'+inp('number',d.agencia,'wpcte-price','data-field="agencia" min="0" step="0.01"')+'</td>';
            b+='<td>'+inp('number',d.domicilio,'wpcte-price','data-field="domicilio" min="0" step="0.01"')+'</td>';
            b+='<td>'+inp('number',d.vol_0_100,'wpcte-price','data-field="vol_0_100" min="0" step="0.01"')+'</td>';
            b+='<td>'+inp('number',d.x_kilo_101_500,'wpcte-price','data-field="x_kilo_101_500" min="0" step="0.01"')+'</td>';
            b+='<td>'+inp('number',d.vol_101_500,'wpcte-price','data-field="vol_101_500" min="0" step="0.01"')+'</td>';
            b+='<td><input type="text" value="'+esc(d.lead)+'" data-field="lead" style="width:90px"></td>';
            b+='<td><button class="wpcte-btn-del btn-del-cg-dest">✕</button></td>';
            b+='</tr>';
        });
        b+='</tbody></table>';
        b+='<button class="wpcte-btn-add btn-add-cg-dest" data-origen="'+esc(origen)+'">+ Añadir destino</button>';
        b+='</div>';
        $w.append(b);
    });
}
renderCG();

$('#btn-add-ruta-cg').on('click',function(){
    T.carga_general.rutas['NUEVO ORIGEN']={titulo:'Nueva Ruta',destinos:{}};
    renderCG();
});
$('#cg-rutas-wrap').on('click','.btn-del-ruta',function(){
    var o=$(this).closest('.cg-ruta-block').data('origen');
    delete T.carga_general.rutas[o];renderCG();
});
$('#cg-rutas-wrap').on('click','.btn-add-cg-dest',function(){
    var o=$(this).data('origen');
    T.carga_general.rutas[o].destinos['NUEVO DESTINO']={base:10,agencia:1,domicilio:1.5,vol_0_100:2,x_kilo_101_500:1,vol_101_500:2,lead:'24 HORAS'};
    renderCG();
});
$('#cg-rutas-wrap').on('click','.btn-del-cg-dest',function(){
    var $r=$(this).closest('tr');
    var o=$(this).closest('tbody').data('cg-origen');
    var d=$r.data('dest');
    delete T.carga_general.rutas[o].destinos[d];renderCG();
});

/* ═══════════════════════════════════════════════════════════
   MERCADERÍA FRECUENTE
════════════════════════════════════════════════════════════ */
/* Poblar textareas de lugares */
/* Render listas de lugares como inputs individuales */
function renderLugarLista(tipo){
    var arr=tipo==='lima'?(T.mercaderia.lugares_lima||[]):(T.mercaderia.lugares_provincia||[]);
    var wrapId=tipo==='lima'?'merc-lima-lista':'merc-prov-lista';
    var $w=$('#'+wrapId);$w.empty();
    arr.forEach(function(lugar,i){
        $w.append('<div style="display:flex;gap:.4rem;align-items:center;margin-bottom:.3rem" data-merc-lugar-tipo="'+tipo+'" data-merc-lugar-idx="'+i+'">'
            +'<input type="text" value="'+esc(lugar)+'" data-field="merc-lugar" style="flex:1;padding:4px 8px;border:1px solid #ccc;border-radius:6px;font-size:.88rem">'
            +'<button class="wpcte-btn-del btn-del-merc-lugar" data-tipo="'+tipo+'" data-idx="'+i+'">✕</button>'
            +'</div>');
    });
}
function renderLugaresLima(){renderLugarLista('lima');}
function renderLugaresProv(){renderLugarLista('provincia');}
renderLugaresLima();
renderLugaresProv();

$(document).on('click','#btn-add-merc-lima',function(){
    if(!T.mercaderia.lugares_lima)T.mercaderia.lugares_lima=[];
    T.mercaderia.lugares_lima.push('');renderLugaresLima();
    /* Foco en el nuevo input */
    $('#merc-lima-lista input').last().focus();
});
$(document).on('click','#btn-add-merc-prov',function(){
    if(!T.mercaderia.lugares_provincia)T.mercaderia.lugares_provincia=[];
    T.mercaderia.lugares_provincia.push('');renderLugaresProv();
    $('#merc-prov-lista input').last().focus();
});
$(document).on('click','.btn-del-merc-lugar',function(){
    var tipo=$(this).data('tipo');
    var idx=$(this).data('idx');
    if(tipo==='lima'){T.mercaderia.lugares_lima.splice(idx,1);renderLugaresLima();}
    else{T.mercaderia.lugares_provincia.splice(idx,1);renderLugaresProv();}
});

var RUTAS_MERC=[
    {id:'ll',label:'Lima → Lima',   orig:'lima',     dest:'lima'},
    {id:'lp',label:'Lima → Provincia', orig:'lima',  dest:'provincia'},
    {id:'pp',label:'Provincia → Provincia',orig:'provincia',dest:'provincia'}
];

function renderMerc(){
    var $w=$('#merc-cats-wrap');$w.empty();
    $.each(T.mercaderia.categorias||{},function(catKey,cat){
        var rutasActivas=cat.rutas||[];
        var b='<div class="merc-cat-block" data-catkey="'+esc(catKey)+'">';
        b+='<div class="merc-cat-header">';
        b+='<span class="merc-cat-title">Categoría:</span>';
        b+='<input type="text" value="'+esc(catKey)+'" data-field="cat-key" class="wpcte-input-sm" style="font-family:monospace">';
        b+='<input type="text" value="'+esc(cat.label)+'" data-field="cat-label" class="wpcte-input-sm" placeholder="Nombre visible">';
        b+='<button class="wpcte-btn-del btn-del-cat" style="margin-left:auto">✕ Eliminar categoría</button>';
        b+='</div>';
        /* Rutas disponibles */
        b+='<div style="margin:.4rem 0;padding:.5rem .75rem;background:#f0f7ff;border-radius:6px;font-size:.84rem">';
        b+='<strong style="color:#0077b6">📍 Disponible en:</strong> ';
        RUTAS_MERC.forEach(function(r){
            var chk=rutasActivas.indexOf(r.id)>-1;
            b+='<label style="margin-right:1rem;cursor:pointer">';
            b+='<input type="checkbox" class="merc-ruta-chk" data-catkey="'+esc(catKey)+'" data-ruta="'+r.id+'"'+(chk?' checked':'')+' style="margin-right:.2rem">';
            b+=esc(r.label)+'</label>';
        });
        b+='</div>';
        /* Productos - tbody con ID único */
        b+='<table class="wpcte-table"><thead><tr><th>Producto</th><th>Precio (S/)</th><th></th></tr></thead>';
        b+='<tbody id="mitems-'+esc(catKey)+'" data-catkey="'+esc(catKey)+'">';
        $.each(cat.items||{},function(prod,precio){
            b+='<tr data-prod="'+esc(prod)+'">';
            b+='<td>'+inp('text',prod,'','data-field="prod-name" style="min-width:260px"')+'</td>';
            b+='<td>'+inp('number',precio,'wpcte-price','data-field="prod-price" min="0" step="1"')+'</td>';
            b+='<td><button class="wpcte-btn-del btn-del-prod" data-catkey="'+esc(catKey)+'">✕</button></td>';
            b+='</tr>';
        });
        b+='</tbody></table>';
        b+='<button class="wpcte-btn-add btn-add-prod" data-catkey="'+esc(catKey)+'">+ Añadir producto</button>';
        b+='</div>';
        $w.append(b);
    });
}
renderMerc();

$(document).on('click','#btn-add-cat',function(){
    var k='NUEVA_CAT';var n=1;while(T.mercaderia.categorias[k])k='NUEVA_CAT_'+(++n);
    T.mercaderia.categorias[k]={label:'Nueva Categoría',items:{},rutas:[]};
    renderMerc();
});
$(document).on('click','.btn-del-cat',function(){
    var k=$(this).closest('.merc-cat-block').data('catkey');
    delete T.mercaderia.categorias[k];renderMerc();
});
$(document).on('click','.btn-add-prod',function(){
    var k=$(this).data('catkey');
    if(!T.mercaderia.categorias[k])return;
    var pk='Nuevo Producto';var n=1;
    while(T.mercaderia.categorias[k].items[pk])pk='Nuevo Producto '+(++n);
    T.mercaderia.categorias[k].items[pk]=0;
    renderMerc();
});
$(document).on('click','.btn-del-prod',function(){
    var k=$(this).data('catkey');
    var p=$(this).closest('tr').data('prod');
    if(T.mercaderia.categorias[k])delete T.mercaderia.categorias[k].items[p];
    renderMerc();
});
$(document).on('change','.merc-ruta-chk',function(){
    var k=$(this).data('catkey');
    var r=$(this).data('ruta');
    if(!T.mercaderia.categorias[k])return;
    var rutas=T.mercaderia.categorias[k].rutas||[];
    if($(this).prop('checked')){if(rutas.indexOf(r)<0)rutas.push(r);}
    else{rutas=rutas.filter(function(x){return x!==r;});}
    T.mercaderia.categorias[k].rutas=rutas;
});

/* ═══════════════════════════════════════════════════════════
   AÉREOS — Rutas (cada ruta: desde → N destinos)
════════════════════════════════════════════════════════════ */
function renderAereo(){
    var $w=$('#aereo-rutas-wrap');$w.empty();
    $.each(T.aereo.rutas||{},function(desde,ruta){
        var b='<div class="cg-ruta-block" data-aereo-desde="'+esc(desde)+'" style="margin-bottom:1.2rem">';
        b+='<div class="cg-ruta-title">';
        b+='<strong>Desde:</strong> <input type="text" value="'+esc(desde)+'" data-field="aer-desde" class="wpcte-input-sm" style="width:140px">';
        b+='<button class="wpcte-btn-del btn-del-ruta-aereo" style="margin-left:auto">✕ Quitar ruta</button>';
        b+='</div>';
        b+='<table class="wpcte-table"><thead><tr>';
        b+='<th>Destino</th><th>Zona</th><th>Base (S/)</th><th>Exceso/kg (S/)</th><th>Precio 1kg (S/)</th><th>Lead</th><th></th>';
        b+='</tr></thead><tbody data-aereo-tbody="'+esc(desde)+'">';
        $.each(ruta.destinos||{},function(dest,d){
            b+='<tr data-aer-dest="'+esc(dest)+'">';
            b+='<td>'+inp('text',dest,'','data-field="aer-dest"')+'</td>';
            b+='<td>'+inp('text',d.zona||'','','data-field="zona"')+'</td>';
            b+='<td>'+inp('number',d.base_kg,'wpcte-price','data-field="base_kg" min="0" step="0.5"')+'</td>';
            b+='<td>'+inp('number',d.exceso_kg,'wpcte-price','data-field="exceso_kg" min="0" step="0.5"')+'</td>';
            b+='<td>'+inp('number',d.precio_1kg,'wpcte-price','data-field="precio_1kg" min="0" step="0.5"')+'</td>';
            b+='<td>'+inp('text',d.lead||'','','data-field="lead" style="width:90px"')+'</td>';
            b+='<td><button class="wpcte-btn-del btn-del-aer-dest" data-desde="'+esc(desde)+'">✕</button></td>';
            b+='</tr>';
        });
        b+='</tbody></table>';
        b+='<button class="wpcte-btn-add btn-add-aer-dest" data-desde="'+esc(desde)+'" style="margin-top:.3rem">+ Añadir destino</button>';
        b+='</div>';
        $w.append(b);
    });
}
renderAereo();

$(document).on('click','#btn-add-ruta-aereo',function(){
    if(!T.aereo.rutas)T.aereo.rutas={};
    var k='NUEVO ORIGEN';var n=1;while(T.aereo.rutas[k])k='NUEVO ORIGEN '+(++n);
    T.aereo.rutas[k]={destinos:{}};renderAereo();
});
$(document).on('click','.btn-del-ruta-aereo',function(){
    var d=$(this).closest('[data-aereo-desde]').data('aereo-desde');
    delete T.aereo.rutas[d];renderAereo();
});
$(document).on('click','.btn-add-aer-dest',function(){
    var desde=$(this).data('desde');
    if(!T.aereo.rutas[desde])return;
    var k='NUEVO';var n=1;
    while(T.aereo.rutas[desde].destinos[k])k='NUEVO_'+(++n);
    T.aereo.rutas[desde].destinos[k]={zona:'',base_kg:15,exceso_kg:6,precio_1kg:21,lead:'48 HORAS'};
    renderAereo();
});
$(document).on('click','.btn-del-aer-dest',function(){
    var desde=$(this).data('desde');
    var dest=$(this).closest('tr').data('aer-dest');
    if(T.aereo.rutas[desde])delete T.aereo.rutas[desde].destinos[dest];
    renderAereo();
});

/* ═══════════════════════════════════════════════════════════
   SOBRES — 3 tipos fijos + lugares
════════════════════════════════════════════════════════════ */
var SOBRE_TIPOS_DEF=[
    {key:'Lima - Lima',           label:'Lima → Lima'},
    {key:'Lima - Provincia',      label:'Lima → Provincia'},
    {key:'Provincia - Provincia', label:'Provincia → Provincia'}
];
function renderSobres(){
    var $b=$('#body-sobres');$b.empty();
    if(!T.sobres.tarifas){T.sobres.tarifas={};}
    SOBRE_TIPOS_DEF.forEach(function(meta){
        var d=T.sobres.tarifas[meta.key]||{activo:true,agencia:0,domicilio:0,devolucion:0};
        var activo=d.activo!==false;
        $b.append('<tr data-tipo="'+esc(meta.key)+'">'            +'<td><strong>'+esc(meta.label)+'</strong></td>'            +'<td><label style="cursor:pointer"><input type="checkbox" class="sobre-activo-chk" data-tipo="'+esc(meta.key)+'"'+(activo?' checked':'')+'>'            +' '+(activo?'<span style="color:#2a9d8f">Activo</span>':'<span style="color:#aaa">Inactivo</span>')+'</label></td>'            +'<td>'+inp('number',d.agencia,'wpcte-price','data-field="agencia" min="0" step="0.5"')+'</td>'            +'<td>'+inp('number',d.domicilio,'wpcte-price','data-field="domicilio" min="0" step="0.5"')+'</td>'            +'<td>'+inp('number',d.devolucion,'wpcte-price','data-field="devolucion" min="0" step="0.5"')+'</td>'        +'</tr>');
    });
    /* Poblar textareas de lugares */
    $('#sobres-lima-lugares').val((T.sobres.lugares_lima||[]).join('\n'));
    $('#sobres-prov-lugares').val((T.sobres.lugares_provincia||[]).join('\n'));
}
renderSobres();
$(document).on('change','.sobre-activo-chk',function(){
    var k=$(this).data('tipo');
    if(!T.sobres.tarifas[k])T.sobres.tarifas[k]={};
    T.sobres.tarifas[k].activo=$(this).prop('checked');
    renderSobres();
});

/* ═══════════════════════════════════════════════════════════
   RECOPILAR DATOS DEL DOM → T antes de guardar
════════════════════════════════════════════════════════════ */
function recopilarDatos(){
    /* Vehículos */
    var nuevosVehs={};
    $('#body-vehiculos tr').each(function(){
        var key=$('input[data-field="vkey"]',this).val().trim().replace(/\s+/g,'_');
        if(!key)return;
        nuevosVehs[key]={
            label:$('input[data-field="vlabel"]',this).val().trim(),
            precio_base:parseFloat($('input[data-field="vbase"]',this).val())||0,
        };
    });
    T.lima_lima.vehiculos=nuevosVehs;

    /* Distritos */
    var nuevosDist={};
    $('#body-distritos tr').each(function(){
        var dist=$('input[data-field="dist"]',this).val().trim().toUpperCase();
        if(!dist)return;
        var precios={};
        $('input[data-field="dprice"]',this).each(function(){
            var v=$(this).val();var veh=$(this).data('veh');
            precios[veh]=(v===''||v==='-')?null:parseFloat(v);
        });
        nuevosDist[dist]=precios;
    });
    T.lima_lima.distritos=nuevosDist;

    /* Periféricas */
    var nuevosPer={};
    $('#body-perifericas tr').each(function(){
        var zona=$('input[data-field="zona"]',this).val().trim().toUpperCase();
        if(!zona)return;
        var precios={};
        $('input[data-field="pprice"]',this).each(function(){
            var v=$(this).val();var veh=$(this).data('veh');
            precios[veh]=(v===''||v==='-')?null:parseFloat(v);
        });
        nuevosPer[zona]=precios;
    });
    T.lima_lima.perifericas=nuevosPer;

    /* Carga General */
    var nuevasRutas={};
    $('.cg-ruta-block').each(function(){
        var origenNew=$('input[data-field="cg-origen"]',this).val().trim().toUpperCase();
        var tituloNew=$('input[data-field="cg-titulo"]',this).val().trim();
        if(!origenNew)return;
        var destinos={};
        $('tbody[data-cg-origen] tr',this).each(function(){
            var destNew=$('input[data-field="cg-dest"]',this).val().trim().toUpperCase();
            if(!destNew)return;
            destinos[destNew]={
                base:parseFloat($('input[data-field="base"]',this).val())||0,
                agencia:parseFloat($('input[data-field="agencia"]',this).val())||0,
                domicilio:parseFloat($('input[data-field="domicilio"]',this).val())||0,
                vol_0_100:parseFloat($('input[data-field="vol_0_100"]',this).val())||0,
                x_kilo_101_500:parseFloat($('input[data-field="x_kilo_101_500"]',this).val())||0,
                vol_101_500:parseFloat($('input[data-field="vol_101_500"]',this).val())||0,
                lead:$('input[data-field="lead"]',this).val().trim(),
            };
        });
        nuevasRutas[origenNew]={titulo:tituloNew,destinos:destinos};
    });
    T.carga_general.rutas=nuevasRutas;

    /* Mercadería — 2 pasos:
       1. Leer DOM para actualizar nombres/precios de productos en T
       2. Usar T (ya actualizado) para construir nuevasCats
    */
    /* PASO 1: sync DOM → T.items */
    $('.merc-cat-block').each(function(){
        var $bl=$(this);
        var catKeyOrig=$bl.data('catkey');
        if(!T.mercaderia.categorias[catKeyOrig])return;
        /* Solo actualizar precios/nombres — el orden de items lo da T */
        var newItems={};
        $bl.find('tbody tr').each(function(){
            var prodNew=$('input[data-field="prod-name"]',this).val().trim();
            var price=parseFloat($('input[data-field="prod-price"]',this).val())||0;
            if(prodNew)newItems[prodNew]=price;
        });
        if(Object.keys(newItems).length){
            T.mercaderia.categorias[catKeyOrig].items=newItems;
        }
    });
    /* PASO 2: construir nuevasCats desde T (ya con items actualizados) */
    var nuevasCats={};
    $('.merc-cat-block').each(function(){
        var $bl=$(this);
        var catKeyOrig=$bl.data('catkey');
        var catKeyNew=$('input[data-field="cat-key"]',$bl).first().val().trim();
        var catLabel=$('input[data-field="cat-label"]',$bl).first().val().trim();
        if(!catKeyNew)return;
        var cat=T.mercaderia.categorias[catKeyOrig]||{};
        nuevasCats[catKeyNew]={
            label:catLabel,
            items:cat.items||{},
            rutas:cat.rutas||[]
        };
    });
    T.mercaderia.categorias=nuevasCats;
    /* Lugares de mercadería */
    /* Leer inputs individuales de lugares Lima */
    T.mercaderia.lugares_lima=[];
    $('#merc-lima-lista input[data-field="merc-lugar"]').each(function(){
        var v=$(this).val().trim().toUpperCase();if(v)T.mercaderia.lugares_lima.push(v);
    });
    T.mercaderia.lugares_provincia=[];
    $('#merc-prov-lista input[data-field="merc-lugar"]').each(function(){
        var v=$(this).val().trim().toUpperCase();if(v)T.mercaderia.lugares_provincia.push(v);
    });

    /* Aéreos — estructura de rutas */
    var nuevasRutasAer={};
    $('#aereo-rutas-wrap [data-aereo-desde]').each(function(){
        var $bloque=$(this);
        var desdeOrig=$bloque.data('aereo-desde');
        var desdeNew=$('input[data-field="aer-desde"]',$bloque).first().val().trim().toUpperCase();
        if(!desdeNew)return;
        var destinos={};
        $bloque.find('[data-aereo-tbody] tr, tbody tr').each(function(){
            var destNew=$('input[data-field="aer-dest"]',this).val().trim().toUpperCase();
            if(!destNew)return;
            destinos[destNew]={
                zona:$('input[data-field="zona"]',this).val().trim(),
                base_kg:parseFloat($('input[data-field="base_kg"]',this).val())||0,
                exceso_kg:parseFloat($('input[data-field="exceso_kg"]',this).val())||0,
                precio_1kg:parseFloat($('input[data-field="precio_1kg"]',this).val())||0,
                lead:$('input[data-field="lead"]',this).val().trim(),
            };
        });
        nuevasRutasAer[desdeNew]={destinos:destinos};
    });
    T.aereo.rutas=nuevasRutasAer;

    /* Sobres — 3 tipos fijos */
    var nuevosSobres={};
    SOBRE_TIPOS_DEF.forEach(function(meta){
        var $row=$('#body-sobres tr[data-tipo="'+meta.key+'"]');
        if(!$row.length)return;
        nuevosSobres[meta.key]={
            activo:$row.find('.sobre-activo-chk').prop('checked'),
            agencia:parseFloat($row.find('input[data-field="agencia"]').val())||0,
            domicilio:parseFloat($row.find('input[data-field="domicilio"]').val())||0,
            devolucion:parseFloat($row.find('input[data-field="devolucion"]').val())||0,
        };
    });
    T.sobres.tarifas=nuevosSobres;
    T.sobres.lugares_lima=($('#sobres-lima-lugares').val()||'').split('\n').map(function(l){return l.trim().toUpperCase();}).filter(Boolean);
    T.sobres.lugares_provincia=($('#sobres-prov-lugares').val()||'').split('\n').map(function(l){return l.trim().toUpperCase();}).filter(Boolean);
}

/* ═══════════════════════════════════════════════════════════
   GUARDAR
════════════════════════════════════════════════════════════ */
$('#btn-guardar').on('click',function(){
    recopilarDatos();
    $(this).prop('disabled',true).text('Guardando...');
    $.post(ajax,{action:'wpcte_save_tarifario',nonce:nonce,tarifario:JSON.stringify(T)},function(res){
        $('#btn-guardar').prop('disabled',false).text('💾 Guardar Tarifario');
        var $n=$('#wpcte-notice');
        if(res.success){$n.attr('class','notice updated').html('<p><strong>✅ Tarifario guardado correctamente.</strong></p>').show();}
        else{$n.attr('class','notice error').html('<p><strong>❌ Error al guardar.</strong></p>').show();}
        $('html,body').animate({scrollTop:0},400);
        setTimeout(function(){$n.fadeOut();},4000);
    },'json').fail(function(){
        $('#btn-guardar').prop('disabled',false).text('💾 Guardar Tarifario');
        alert('Error de conexión al guardar.');
    });
});

/* ═══════════════════════════════════════════════════════════
   RESET
════════════════════════════════════════════════════════════ */
$('#btn-reset').on('click',function(){
    if(!confirm('¿Restaurar todos los valores por defecto? Se perderán los cambios no guardados.'))return;
    $.post(ajax,{action:'wpcte_reset_tarifario',nonce:nonce},function(res){
        if(res.success){
            T=res.data;
            renderLimaLima();renderCG();renderLugaresLima();renderLugaresProv();renderMerc();renderAereo();renderSobres();
            $('#wpcte-notice').attr('class','notice updated').html('<p><strong>↺ Tarifario restaurado.</strong></p>').show();
            setTimeout(function(){$('#wpcte-notice').fadeOut();},3000);
        }
    },'json');
});


})(jQuery);
</script>
