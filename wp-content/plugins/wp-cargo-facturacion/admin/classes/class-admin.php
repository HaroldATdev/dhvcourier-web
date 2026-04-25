<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPC_Facturacion_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function register_menus() {
		add_menu_page(
			'Facturación SUNAT',
			'Facturación SUNAT',
			'manage_options',
			'wpcfact-comprobantes',
			array( $this, 'render_comprobantes_page' ),
			'dashicons-media-document',
			56
		);

		add_submenu_page(
			'wpcfact-comprobantes',
			'Comprobantes',
			'Comprobantes',
			'manage_options',
			'wpcfact-comprobantes',
			array( $this, 'render_comprobantes_page' )
		);

		add_submenu_page(
			'wpcfact-comprobantes',
			'Emitir Comprobante',
			'Emitir Comprobante',
			'manage_options',
			'wpcfact-emitir',
			array( $this, 'render_emitir_page' )
		);

		add_submenu_page(
			'wpcfact-comprobantes',
			'Configuración',
			'Configuración',
			'manage_options',
			'wpcfact-configuracion',
			array( $this, 'render_config_page' )
		);
	}

	public function enqueue_scripts( $hook ) {
		if ( strpos( $hook, 'wpcfact-emitir' ) !== false ) {
			wp_enqueue_script( 'wpcfact-wizard-js', WPC_FACTURACION_URL . 'admin/assets/js/wizard.js', array( 'jquery' ), WPC_FACTURACION_VERSION, true );
			wp_localize_script( 'wpcfact-wizard-js', 'wpcfact_ajax', array(
				'url'   => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'wpcfact_wizard_nonce' ),
			) );
			
			// Simple inline CSS for the wizard
			wp_add_inline_style( 'wp-admin', '
				.wpcfact-wizard-step { display: none; background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); max-width: 800px; margin-top: 20px; }
				.wpcfact-wizard-step.active { display: block; }
				.wpcfact-step-nav { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
				.wpcfact-step-indicator { padding: 5px 15px; background: #eee; border-radius: 4px; color: #666; font-weight: bold; }
				.wpcfact-step-indicator.active { background: #2271b1; color: #fff; }
				.wpcfact-shipment-list table { width: 100%; border-collapse: collapse; margin-top: 15px; }
				.wpcfact-shipment-list th, .wpcfact-shipment-list td { text-align: left; padding: 8px; border-bottom: 1px solid #ddd; }
				.wpcfact-summary-box { background: #f8f9fa; padding: 15px; border-left: 4px solid #2271b1; margin-top: 20px; }
				.wpcfact-summary-box p { margin: 5px 0; font-size: 14px; }
				.wpcfact-summary-box strong { font-size: 16px; }
			' );
		}
	}

	public function render_comprobantes_page() {
		include WPC_FACTURACION_PATH . 'admin/templates/comprobantes.php';
	}

	public function render_emitir_page() {
		include WPC_FACTURACION_PATH . 'admin/templates/wizard.php';
	}

	public function render_config_page() {
		echo '<div class="wrap"><h1>Configuración SUNAT</h1><p>Configuración en construcción...</p></div>';
	}
}

new WPC_Facturacion_Admin();
