<?php
if ( ! defined( 'ABSPATH' ) ) exit;

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
            '📋 Tarifario DHV',
            'manage_options',
            'wpcte-tarifario',
            'wpcte_admin_page'
        );
        add_submenu_page(
            $wpcargo_slug,
            'Tarifario DHV',
            '📋 Tarifario DHV',
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
