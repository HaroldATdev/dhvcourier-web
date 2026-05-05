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
define( 'WPC_FACTURACION_FILE', __FILE__ );

// Includes Base
require_once WPC_FACTURACION_PATH . 'includes/install.php';
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

	// 1. Crear página padre: Facturación SUNAT
	$padre = get_page_by_path( 'facturacion-sunat' );
	if ( ! $padre ) {
		$padre_id = wp_insert_post( array(
			'post_title'   => 'Facturación SUNAT',
			'post_content' => '[wpcfact-admin-dashboard]',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_name'    => 'facturacion-sunat',
		) );
	} else {
		$padre_id = $padre->ID;
	}
	if ( $padre_id ) {
		update_post_meta( $padre_id, '_wp_page_template', 'dashboard.php' );
	}

	// 2. Páginas hijas (dependen del padre)
	$hijas = array(
		array(
			'post_title'   => 'Emitir Comprobante',
			'post_content' => '[wpcfact-emitir-dashboard]',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_name'    => 'emitir-comprobante',
			'post_parent'  => $padre_id,
		),
		array(
			'post_title'   => 'Configuración SUNAT',
			'post_content' => '[wpcfact-configuracion-dashboard]',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_name'    => 'configuracion-sunat',
			'post_parent'  => $padre_id,
		),
		array(
			'post_title'   => 'Mis Comprobantes',
			'post_content' => '[wpcfact-mis-comprobantes]',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_name'    => 'mis-comprobantes',
			'post_parent'  => $padre_id,
		),
	);

	foreach ( $hijas as $hija ) {
		// Buscar por slug dentro de las hijas del padre
		$exists = get_page_by_path( 'facturacion-sunat/' . $hija['post_name'] );
		if ( ! $exists ) {
			$hija_id = wp_insert_post( $hija );
		} else {
			$hija_id = $exists->ID;
			// Asegurarse que el parent_id esté correcto
			wp_update_post( array( 'ID' => $hija_id, 'post_parent' => $padre_id ) );
		}
		if ( $hija_id ) {
			update_post_meta( $hija_id, '_wp_page_template', 'dashboard.php' );
		}
	}
}

