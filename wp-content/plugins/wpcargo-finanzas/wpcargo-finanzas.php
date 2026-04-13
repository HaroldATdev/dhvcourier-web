<?php
/**
 * Plugin Name: WPCargo Finanzas
 * Plugin URI:  https://dhvcourier.com
 * Description: Módulo financiero para DHV Courier. Gestiona métodos de pago, condiciones, penalidades y balances por envío.
 * Version:     1.0.0
 * Author:      DHV Courier
 * Text Domain: wpcargo-finanzas
 * Requires PHP: 8.1
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WCFIN_VERSION',  '1.0.0' );
define( 'WCFIN_PATH',     plugin_dir_path( __FILE__ ) );
define( 'WCFIN_URL',      plugin_dir_url( __FILE__ ) );
define( 'WCFIN_BASENAME', plugin_basename( __FILE__ ) );

require_once WCFIN_PATH . 'includes/functions.php';
require_once WCFIN_PATH . 'includes/class-database.php';
require_once WCFIN_PATH . 'includes/class-motor.php';
require_once WCFIN_PATH . 'admin/classes/class-condicion.php';
require_once WCFIN_PATH . 'admin/classes/class-metodo.php';
require_once WCFIN_PATH . 'admin/classes/class-penalidad.php';
require_once WCFIN_PATH . 'admin/classes/class-admin.php';
require_once WCFIN_PATH . 'admin/classes/class-metabox.php';
require_once WCFIN_PATH . 'admin/classes/class-frontend.php';

register_activation_hook( __FILE__, 'wcfin_activar' );
add_action( 'plugins_loaded', 'wcfin_maybe_upgrade' );

function wcfin_activar(): void {
    WCFIN_Database::crear_tablas();
    // Crear la página frontend con el shortcode y template del dashboard de WPCargo
    wcfin_get_frontend_page_id();
}

function wcfin_maybe_upgrade(): void {
    if ( get_option('wcfin_db_version') !== WCFIN_VERSION ) {
        WCFIN_Database::crear_tablas();
        update_option('wcfin_db_version', WCFIN_VERSION);
    }
}
