<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPC_Facturacion_Frontend {

	public function __construct() {
		// Shortcode para la vista admin/manager en el dashboard frontend
		add_shortcode( 'wpcfact-admin-dashboard', array( $this, 'render_dashboard' ) );
		add_shortcode( 'wpcfact-emitir-dashboard', array( $this, 'render_emitir' ) );
		add_shortcode( 'wpcfact-configuracion-dashboard', array( $this, 'render_configuracion' ) );
		
		// Hook para agregar al menú de WPCargo Frontend
		add_filter( 'wpcfe_after_sidebar_menus', array( $this, 'add_sidebar_menu' ), 40, 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function enqueue_scripts() {
		// Enqueue scripts solo en el frontend, se llamará directamente desde el shortcode
	}

	public function add_sidebar_menu( $menus ) {
		if ( current_user_can( 'manage_options' ) || current_user_can( 'wpc_shipment_manager' ) ) {
			$page_dash = get_page_by_path( 'facturacion-sunat' );
			$page_dash_id = $page_dash ? $page_dash->ID : 0;
			
			$menus['wpcfact-facturacion'] = array(
				'page-id'   => $page_dash_id,
				'label'     => 'Facturación SUNAT',
				'permalink' => $page_dash ? get_permalink( $page_dash_id ) : home_url( '/facturacion-sunat/' ),
				'icon'      => 'fa-file-text-o',
			);

			$page_emitir = get_page_by_path( 'facturacion-sunat/emitir-comprobante' );
			if ( ! $page_emitir ) $page_emitir = get_page_by_path( 'emitir-comprobante' );
			$page_emitir_id = $page_emitir ? $page_emitir->ID : 0;
			
			$menus['wpcfact-emitir'] = array(
				'page-id'   => $page_emitir_id,
				'label'     => 'Emitir Comprobante',
				'permalink' => $page_emitir ? get_permalink( $page_emitir_id ) : home_url( '/facturacion-sunat/emitir-comprobante/' ),
				'icon'      => 'fa-plus',
			);

			$page_config = get_page_by_path( 'facturacion-sunat/configuracion-sunat' );
			if ( ! $page_config ) $page_config = get_page_by_path( 'configuracion-sunat' );
			$page_config_id = $page_config ? $page_config->ID : 0;

			$menus['wpcfact-config'] = array(
				'page-id'   => $page_config_id,
				'label'     => 'Configuración SUNAT',
				'permalink' => $page_config ? get_permalink( $page_config_id ) : home_url( '/facturacion-sunat/configuracion-sunat/' ),
				'icon'      => 'fa-cog',
			);
		}
		return $menus;
	}

	public function render_dashboard() {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'wpc_shipment_manager' ) ) {
			return '<div class="wpcargo-container"><p class="alert alert-danger">No tienes permisos para ver la facturación.</p></div>';
		}

		global $wpdb;
		$table = WPC_Facturacion_Comprobante::get_table();

		// KPIs
		$kpi_emitidos = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())" );
		$kpi_aceptados = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE estado = 'ACEPTADO' AND MONTH(created_at) = MONTH(CURRENT_DATE())" );
		$kpi_pendientes = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE estado = 'PENDIENTE'" );
		$kpi_rechazados = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE estado = 'RECHAZADO' OR estado = 'ERROR'" );

		$comprobantes = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 50" );

		ob_start();
		include WPC_FACTURACION_PATH . 'frontend/templates/admin-dashboard.php';
		return ob_get_clean();
	}

	public function render_emitir() {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'wpc_shipment_manager' ) ) {
			return '<div class="wpcargo-container"><p class="alert alert-danger">No tienes permisos para emitir comprobantes.</p></div>';
		}

		wp_enqueue_style( 'wpcfact-wizard-css', WPC_FACTURACION_URL . 'admin/assets/css/wizard.css', array(), WPC_FACTURACION_VERSION );
		wp_enqueue_script( 'wpcfact-wizard-js', WPC_FACTURACION_URL . 'admin/assets/js/wizard.js', array( 'jquery' ), WPC_FACTURACION_VERSION, true );
		wp_localize_script( 'wpcfact-wizard-js', 'wpcfact_ajax', array(
			'url'         => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'wpcfact_wizard_nonce' ),
			'url_emitir'  => home_url( '/emitir-comprobante/' ),
			'url_listado' => home_url( '/facturacion-sunat/' )
		) );

		ob_start();
		echo '<div class="wpcargo-container wpcfact-dashboard">';
		include WPC_FACTURACION_PATH . 'admin/templates/wizard.php';
		echo '</div>';
		return ob_get_clean();
	}

	public function render_configuracion() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return '<div class="wpcargo-container"><p class="alert alert-danger">No tienes permisos para acceder a la configuración.</p></div>';
		}

		ob_start();
		echo '<div class="wpcargo-container wpcfact-dashboard">';
		include WPC_FACTURACION_PATH . 'admin/templates/config.php';
		echo '</div>';
		return ob_get_clean();
	}
}

new WPC_Facturacion_Frontend();
