<?php
/**
 * Plugin Name: WP Cargo Viáticos
 * Plugin URI:  https://dhvcourier.com
 * Description: Gestión de viáticos y gastos para DHV Courier.
 * Version:     4.0.0
 * Author:      DHV Courier
 * Text Domain: wp-cargo-viaticos
 * Requires PHP: 8.1
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WPCV_VERSION',  '4.0.0' );
define( 'WPCV_PATH',     plugin_dir_path( __FILE__ ) );
define( 'WPCV_URL',      plugin_dir_url( __FILE__ ) );
define( 'WPCV_BASENAME', plugin_basename( __FILE__ ) );

require_once WPCV_PATH . 'includes/functions.php';
require_once WPCV_PATH . 'admin/classes/class-database.php';
require_once WPCV_PATH . 'admin/classes/class-viatico.php';
require_once WPCV_PATH . 'admin/classes/class-tipos-gasto.php';
require_once WPCV_PATH . 'admin/classes/class-gasto.php';
require_once WPCV_PATH . 'admin/classes/class-admin.php';
require_once WPCV_PATH . 'admin/classes/class-frontend.php';

register_activation_hook( __FILE__, 'wpcv_activar' );
add_action( 'plugins_loaded', 'wpcv_maybe_upgrade' );

function wpcv_activar(): void {
	WPCV_Database::crear_tablas();
	wpcv_get_frontend_page_id();
}

function wpcv_maybe_upgrade(): void {
	if ( get_option( 'wpcv_db_version' ) !== WPCV_VERSION ) {
		WPCV_Database::crear_tablas();
	}
}
