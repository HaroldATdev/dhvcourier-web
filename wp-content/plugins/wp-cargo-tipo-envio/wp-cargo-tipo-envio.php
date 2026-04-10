<?php
/**
 * Plugin Name: WP Cargo Tipo de Envio
 * Version: 2.5.0
 * Author: DHV Courier
 * Description: Gestión de modalidades, tarifarios y cotizador DHV.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WPCTE_VERSION', '2.5.0' );
define( 'WPCTE_PATH',    plugin_dir_path( __FILE__ ) );
define( 'WPCTE_URL',     plugin_dir_url( __FILE__ ) );

/* ======================================================================
   TARIFARIO CENTRAL — editable desde admin
   ====================================================================== */
function wpcte_tarifario() {
    $saved = get_option( 'wpcte_tarifario', null );
    if ( $saved && is_array( $saved ) ) {
        // Asegurar que items de mercadería sean asociativos
        if ( isset( $saved['mercaderia']['categorias'] ) ) {
            foreach ( $saved['mercaderia']['categorias'] as $key => &$cat ) {
                if ( empty($cat['items']) ) $cat['items'] = [];
                elseif ( isset($cat['items']) && array_values($cat['items']) === $cat['items'] ) {
                    $cat['items'] = []; // era array indexado, resetear
                }
                if ( !isset($cat['rutas']) ) $cat['rutas'] = [];
            }
            unset($cat);
        }
        return $saved;
    }
    return wpcte_tarifario_default();
}

function wpcte_tarifario_default() {
    return array(
        'lima_lima'     => array( 'tipo'=>'lima_lima',     'titulo'=>'DHV-Dentro de Lima',        'vehiculos'=>array(), 'distritos'=>array(), 'perifericas'=>array() ),
        'carga_general' => array( 'tipo'=>'carga_general', 'titulo'=>'DHV-Carga General',         'rutas'=>array() ),
        'mercaderia' => array(
            'tipo'             => 'mercaderia',
            'titulo'           => 'DHV-Mercadería Frecuente',
            'lugares_lima'     => array(),
            'lugares_provincia'=> array(),
            'categorias'       => array(),
        ),
        'aereo' => array(
            'tipo'  => 'aereo',
            'titulo'=> 'DHV-Aéreos',
            'rutas' => array(),
        ),
        'sobres' => array(
            'tipo'              => 'sobres',
            'titulo'            => 'DHV-Sobres',
            'lugares_lima'      => array(),
            'lugares_provincia' => array(),
            'tarifas' => array(
                'Lima - Lima'           => array('activo'=>true,'agencia'=>7, 'domicilio'=>12,'devolucion'=>3),
                'Lima - Provincia'      => array('activo'=>true,'agencia'=>7, 'domicilio'=>12,'devolucion'=>3),
                'Provincia - Provincia' => array('activo'=>true,'agencia'=>5, 'domicilio'=>10,'devolucion'=>2),
            ),
        ),
    );
}
function wpcte_lugares() {
    $tar = wpcte_tarifario();

    // Orígenes y destinos de Lima: vienen de los distritos + periféricas
    $distritos   = array_keys( $tar['lima_lima']['distritos']   ?? [] );
    $perifericas = array_keys( $tar['lima_lima']['perifericas'] ?? [] );
    $all_lima    = array_merge( $distritos, $perifericas );

    // Provincias: todos los destinos de carga general que no están en lima
    $provincias = [];
    foreach ( $tar['carga_general']['rutas'] ?? [] as $ruta ) {
        foreach ( array_keys( $ruta['destinos'] ?? [] ) as $dest ) {
            if ( ! in_array( $dest, $all_lima ) && ! in_array( $dest, $provincias ) ) {
                $provincias[] = $dest;
            }
        }
    }

    // Orígenes aéreos: keys de rutas de aereo
    $aereo_origenes = array_keys( $tar['aereo']['rutas'] ?? [] );
    // Destinos por origen aéreo
    $aereo_rutas = [];
    foreach ( $tar['aereo']['rutas'] ?? [] as $desde => $ruta ) {
        $aereo_rutas[$desde] = array_keys( $ruta['destinos'] ?? [] );
    }

    return array(
        'lima'              => $distritos,
        'lima_perifericas'  => $perifericas,
        'provincias'        => $provincias,
        'aereo_origenes'    => $aereo_origenes,
        'aereo_rutas'       => $aereo_rutas,
        'merc_lima'         => $tar['mercaderia']['lugares_lima']      ?? [],
        'merc_provincia'    => $tar['mercaderia']['lugares_provincia']  ?? [],
        'sobres_lima'       => $tar['sobres']['lugares_lima']           ?? [],
        'sobres_provincia'  => $tar['sobres']['lugares_provincia']      ?? [],
    );
}
/* ======================================================================
   REGLAS: qué modalidades aparecen en cada tipo de envío
   ====================================================================== */
function wpcte_modalidades_por_tipo() {
    return array(
        'puerta_puerta' => array( 'lima_lima', 'mercaderia', 'sobres', 'carga_general' ),
        'agencia'       => array( 'lima_lima', 'aereo', 'sobres', 'carga_general' ),
        'almacen'       => array( 'lima_lima', 'carga_general' ),
    );
}

/* ======================================================================
   AJAX
   ====================================================================== */
add_action( 'wp_ajax_wpcte_set_tipo',           'wpcte_ajax_set_tipo' );
add_action( 'wp_ajax_wpcte_get_user_data',      'wpcte_ajax_get_user_data' );
add_action( 'wp_ajax_wpcte_get_tipo',           'wpcte_ajax_get_tipo' );
add_action( 'wp_ajax_nopriv_wpcte_get_tipo',    'wpcte_ajax_get_tipo' );
add_action( 'wp_ajax_wpcte_save_tarifario',     'wpcte_ajax_save_tarifario' );
add_action( 'wp_ajax_wpcte_reset_tarifario',    'wpcte_ajax_reset_tarifario' );
add_action('wp_footer', 'wpcte_tipo_en_lista');   // cliente
add_action('admin_footer', 'wpcte_tipo_en_lista'); // admin
add_action('wp_ajax_wpcte_get_tipos_batch', 'wpcte_ajax_get_tipos_batch');
add_action('wp_ajax_nopriv_wpcte_get_tipos_batch', 'wpcte_ajax_get_tipos_batch');

function wpcte_ajax_set_tipo() {
    if ( ! current_user_can('manage_options') ) wp_send_json_error('no_permission');
    $post_id = absint( $_POST['post_id'] ?? 0 );
    $tipo    = sanitize_key( $_POST['tipo'] ?? '' );
    if ( ! $post_id || ! $tipo ) wp_send_json_error('missing');
    update_post_meta( $post_id, 'tipo_envio', $tipo );
    wp_send_json_success( array( 'post_id' => $post_id, 'tipo' => $tipo ) );
}

function wpcte_ajax_get_user_data() {
    if ( ! current_user_can('manage_options') ) wp_send_json_error('no_permission');
    $uid = absint( $_POST['uid'] ?? 0 );
    if ( ! $uid ) wp_send_json_error('no_uid');
    $u = get_userdata( $uid );
    if ( ! $u ) wp_send_json_error('not_found');
    $nombre = trim( $u->first_name . ' ' . $u->last_name ) ?: $u->display_name;
    $dir1   = get_user_meta( $uid, 'billing_address_1', true );
    $dir2   = get_user_meta( $uid, 'billing_address_2', true );
    $dir    = $dir2 ? $dir1 . ', ' . $dir2 : $dir1;
    wp_send_json_success( array(
        'nombre'    => $nombre,
        'telefono'  => get_user_meta( $uid, 'billing_phone', true ),
        'direccion' => $dir,
        'ciudad'    => get_user_meta( $uid, 'billing_city', true ),
    ));
}


function wpcte_ajax_get_tipo() {
    $post_id = absint( $_POST['post_id'] ?? 0 );
    if ( ! $post_id ) wp_send_json_error();
    wp_send_json_success( array( 'tipo' => get_post_meta( $post_id, 'tipo_envio', true ) ) );
}

function wpcte_ajax_save_tarifario() {
    if ( ! current_user_can('manage_options') ) wp_send_json_error('no_permission');
    check_ajax_referer( 'wpcte_admin_nonce', 'nonce' );
    $data = json_decode( stripslashes( $_POST['tarifario'] ?? '{}' ), true );
    if ( ! is_array( $data ) ) wp_send_json_error('invalid_data');
    // Asegurar que items de mercadería sean objetos (no arrays vacíos)
    if ( isset( $data['mercaderia']['categorias'] ) ) {
        foreach ( $data['mercaderia']['categorias'] as $key => &$cat ) {
            if ( isset($cat['items']) && ( !is_array($cat['items']) || array_keys($cat['items']) === range(0, count($cat['items'])-1) ) ) {
                // Si items es un array indexado (no asociativo) o vacío, convertir a objeto
                if ( empty($cat['items']) ) {
                    $cat['items'] = new stdClass(); // se serializará como {}
                }
            }
            if ( !isset($cat['rutas']) ) $cat['rutas'] = [];
        }
        unset($cat);
    }
    update_option( 'wpcte_tarifario', $data );
    wp_send_json_success( 'saved' );
}

function wpcte_ajax_reset_tarifario() {
    if ( ! current_user_can('manage_options') ) wp_send_json_error('no_permission');
    check_ajax_referer( 'wpcte_admin_nonce', 'nonce' );
    delete_option( 'wpcte_tarifario' );
    wp_send_json_success( wpcte_tarifario_default() );
}

/* ======================================================================
   ADMIN MENU — Cotizador / Tarifario
   ====================================================================== */
/* ======================================================================
   ADMIN MENU — Se engancha en el menú de WPCargo FM
   WPCargo FM usa el parent slug "wpcargo" para su menú principal.
   Añadimos nuestras páginas como submenús de ese menú existente.
   Si WPCargo no está activo, creamos nuestro propio menú de respaldo.
   ====================================================================== */
add_action( 'admin_menu', 'wpcte_admin_menu', 99 ); // prioridad 99: después de que WPCargo registre el suyo
function wpcte_admin_menu() {
    // Detectar el slug padre de WPCargo FM
    // WPCargo FM registra su menú con el slug 'wpcargo'
    // Si existe ese menú, nos colgamos de él; si no, creamos uno propio.
    global $menu, $submenu;

    $wpcargo_slug = 'wpcargo'; // slug principal de WPCargo FM
    $parent_exists = false;

    if ( ! empty( $menu ) ) {
        foreach ( $menu as $item ) {
            if ( isset( $item[2] ) && $item[2] === $wpcargo_slug ) {
                $parent_exists = true;
                break;
            }
        }
    }

    if ( $parent_exists ) {
        // ── Modo integrado: submenús dentro de WPCargo ──────────────
        add_submenu_page(
            $wpcargo_slug,
            'Tarifario DHV',
            'Tarifario DHV',
            'manage_options',
            'wpcte-tarifario',
            'wpcte_admin_page'
        );
        add_submenu_page(
            $wpcargo_slug,
            'Tarifario DHV',
            'Tarifario DHV',
            'manage_options',
            'wpcte-tarifario',
            'wpcte_cotizador_page'
        );
    } else {
        // ── Modo standalone: menú propio si WPCargo no está ─────────
        add_menu_page(
            'DHV Tarifario', '📋 DHV Tarifario',
            'manage_options', 'wpcte-tarifario',
            'wpcte_admin_page',
            'dashicons-calculator', 56
        );
        add_submenu_page(
            'wpcte-tarifario', 'Tarifario', 'Tarifario',
            'manage_options', 'wpcte-tarifario',
            'wpcte_admin_page'
        );
        }
}

add_action( 'admin_enqueue_scripts', 'wpcte_admin_enqueue' );
function wpcte_admin_enqueue( $hook ) {
    // Cargar en nuestras páginas (independientemente de si son submenú de wpcargo o standalone)
    if ( strpos( $hook, 'wpcte' ) === false ) return;
    wp_enqueue_style(  'wpcte-admin-css', WPCTE_URL . 'admin/assets/css/tipo-envio.css', array(), WPCTE_VERSION );
    wp_enqueue_script( 'wpcte-admin-js',  WPCTE_URL . 'admin/assets/js/tipo-envio-admin.js', array('jquery'), WPCTE_VERSION, true );
    wp_localize_script( 'wpcte-admin-js', 'WPCTE_ADMIN', array(
        'ajax'      => admin_url('admin-ajax.php'),
        'nonce'     => wp_create_nonce('wpcte_admin_nonce'),
        'tarifario' => wpcte_tarifario(),
        'lugares'   => wpcte_lugares(),
    ));
}

function wpcte_admin_page() {
    require WPCTE_PATH . 'admin/pages/tarifario.php';
}

function wpcte_cotizador_page() {
    require WPCTE_PATH . 'admin/pages/cotizador.php';
}

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
add_action( 'wp_after_insert_post', 'wpcte_guardar_meta_late', 10, 1 );

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
    if ( $wpcfe !== 'add' && $wpcfe !== 'update' ) return;
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
    <!-- Sobres: solo Tipo — Origen/Destino de arriba se filtran según tipo -->
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
        <input type="checkbox" id="wpcte-devolucion" style="display:none">
        <label for="wpcte-devolucion" id="wpcte-dev-label" style="margin:0;cursor:pointer;display:inline-flex;align-items:center;gap:.5rem;padding:.4rem .9rem;border-radius:20px;font-size:.84rem;font-weight:600;border:2px solid #d0e8f5;background:#f0f9ff;color:#555;transition:all .2s;user-select:none">
            <span id="wpcte-dev-icon">☐</span>
            <span>Cargo por devolución <strong>(S/ <span id="wpcte-dev-monto">-</span>)</strong></span>
        </label>
    </div>
</div>
<script>
(function(){
    var lbl=document.getElementById('wpcte-dev-label');
    var chk=document.getElementById('wpcte-devolucion');
    if(lbl&&chk){
        lbl.addEventListener('click',function(){
            setTimeout(function(){
                var on=chk.checked;
                lbl.style.background=on?'#e8f5e9':'#f0f9ff';
                lbl.style.borderColor=on?'#2a9d8f':'#d0e8f5';
                lbl.style.color=on?'#165a52':'#555';
                document.getElementById('wpcte-dev-icon').textContent=on?'☑':'☐';
            },0);
        });
    }
})();
</script>

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

/* ── Sobres: Tipo fijo → Origen → Destino ───────────────────── */
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

/* Paso 1: poblar tipos activos */
function poblarTiposSobre(){
    if(!sobreTipoSel)return;
    sobreTipoSel.innerHTML='<option value="">-- Seleccione tipo --</option>';
    Object.keys(SOBRE_MAP).forEach(function(key){
        var d=T.sobres.tarifas[key];
        if(!d||d.activo===false)return;
        sobreTipoSel.innerHTML+='<option value="'+key+'">'+key+'</option>';
    });
    repoblarSelect('wpcte-orig',[],false);
    repoblarSelect('wpcte-dest',[],false);
    if(devRow)devRow.style.display='none';
}

/* Paso 2: al elegir tipo → filtrar Origen/Destino de arriba */
function onSobreTipoChange(){
    var key=sobreTipoSel?sobreTipoSel.value:'';
    repoblarSelect('wpcte-orig',[],false);
    repoblarSelect('wpcte-dest',[],false);
    if(devRow)devRow.style.display='none';
    if(!key)return;
    var map=SOBRE_MAP[key];if(!map)return;
    repoblarSelect('wpcte-orig',L[map.orig]||[],false);
    repoblarSelect('wpcte-dest',L[map.dest]||[],false);
    /* Mostrar devolución si aplica */
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

    var orig=(m==='aereo')?'LIMA':(m==='carga_general'?(cgOrigSel?cgOrigSel.value:''):(document.getElementById('wpcte-orig')?document.getElementById('wpcte-orig').value:''));
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
       if(medida==='vol'){
    xkg2=pesoEf<=100?(tar2.vol_0_100||0):(tar2.vol_101_500||0);
    mLabel2='Volumétrico';
} else if(mod2==='agencia'){
    xkg2=pesoEf<=100?tar2.agencia:(tar2.x_kilo_101_500||0);
    mLabel2='Agencia';
} else {
    xkg2=pesoEf<=100?tar2.domicilio:(tar2.domicilio_101_500||tar2.x_kilo_101_500||0);
    mLabel2='Domicilio';
}
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
            var exc=peso3-1;p=tar3.base_kg+peso3*tar3.exceso_kg;
            des='<div class="wpcte-desglose"><div class="wpcte-desglose-row"><span>Base (1 kg)</span><span>S/ '+tar3.base_kg.toFixed(2)+'</span></div><div class="wpcte-desglose-row"><span>'+peso3.toFixed(1)+' kg × S/ '+tar3.exceso_kg.toFixed(2)+'</span><span>S/ '+(peso3*tar3.exceso_kg).toFixed(2)+'</span></div><div class="wpcte-total-row"><span>TOTAL</span><span>S/ '+p.toFixed(2)+'</span></div></div>';
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
    if ( ! in_array( $tipo, $tipos_validos ) ) $tipo = '';
    $page_url   = get_permalink();
    $tiene_alm  = wpcte_tiene_almacen();

    $tipos = array(
        'puerta_puerta' => array( 'label'=>'Puerta a Puerta', 'icon'=>'fa-home',    'desc'=>'Recojo y entrega en domicilio.' ),
        'agencia'       => array( 'label'=>'Agencia',         'icon'=>'fa-building','desc'=>'Entrega desde nuestra agencia.' ),
        'almacen'       => array( 'label'=>'Almacén',         'icon'=>'fa-archive', 'desc'=>'Envío desde tu almacén DHV.' ),
    );
    if ( ! $tiene_alm ) unset( $tipos['almacen'] );

    if ( ! $tipo ) {
        ?>
        <div id="wpcte-pantalla-tipo">
        <div id="wpcte-selector">
        <h4 class="wpcte-title"><i class="fa fa-truck"></i> ¿Qué tipo de envío deseas crear?</h4>
        <div class="wpcte-grid">
        <?php foreach ( $tipos as $key => $t ): ?>
            <a href="<?php echo esc_url( add_query_arg( array( 'wpcfe'=>'add','tipo_envio'=>$key ), $page_url ) ); ?>" class="wpcte-card">
            <i class="fa <?php echo esc_attr($t['icon']); ?>"></i>
            <strong><?php echo esc_html($t['label']); ?></strong>
            <span><?php echo esc_html($t['desc']); ?></span>
            </a>
        <?php endforeach; ?>
        </div></div></div>
        <script>
        jQuery(document).ready(function(){
            var f=document.querySelector('form.add-shipment');
            var s=document.getElementById('wpcte-pantalla-tipo');
            if(f&&s){f.style.display='none';f.parentNode.insertBefore(s,f);}
        });
        </script>
        <?php
        return;
    }

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
            cambL('shipment_container','Contenedor de recojo');
            clonarG('shipment_container','wpcte-cont-eg','Contenedor de entrega','shipment_container_entrega',contEgId);
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
		if(typeof wpcte_listenCliente==='undefined'){
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
}

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

function wpcte_ajax_get_tipos_batch() {
    $ids = json_decode(stripslashes($_POST['ids'] ?? '[]'), true);
    if(!is_array($ids)) wp_send_json_error();

    global $wpdb;

    $tabla_posts = 'wp_hEhUP_posts';
    $tabla_meta  = 'wp_hEhUP_postmeta';

    $out = [];

    foreach($ids as $tracking){

        // limpiar tracking si es string tipo DHV
        $clean_tracking = trim($tracking);
        $clean_tracking = preg_replace('/Nuevo/i', '', $clean_tracking);
        $clean_tracking = preg_replace('/[^A-Z0-9\-]/', '', $clean_tracking);

        if(is_numeric($tracking)){
            // Si es un ID de WP
            $row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT pm.meta_value AS tipo
                     FROM $tabla_meta pm
                     WHERE pm.post_id = %d
                     AND pm.meta_key = 'tipo_envio'
                     LIMIT 1",
                    $tracking
                )
            );
        } else {
            // Si es tracking code
            $row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT pm.meta_value AS tipo
                     FROM $tabla_posts p
                     INNER JOIN $tabla_meta pm ON p.ID = pm.post_id
                     WHERE p.post_title = %s
                     AND pm.meta_key = 'tipo_envio'
                     LIMIT 1",
                    $clean_tracking
                )
            );
        }

        if($row){
            $out[$tracking] = $row->tipo;
        }
    }

    wp_send_json_success($out);
}

function wpcte_tipo_en_lista() {

    $ajax = wp_json_encode(admin_url('admin-ajax.php'));
?>
<script>
jQuery(document).ready(function(){

    var ajaxUrl = <?php echo $ajax; ?>;

    var labels = {
        'puerta_puerta':'Puerta a Puerta',
        'agencia':'Agencia',
        'almacen':'Almacén'
    };

    function procesar(){

        var pendientes = {};
        var ids = [];

        jQuery('table tbody tr').each(function(){

            var row = jQuery(this);
            var pid = null;

            // 🔹 detectar ID o tracking
            row.find('a').each(function(){

                var h = this.href || '';
                var txt = jQuery(this).text().replace('Nuevo','').trim();

                // admin
                var m = h.match(/id=(\d+)/);
                if(m){
                    pid = m[1];
                    return false;
                }

                // cliente
                if(txt.match(/[A-Z]{2,}-\d+/)){
                    pid = txt;
                    return false;
                }

            });

            if(!pid){
                return;
            }

            // 🔹 buscar celda "Por defecto"
            row.find('td').each(function(){

                var cell = jQuery(this);
              var t = cell.text().replace(/\s+/g,' ').trim().toLowerCase();

if(
    t.includes('defecto') ||
    t.includes('default') ||
    t.includes('standard')
){
                    pendientes[pid] = cell;
                    ids.push(pid);
                }

            });

        });

        if(!ids.length){
            return;
        }

        ids = [...new Set(ids)];

        jQuery.post(ajaxUrl,{
            action:'wpcte_get_tipos_batch',
            ids: JSON.stringify(ids)
        }, function(res){

            if(!res || !res.success){
                return;
            }

            var mapa = res.data;

            jQuery.each(pendientes, function(id, cell){

                var tipo = mapa[id];

                if(!tipo){
                    return;
                }

                var label = labels[tipo] || tipo;

                var color =
                    tipo === 'puerta_puerta' ? '#0077b6' :
                    tipo === 'agencia' ? '#2a9d8f' :
                    tipo === 'almacen' ? '#e76f51' :
                    '#999';

                cell.html(
                    '<span style="background:'+color+';color:#fff;padding:4px 10px;border-radius:12px;font-size:.75rem;font-weight:600;">'+label+'</span>'
                );

            });

        },'json');
    }

    setTimeout(procesar, 1000);

    new MutationObserver(function(){
        setTimeout(procesar, 300);
    }).observe(document.body,{
        childList:true,
        subtree:true
    });

});
</script>
<?php
}
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
            TARIFARIO DHV
        </h2>

    </div>

    <?php if ( $wpcfe === 'tarifario_dhv' ): ?>
    <!-- ═══════════════════ TARIFARIO FRONTEND ═══════════════════ -->
    <div class="wpcte-fe-tabs">
        <button class="wpcte-fe-tab active" data-tab="fe-lima">DENTRO DE LIMA</button>
        <button class="wpcte-fe-tab" data-tab="fe-carga">CARGA GENERAL</button>
        <button class="wpcte-fe-tab" data-tab="fe-merc">MERCADERÍA FRECUENTE</button>
        <button class="wpcte-fe-tab" data-tab="fe-aereo">AÉREOS</button>
        <button class="wpcte-fe-tab" data-tab="fe-sobres">SOBRES</button>
    </div>

    <!-- DENTRO DE LIMA -->
    <div class="wpcte-fe-tab-content active" id="fe-lima">
        <div class="wpcte-fe-card">
            <strong style="color:#0077b6">VEHÍCULOS Y PRECIOS BASE</strong>
            <table class="wpcte-fe-table" id="fet-vehs">
                <thead><tr><th>Vehículo</th><th>Precio Base (S/)</th><?php if($es_admin): ?><th class="wpcte-admin-only"></th><?php endif; ?></tr></thead>
                <tbody></tbody>
            </table>
            <?php if($es_admin): ?><button class="wpcte-fe-btn-add wpcte-admin-only" id="fe-add-veh">+ Añadir vehículo</button><?php endif; ?>
        </div>
        <div class="wpcte-fe-card">
            <strong style="color:#0077b6">DISTRITOS NORMALES <small style="font-weight:400;font-size:.8rem;color:#888">(precio base + adicional por distrito)</small></strong>
            <input type="text" id="fe-search-dist" placeholder="Buscar distrito..." style="width:100%;max-width:280px;padding:5px 10px;border:1px solid #ccc;border-radius:8px;margin-bottom:.75rem;font-size:.85rem">
            <div style="overflow-x:auto">
            <table class="wpcte-fe-table" id="fet-distritos">
                <thead><tr id="fet-dist-head"><th>Distrito</th></tr></thead>
                <tbody></tbody>
            </table>
            </div>
            <?php if($es_admin): ?><button class="wpcte-fe-btn-add wpcte-admin-only" id="fe-add-dist">+ Añadir distrito</button><?php endif; ?>
        </div>
        <div class="wpcte-fe-card" style="border-color:#f4a261">
            <strong style="color:#e76f51">ZONAS PERIFÉRICAS <small style="font-weight:400;font-size:.8rem;color:#888">(precio fijo total)</small></strong>
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
            <button class="wpcte-fe-save-btn" id="fe-save-lima">Guardar cambios Lima</button>
            <span class="wpcte-fe-notice" id="fe-notice-lima"></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- CARGA GENERAL -->
    <div class="wpcte-fe-tab-content" id="fe-carga">
        <div class="wpcte-fe-card">
            <strong style="color:#0077b6">TARIFAS DE CARGA GENERAL</strong>
            <p style="font-size:.82rem;color:#666;margin-bottom:.75rem">Fórmula: <strong>Base + (kg × precio/kg)</strong>. Agencia solo aplica para envíos Agencia/Almacén.</p>
            <div id="fe-carga-contenido"></div>
            <?php if($es_admin): ?>
            <button class="wpcte-fe-btn-add wpcte-admin-only" id="fe-add-ruta-cg">+ Añadir ruta/origen</button>
            <div class="wpcte-fe-save-row wpcte-admin-only">
                <button class="wpcte-fe-save-btn" id="fe-save-cg">Guardar cambios Carga</button>
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
                <strong style="color:#0077b6">LUGARES DE LIMA</strong>
                <p style="font-size:.8rem;color:#666;margin:.2rem 0 .4rem">Agrega los lugares de Lima para mercadería.</p>
                <div id="fe-merc-lima-lista" style="margin-bottom:.4rem"></div>
                <button class="wpcte-fe-btn-add wpcte-admin-only" id="fe-add-merc-lima">+ Añadir lugar Lima</button>
            </div>
            <div style="flex:1;min-width:200px">
                <strong style="color:#0077b6">LUGARES DE PROVINCIA</strong>
                <p style="font-size:.8rem;color:#666;margin:.2rem 0 .4rem">Agrega las ciudades de provincia para mercadería.</p>
                <div id="fe-merc-prov-lista" style="margin-bottom:.4rem"></div>
                <button class="wpcte-fe-btn-add wpcte-admin-only" id="fe-add-merc-prov">+ Añadir lugar Provincia</button>
            </div>
        </div>
        <?php endif; ?>
        <div class="wpcte-fe-card">
            <strong style="color:#0077b6">MERCADERÍA FRECUENTE</strong>
            <p style="font-size:.82rem;color:#666;margin-bottom:.75rem">Precio fijo por producto.</p>
            <div id="fe-merc-contenido"></div>
            <?php if($es_admin): ?>
            <button class="wpcte-fe-btn-add wpcte-admin-only" id="fe-add-cat-merc">+ Añadir categoría</button>
            <div class="wpcte-fe-save-row wpcte-admin-only">
                <button class="wpcte-fe-save-btn" id="fe-save-merc">Guardar Mercadería</button>
                <span class="wpcte-fe-notice" id="fe-notice-merc"></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- AÉREOS -->
    <div class="wpcte-fe-tab-content" id="fe-aereo">
        <div class="wpcte-fe-card">
             <strong style="color:#0077b6">RUTAS AÉREAS</strong>
            <p style="font-size:.82rem;color:#666;margin-bottom:.75rem">Cada ruta tiene un origen (<strong>Desde</strong>) y sus destinos con precios.</p>
            <div id="fe-aereo-contenido"></div>
            <?php if($es_admin): ?>
            <button class="wpcte-fe-btn-add wpcte-admin-only" id="fe-add-ruta-aereo-fe">+ Añadir ruta (Desde)</button>
            <div class="wpcte-fe-save-row wpcte-admin-only">
                <button class="wpcte-fe-save-btn" id="fe-save-aereo">Guardar Aéreos</button>
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
                <strong style="color:#0077b6">LUGARES DE LIMA</strong>
                <p style="font-size:.8rem;color:#666;margin:.2rem 0 .4rem">Lugares para envíos Lima→Lima y Lima→Provincia.</p>
                <div id="fe-sobres-lima-lista" style="margin-bottom:.4rem"></div>
                <button class="wpcte-fe-btn-add wpcte-admin-only" id="fe-add-sobre-lima">+ Añadir lugar Lima</button>
            </div>
            <div style="flex:1;min-width:200px">
                <strong style="color:#0077b6">LUGARES DE PROVINCIA</strong>
                <p style="font-size:.8rem;color:#666;margin:.2rem 0 .4rem">Lugares para Lima→Provincia y Provincia→Provincia.</p>
                <div id="fe-sobres-prov-lista" style="margin-bottom:.4rem"></div>
                <button class="wpcte-fe-btn-add wpcte-admin-only" id="fe-add-sobre-prov">+ Añadir lugar Provincia</button>
            </div>
        </div>
        <?php endif; ?>
        <div class="wpcte-fe-card">
            <strong style="color:#0077b6">TARIFAS DE SOBRES</strong>
            <table class="wpcte-fe-table" id="fet-sobres">
                <thead><tr>
                    <th>Tipo</th><th>Agencia (S/)</th><th>Domicilio (S/)</th><th>Devolución (S/)</th>
                    <?php if($es_admin): ?><th class="wpcte-admin-only">Estado</th><?php endif; ?>
                </tr></thead>
                <tbody></tbody>
            </table>
            <?php if($es_admin): ?>
            <div class="wpcte-fe-save-row wpcte-admin-only">
                <button class="wpcte-fe-save-btn" id="fe-save-sobres">Guardar Sobres</button>
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
        b+='<th>Destino</th><th>Base (S/)</th><th>Agencia/kg (0-100)</th><th>Domicilio/kg (0-100)</th><th>Agencia/kg (101-500)</th><th>Domicilio/kg (101-500)</th><th>Vol/kg (0-100)</th><th>Vol/kg (101-500)</th><th>Lead</th>';
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
b+='<td>'+inp('number',d.domicilio_101_500||0,'p','data-f="dom101" min="0" step="0.01"')+'</td>';
b+='<td>'+inp('number',d.vol_0_100||0,'p','data-f="vol_0_100" min="0" step="0.01"')+'</td>';
b+='<td>'+inp('number',d.vol_101_500||0,'p','data-f="vol_101_500" min="0" step="0.01"')+'</td>';
                b+='<td>'+inp('text',d.lead,'','data-f="lead" style="width:80px"')+'</td>';
                b+='<td class="wpcte-admin-only"><button class="wpcte-fe-btn-del fe-del-cg-dest">✕</button></td>';
            } else {
                b+='<td><strong>'+esc(dest)+'</strong></td>';
                b+='<td>S/ '+Number(d.base).toFixed(2)+'</td>';
                b+='<td>S/ '+Number(d.agencia).toFixed(2)+'/kg</td>';
b+='<td>S/ '+Number(d.domicilio).toFixed(2)+'/kg</td>';
b+='<td>S/ '+Number(d.x_kilo_101_500).toFixed(2)+'/kg</td>';
b+='<td>S/ '+Number(d.domicilio_101_500||0).toFixed(2)+'/kg</td>';
b+='<td>S/ '+Number(d.vol_0_100||0).toFixed(2)+'/kg</td>';
b+='<td>S/ '+Number(d.vol_101_500||0).toFixed(2)+'/kg</td>';
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
           ds[dn]={
  base:parseFloat($('[data-f="base"]',this).val())||0,
  agencia:parseFloat($('[data-f="agencia"]',this).val())||0,
  domicilio:parseFloat($('[data-f="domicilio"]',this).val())||0,
  x_kilo_101_500:parseFloat($('[data-f="x101"]',this).val())||0,
  domicilio_101_500:parseFloat($('[data-f="dom101"]',this).val())||0,
  vol_0_100:parseFloat($('[data-f="vol_0_100"]',this).val())||0,
  vol_101_500:parseFloat($('[data-f="vol_101_500"]',this).val())||0,
  lead:($('[data-f="lead"]',this).val()||'').trim()||'24 HORAS'
};
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

    /* Aereo: sincronizar desde el DOM (nombres + parámetros) */
    if(IS_ADMIN&&$('#fe-aereo-contenido').length){
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
        if(Object.keys(newRutas).length||$('#fe-aereo-contenido [data-aereo-desde]').length)
            T.aereo.rutas=newRutas;
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
    /* Aereo: sync solo si tab activo */
    if(IS_ADMIN&&$('#fe-aereo').hasClass('active')&&$('#fe-aereo-contenido').length){
        var newRutas={};
        $('#fe-aereo-contenido [data-aereo-desde]').each(function(){
            var $blk=$(this);var desdeOrig=$blk.data('aereo-desde');
            var desdeNew=($('input[data-f="aer-desde"]',$blk).val()||desdeOrig).trim().toUpperCase();if(!desdeNew)return;
            var destinos={};
            $blk.find('tbody tr').each(function(){
                var dn=($('input[data-f="aer-dest"]',this).val()||$(this).data('aer-dest')||'').trim().toUpperCase();if(!dn)return;
                destinos[dn]={zona:($('input[data-f="zona"]',this).val()||'').trim(),base_kg:parseFloat($('input[data-f="base_kg"]',this).val())||0,exceso_kg:parseFloat($('input[data-f="exceso_kg"]',this).val())||0,precio_1kg:parseFloat($('input[data-f="precio_1kg"]',this).val())||0,lead:($('input[data-f="lead"]',this).val()||'').trim()||'48 HORAS'};
            });
            newRutas[desdeNew]={destinos:destinos};
        });
        if(Object.keys(newRutas).length)T.aereo.rutas=newRutas;
    }
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

    /* Aereo: leer del DOM al guardar */
    if(IS_ADMIN&&$('#fe-aereo-contenido').length){
        var newRutasG={};
        $('#fe-aereo-contenido [data-aereo-desde]').each(function(){
            var $blk=$(this);var desdeOrig=$blk.data('aereo-desde');
            var desdeNew=($('input[data-f="aer-desde"]',$blk).val()||desdeOrig).trim().toUpperCase();
            if(!desdeNew)return;
            var destinos={};
            $blk.find('tbody tr').each(function(){
                var dn=($('input[data-f="aer-dest"]',this).val()||$(this).data('aer-dest')||'').trim().toUpperCase();
                if(!dn)return;
                destinos[dn]={zona:($('input[data-f="zona"]',this).val()||'').trim(),base_kg:parseFloat($('input[data-f="base_kg"]',this).val())||0,exceso_kg:parseFloat($('input[data-f="exceso_kg"]',this).val())||0,precio_1kg:parseFloat($('input[data-f="precio_1kg"]',this).val())||0,lead:($('input[data-f="lead"]',this).val()||'').trim()||'48 HORAS'};
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
        $btn.prop('disabled',false).text('Guardar');
        if(res&&res.success){
            $('.wpcte-fe-notice').attr('class','wpcte-fe-notice ok').text('Tarifario completo guardado').show();
            setTimeout(function(){$('.wpcte-fe-notice').fadeOut();},3500);
        } else {
            var msg=(res&&res.data)?(res.data):'Error al guardar';
            $('#'+noticeId).attr('class','wpcte-fe-notice err').text(msg).show();
        }
    },'json').fail(function(xhr){
        $btn.prop('disabled',false).text('Guardar');
        $('#'+noticeId).attr('class','wpcte-fe-notice err').text('Error de red: '+xhr.status).show();
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
