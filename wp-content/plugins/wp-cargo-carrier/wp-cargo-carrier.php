<?php
/**
 * Plugin Name: WP Cargo Carrier
 * Plugin URI:  https://dhvcourier.com
 * Description: Gestión de transportistas para DHV Courier.
 * Version:     4.1.0
 * Author:      DHV Courier
 * Text Domain: wp-cargo-carrier
 * Requires PHP: 8.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WPCC_VERSION',  '4.0.0' );
define( 'WPCC_PATH',     plugin_dir_path( __FILE__ ) );
define( 'WPCC_URL',      plugin_dir_url( __FILE__ ) );
define( 'WPCC_BASENAME', plugin_basename( __FILE__ ) );

require_once WPCC_PATH . 'includes/functions.php';
require_once WPCC_PATH . 'admin/classes/class-database.php';
require_once WPCC_PATH . 'admin/classes/class-transportista.php';
require_once WPCC_PATH . 'admin/classes/class-admin.php';
require_once WPCC_PATH . 'admin/classes/class-driver-sync.php';
require_once WPCC_PATH . 'admin/classes/class-frontend.php';

register_activation_hook( __FILE__, 'wpcc_activar' );

function wpcc_activar(): void {
	WPCC_Database::crear_tablas();
	wpcc_get_frontend_page_id();
}

// Siempre eliminar UNIQUE keys en cada carga — corrige instalaciones antiguas
add_action( 'plugins_loaded', function(): void {
	WPCC_Database::eliminar_unique_keys();
} );
