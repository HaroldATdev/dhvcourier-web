<?php
/**
 * Plugin Name: WP Cargo Facturación SUNAT
 * Description: Integración con APISUNAT para emisión de boletas, facturas y guías de remisión desde WPCargo.
 * Version: 1.0.0
 * Author: DHV Courier
 * Text Domain: wp-cargo-facturacion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPC_FACTURACION_VERSION', '1.0.0' );
define( 'WPC_FACTURACION_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPC_FACTURACION_URL', plugin_dir_url( __FILE__ ) );

// Includes Base
require_once WPC_FACTURACION_PATH . 'includes/functions.php';
require_once WPC_FACTURACION_PATH . 'includes/class-database.php';
require_once WPC_FACTURACION_PATH . 'includes/class-apisunat.php';
require_once WPC_FACTURACION_PATH . 'includes/class-comprobante.php';
require_once WPC_FACTURACION_PATH . 'includes/class-constructor-factura.php';
require_once WPC_FACTURACION_PATH . 'includes/class-cron.php';

require_once WPC_FACTURACION_PATH . 'frontend/classes/class-frontend.php';
require_once WPC_FACTURACION_PATH . 'frontend/classes/class-frontend-cliente.php';

if ( is_admin() ) {
	require_once WPC_FACTURACION_PATH . 'admin/classes/class-admin.php';
	require_once WPC_FACTURACION_PATH . 'admin/classes/class-ajax.php';
	require_once WPC_FACTURACION_PATH . 'admin/classes/class-metabox.php';
}

// Inicialización
add_action( 'plugins_loaded', 'wpc_facturacion_init' );

function wpc_facturacion_init() {
	WPC_Facturacion_Cron::init();
}

// Activación del plugin
register_activation_hook( __FILE__, 'wpc_facturacion_activate' );

function wpc_facturacion_activate() {
	require_once WPC_FACTURACION_PATH . 'includes/class-database.php';
	WPC_Facturacion_Database::create_tables();

	// Crear páginas automáticamente
	$paginas = array(
		array(
			'post_title'   => 'Facturación SUNAT',
			'post_content' => '[wpcfact-admin-dashboard]',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_name'    => 'facturacion-sunat'
		),
		array(
			'post_title'   => 'Mis Comprobantes',
			'post_content' => '[wpcfact-mis-comprobantes]',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_name'    => 'mis-comprobantes'
		)
	);

	foreach ( $paginas as $pagina ) {
		$page_exists = get_page_by_path( $pagina['post_name'] );
		if ( ! $page_exists ) {
			wp_insert_post( $pagina );
		}
	}
}
