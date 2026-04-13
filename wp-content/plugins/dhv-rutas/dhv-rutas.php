<?php
/**
 * Plugin Name: DHV Rutas
 * Description: Gestión de rutas de recojo y entrega para motorizados DHV Courier
 * Version: 1.0.0
 * Author: DHV Courier
 * Text Domain: dhv-rutas
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'DHV_RUTAS_VERSION', '1.0.0' );
define( 'DHV_RUTAS_PATH',    plugin_dir_path( __FILE__ ) );
define( 'DHV_RUTAS_URL',     plugin_dir_url( __FILE__ ) );
define( 'DHV_RUTAS_PREFIX',  'wp_hEhUP_' ); // prefijo de tablas BD

require_once DHV_RUTAS_PATH . 'includes/class-db.php';
require_once DHV_RUTAS_PATH . 'includes/class-ajax.php';
require_once DHV_RUTAS_PATH . 'includes/class-shortcode.php';

add_action( 'wp_enqueue_scripts', 'dhv_rutas_enqueue_assets' );
function dhv_rutas_enqueue_assets() {
    wp_enqueue_style(
        'dhv-rutas-css',
        DHV_RUTAS_URL . 'assets/css/dhv-rutas.css',
        array(),
        DHV_RUTAS_VERSION
    );
    wp_enqueue_script(
        'dhv-rutas-js',
        DHV_RUTAS_URL . 'assets/js/dhv-rutas.js',
        array('jquery'),
        DHV_RUTAS_VERSION,
        true
    );
    wp_localize_script( 'dhv-rutas-js', 'dhvRutas', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('dhv_rutas_nonce'),
    ));
}
