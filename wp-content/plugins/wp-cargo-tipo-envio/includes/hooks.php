<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* ======================================================================
   HOOKS FRONT-END
   ====================================================================== */
add_action( 'wp_head',   'wpcte_inline_css' );
add_action( 'wp_enqueue_scripts', 'wpcte_enqueue_frontend' );
function wpcte_enqueue_frontend() {
    $page_id = (int) get_option( 'wpcte_page_id_tarifario_dhv' );
    if ( $page_id && (int) get_queried_object_id() === $page_id ) {
        wp_enqueue_script( 'jquery' );
    }
}
add_action( 'wp_footer', 'wpcte_footer_crear' );
add_action( 'wp_footer', 'wpcte_footer_editar' );
add_action( 'wp_footer', 'wpcte_quitar_required' );
add_action( 'save_post',            'wpcte_guardar_meta' );
add_action( 'wp_after_insert_post', 'wpcte_guardar_meta_lat
e', 10, 1 );

// ── Páginas frontend reales (mismo patrón que wp-cargo-almacen) ────────────────
register_activation_hook( __FILE__, 'wpcte_activar' );
function wpcte_activar() {
    wpcte_get_page_id( 'tarifario_dhv' );
    wpcte_get_page_id( 'cotizador_dhv' );
}

function wpcte_get_page_id( string $slug ): int {
    $option = 'wpcte_page_id_' . $slug;
    $saved  = (int) get_option( $option );
    if ( $saved && get_post_status( $saved ) === 'publish' ) return $saved;

    $shortcode = '[' . $slug . ']';
    global $wpdb;
    $id = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->prefix}posts WHERE post_content LIKE %s AND post_status = 'publish' LIMIT 1",
            '%' . $wpdb->esc_like( $shortcode ) . '%'
        )
    );
    if ( ! $id ) {
        $titles = [ 'tarifario_dhv' => 'Tarifario DHV', 'cotizador_dhv' => 'Cotizador DHV' ];
        $id = (int) wp_insert_post( [
            'post_title'   => $titles[ $slug ] ?? $slug,
            'post_name'    => $slug,
            'post_content' => $shortcode,
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ] );
    }
    if ( $id ) {
        update_post_meta( $id, '_wp_page_template', 'dashboard.php' );
        update_option( $option, $id, false );
    }
    return $id;
}

function wpcte_page_url( string $slug, array $extra = [] ): string {
    $url = get_permalink( wpcte_get_page_id( $slug ) ) ?: home_url( '/' . $slug . '/' );
    return $extra ? add_query_arg( $extra, $url ) : $url;
}

// ── Sidebar: filter nativo de WPCargo (igual que almacén) ─────────────────────
add_filter( 'wpcfe_after_sidebar_menus', 'wpcte_sidebar_items', 30, 1 );
function wpcte_sidebar_items( array $menu ): array {
    if ( ! current_user_can( 'manage_options' ) ) return $menu;
    $menu['wpcte-tarifario'] = [
        'page-id'   => wpcte_get_page_id( 'tarifario_dhv' ),
        'label'     => 'Tarifario DHV',
        'permalink' => wpcte_page_url( 'tarifario_dhv' ),
        'icon'      => 'fa fa-list-alt mr-3',
    ];
    return $menu;
}

// ── Shortcodes ────────────────────────────────────────────────────
add_shortcode( 'tarifario_dhv', 'wpcte_shortcode_tarifario' );

// ── Shortcode selector de tipo de envío ──────────────────────────
// Uso en WPCode o cualquier página: [wpcte_selector_tipo]
// Parámetros opcionales: titulo, agencia, almacen, puerta_a_puerta (1/0)
add_shortcode( 'wpcte_selector_tipo', 'wpcte_shortcode_selector_tipo' );

function wpcte_shortcode_selector_tipo( $atts = [] ): string {
    $atts = shortcode_atts( [
        'titulo'         => '¿Qué tipo de envío deseas crear?',
        'agencia'        => '1',
        'puerta_a_puerta'=> '0',   // desactivado por defecto — el usuario lo maneja aparte
        'almacen'        => '1',
    ], $atts );

    $page_url  = get_permalink();
    $tiene_alm = wpcte_tiene_almacen();

    // Construir la lista de tipos según los atributos del shortcode
    $tipos = [];
    if ( $atts['puerta_a_puerta'] === '1' ) {
        $tipos['puerta_puerta'] = [
            'label' => 'Puerta a Puerta',
            'icon'  => 'fa-home',
            'desc'  => 'Recojo y entrega en domicilio.',
        ];
    }
    if ( $atts['agencia'] === '1' ) {
        $tipos['agencia'] = [
            'label' => 'Agencia',
            'icon'  => 'fa-building',
            'desc'  => 'Entrega desde nuestra agencia.',
        ];
    }
    if ( $atts['almacen'] === '1' && $tiene_alm ) {
        $tipos['almacen'] = [
            'label' => 'Almacén',
            'icon'  => 'fa-archive',
            'desc'  => 'Envío desde tu almacén DHV.',
        ];
    }

    ob_start();
    ?>
    <div id="wpcte-pantalla-tipo">

        <div id="wpcte-selector">
            <h4 class="wpcte-title">
                <i class="fa fa-truck"></i>
                <?php echo esc_html( $atts['titulo'] ); ?>
            </h4>

            <div class="wpcte-grid">
            <?php foreach ( $tipos as $key => $t ) : ?>
                <a href="<?php echo esc_url( add_query_arg( [ 'wpcfe' => 'add', 'tipo_envio' => $key ], $page_url ) ); ?>"
                   class="wpcte-card">
                    <i class="fa <?php echo esc_attr( $t['icon'] ); ?>"></i>
                    <strong><?php echo esc_html( $t['label'] ); ?></strong>
                    <span><?php echo esc_html( $t['desc'] ); ?></span>
                </a>
            <?php endforeach; ?>
            </div>

        </div><!-- #wpcte-selector -->

    </div><!-- #wpcte-pantalla-tipo -->

    <script>
    jQuery( document ).ready( function () {
        var form = document.querySelector( 'form.add-shipment' );
        var sel  = document.getElementById( 'wpcte-pantalla-tipo' );
        if ( form && sel ) {
            form.style.display = 'none';
            form.parentNode.insertBefore( sel, form );
        }
    } );
    </script>
    <?php
    return ob_get_clean();
}

function wpcte_shortcode_tarifario(): string {
    if ( ! current_user_can( 'manage_options' ) ) return '<p>Acceso restringido.</p>';
    ob_start();
    wpcte_render_tarifario_page();
    return ob_get_clean();
}

/* ======================================================================
   HELPERS DE MODALIDAD
   ====================================================================== */
function wpcte_get_label_modalidad( $mod ) {
    $labels = array(
        'lima_lima'     => 'DHV-Dentro de Lima',
        'carga_general' => 'DHV-Carga General',
        'mercaderia'    => 'DHV-Mercadería Frecuente',
        'aereo'         => 'DHV-Aéreos',
        'sobres'        => 'DHV-Sobres',
    );
    return $labels[$mod] ?? $mod;
}

/* ======================================================================
   CSS FRONT-END
   ====================================================================== */
function wpcte_inline_css() {
    $wpcfe = $_GET['wpcfe'] ?? '';
    // Cargar CSS también cuando está el shortcode del selector o el tarifario
    global $post;
    $has_shortcode = is_a( $post, 'WP_Post' ) &&
        ( has_shortcode( $post->post_content, 'wpcte_selector_tipo' ) ||
          has_shortcode( $post->post_content, 'tarifario_dhv' ) );
    if ( $wpcfe !== 'add' && $wpcfe !== 'update' && ! $has_shortcode ) return;
    ?>
<style id="wpcte-css">
/* ── Selector tipo ─────────────────────────────────────────── */
#wpcte-selector{padding:2rem 0;text-align:center;}
.wpcte-title{font-size:1.3rem;font-weight:700;color:#222;margin-bottom:1.5rem;}
.wpcte-grid{display:flex;gap:1.25rem;justify-content:center;flex-wrap:wrap;}
.wpcte-card{display:flex;flex-direction:column;align-items:center;gap:.6rem;background:#fff;border:2px solid #e4e4e4;border-radius:16px;padding:2rem 1.5rem;min-width:160px;max-width:210px;flex:1;text-decoration:none!important;color:#333!important;transition:border-color .2s,box-shadow .2s,transform .15s;box-shadow:0 2px 12px rgba(0,0,0,.07);cursor:pointer;}
.wpcte-card:hover{border-color:#00a8e8;box-shadow:0 8px 28px rgba(0,168,232,.2);transform:translateY(-4px);color:#00a8e8!important;}
.wpcte-card .fa{font-size:2.5rem;color:#00a8e8;}
/* ── Badge ─────────────────────────────────────────────────── */
.wpcte-badge{display:inline-flex;align-items:center;gap:.6rem;background:#e8f5fd;border:1px solid #b3ddf5;color:#0077b6;border-radius:8px;padding:.5rem 1rem;font-size:.92rem;margin-bottom:1rem;}
/* ── Pantallas ─────────────────────────────────────────────── */
#wpcte-pantalla-cotizador{padding:1rem 0;}
#wpcte-pantalla-cotizador .wpcte-back-btn{background:none;border:1px solid #b3ddf5;color:#0077b6;padding:.35rem .9rem;border-radius:8px;cursor:pointer;font-size:.85rem;margin-bottom:1rem;display:inline-flex;align-items:center;gap:.4rem;}
#wpcte-pantalla-cotizador .wpcte-back-btn:hover{background:#e8f5fd;}
/* ── Cotizador box ─────────────────────────────────────────── */
#wpcte-cotizador{background:#fff;border:1.5px solid #d0e8f5;border-radius:14px;padding:1.5rem 1.75rem;margin-bottom:1.5rem;box-shadow:0 2px 12px rgba(0,120,200,.07);}
#wpcte-cotizador h5{font-weight:700;color:#0077b6;margin-bottom:1rem;font-size:1.05rem;}
.wpcte-cot-row{display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-end;margin-bottom:.75rem;}
.wpcte-cot-group{display:flex;flex-direction:column;flex:1;min-width:160px;}
.wpcte-cot-group label{font-size:.8rem;font-weight:600;color:#555;margin-bottom:3px;}
#wpcte-cotizador select,#wpcte-cotizador input[type=number],#wpcte-cotizador input[type=text]{display:block!important;visibility:visible!important;opacity:1!important;height:34px!important;width:100%!important;padding:4px 8px!important;border:1px solid #ccc!important;border-radius:8px!important;font-size:.9rem!important;background:#fff!important;box-sizing:border-box!important;}
#wpcte-cotizador select{appearance:auto!important;-webkit-appearance:menulist!important;}
#wpcte-cotizador input[type=number]{-webkit-appearance:textfield!important;}
#wpcte-cotizador input[readonly]{background:#f0f9ff!important;font-weight:600;color:#0077b6;}
#wpcte-cot-btn{background:#0077b6;color:#fff;border:none;padding:.5rem 1.4rem;border-radius:8px;font-size:.9rem;font-weight:600;cursor:pointer;margin-top:4px;}
#wpcte-cot-btn:hover{background:#005f99;}
#wpcte-cot-resultado{margin-top:.75rem;padding:.75rem 1rem;border-radius:8px;background:#f0f9ff;border:1px solid #b3ddf5;font-size:.9rem;line-height:1.8;display:none;}
#wpcte-cot-resultado.ok{display:block;}
#wpcte-cot-resultado strong{color:#0077b6;}
.wpcte-desglose{margin-top:.5rem;border-top:1px solid #d0e8f5;padding-top:.5rem;font-size:.85rem;color:#555;}
.wpcte-desglose-row{display:flex;justify-content:space-between;padding:1px 0;}
.wpcte-total-row{display:flex;justify-content:space-between;font-weight:700;color:#0077b6;font-size:.95rem;border-top:1px solid #b3ddf5;margin-top:3px;padding-top:3px;}
/* ── Botón continuar ──────────────────────────────────────── */
#wpcte-btn-continuar{background:#2a9d8f;color:#fff;border:none;padding:.55rem 1.6rem;border-radius:8px;font-size:.95rem;font-weight:600;cursor:pointer;margin-top:.5rem;display:none;}
#wpcte-btn-continuar.visible{display:inline-block;}
#wpcte-btn-continuar:hover{background:#21867a;}
/* ── Editar: verificar ─────────────────────────────────────── */
#wpcte-cot-edit-wrap{background:#f8fbff;border:1.5px solid #d0e8f5;border-radius:12px;padding:1.2rem 1.5rem;margin-bottom:1.2rem;}
#wpcte-verificar-btn{background:#0077b6;color:#fff;border:none;padding:.5rem 1.4rem;border-radius:8px;font-size:.9rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:.5rem;margin-bottom:.75rem;}
#wpcte-verificar-btn:hover{background:#005f99;}
#wpcte-cot-body{display:none;padding-top:.5rem;}
#wpcte-cot-body.open{display:block;}
#package_id{display:none!important;}
</style>
    <?php
}

/* ======================================================================
   COTIZADOR HTML — compartido crear/editar
   ====================================================================== */
function wpcte_render_cotizador_html( $tarifario, $tipo_envio = '', $modo = 'crear' ) {
    $tar_js    = wp_json_encode( $tarifario );
    $lug_js    = wp_json_encode( wpcte_lugares() );
    $mod_rules = wp_json_encode( wpcte_modalidades_por_tipo() );
    $tipo_js   = wp_json_encode( $tipo_envio );

    // Listas para selects
    $lug       = wpcte_lugares();
    $all_lima    = array_merge( $lug['lima'] ?? [], $lug['lima_perifericas'] ?? [] );
    $all_lug     = array_merge( $all_lima, $lug['provincias'] ?? [] );
    $cats        = $tarifario['mercaderia']['categorias'] ?? [];
    $cg_origenes = array_keys( $tarifario['carga_general']['rutas'] ?? [] );
    $aereo_dests = array_keys( $tarifario['aereo']['destinos'] ?? [] );
    $vehiculos   = $tarifario['lima_lima']['vehiculos'] ?? [];

    ob_start();
?>
<div id="wpcte-cotizador">
<h5><i class="fa fa-calculator"></i> <?php echo $modo === 'editar' ? 'Verificar precio' : 'Cotizar envío'; ?></h5>

<!-- Modalidad -->
<div class="wpcte-cot-row">
    <div class="wpcte-cot-group"><label>Modalidad</label>
    <select id="wpcte-mod">
        <option value="">-- Seleccione --</option>
        <option value="lima_lima">DHV-Dentro de Lima</option>
        <option value="carga_general">DHV-Carga General</option>
        <option value="mercaderia">DHV-Mercadería Frecuente</option>
        <option value="aereo">DHV-Aéreos</option>
        <option value="sobres">DHV-Sobres</option>
    </select></div>
</div>

<!-- Origen / Destino genérico -->
<div class="wpcte-cot-row" id="wpcte-orig-dest-row" style="display:none">
    <div class="wpcte-cot-group" id="wpcte-orig-select-wrap"><label>Origen</label>
    <select id="wpcte-orig"><option value="">-- Seleccione --</option>
    <?php foreach ( $all_lima as $l ) echo '<option value="'.esc_attr($l).'">'.esc_html($l).'</option>'; ?>
    </select></div>
    <!-- Origen aéreo: select dinámico de orígenes -->
    <div class="wpcte-cot-group" id="wpcte-orig-aereo-wrap" style="display:none"><label>Origen</label>
    <select id="wpcte-orig-aereo-sel"><option value="">-- Seleccione --</option></select></div>
    <!-- Origen select para carga general (ciudades) -->
    <div class="wpcte-cot-group" id="wpcte-orig-cg-wrap" style="display:none"><label>Origen</label>
    <select id="wpcte-orig-cg"><option value="">-- Seleccione --</option>
    <?php foreach ( $cg_origenes as $o ) echo '<option value="'.esc_attr($o).'">'.esc_html($o).'</option>'; ?>
    </select></div>
    <div class="wpcte-cot-group"><label>Destino</label>
    <select id="wpcte-dest"><option value="">-- Seleccione --</option>
    <?php foreach ( $all_lima as $l ) echo '<option value="'.esc_attr($l).'">'.esc_html($l).'</option>'; ?>
    </select></div>
</div>

<!-- Mercadería frecuente: tipo ruta + categoría + producto -->
<div class="wpcte-cot-row" id="wpcte-merc-row" style="display:none">
    <div class="wpcte-cot-group"><label>Tipo de envío</label>
    <select id="wpcte-merc-tipo">
        <option value="">-- Seleccione tipo --</option>
        <option value="ll">Lima → Lima</option>
        <option value="lp">Lima → Provincia</option>
        <option value="pp">Provincia → Provincia</option>
    </select></div>
    <div class="wpcte-cot-group"><label>Categoría</label>
    <select id="wpcte-merc-cat"><option value="">-- Seleccione --</option>
    <?php foreach ( $cats as $k => $c ) echo '<option value="'.esc_attr($k).'">'.esc_html($c['label']).'</option>'; ?>
    </select></div>
    <div class="wpcte-cot-group"><label>Producto</label>
    <select id="wpcte-merc-prod"><option value="">-- Seleccione categoría --</option></select></div>
</div>

<!-- Extras por modalidad -->
<div class="wpcte-cot-row" id="wpcte-extra-row" style="display:none">
    <!-- Vehículo (Dentro de Lima) — opciones se generan dinámicamente desde T -->
    <div class="wpcte-cot-group" id="wpcte-veh-wrap" style="display:none"><label>Vehículo</label>
    <select id="wpcte-veh">
    <?php foreach($tarifario['lima_lima']['vehiculos'] as $vk=>$vv): ?>
        <option value="<?php echo esc_attr($vk); ?>"><?php echo esc_html($vv['label']); ?></option>
    <?php endforeach; ?>
    </select></div>
    <!-- Entrega (Carga General / Sobres) -->
    <div class="wpcte-cot-group" id="wpcte-mod2-wrap" style="display:none"><label>Entrega</label>
    <select id="wpcte-mod2">
        <option value="agencia">Agencia</option>
        <option value="domicilio">Domicilio</option>
    </select></div>
    <!-- Tipo medida (Carga General) -->
    <div class="wpcte-cot-group" id="wpcte-tipo-medida-wrap" style="display:none"><label>Tipo de medida</label>
    <select id="wpcte-tipo-medida">
        <option value="kg">Peso (kg)</option>
        <option value="vol">Volumen (cm)</option>
    </select></div>
    <!-- Peso -->
    <div class="wpcte-cot-group" id="wpcte-peso-wrap" style="display:none"><label>Peso (kg)</label>
    <input type="number" id="wpcte-peso" min="0.1" step="0.1" value="1"></div>
    <!-- Dimensiones volumétricas -->
    <div class="wpcte-cot-group" id="wpcte-alto-wrap" style="display:none"><label>Alto (cm)</label>
    <input type="number" id="wpcte-alto" min="1" step="1" value="10"></div>
    <div class="wpcte-cot-group" id="wpcte-ancho-wrap" style="display:none"><label>Ancho (cm)</label>
    <input type="number" id="wpcte-ancho" min="1" step="1" value="10"></div>
    <div class="wpcte-cot-group" id="wpcte-largo-wrap" style="display:none"><label>Largo (cm)</label>
    <input type="number" id="wpcte-largo" min="1" step="1" value="10"></div>
    <!-- Sobres: solo Tipo de envío — Origen/Destino usan los selects de arriba -->
    <div class="wpcte-cot-group" id="wpcte-sobre-tipo-wrap" style="display:none"><label>Tipo de envío</label>
    <select id="wpcte-sobre-tipo"><option value="">-- Seleccione tipo --</option></select></div>
    <div class="wpcte-cot-group" id="wpcte-mod-sobre-wrap" style="display:none"><label>Entrega</label>
    <select id="wpcte-mod-sobre">
        <option value="agencia">Agencia</option>
        <option value="domicilio">Domicilio</option>
    </select></div>
</div>

<!-- Devolución de cargo (sobres) -->
<div class="wpcte-cot-row" id="wpcte-devolucion-row" style="display:none">
    <div class="wpcte-cot-group" style="flex-direction:row;align-items:center;gap:.5rem">
        <input type="checkbox" id="wpcte-devolucion" style="width:auto!important;height:auto!important;margin:0">
        <label for="wpcte-devolucion" style="margin:0;font-size:.85rem;color:#555;cursor:pointer">
            Agregar cargo por devolución (S/ <span id="wpcte-dev-monto">-</span>)
        </label>
    </div>
</div>

<button type="button" id="wpcte-cot-btn"><i class="fa fa-search"></i> Calcular</button>
<div id="wpcte-cot-resultado"></div>
</div>
<?php if ( $modo === 'crear' ): ?>
<button type="button" id="wpcte-btn-continuar">Continuar al formulario &rarr;</button>
<?php endif; ?>

<script>
(function(){
var T=<?php echo $tar_js; ?>;
var L=<?php echo $lug_js; ?>;
var MOD_RULES=<?php echo $mod_rules; ?>;
var TIPO_ENVIO=<?php echo $tipo_js; ?>;
var precio=null,origCot='',destCot='';

/* ── Listas dinámicas desde T (sin hardcode) ─────────────────── */
function getLimaOpts(){
    var r=[];
    Object.keys(T.lima_lima.distritos||{}).forEach(function(d){if(r.indexOf(d)<0)r.push(d);});
    Object.keys(T.lima_lima.perifericas||{}).forEach(function(d){if(r.indexOf(d)<0)r.push(d);});
    return r;
}
function getAereoDestinos(){/* legacy - aereo uses rutas now */return [];}
function getCGOrigenes(){return Object.keys(T.carga_general.rutas||{});}

/* ── Repoblar un select desde un array de valores ────────────── */
function repoblarSelect(selId, valores, mantenerValor){
    var sel=document.getElementById(selId);if(!sel)return;
    var actual=mantenerValor?sel.value:'';
    // Vaciar excepto primer option vacío
    while(sel.options.length>1)sel.remove(1);
    valores.forEach(function(v){
        var op=document.createElement('option');
        op.value=v;op.textContent=v;
        sel.appendChild(op);
    });
    if(actual&&Array.from(sel.options).some(function(o){return o.value===actual;}))sel.value=actual;
}

function esc(v){return (v===null||v===undefined)?'':String(v).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');}

/* ── Forzar visibilidad selects ─────────────────────────────── */
function forzar(){
    ['wpcte-mod','wpcte-orig','wpcte-dest','wpcte-orig-cg','wpcte-orig-aereo-sel','wpcte-veh','wpcte-mod2',
     'wpcte-tipo-medida','wpcte-sobre-tipo','wpcte-mod-sobre','wpcte-merc-cat','wpcte-merc-prod']
    .forEach(function(id){
        var el=document.getElementById(id);
        if(el){el.style.display='block';el.style.visibility='visible';}
    });
}
setTimeout(forzar,200);setTimeout(forzar,800);setTimeout(forzar,1800);

/* ── Filtrar opciones en un select ─────────────────────────── */
function filtrarOpts(selId, permitidos){
    var sel=document.getElementById(selId);
    if(!sel)return;
    Array.from(sel.options).forEach(function(op){
        if(!op.value)return;
        op.style.display=(!permitidos||permitidos.indexOf(op.value)>-1)?'':'none';
    });
    sel.value='';
}

/* ── Mostrar/ocultar modo origen ────────────────────────────── */
function modoOrigen(modo){ // 'select' | 'aereo' | 'cg'
    var sw=document.getElementById('wpcte-orig-select-wrap');
    var aw=document.getElementById('wpcte-orig-aereo-wrap');
    var cg=document.getElementById('wpcte-orig-cg-wrap');
    if(sw)sw.style.display='none';
    if(aw)aw.style.display='none';
    if(cg)cg.style.display='none';
    if(modo==='select'&&sw)sw.style.display='block';
    if(modo==='aereo'&&aw)aw.style.display='block';
    if(modo==='cg'&&cg)cg.style.display='block';
}

/* ── Filtrar opciones de modalidad por tipo de envío ─────────── */
function filtrarModalidades(){
    var sel=document.getElementById('wpcte-mod');
    if(!sel||!TIPO_ENVIO||!MOD_RULES[TIPO_ENVIO])return;
    var permitidas=MOD_RULES[TIPO_ENVIO];
    Array.from(sel.options).forEach(function(op){
        if(!op.value)return;
        op.style.display=permitidas.indexOf(op.value)>-1?'':'none';
    });
}

/* ── showExtra ──────────────────────────────────────────────── */
function showExtra(ids){
    ['wpcte-veh-wrap','wpcte-mod2-wrap','wpcte-tipo-medida-wrap','wpcte-peso-wrap',
     'wpcte-alto-wrap','wpcte-ancho-wrap','wpcte-largo-wrap',
     'wpcte-sobre-tipo-wrap','wpcte-mod-sobre-wrap'].forEach(function(id){
        var el=document.getElementById(id);if(el)el.style.display='none';
    });
    ids.forEach(function(id){var el=document.getElementById(id);if(el)el.style.display='block';});
    var extraRow=document.getElementById('wpcte-extra-row');
    if(extraRow)extraRow.style.display=ids.length?'flex':'none';
    forzar();
}

/* ── Campos medida ──────────────────────────────────────────── */
var tipoMedidaSel=document.getElementById('wpcte-tipo-medida');
function actualizarCamposMedida(){
    var med=tipoMedidaSel?tipoMedidaSel.value:'kg';
    ['wpcte-peso-wrap'].forEach(function(id){var e=document.getElementById(id);if(e)e.style.display=med==='kg'?'block':'none';});
    ['wpcte-alto-wrap','wpcte-ancho-wrap','wpcte-largo-wrap'].forEach(function(id){var e=document.getElementById(id);if(e)e.style.display=med==='vol'?'block':'none';});
}
if(tipoMedidaSel) tipoMedidaSel.addEventListener('change',actualizarCamposMedida);

/* ── Sobres: Tipo fijo → filtra Origen/Destino de arriba ──────── */
var devRow=document.getElementById('wpcte-devolucion-row');
var devCheck=document.getElementById('wpcte-devolucion');
var devMonto=document.getElementById('wpcte-dev-monto');
var sobreTipoSel=document.getElementById('wpcte-sobre-tipo');

/* Mapa: tipo → qué listas usar para origen y destino */
var SOBRE_MAP={
    'Lima - Lima':           {orig:'sobres_lima',     dest:'sobres_lima'},
    'Lima - Provincia':      {orig:'sobres_lima',     dest:'sobres_provincia'},
    'Provincia - Provincia': {orig:'sobres_provincia',dest:'sobres_provincia'}
};

/* Poblar tipos activos */
function poblarTiposSobre(){
    if(!sobreTipoSel)return;
    sobreTipoSel.innerHTML='<option value="">-- Seleccione tipo --</option>';
    Object.keys(SOBRE_MAP).forEach(function(key){
        var d=T.sobres.tarifas[key];
        if(!d||d.activo===false)return;
        sobreTipoSel.innerHTML+='<option value="'+key+'">'+key+'</option>';
    });
    /* Limpiar Origen/Destino de arriba */
    repoblarSelect('wpcte-orig',[],false);
    repoblarSelect('wpcte-dest',[],false);
    if(devRow)devRow.style.display='none';
}

/* Al elegir tipo → filtrar Origen y Destino de arriba */
function onSobreTipoChange(){
    var key=sobreTipoSel?sobreTipoSel.value:'';
    repoblarSelect('wpcte-orig',[],false);
    repoblarSelect('wpcte-dest',[],false);
    if(devRow)devRow.style.display='none';
    if(!key)return;
    var map=SOBRE_MAP[key];if(!map)return;
    var lugsOrig=L[map.orig]||[];
    var lugsDest=L[map.dest]||[];
    repoblarSelect('wpcte-orig',lugsOrig,false);
    repoblarSelect('wpcte-dest',lugsDest,false);
    /* Devolución */
    var d=T.sobres.tarifas[key];
    if(d&&d.devolucion){
        if(devMonto)devMonto.textContent=Number(d.devolucion).toFixed(2);
        if(devRow)devRow.style.display='flex';
    }
    if(devCheck)devCheck.checked=false;
}

if(sobreTipoSel) sobreTipoSel.addEventListener('change',onSobreTipoChange);

/* ── Poblar y filtrar vehículos según destino (Dentro de Lima) ── */
function filtrarVehiculos(destino){
    var vehSel=document.getElementById('wpcte-veh');if(!vehSel)return;
    /* Repoblar desde T (por si se añadieron vehículos) */
    var actual=vehSel.value;
    vehSel.innerHTML='';
    $.each(T.lima_lima.vehiculos||{},function(k,v){
        var op=document.createElement('option');
        op.value=k;op.textContent=v.label;
        vehSel.appendChild(op);
    });
    /* Filtrar los no disponibles en este destino */
    var data=null;
    if(T.lima_lima.perifericas[destino]) data=T.lima_lima.perifericas[destino];
    else if(T.lima_lima.distritos[destino]) data=T.lima_lima.distritos[destino];
    Array.from(vehSel.options).forEach(function(op){
        if(!op.value)return;
        op.style.display=(data&&data[op.value]===null)?'none':'';
    });
    /* Restaurar selección o elegir primero disponible */
    vehSel.value=actual;
    if(!vehSel.value||vehSel.options[vehSel.selectedIndex].style.display==='none'){
        for(var i=0;i<vehSel.options.length;i++){
            if(vehSel.options[i].style.display!=='none'&&vehSel.options[i].value){vehSel.value=vehSel.options[i].value;break;}
        }
    }
}

/* ── Cambio de modalidad ────────────────────────────────────── */
var modSel=document.getElementById('wpcte-mod');
var origDestRow=document.getElementById('wpcte-orig-dest-row');
var mercRow=document.getElementById('wpcte-merc-row');
var res=document.getElementById('wpcte-cot-resultado');
var btnCont=document.getElementById('wpcte-btn-continuar');

// Al init: filtrar modalidades
filtrarModalidades();

modSel.addEventListener('change',function(){
    var m=this.value;
    precio=null;origCot='';destCot='';
    if(res){res.className='';res.innerHTML='';}
    if(btnCont)btnCont.className='';
    if(devRow)devRow.style.display='none';
    if(devCheck)devCheck.checked=false;

    origDestRow.style.display=(m&&m!=='')?'flex':'none';
    mercRow.style.display=(m==='mercaderia')?'flex':'none';
    modoOrigen('select');

    if(m==='lima_lima'){
        repoblarSelect('wpcte-orig',getLimaOpts(),false);
        repoblarSelect('wpcte-dest',getLimaOpts(),false);
        showExtra(['wpcte-veh-wrap']);
    } else if(m==='carga_general'){
        modoOrigen('cg');
        repoblarSelect('wpcte-orig-cg',getCGOrigenes(),true);
        repoblarSelect('wpcte-dest',[],false);
        showExtra(['wpcte-mod2-wrap','wpcte-tipo-medida-wrap','wpcte-peso-wrap']);
        actualizarCamposMedida();
        var cgSel=document.getElementById('wpcte-orig-cg');
        if(cgSel&&cgSel.value) actualizarDestinosCG(cgSel.value);
    } else if(m==='mercaderia'){
        /* Origen: todos los lugares de Lima + Provincia de mercadería */
        var lugsOrig=[];
        (L.merc_lima||[]).forEach(function(l){if(lugsOrig.indexOf(l)<0)lugsOrig.push(l);});
        (L.merc_provincia||[]).forEach(function(l){if(lugsOrig.indexOf(l)<0)lugsOrig.push(l);});
        repoblarSelect('wpcte-orig',lugsOrig,false);
        /* Destino: vacío hasta que se elija origen */
        repoblarSelect('wpcte-dest',[],false);
        /* Repoblar categorías */
        var $catEl=document.getElementById('wpcte-merc-cat');
        if($catEl){while($catEl.options.length>1)$catEl.remove(1);Object.keys(T.mercaderia.categorias||{}).forEach(function(k){var op=document.createElement('option');op.value=k;op.textContent=T.mercaderia.categorias[k].label||k;$catEl.appendChild(op);});}
        showExtra([]);
    } else if(m==='aereo'){
        modoOrigen('aereo');
        /* Poblar select de origen aéreo */
        repoblarSelect('wpcte-orig-aereo-sel',L.aereo_origenes||[],false);
        /* Destinos vacíos hasta elegir origen */
        repoblarSelect('wpcte-dest',[],false);
        showExtra(['wpcte-peso-wrap']);
    } else if(m==='sobres'){
        /* mostrar orig/dest de arriba para sobres */
        origDestRow.style.display='flex';
        showExtra(['wpcte-sobre-tipo-wrap','wpcte-mod-sobre-wrap']);
        poblarTiposSobre();
    } else {
        origDestRow.style.display='none';showExtra([]);
    }
    forzar();
});

/* ── Entrega Carga General: ajustar según tipo de envío ──────── */
function ajustarEntregaCG(){
    var mod2=document.getElementById('wpcte-mod2');
    if(!mod2)return;
    // agencia solo puede agencia (si tipo=agencia); almacen solo agencia
    if(TIPO_ENVIO==='agencia'||TIPO_ENVIO==='almacen'){
        Array.from(mod2.options).forEach(function(op){
            op.style.display=op.value==='agencia'?'':'none';
        });
        mod2.value='agencia';
    } else {
        Array.from(mod2.options).forEach(function(op){op.style.display='';});
    }
}

/* ── Actualizar destinos Carga General según origen ─────────── */
function actualizarDestinosCG(origen){
    var ruta=T.carga_general.rutas[origen];
    var dests=ruta?Object.keys(ruta.destinos):[];
    repoblarSelect('wpcte-dest',dests,false);
}
var cgOrigSel=document.getElementById('wpcte-orig-cg');
if(cgOrigSel) cgOrigSel.addEventListener('change',function(){actualizarDestinosCG(this.value);});

/* ── Cambio de destino: filtrar vehículos (Dentro de Lima) ──── */
var destSel=document.getElementById('wpcte-dest');
if(destSel) destSel.addEventListener('change',function(){
    if(modSel.value==='lima_lima') filtrarVehiculos(this.value);
});
/* Aéreo: al cambiar origen → filtrar destinos */
var aereoOrigSel=document.getElementById('wpcte-orig-aereo-sel');
if(aereoOrigSel) aereoOrigSel.addEventListener('change',function(){
    var origen=this.value;
    var dests=(L.aereo_rutas||{})[origen]||[];
    repoblarSelect('wpcte-dest',dests,false);
});

/* ── Mercadería: cargar productos de categoría ──────────────── */
var catSel=document.getElementById('wpcte-merc-cat');
var prodSel=document.getElementById('wpcte-merc-prod');
var mercTipoSel=document.getElementById('wpcte-merc-tipo');
var MERC_TIPO_MAP={
    'll':{orig:'lima',  dest:'lima',     label:'Lima → Lima'},
    'lp':{orig:'lima',  dest:'provincia',label:'Lima → Provincia'},
    'pp':{orig:'provincia',dest:'provincia',label:'Provincia → Provincia'}
};

function cargarProductosCat(cat){
    if(!prodSel)return;
    prodSel.innerHTML='<option value="">-- Seleccione producto --</option>';
    if(!cat||!T.mercaderia.categorias[cat])return;
    var items=T.mercaderia.categorias[cat].items||{};
    Object.keys(items).forEach(function(n){
        var op=document.createElement('option');
        op.value=n;op.textContent=n+' — S/ '+Number(items[n]).toFixed(2);
        prodSel.appendChild(op);
    });
}

/* Al cambiar tipo de ruta → filtrar origen y destino */
function onMercTipoChange(){
    var tid=mercTipoSel?mercTipoSel.value:'';
    if(!tid){
        repoblarSelect('wpcte-orig',[],false);
        repoblarSelect('wpcte-dest',[],false);
        if(catSel){catSel.value='';cargarProductosCat('');}
        return;
    }
    var map=MERC_TIPO_MAP[tid];if(!map)return;
    var lugsOrig=map.orig==='lima'?(L.merc_lima||[]):(L.merc_provincia||[]);
    var lugsDest=map.dest==='lima'?(L.merc_lima||[]):(L.merc_provincia||[]);
    repoblarSelect('wpcte-orig',lugsOrig,false);
    repoblarSelect('wpcte-dest',lugsDest,false);
    /* Filtrar categorías según tipo */
    if(catSel){
        Array.from(catSel.options).forEach(function(op){
            if(!op.value){return;}
            var cat=T.mercaderia.categorias[op.value];
            if(!cat){op.style.display='';return;}
            var rutas=cat.rutas||[];
            if(!rutas.length){op.style.display='';return;}
            op.style.display=rutas.indexOf(tid)>-1?'':'none';
        });
        if(catSel.value&&catSel.options[catSel.selectedIndex].style.display==='none'){
            catSel.value='';cargarProductosCat('');
        }
    }
}
if(mercTipoSel) mercTipoSel.addEventListener('change',onMercTipoChange);
/* Al cambiar ORIGEN en mercadería → filtrar destinos según Lima/Provincia */
function actualizarDestinosMerc(){
    var orig=document.getElementById('wpcte-orig')?document.getElementById('wpcte-orig').value:'';
    var origEsLima=(L.merc_lima||[]).indexOf(orig.toUpperCase())>-1;
    var origEsProv=(L.merc_provincia||[]).indexOf(orig.toUpperCase())>-1;
    var RUTA_MAP={ll:{orig:'lima',dest:'lima'},lp:{orig:'lima',dest:'provincia'},pp:{orig:'provincia',dest:'provincia'}};
    /* Qué destinos son alcanzables desde este origen (según rutas de cualquier categoría) */
    var destinosAlc={lima:false,provincia:false};
    Object.values(T.mercaderia.categorias||{}).forEach(function(cat){
        (cat.rutas||[]).forEach(function(rid){
            var r=RUTA_MAP[rid];if(!r)return;
            if(origEsLima&&r.orig==='lima'){destinosAlc[r.dest]=true;}
            if(origEsProv&&r.orig==='provincia'){destinosAlc[r.dest]=true;}
        });
    });
    /* Si no hay rutas definidas, mostrar todos */
    var sinRutas=Object.values(T.mercaderia.categorias||{}).every(function(c){return !c.rutas||!c.rutas.length;});
    var lugsDest=[];
    if(sinRutas||destinosAlc.lima)(L.merc_lima||[]).forEach(function(l){if(lugsDest.indexOf(l)<0)lugsDest.push(l);});
    if(sinRutas||destinosAlc.provincia)(L.merc_provincia||[]).forEach(function(l){if(lugsDest.indexOf(l)<0)lugsDest.push(l);});
    repoblarSelect('wpcte-dest',lugsDest,false);
    filtrarCatsMerc();
}

/* Filtrar categorías según orig/dest y sus rutas */
function filtrarCatsMerc(){
    if(!catSel)return;
    var orig=document.getElementById('wpcte-orig')?document.getElementById('wpcte-orig').value:'';
    var dest=destSel?destSel.value:'';
    var origEsLima=(L.merc_lima||[]).indexOf(orig.toUpperCase())>-1;
    var origEsProv=(L.merc_provincia||[]).indexOf(orig.toUpperCase())>-1;
    var destEsLima=(L.merc_lima||[]).indexOf(dest.toUpperCase())>-1;
    var destEsProv=(L.merc_provincia||[]).indexOf(dest.toUpperCase())>-1;
    var RUTA_MAP={ll:{orig:'lima',dest:'lima'},lp:{orig:'lima',dest:'provincia'},pp:{orig:'provincia',dest:'provincia'}};
    Array.from(catSel.options).forEach(function(op){
        if(!op.value){return;}
        var cat=T.mercaderia.categorias[op.value];
        if(!cat){op.style.display='';return;}
        var rutas=cat.rutas||[];
        if(!rutas.length){op.style.display='';return;}
        var ok=rutas.some(function(rid){
            var r=RUTA_MAP[rid];if(!r)return false;
            var origOk=!orig||(r.orig==='lima'?origEsLima:origEsProv);
            var destOk=!dest||(r.dest==='lima'?destEsLima:destEsProv);
            return origOk&&destOk;
        });
        op.style.display=ok?'':'none';
    });
    if(catSel.value&&catSel.options[catSel.selectedIndex]&&catSel.options[catSel.selectedIndex].style.display==='none'){
        catSel.value='';cargarProductosCat('');
    }
}
if(catSel) catSel.addEventListener('change',function(){cargarProductosCat(this.value);});
if(document.getElementById('wpcte-orig')) document.getElementById('wpcte-orig').addEventListener('change',function(){
    if(modSel.value==='mercaderia')actualizarDestinosMerc();
});
if(destSel) destSel.addEventListener('change',function(){
    if(modSel.value==='mercaderia')filtrarCatsMerc();
});

/* ── CALCULAR ──────────────────────────────────────────────── */
document.getElementById('wpcte-cot-btn').addEventListener('click',function(){
    var m=modSel.value;
    precio=null;origCot='';destCot='';
    if(res){res.className='';res.innerHTML='';}
    if(btnCont)btnCont.className='';
    var lines=[],des='',p=null,lead='';

    var orig=(m==='aereo')?'':(m==='carga_general'?(cgOrigSel?cgOrigSel.value:''):(document.getElementById('wpcte-orig')?document.getElementById('wpcte-orig').value:''));
    var dest=destSel?destSel.value:'';

    if(!m){res.innerHTML='&#9888; Seleccione modalidad.';res.className='ok';return;}

    /* ── Dentro de Lima ── */
    if(m==='lima_lima'){
        if(!orig||!dest){res.innerHTML='&#9888; Seleccione origen y destino.';res.className='ok';return;}
        var veh=document.getElementById('wpcte-veh').value;
        var esPer=T.lima_lima.perifericas[dest]!==undefined;
        if(esPer){
            var tarP=T.lima_lima.perifericas[dest];
            p=tarP[veh];
            if(p===null||p===undefined){res.innerHTML='&#9888; Vehículo no disponible para zona periférica <strong>'+dest+'</strong>.';res.className='ok';return;}
            origCot=orig;destCot=dest;
            lines.push('<strong>'+orig+' &rarr; '+dest+'</strong> <span style="background:#e76f51;color:#fff;padding:1px 6px;border-radius:4px;font-size:.78rem">Zona Periférica</span>');
            lines.push('Vehículo: <strong>'+veh.charAt(0).toUpperCase()+veh.slice(1)+'</strong> (precio fijo)');
            des='<div class="wpcte-desglose"><div class="wpcte-desglose-row"><span>Tarifa fija '+veh+'</span><span>S/ '+p.toFixed(2)+'</span></div><div class="wpcte-total-row"><span>TOTAL</span><span>S/ '+p.toFixed(2)+'</span></div></div>';
        } else {
            var tar=T.lima_lima.distritos[dest];
            if(!tar){res.innerHTML='&#9888; Destino no encontrado.';res.className='ok';return;}
            var precioAd=tar[veh];
            if(precioAd===null||precioAd===undefined){res.innerHTML='&#9888; Vehículo no disponible en '+dest+'.';res.className='ok';return;}
            var vehData=T.lima_lima.vehiculos[veh];
            var base_v=vehData?vehData.precio_base:0;
            p=base_v+precioAd;
            origCot=orig;destCot=dest;
            lines.push('<strong>'+orig+' &rarr; '+dest+'</strong>');
            lines.push('Vehículo: <strong>'+veh.charAt(0).toUpperCase()+veh.slice(1)+'</strong>');
            des='<div class="wpcte-desglose"><div class="wpcte-desglose-row"><span>Base vehículo ('+veh+')</span><span>S/ '+base_v.toFixed(2)+'</span></div><div class="wpcte-desglose-row"><span>Tarifa destino '+dest+'</span><span>S/ '+precioAd.toFixed(2)+'</span></div><div class="wpcte-total-row"><span>TOTAL</span><span>S/ '+p.toFixed(2)+'</span></div></div>';
        }

    /* ── Carga General ── */
    } else if(m==='carga_general'){
        if(!orig||!dest){res.innerHTML='&#9888; Seleccione origen y destino.';res.className='ok';return;}
        var mod2=document.getElementById('wpcte-mod2').value;
        var medida=tipoMedidaSel?tipoMedidaSel.value:'kg';
        var ruta2=T.carga_general.rutas[orig];
        if(!ruta2||!ruta2.destinos[dest]){res.innerHTML='&#9888; Ruta no encontrada.';res.className='ok';return;}
        var tar2=ruta2.destinos[dest];
        origCot=orig;destCot=dest;
        var pesoEf;var esVol=false;
        if(medida==='kg'){
            pesoEf=parseFloat(document.getElementById('wpcte-peso').value)||1;
        } else {
            var alto=parseFloat(document.getElementById('wpcte-alto').value)||1;
            var ancho=parseFloat(document.getElementById('wpcte-ancho').value)||1;
            var largo=parseFloat(document.getElementById('wpcte-largo').value)||1;
            pesoEf=(alto*ancho*largo)/5000;esVol=true;
        }
        var base2=tar2.base,xkg2,mLabel2;
        if(mod2==='agencia'){xkg2=pesoEf<=100?tar2.agencia:tar2.x_kilo_101_500;mLabel2='Agencia';}
        else{xkg2=pesoEf<=100?tar2.domicilio:tar2.x_kilo_101_500;mLabel2='Domicilio';}
        p=base2+xkg2*pesoEf;lead=tar2.lead;
        lines.push('<strong>'+orig+' &rarr; '+dest+'</strong> — '+mLabel2);
        if(esVol){
            lines.push('Vol: <strong>'+alto+'x'+ancho+'x'+largo+' cm</strong> = '+pesoEf.toFixed(2)+' kg vol | Lead: <strong>'+lead+'</strong>');
            des='<div class="wpcte-desglose"><div class="wpcte-desglose-row"><span>Base</span><span>S/ '+base2.toFixed(2)+'</span></div><div class="wpcte-desglose-row"><span>'+pesoEf.toFixed(2)+' kg vol × S/ '+xkg2.toFixed(2)+' ('+mLabel2+')</span><span>S/ '+(xkg2*pesoEf).toFixed(2)+'</span></div><div class="wpcte-total-row"><span>TOTAL</span><span>S/ '+p.toFixed(2)+'</span></div></div>';
        } else {
            lines.push('Peso: <strong>'+pesoEf+' kg</strong> | Lead: <strong>'+lead+'</strong>');
            des='<div class="wpcte-desglose"><div class="wpcte-desglose-row"><span>Base</span><span>S/ '+base2.toFixed(2)+'</span></div><div class="wpcte-desglose-row"><span>'+pesoEf+' kg × S/ '+xkg2.toFixed(2)+' ('+mLabel2+')</span><span>S/ '+(xkg2*pesoEf).toFixed(2)+'</span></div><div class="wpcte-total-row"><span>TOTAL</span><span>S/ '+p.toFixed(2)+'</span></div></div>';
        }

    /* ── Mercadería Frecuente ── */
    } else if(m==='mercaderia'){
        var cat=catSel.value,prod=prodSel.value;
        if(!orig||!dest){res.innerHTML='&#9888; Seleccione origen y destino.';res.className='ok';return;}
        if(!cat||!prod){res.innerHTML='&#9888; Seleccione categoría y producto.';res.className='ok';return;}
        p=T.mercaderia.categorias[cat].items[prod];
        if(!p){res.innerHTML='&#9888; Producto no encontrado.';res.className='ok';return;}
        origCot=orig;destCot=dest;
        lines.push('<strong>Mercadería Frecuente</strong>');
        lines.push(T.mercaderia.categorias[cat].label+': <strong>'+prod+'</strong>');
        lines.push(orig+' &rarr; '+dest);
        des='<div class="wpcte-desglose"><div class="wpcte-desglose-row"><span>Precio fijo</span><span>S/ '+p.toFixed(2)+'</span></div><div class="wpcte-total-row"><span>TOTAL</span><span>S/ '+p.toFixed(2)+'</span></div></div>';

    /* ── Aéreos ── */
    } else if(m==='aereo'){
        if(!dest){res.innerHTML='&#9888; Seleccione destino.';res.className='ok';return;}
        var peso3=parseFloat(document.getElementById('wpcte-peso').value)||1;
        var aerOrig=aereoOrigSel?aereoOrigSel.value:'';
        var tar3=(T.aereo.rutas&&T.aereo.rutas[aerOrig])?T.aereo.rutas[aerOrig].destinos[dest]:null;
        if(!tar3){res.innerHTML='&#9888; Ruta aérea no encontrada.';res.className='ok';return;}
        origCot=aereoOrigSel?aereoOrigSel.value:'';destCot=dest;lead=tar3.lead;
        if(peso3<=1){
            p=tar3.precio_1kg;
            des='<div class="wpcte-desglose"><div class="wpcte-desglose-row"><span>1 kg (tarifa base)</span><span>S/ '+p.toFixed(2)+'</span></div><div class="wpcte-total-row"><span>TOTAL</span><span>S/ '+p.toFixed(2)+'</span></div></div>';
        } else {
            var exc=peso3-1;p=tar3.base_kg+exc*tar3.exceso_kg;
            des='<div class="wpcte-desglose"><div class="wpcte-desglose-row"><span>Base (1 kg)</span><span>S/ '+tar3.base_kg.toFixed(2)+'</span></div><div class="wpcte-desglose-row"><span>'+exc.toFixed(1)+' kg × S/ '+tar3.exceso_kg.toFixed(2)+'</span><span>S/ '+(exc*tar3.exceso_kg).toFixed(2)+'</span></div><div class="wpcte-total-row"><span>TOTAL</span><span>S/ '+p.toFixed(2)+'</span></div></div>';
        }
        lines.push('<strong>'+(aereoOrigSel?aereoOrigSel.value:'?')+' &rarr; '+dest+'</strong>'+(tar3.zona?' ('+tar3.zona+')':''));
        lines.push('Peso: <strong>'+peso3+' kg</strong> | Lead: <strong>'+lead+'</strong>');

    /* ── Sobres ── */
    } else if(m==='sobres'){
        var sobreTipo=sobreTipoSel?sobreTipoSel.value:'';
        var sobreOrig=document.getElementById('wpcte-orig')?document.getElementById('wpcte-orig').value:'';
        var sobreDest=destSel?destSel.value:'';
        if(!sobreTipo){res.innerHTML='&#9888; Seleccione tipo de sobre.';res.className='ok';return;}
        if(!sobreOrig||!sobreDest){res.innerHTML='&#9888; Seleccione origen y destino.';res.className='ok';return;}
        var mS=document.getElementById('wpcte-mod-sobre').value;
        var tar4=T.sobres.tarifas[sobreTipo];
        if(!tar4){res.innerHTML='&#9888; Tarifa no encontrada.';res.className='ok';return;}
        origCot=sobreOrig;destCot=sobreDest;
        var tS4=sobreTipo;
        var baseP4=tar4[mS]||0;
        var conDev=devCheck&&devCheck.checked&&tar4.devolucion;
        var devValor=conDev?tar4.devolucion:0;
        p=baseP4+devValor;
        lines.push('<strong>Sobre: '+esc(tS4)+'</strong> ('+mS+')');
        lines.push(sobreOrig+' &rarr; '+sobreDest);
        des='<div class="wpcte-desglose"><div class="wpcte-desglose-row"><span>'+mS.charAt(0).toUpperCase()+mS.slice(1)+'</span><span>S/ '+baseP4.toFixed(2)+'</span></div>';
        if(conDev) des+='<div class="wpcte-desglose-row"><span>Devolución de cargo</span><span>S/ '+devValor.toFixed(2)+'</span></div>';
        des+='<div class="wpcte-total-row"><span>TOTAL</span><span>S/ '+p.toFixed(2)+'</span></div></div>';
    } else {
        res.innerHTML='&#9888; Seleccione modalidad.';res.className='ok';return;
    }

    if(p===null){res.innerHTML='&#9888; Error calculando precio.';res.className='ok';return;}
    res.innerHTML=lines.join('<br>')+des;
    res.className='ok';
    precio=p;
    if(btnCont)btnCont.className='visible';
    window._wpcte_precio=precio;
    window._wpcte_orig=origCot;
    window._wpcte_dest=destCot;
});

/* ── Botón continuar (solo crear) ─────────────────────────── */
var btnCont2=document.getElementById('wpcte-btn-continuar');
if(btnCont2){
    btnCont2.addEventListener('click',function(){
        if(precio===null)return;
        var _pc=precio,_oc=origCot,_dc=destCot;
        var pantallaForm=document.getElementById('wpcte-pantalla-form');
        var pantallaCot=document.getElementById('wpcte-pantalla-cotizador');
        if(pantallaForm) pantallaForm.style.display='block';
        if(pantallaCot) pantallaCot.style.display='none';
        function _fillCampos(){
            var me=document.getElementById('monto');if(me)me.value=_pc.toFixed(2);
            function s2(id,val){
                if(!val)return;
                var $s=typeof jQuery!=='undefined'?jQuery('#'+id):null;
                if(!$s||!$s.length)return;
                if($s.find('option[value="'+val+'"]').length){$s.val(val).trigger('change');}
                else{$s.append(new Option(val,val,true,true)).trigger('change');}
            }
            if(_oc)s2('lugar_origen',_oc);
            if(_dc)s2('lugar_destino',_dc);
        }
        _fillCampos();setTimeout(_fillCampos,300);setTimeout(_fillCampos,800);setTimeout(_fillCampos,1500);
    });
}

// Init entrega CG
ajustarEntregaCG();
})();
</script>
<?php
    return ob_get_clean();
}

/* ======================================================================
   FOOTER CREAR
   ====================================================================== */
function wpcte_footer_crear() {
    if ( ( $_GET['wpcfe'] ?? '' ) !== 'add' ) return;

    $tipos_validos = array( 'puerta_puerta', 'agencia', 'almacen' );
    $tipo          = sanitize_key( $_GET['tipo_envio'] ?? '' );

    // Si no hay tipo, no mostramos nada aquí.
    // El selector de tipo se maneja externamente (ej. WPCode / shortcode [wpcte_selector_tipo]).
    if ( ! in_array( $tipo, $tipos_validos ) ) return;

    $t          = $tipos[$tipo] ?? array( 'label'=>$tipo, 'icon'=>'fa-truck' );
    $tarifario  = wpcte_tarifario();
    $es_admin   = current_user_can('manage_options') ? 1 : 0;
    $ajaxurl    = admin_url('admin-ajax.php');
    $nonce      = $es_admin ? wp_create_nonce('wpcte_get_user') : '';

    if ( ! $es_admin ) {
        $u      = wp_get_current_user();
        $nombre = trim( $u->first_name.' '.$u->last_name ) ?: $u->display_name;
        $dir1   = get_user_meta( $u->ID, 'billing_address_1', true );
        $dir2   = get_user_meta( $u->ID, 'billing_address_2', true );
        $dir    = $dir2 ? $dir1.', '.$dir2 : $dir1;
        $datos_js = wp_json_encode( array( 'nombre'=>$nombre,'telefono'=>get_user_meta($u->ID,'billing_phone',true),'direccion'=>$dir,'ciudad'=>get_user_meta($u->ID,'billing_city',true) ) );
    } else {
        $datos_js = 'null';
    }

    $tipo_js    = wp_json_encode( $tipo );
    $ajaxurl_js = wp_json_encode( $ajaxurl );
    $nonce_js   = wp_json_encode( $nonce );
    $esadmin_js = $es_admin ? 'true' : 'false';
    ?>
    <div id="wpcte-pantalla-cotizador" style="display:none">
        <button type="button" class="wpcte-back-btn" onclick="wpcteIrSelector()">&larr; Cambiar tipo de envío</button>
        <div class="wpcte-badge">
            <i class="fa <?php echo esc_attr($t['icon']); ?>"></i>
            Tipo: <strong><?php echo esc_html($t['label']); ?></strong>
        </div>
        <?php echo wpcte_render_cotizador_html( $tarifario, $tipo, 'crear' ); ?>
    </div>
    <div id="wpcte-pantalla-form" style="display:none">
        <button type="button" class="wpcte-back-btn" onclick="wpcteIrCotizador()">&larr; Volver al cotizador</button>
        <div class="wpcte-badge">
            <i class="fa <?php echo esc_attr($t['icon']); ?>"></i>
            Tipo: <strong><?php echo esc_html($t['label']); ?></strong>
        </div>
    </div>
    <script>
    window._wpcte_tipo=<?php echo $tipo_js; ?>;
    function wpcteIrSelector(){var url=new URL(window.location.href);url.searchParams.delete('tipo_envio');window.location.href=url.toString();}
    function wpcteIrCotizador(){document.getElementById('wpcte-pantalla-cotizador').style.display='block';document.getElementById('wpcte-pantalla-form').style.display='none';}
    jQuery(document).ready(function($){
        var tipo=<?php echo $tipo_js; ?>;
        var uData=<?php echo $datos_js; ?>;
        var isAdmin=<?php echo $esadmin_js; ?>;
        var ajaxUrl=<?php echo $ajaxurl_js; ?>;
        var nonce=<?php echo $nonce_js; ?>;
        var f=document.querySelector('form.add-shipment');
        if(!isAdmin&&uData){(function(d){
            var rem=document.getElementById('remitente');if(rem)rem.value=d.nombre||'';
            var tel=document.getElementById('telefono_remitente');if(tel)tel.value=d.telefono||'';
            wpcte_insertDir(d.direccion||'');window._clienteCiudad=d.ciudad||'';
        })(uData);}
        if(!f)return;
        f.style.display='none';
        var cotEl=document.getElementById('wpcte-pantalla-cotizador');
        var formEl=document.getElementById('wpcte-pantalla-form');
        f.parentNode.insertBefore(cotEl,f);f.parentNode.insertBefore(formEl,f);
        cotEl.style.display='block';formEl.appendChild(f);f.style.display='block';
        if(!f.querySelector('[name="wpcte_tipo_envio"]')){var hi=document.createElement('input');hi.type='hidden';hi.name='wpcte_tipo_envio';hi.value=tipo;f.appendChild(hi);}
        var pkg=document.getElementById('package_id');if(pkg)pkg.style.display='none';
        wpcte_insertDir('');
        wpcte_ajustarDrivers(tipo,f,null,null);
        if(isAdmin) wpcte_listenCliente(ajaxUrl,nonce);
    });
    </script>
    <?php if ( $es_admin ): ?>
    <script>
    (function(){
        var _aj=<?php echo $ajaxurl_js; ?>,_nn=<?php echo $nonce_js; ?>;
        function _reg(){
            jQuery(document).off('select2:select.wpcli change.wpcli','#registered_client');
            jQuery(document).on('select2:select.wpcli change.wpcli','#registered_client',function(){
                var uid=jQuery(this).val();if(!uid)return;
                var fd=new FormData();fd.append('action','wpcte_get_user_data');fd.append('uid',uid);fd.append('_ajax_nonce',_nn);
                fetch(_aj,{method:'POST',credentials:'include',body:fd}).then(r=>r.json()).then(function(res){
                    if(!res.success)return;
                    var el=document.getElementById('remitente');if(el)el.value=res.data.nombre;
                    var te=document.getElementById('telefono_remitente');if(te)te.value=res.data.telefono;
                    wpcte_insertDir(res.data.direccion);window._clienteCiudad=res.data.ciudad||'';
                });
            });
        }
        jQuery(document).ready(function(){_reg();setTimeout(_reg,1000);setTimeout(_reg,3000);});
    })();
    </script>
    <?php endif; ?>
    <script>
    /* Helpers compartidos */
    function wpcte_insertDir(dir){
        var telEl=document.getElementById('telefono_remitente');if(!telEl)return;
        var tg=telEl.closest('.form-group')||telEl.parentElement;
        var ex=document.getElementById('wpcte-dir-rem');
        if(!ex){var ng=tg.cloneNode(true);ng.id='wpcte-dir-rem-wrap';var nl=ng.querySelector('label');if(nl)nl.textContent='Dirección Remitente';var ni=ng.querySelector('input');if(ni){ni.id='wpcte-dir-rem';ni.name='direccion_remitente';}tg.parentNode.insertBefore(ng,tg.nextSibling);ex=ni||ng;}
        var ni2=(ex&&ex.tagName==='INPUT')?ex:(ex?ex.querySelector('input'):null);
        if(ni2)ni2.value=dir||'';
    }
    function wpcte_ajustarDrivers(tipo,formEl,drvEgId,contEgId){
        function cambL(sn,nl){var ds=formEl?formEl.querySelector('select[name="'+sn+'"]'):null;if(!ds)return;var dg=ds.parentElement;while(dg&&!dg.querySelector(':scope>label')){dg=dg.parentElement;}var dl=dg?dg.querySelector(':scope>label'):null;if(dl)dl.textContent=nl;}
        function cambP(sn,txt){var ds=formEl?formEl.querySelector('select[name="'+sn+'"]'):null;if(!ds)return;var op=ds.querySelector('option[value=""]');if(op)op.textContent=txt;}
        function clonarG(sn,nid,nl2,nn,pre){if(document.getElementById(nid))return;var ds=formEl?formEl.querySelector('select[name="'+sn+'"]'):null;if(!ds)return;var dg=ds.parentElement;while(dg&&!dg.querySelector(':scope>label')){dg=dg.parentElement;}if(!dg)return;var c=dg.cloneNode(true);c.id=nid;var cl=c.querySelector(':scope>label');if(cl)cl.textContent=nl2;var cs=c.querySelector('select');if(cs){cs.name=nn;cs.id=nn;if(pre)cs.value=pre;}dg.parentNode.insertBefore(c,dg.nextSibling);}
        cambP('wpcargo_driver','-- Seleccione conductor --');
        if(tipo==='puerta_puerta'){
            cambL('wpcargo_driver','Conductor de recojo');
            clonarG('wpcargo_driver','wpcte-drv-eg','Conductor de entrega','wpcargo_driver_entrega',drvEgId);
            cambP('wpcargo_driver_entrega','-- Seleccione conductor --');
            // Renombrar el select original para que envíe como `wpcargo_driver_recojo`
            try{
                var _ds = formEl?formEl.querySelector('select[name="wpcargo_driver"]'):null;
                if(_ds){ _ds.name = 'wpcargo_driver_recojo'; _ds.id = 'wpcargo_driver_recojo'; }
            }catch(e){}
            cambL('shipment_container','Contenedor de recojo');
            clonarG('shipment_container','wpcte-cont-eg','Contenedor de entrega','shipment_container_entrega',contEgId);
            // Ensure hidden visibility field and updater
            try{
                if(formEl && !formEl.querySelector('[name="wpcargo_driver"]')){
                    var _h=document.createElement('input');_h.type='hidden';_h.name='wpcargo_driver';_h.value='';formEl.appendChild(_h);
                }
                var _upd = function(){
                    var hid = formEl.querySelector('[name="wpcargo_driver"]'); if(!hid) return;
                    var reco = formEl.querySelector('select[name="wpcargo_driver_recojo"]') || formEl.querySelector('#wpcargo_driver_recojo');
                    var ent = formEl.querySelector('select[name="wpcargo_driver_entrega"]');
                    hid.value = reco && reco.value ? reco.value : '';
                };
                var _rsel = formEl.querySelector('select[name="wpcargo_driver_recojo"]'); if(_rsel) _rsel.addEventListener('change', _upd);
                var _esel = formEl.querySelector('select[name="wpcargo_driver_entrega"]'); if(_esel) _esel.addEventListener('change', _upd);
                _upd();
            }catch(e){}
        } else {
            cambL('wpcargo_driver','Conductor de entrega');
            cambL('shipment_container','Contenedor de entrega');
        }
    }
    function wpcte_listenCliente(ajaxUrl,nonce){
        setTimeout(function(){
            jQuery(document).off('select2:select.wpcte2 change.wpcte2','#registered_client');
            jQuery(document).on('select2:select.wpcte2 change.wpcte2','#registered_client',function(){
                var uid=jQuery(this).val();if(!uid)return;
                var fd=new FormData();fd.append('action','wpcte_get_user_data');fd.append('uid',uid);fd.append('_ajax_nonce',nonce);
                fetch(ajaxUrl,{method:'POST',credentials:'include',body:fd}).then(r=>r.json()).then(function(res){
                    if(!res.success)return;
                    var el=document.getElementById('remitente');if(el)el.value=res.data.nombre;
                    var te=document.getElementById('telefono_remitente');if(te)te.value=res.data.telefono;
                    wpcte_insertDir(res.data.direccion);window._clienteCiudad=res.data.ciudad||'';
                });
            });
        },600);
    }
    </script>
    <?php
}

/* ======================================================================
   FOOTER EDITAR
   ====================================================================== */
function wpcte_footer_editar() {
    if ( ( $_GET['wpcfe'] ?? '' ) !== 'update' ) return;
    if ( ! isset( $_GET['id'] ) ) return;

    $post_id    = absint( $_GET['id'] );
    $tipo       = get_post_meta( $post_id, 'tipo_envio', true ) ?: 'agencia';
    $tipo_label = array( 'puerta_puerta'=>'Puerta a Puerta','agencia'=>'Agencia','almacen'=>'Almacén' )[$tipo] ?? $tipo;
    $dir_rem    = get_post_meta( $post_id, 'direccion_remitente', true );
    $drv_eg     = (int) get_post_meta( $post_id, 'driver_entrega_id', true );
    $cont_eg    = get_post_meta( $post_id, 'contenedor_entrega_id', true );
    $es_admin   = current_user_can('manage_options') ? 1 : 0;
    $nonce      = $es_admin ? wp_create_nonce('wpcte_get_user') : '';
    $ajaxurl    = admin_url('admin-ajax.php');
    $tarifario  = wpcte_tarifario();

    $tipo_js = wp_json_encode($tipo);
    $tl_js   = wp_json_encode($tipo_label);
    $dr_js   = wp_json_encode($dir_rem);
    $dv_js   = wp_json_encode($drv_eg);
    $ce_js   = wp_json_encode($cont_eg);
    $ea_js   = $es_admin ? 'true' : 'false';
    $nj      = wp_json_encode($nonce);
    $aj      = wp_json_encode($ajaxurl);
    ?>
    <div style="margin-bottom:1rem">
        <span class="wpcte-badge"><i class="fa fa-truck"></i> Tipo de envío: <strong><?php echo esc_html($tipo_label); ?></strong></span>
    </div>
    <div id="wpcte-cot-edit-wrap">
        <button type="button" id="wpcte-verificar-btn">
            <i class="fa fa-calculator"></i> Verificar precio
        </button>
        <div id="wpcte-cot-body">
            <?php echo wpcte_render_cotizador_html( $tarifario, $tipo, 'editar' ); ?>
        </div>
    </div>
    <script>
		 if(typeof wpcte_insertDir==='undefined'){
    function wpcte_insertDir(dir){
        var telEl=document.getElementById('telefono_remitente');if(!telEl)return;
        var tg=telEl.closest('.form-group')||telEl.parentElement;
        var ex=document.getElementById('wpcte-dir-rem');
        if(!ex){var ng=tg.cloneNode(true);ng.id='wpcte-dir-rem-wrap';var nl=ng.querySelector('label');if(nl)nl.textContent='Dirección Remitente';var ni=ng.querySelector('input');if(ni){ni.id='wpcte-dir-rem';ni.name='direccion_remitente';}tg.parentNode.insertBefore(ng,tg.nextSibling);ex=ni||ng;}
        var ni2=(ex&&ex.tagName==='INPUT')?ex:(ex?ex.querySelector('input'):null);
        if(ni2)ni2.value=dir||'';
    }
    }
    if(typeof wpcte_ajustarDrivers==='undefined'){
    function wpcte_ajustarDrivers(tipo,formEl,drvEgId,contEgId){
        function cambL(sn,nl){var ds=formEl?formEl.querySelector('select[name="'+sn+'"]'):null;if(!ds)return;var dg=ds.parentElement;while(dg&&!dg.querySelector(':scope>label')){dg=dg.parentElement;}var dl=dg?dg.querySelector(':scope>label'):null;if(dl)dl.textContent=nl;}
        function cambP(sn,txt){var ds=formEl?formEl.querySelector('select[name="'+sn+'"]'):null;if(!ds)return;var op=ds.querySelector('option[value=""]');if(op)op.textContent=txt;}
        function clonarG(sn,nid,nl2,nn,pre){if(document.getElementById(nid))return;var ds=formEl?formEl.querySelector('select[name="'+sn+'"]'):null;if(!ds)return;var dg=ds.parentElement;while(dg&&!dg.querySelector(':scope>label')){dg=dg.parentElement;}if(!dg)return;var c=dg.cloneNode(true);c.id=nid;var cl=c.querySelector(':scope>label');if(cl)cl.textContent=nl2;var cs=c.querySelector('select');if(cs){cs.name=nn;cs.id=nn;if(pre)cs.value=pre;}dg.parentNode.insertBefore(c,dg.nextSibling);}
        cambP('wpcargo_driver','-- Seleccione conductor --');
        if(tipo==='puerta_puerta'){
            cambL('wpcargo_driver','Conductor de recojo');
            clonarG('wpcargo_driver','wpcte-drv-eg','Conductor de entrega','wpcargo_driver_entrega',drvEgId);
            cambP('wpcargo_driver_entrega','-- Seleccione conductor --');
            cambL('shipment_container','Contenedor de recojo');
            clonarG('shipment_container','wpcte-cont-eg','Contenedor de entrega','shipment_container_entrega',contEgId);
        } else {
            cambL('wpcargo_driver','Conductor de entrega');
            cambL('shipment_container','Contenedor de entrega');
        }
    }
    }
    jQuery(document).ready(function($){
        window._wpcte_tipo=<?php echo $tipo_js; ?>;
        var tipo=<?php echo $tipo_js; ?>;
        var isAdmin=<?php echo $ea_js; ?>;
        var ajaxUrl=<?php echo $aj; ?>;
        var nonce=<?php echo $nj; ?>;
        var formEl=$('form').filter(function(){return $(this).find('[name=shipment_id],[name=update_shipment]').length>0;}).get(0)||document.querySelector('form');

        // Inicializar dirección y drivers
        wpcte_insertDir(<?php echo $dr_js; ?>);
        wpcte_ajustarDrivers(tipo,formEl,<?php echo $dv_js; ?>,<?php echo $ce_js; ?>);
        if(isAdmin) wpcte_listenCliente(ajaxUrl,nonce);

        // Toggle verificar
        var _vBtn=document.getElementById('wpcte-verificar-btn');
        var _vBody=document.getElementById('wpcte-cot-body');
        if(_vBtn&&_vBody){
            _vBtn.addEventListener('click',function(){
                var isOpen=_vBody.classList.toggle('open');
                _vBtn.innerHTML=isOpen?'<i class="fa fa-times"></i> Cerrar cotizador':'<i class="fa fa-calculator"></i> Verificar precio';
            });
        }

        // Al calcular: aplicar monto
        var calcBtn=document.getElementById('wpcte-cot-btn');
        if(calcBtn){
            calcBtn.addEventListener('click',function(){
                setTimeout(function(){
                    var p=window._wpcte_precio;if(p===null||p===undefined)return;
                    var me=document.getElementById('monto');if(me)me.value=p.toFixed(2);
                    function s2(id,val){if(!val)return;var $s=$('#'+id);if(!$s.length)return;if($s.find('option[value="'+val+'"]').length){$s.val(val).trigger('change');}else{$s.append(new Option(val,val,true,true)).trigger('change');}}
                    function _fE(){var o=window._wpcte_orig,d=window._wpcte_dest;if(o)s2('lugar_origen',o);if(d)s2('lugar_destino',d);}
                    _fE();setTimeout(_fE,400);setTimeout(_fE,900);
                },100);
            });
        }

        $('[name=location],[name=remarks]').removeAttr('required');
        $('.card-header').filter(function(){return $(this).text().trim()==='Paquetes';}).closest('.card').parent().hide();
        $('#package_id').closest('.form-group,.mb-4').hide();

        // Mover badge y cotizador al inicio
        setTimeout(function(){
            var badgeDiv=document.querySelector('[style*="margin-bottom:1rem"]');
            var cotDiv=document.getElementById('wpcte-cot-edit-wrap');
            var target=formEl?formEl.parentElement:document.querySelector('form');
            if(!target||!cotDiv)return;
            if(badgeDiv&&badgeDiv.parentElement!==target)target.insertBefore(badgeDiv,target.firstChild);
            var after=badgeDiv&&badgeDiv.parentElement===target?badgeDiv.nextSibling:target.firstChild;
            target.insertBefore(cotDiv,after);
        },100);
    });
    </script>
    <?php
}

/* ======================================================================
   LISTA TIPOS
   ====================================================================== */



/* ======================================================================
   QUITAR REQUIRED
   ====================================================================== */
function wpcte_quitar_required() {
    $wpcfe = $_GET['wpcfe'] ?? '';
    if ( $wpcfe !== 'add' && $wpcfe !== 'update' ) return;
    echo '<script>jQuery(document).ready(function(){jQuery("#location,#remarks,[name=location],[name=remarks],[name=status]").removeAttr("required").prop("required",false);});</script>';
}

/* ======================================================================
   GUARDAR META
   ====================================================================== */
function wpcte_guardar_meta( $post_id ) {
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( wp_is_post_revision($post_id) ) return;
    if ( ! empty( $_POST['wpcte_tipo_envio'] ) )
        update_post_meta( $post_id, 'tipo_envio', sanitize_key( $_POST['wpcte_tipo_envio'] ) );
    if ( ! empty( $_POST['wpcargo_driver_entrega'] ) )
        update_post_meta( $post_id, 'driver_entrega_id', absint( $_POST['wpcargo_driver_entrega'] ) );
    if ( ! empty( $_POST['shipment_container_entrega'] ) )
        update_post_meta( $post_id, 'contenedor_entrega_id', sanitize_text_field( $_POST['shipment_container_entrega'] ) );
    if ( isset( $_POST['direccion_remitente'] ) )
        update_post_meta( $post_id, 'direccion_remitente', sanitize_text_field( $_POST['direccion_remitente'] ) );
}

function wpcte_guardar_meta_late( $post_id ) {
    if ( get_post_type($post_id) !== 'wpcargo' ) return;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( ! empty( $_POST['wpcte_tipo_envio'] ) )
        update_post_meta( $post_id, 'tipo_envio', sanitize_key( $_POST['wpcte_tipo_envio'] ) );
    if ( isset( $_POST['direccion_remitente'] ) )
        update_post_meta( $post_id, 'direccion_remitente', sanitize_text_field( $_POST['direccion_remitente'] ) );
}

/* ======================================================================
   HELPERS
   ====================================================================== */
function wpcte_tiene_almacen() {
    if ( ! is_user_logged_in() ) return false;
    $user = wp_get_current_user();
    if ( in_array('administrator', (array)$user->roles) ) return true;
    global $wpdb;
    $tabla = $wpdb->prefix.'wpca_productos';
    if ( $wpdb->get_var("SHOW TABLES LIKE '{$tabla}'")<>$tabla ) return false;
    $marca = get_user_meta($user->ID,'billing_company',true) ?: $user->display_name;
    $n = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tabla} WHERE marca=%s AND stock_actual>0 AND activo=1",$marca));
    return (int)$n > 0;
}


function wpcte_render_tarifario_page(): void {
    wpcte_render_frontend_page( 'tarifario_dhv' );
}

function wpcte_render_cotizador_page(): void {
    wpcte_render_frontend_page( 'cotizador_dhv' );
}
