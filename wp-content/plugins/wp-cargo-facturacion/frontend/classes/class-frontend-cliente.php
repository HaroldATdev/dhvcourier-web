<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPC_Facturacion_Frontend_Cliente {

	public function __construct() {
		// Shortcode para que el cliente vea sus propios comprobantes
		add_shortcode( 'wpcfact-mis-comprobantes', array( $this, 'render_mis_comprobantes' ) );
		
		add_filter( 'wpcfe_after_sidebar_menus', array( $this, 'add_sidebar_menu_cliente' ), 45, 1 );
	}

	public function add_sidebar_menu_cliente( $menus ) {
		if ( current_user_can( 'wpcargo_client' ) ) {
			// Idealmente el admin crea una página con [wpcfact-mis-comprobantes]
			$menus['wpcfact-mis-comprobantes'] = array(
				'label'     => 'Mis Comprobantes',
				'permalink' => home_url( '/mis-comprobantes/' ),
				'icon'      => 'fa-file-pdf-o',
			);
		}
		return $menus;
	}

	public function render_mis_comprobantes() {
		if ( ! is_user_logged_in() ) {
			return '<p>Debes iniciar sesión para ver tus comprobantes.</p>';
		}

		$user_id = get_current_user_id();
		$doc_num = get_user_meta( $user_id, 'wpcfact_doc_num', true );

		if ( empty( $doc_num ) ) {
			return '<div class="wpcargo-container"><p class="alert alert-info">Aún no se han emitido comprobantes a tu nombre.</p></div>';
		}

		global $wpdb;
		$table = WPC_Facturacion_Comprobante::get_table();

		// Buscar comprobantes por el documento asociado a este cliente
		$comprobantes = $wpdb->get_results( $wpdb->prepare( "
			SELECT * FROM {$table} 
			WHERE cliente_doc_num = %s AND estado = 'ACEPTADO' 
			ORDER BY created_at DESC LIMIT 50
		", $doc_num ) );

		ob_start();
		include WPC_FACTURACION_PATH . 'frontend/templates/cliente-dashboard.php';
		return ob_get_clean();
	}
}

new WPC_Facturacion_Frontend_Cliente();
