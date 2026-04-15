<?php
/**
 * Plugin Name: DHV Escáner
 * Plugin URI:  https://dhv.com
 * Description: Módulo de escáner de envíos. Permite actualizar el estado de envíos escaneando el código de barras o ingresando el número de tracking manualmente.
 * Version:     1.0.0
 * Author:      DHV
 * Text Domain: dhv-escaner
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'DHV_VERSION',  '1.0.0' );
define( 'DHV_PATH',     plugin_dir_path( __FILE__ ) );
define( 'DHV_URL',      plugin_dir_url( __FILE__ ) );

require_once DHV_PATH . 'includes/class-escaner-frontend.php';
require_once DHV_PATH . 'includes/class-escaner-ajax.php';
// Hooks adicionales
if ( file_exists( DHV_PATH . 'includes/hooks.php' ) ) {
    require_once DHV_PATH . 'includes/hooks.php';
}

// ── Activación ────────────────────────────────────────────────────────────────
register_activation_hook( __FILE__, 'dhv_activate' );
function dhv_activate() {
    dhv_get_frontend_page_id();
}

// ── Desinstalación ────────────────────────────────────────────────────────────
register_uninstall_hook( __FILE__, 'dhv_uninstall' );
function dhv_uninstall() {
    $page_id = get_option( 'dhv_frontend_page_id' );
    if ( $page_id ) {
        wp_delete_post( $page_id, true );
        delete_option( 'dhv_frontend_page_id' );
    }
}

// ── Crear/recuperar la página del módulo ──────────────────────────────────────
function dhv_get_frontend_page_id(): int {
    $saved = (int) get_option( 'dhv_frontend_page_id' );
    if ( $saved && get_post_status( $saved ) === 'publish' ) {
        return $saved;
    }

    global $wpdb;
    $id = (int) $wpdb->get_var(
        "SELECT ID FROM {$wpdb->prefix}posts
         WHERE post_content LIKE '%[dhv-escaner]%'
           AND post_status = 'publish' LIMIT 1"
    );

    if ( ! $id ) {
        $id = (int) wp_insert_post([
            'post_title'   => 'Escáner',
            'post_content' => '[dhv-escaner]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }

    if ( $id ) {
        update_post_meta( $id, '_wp_page_template', 'dashboard.php' );
        update_post_meta( $id, 'wpcfe_menu_icon',   'fa fa-barcode mr-3' );
        update_option( 'dhv_frontend_page_id', $id, false );
    }

    return $id;
}

// ── Shortcode [dhv-escaner] ───────────────────────────────────────────────────
add_shortcode( 'dhv-escaner', 'dhv_render_shortcode' );
function dhv_render_shortcode(): string {
    if ( ! is_user_logged_in() ) {
        return '<p>Debes iniciar sesión para usar el escáner.</p>';
    }

    if ( ! dhv_can_scan() ) {
        return '<p>No tienes permisos para acceder al escáner.</p>';
    }

    wp_enqueue_style( 'dhv-style', DHV_URL . 'assets/css/escaner.css', [], DHV_VERSION );
    wp_enqueue_script( 'dhv-script', DHV_URL . 'assets/js/escaner.js', [ 'jquery' ], DHV_VERSION, true );
    wp_localize_script( 'dhv-script', 'DHV_Config', [
        'ajax_url'      => admin_url( 'admin-ajax.php' ),
        'nonce'         => wp_create_nonce( 'dhv_nonce' ),
        'beep_url'      => DHV_URL . 'assets/audio/beep.mp3',
        'txt_found'     => 'Envío actualizado correctamente.',
        'txt_not_found' => 'Número de seguimiento no encontrado.',
        'txt_error'     => 'Error al procesar la solicitud.',
        'txt_scanning'  => 'Procesando...',
    ]);

    ob_start();
    include DHV_PATH . 'templates/escaner-page.php';
    return ob_get_clean();
}

// ── Función de permiso ────────────────────────────────────────────────────────
function dhv_can_scan(): bool {
    $allowed_roles = apply_filters( 'dhv_scanner_roles', [ 'administrator', 'wpcargo_employee' ] );
    $user  = wp_get_current_user();
    $roles = (array) $user->roles;
    return ! empty( array_intersect( $roles, $allowed_roles ) );
}

// ── Init ──────────────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', function() {
    new DHV_Escaner_Frontend();
    new DHV_Escaner_Ajax();
    dhv_get_frontend_page_id();
});

