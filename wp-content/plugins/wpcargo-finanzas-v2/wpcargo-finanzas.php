<?php
/**
 * Plugin Name: WPCargo Finanzas
 * Plugin URI:  https://dhvcourier.com
 * Description: Módulo financiero completo para DHV Courier. Panel de cajas por motorizado y cliente, liquidaciones, comprobantes bilaterales, y vistas frontend para cada rol.
 * Version:     2.0.0
 * Author:      DHV Courier
 * Text Domain: wpcargo-finanzas
 * Requires PHP: 8.1
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WCFIN_VERSION',  '2.0.0' );
define( 'WCFIN_PATH',     plugin_dir_path( __FILE__ ) );
define( 'WCFIN_URL',      plugin_dir_url( __FILE__ ) );
define( 'WCFIN_BASENAME', plugin_basename( __FILE__ ) );

// Core
require_once WCFIN_PATH . 'includes/functions.php';
require_once WCFIN_PATH . 'includes/class-database.php';
require_once WCFIN_PATH . 'includes/class-motor.php';
require_once WCFIN_PATH . 'includes/class-caja.php';

// Admin: configuración
require_once WCFIN_PATH . 'admin/classes/class-condicion.php';
require_once WCFIN_PATH . 'admin/classes/class-metodo.php';
require_once WCFIN_PATH . 'admin/classes/class-penalidad.php';
require_once WCFIN_PATH . 'admin/classes/class-admin.php';
require_once WCFIN_PATH . 'admin/classes/class-metabox.php';

// Admin: panel de cajas (wp-admin)
require_once WCFIN_PATH . 'admin/classes/class-admin-caja.php';

// Frontend: dashboard WPCargo — items de sidebar y shortcodes
require_once WCFIN_PATH . 'admin/classes/class-frontend.php';
require_once WCFIN_PATH . 'admin/classes/class-frontend-cliente.php';

register_activation_hook( __FILE__, 'wcfin_activar' );
add_action( 'plugins_loaded', 'wcfin_maybe_upgrade' );

function wcfin_activar(): void {
    WCFIN_Database::crear_tablas();
    // Crear las 3 páginas del dashboard al activar
    wcfin_get_frontend_page_id(); // [wcfin-finanzas]   → admin
    wcfin_get_cliente_page_id();  // [wcfin-mi-cuenta]  → cliente
    wcfin_get_driver_page_id();   // [wcfin-mi-caja]    → driver
}

function wcfin_maybe_upgrade(): void {
    if ( get_option('wcfin_db_version') !== WCFIN_VERSION ) {
        WCFIN_Database::crear_tablas();
        update_option('wcfin_db_version', WCFIN_VERSION);
    }
}
