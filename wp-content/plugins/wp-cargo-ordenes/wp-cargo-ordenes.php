<?php
/**
 * Plugin Name: WP Cargo Órdenes de Servicio
 * Description: Gestión de órdenes de servicio para DHV Courier.
 * Version:     1.0.0
 * Author:      DHV Courier
 * Text Domain: wp-cargo-ordenes
 * Requires PHP: 8.1
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WPCO_VERSION',  '1.0.0' );
define( 'WPCO_PATH',     plugin_dir_path( __FILE__ ) );
define( 'WPCO_URL',      plugin_dir_url( __FILE__ ) );

require_once WPCO_PATH . 'includes/functions.php';
require_once WPCO_PATH . 'admin/classes/class-database.php';
require_once WPCO_PATH . 'admin/classes/class-orden.php';
require_once WPCO_PATH . 'admin/classes/class-admin.php';
require_once WPCO_PATH . 'admin/classes/class-frontend.php';

register_activation_hook( __FILE__, 'wpco_activar' );
add_action( 'plugins_loaded', 'wpco_maybe_upgrade' );

function wpco_activar(): void {
	WPCO_Database::crear_tablas();
	wpco_get_frontend_page_id();
}
function wpco_maybe_upgrade(): void {
	if ( get_option( 'wpco_db_version' ) !== WPCO_VERSION ) {
		WPCO_Database::crear_tablas();
	}
}
