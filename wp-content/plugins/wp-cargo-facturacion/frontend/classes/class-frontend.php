<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPC_Facturacion_Frontend {

	public function __construct() {
		// Shortcode para la vista admin/manager en el dashboard frontend
		add_shortcode( 'wpcfact-admin-dashboard', array( $this, 'render_dashboard' ) );
		// Hook para agregar al menú de WPCargo Frontend (si usa wpcfe_after_sidebar_menus como otros plugins)
		add_filter( 'wpcfe_after_sidebar_menus', array( $this, 'add_sidebar_menu' ), 40, 1 );
	}

	public function add_sidebar_menu( $menus ) {
		if ( current_user_can( 'manage_options' ) || current_user_can( 'wpc_shipment_manager' ) ) {
			// Encontrar la página que tenga el shortcode (esto asume que el admin creó la página)
			// Por defecto pondremos un permalink "#" o uno estático si se conoce.
			// Idealmente el admin crea una página con [wpcfact-admin-dashboard]
			$menus['wpcfact-facturacion'] = array(
				'label'     => 'Facturación SUNAT',
				'permalink' => home_url( '/facturacion-sunat/' ), // URL recomendada para la página
				'icon'      => 'fa-file-text-o',
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
}

new WPC_Facturacion_Frontend();
