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
		if ( strpos( $hook, 'wpcfact' ) === false ) {
			return;
		}

		wp_enqueue_style( 'wpcfact-wizard-css', WPC_FACTURACION_URL . 'admin/assets/css/wizard.css', array(), WPC_FACTURACION_VERSION );
		wp_enqueue_script( 'wpcfact-wizard-js', WPC_FACTURACION_URL . 'admin/assets/js/wizard.js', array( 'jquery' ), WPC_FACTURACION_VERSION, true );
		wp_localize_script( 'wpcfact-wizard-js', 'wpcfact_ajax', array(
			'url'   => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'wpcfact_wizard_nonce' ),
		) );
	}

	public function render_comprobantes_page() {
		include WPC_FACTURACION_PATH . 'admin/templates/comprobantes.php';
	}

	public function render_emitir_page() {
		include WPC_FACTURACION_PATH . 'admin/templates/wizard.php';
	}

	public function render_config_page() {
		include WPC_FACTURACION_PATH . 'admin/templates/config.php';
	}
}

new WPC_Facturacion_Admin();
