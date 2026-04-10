<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPCC_Frontend {

	public function __construct() {
		add_shortcode( 'wpcc-transportistas',       [ $this, 'render_shortcode' ] );
		add_filter( 'wpcfe_after_sidebar_menus',    [ $this, 'sidebar_item' ], 20, 1 );
		add_action( 'wp_enqueue_scripts',           [ $this, 'encolar_assets' ], 100 );
		add_action( 'admin_post_wpcc_guardar_fe',   [ $this, 'handle_guardar' ] );
		add_action( 'admin_post_wpcc_estado_fe',    [ $this, 'handle_estado' ] );
	}

	public function sidebar_item( array $menu ): array {
		if ( ! current_user_can( 'manage_options' ) ) return $menu;
		$menu['wpcc-menu'] = [
			'page-id'   => wpcc_get_frontend_page_id(),
			'label'     => __( 'Transportistas', 'wp-cargo-carrier' ),
			'permalink' => wpcc_frontend_url(),
			'icon'      => 'fa-id-card',
		];
		return $menu;
	}

	public function encolar_assets(): void {
		if ( (int) get_queried_object_id() !== wpcc_get_frontend_page_id() ) return;
		wp_enqueue_style( 'wpcc-frontend', WPCC_URL . 'admin/assets/css/frontend.css', [], WPCC_VERSION );
	}

	public function render_shortcode(): string {
		if ( ! current_user_can( 'manage_options' ) ) {
			return '<p>' . esc_html__( 'Acceso restringido.', 'wp-cargo-carrier' ) . '</p>';
		}
		ob_start();
		$action = sanitize_key( $_GET['wpcc'] ?? '' );
		// Solo editar — no se puede crear desde frontend
		if ( $action === 'edit' ) {
			$this->render_form();
		} else {
			$this->render_list();
		}
		return ob_get_clean();
	}

	private function render_list(): void {
		$estado         = sanitize_text_field( wp_unslash( $_GET['estado'] ?? '' ) );
		$buscar         = sanitize_text_field( wp_unslash( $_GET['buscar'] ?? '' ) );
		$mensaje        = sanitize_key( $_GET['msg'] ?? '' );
		$transportistas = WPCC_Transportista::obtener_todos( compact( 'estado', 'buscar' ) );
		$page_url       = wpcc_frontend_url();
		wpcc_include_template( 'frontend/list.tpl.php', compact( 'transportistas', 'estado', 'buscar', 'mensaje', 'page_url' ) );
	}

	private function render_form(): void {
		$id            = (int) ( $_GET['id'] ?? 0 );
		$transportista = $id ? WPCC_Transportista::obtener_por_id( $id ) : null;
		$page_url      = wpcc_frontend_url();
		// Leer flash (error + valores previos) guardado por handle_guardar
		$uid   = get_current_user_id();
		$flash = get_transient( 'wpcc_flash_' . wp_get_session_token() );
		if ( $flash ) delete_transient( 'wpcc_flash_' . wp_get_session_token() );
		$error = $flash['error'] ?? '';
		$prev  = $flash['prev']  ?? null;
		wpcc_include_template( 'frontend/form.tpl.php', compact( 'id', 'transportista', 'error', 'prev', 'page_url' ) );
	}

	/* ── Guardar (admin-post.php → redirect back al frontend) ── */
	public function handle_guardar(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		check_admin_referer( 'wpcc_fe_nonce' );

		$id = (int) ( $_POST['id'] ?? 0 );
		if ( ! $id ) {
			wp_safe_redirect( wpcc_frontend_url() );
			exit;
		}

		$datos = [
			'nombres'  => sanitize_text_field( wp_unslash( $_POST['nombres']   ?? '' ) ),
			'apellidos'=> sanitize_text_field( wp_unslash( $_POST['apellidos'] ?? '' ) ),
			'dni'      => sanitize_text_field( wp_unslash( $_POST['dni']       ?? '' ) ),
			'brevete'  => sanitize_text_field( wp_unslash( $_POST['brevete']   ?? '' ) ),
			'telefono' => sanitize_text_field( wp_unslash( $_POST['telefono']  ?? '' ) ),
			'email'    => sanitize_email(      wp_unslash( $_POST['email']     ?? '' ) ),
		];

		$result = WPCC_Transportista::actualizar( $id, $datos );

		if ( is_wp_error( $result ) ) {
			set_transient( 'wpcc_flash_' . wp_get_session_token(), [ 'error' => $result->get_error_message(), 'prev' => $datos ], 60 );
			wp_safe_redirect( wpcc_frontend_url( [ 'wpcc' => 'edit', 'id' => $id ] ) );
		} else {
			wp_safe_redirect( wpcc_frontend_url( [ 'msg' => 'actualizado' ] ) );
		}
		exit;
	}

	public function handle_estado(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		check_admin_referer( 'wpcc_fe_estado_nonce' );
		WPCC_Transportista::cambiar_estado(
			(int) ( $_POST['id'] ?? 0 ),
			sanitize_text_field( wp_unslash( $_POST['estado'] ?? '' ) )
		);
		wp_safe_redirect( wpcc_frontend_url( [ 'msg' => 'estado_actualizado' ] ) );
		exit;
	}
}

new WPCC_Frontend();
