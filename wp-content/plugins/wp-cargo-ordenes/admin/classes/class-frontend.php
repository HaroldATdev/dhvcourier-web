<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPCO_Frontend {

	public function __construct() {
		add_shortcode( 'wpco-ordenes',           [ $this, 'render_shortcode' ] );
		add_filter( 'wpcfe_after_sidebar_menus', [ $this, 'sidebar_item' ], 30, 1 );
		add_action( 'wp_enqueue_scripts',        [ $this, 'encolar_assets' ], 100 );
		add_action( 'admin_post_wpco_guardar_fe', [ $this, 'handle_guardar' ] );
	}

	public function sidebar_item( array $menu ): array {
		if ( ! current_user_can( 'manage_options' ) ) return $menu;
		$menu['wpco-menu'] = [
			'page-id'   => wpco_get_frontend_page_id(),
			'label'     => __( 'Órdenes', 'wp-cargo-ordenes' ),
			'permalink' => wpco_frontend_url(),
			'icon'      => 'fa-file-text',
		];
		return $menu;
	}

	public function encolar_assets(): void {
		if ( (int) get_queried_object_id() !== wpco_get_frontend_page_id() ) return;
		wp_enqueue_style( 'wpco-frontend', WPCO_URL . 'admin/assets/css/frontend.css', [], WPCO_VERSION );
	}

	public function render_shortcode(): string {
		if ( ! current_user_can( 'manage_options' ) ) {
			return '<p>' . esc_html__( 'Acceso restringido.', 'wp-cargo-ordenes' ) . '</p>';
		}
		ob_start();
		$action = sanitize_key( $_GET['wpco'] ?? '' );
		if ( in_array( $action, [ 'add', 'edit' ], true ) ) {
			$this->render_form();
		} else {
			$this->render_list();
		}
		return ob_get_clean();
	}

	private function render_list(): void {
		$estado   = sanitize_text_field( wp_unslash( $_GET['estado'] ?? '' ) );
		$buscar   = sanitize_text_field( wp_unslash( $_GET['buscar'] ?? '' ) );
		$mensaje  = sanitize_key( $_GET['msg'] ?? '' );
		$ordenes  = WPCO_Orden::obtener_todos( compact( 'estado', 'buscar' ) );
		$page_url = wpco_frontend_url();
		wpco_include_template( 'frontend/list.tpl.php', compact( 'ordenes', 'estado', 'buscar', 'mensaje', 'page_url' ) );
	}

	private function render_form(): void {
		$id             = (int) ( $_GET['id'] ?? 0 );
		$orden          = $id ? WPCO_Orden::obtener_por_id( $id ) : null;
		$transportistas = WPCO_Orden::obtener_transportistas();
		$page_url       = wpco_frontend_url();
		$token          = wp_get_session_token();
		$flash          = get_transient( 'wpco_flash_' . $token );
		if ( $flash ) delete_transient( 'wpco_flash_' . $token );
		$error = $flash['error'] ?? '';
		$prev  = $flash['prev']  ?? null;
		wpco_include_template( 'frontend/form.tpl.php', compact( 'id', 'orden', 'transportistas', 'error', 'prev', 'page_url' ) );
	}

	public function handle_guardar(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		check_admin_referer( 'wpco_fe_nonce' );

		$id    = (int) ( $_POST['id'] ?? 0 );
		$datos = [
			'cliente'          => sanitize_text_field( wp_unslash( $_POST['cliente']          ?? '' ) ),
			'origen'           => sanitize_text_field( wp_unslash( $_POST['origen']           ?? '' ) ),
			'destino'          => sanitize_text_field( wp_unslash( $_POST['destino']          ?? '' ) ),
			'peso'             => $_POST['peso']     ?? 0,
			'cantidad'         => $_POST['cantidad'] ?? 1,
			'costo'            => $_POST['costo']    ?? 0,
			'transportista_id' => sanitize_text_field( wp_unslash( $_POST['transportista_id'] ?? '' ) ),
			'estado'           => sanitize_text_field( wp_unslash( $_POST['estado']           ?? 'Registrado' ) ),
			'notas'            => sanitize_textarea_field( wp_unslash( $_POST['notas']        ?? '' ) ),
		];

		$result = $id ? WPCO_Orden::actualizar( $id, $datos ) : WPCO_Orden::crear( $datos );

		if ( is_wp_error( $result ) ) {
			$token = wp_get_session_token();
			set_transient( 'wpco_flash_' . $token, [ 'error' => $result->get_error_message(), 'prev' => $datos ], 60 );
			$params = [ 'wpco' => $id ? 'edit' : 'add' ];
			if ( $id ) $params['id'] = $id;
			wp_safe_redirect( wpco_frontend_url( $params ) );
		} else {
			wp_safe_redirect( wpco_frontend_url( [ 'msg' => $id ? 'actualizado' : 'guardado' ] ) );
		}
		exit;
	}
}

new WPCO_Frontend();
