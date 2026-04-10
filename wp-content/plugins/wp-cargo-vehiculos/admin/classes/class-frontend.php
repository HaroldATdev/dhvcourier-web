<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPCVH_Frontend {

	public function __construct() {
		add_shortcode( 'wpcvh-vehiculos',        [ $this, 'render_shortcode' ] );
		add_filter( 'wpcfe_after_sidebar_menus', [ $this, 'sidebar_item' ], 35, 1 );
		add_action( 'wp_enqueue_scripts',        [ $this, 'encolar_assets' ], 100 );
		add_action( 'admin_post_wpcvh_fe_guardar',       [ $this, 'handle_guardar' ] );
		add_action( 'admin_post_wpcvh_fe_estado',        [ $this, 'handle_estado' ] );
		add_action( 'admin_post_wpcvh_fe_actualizar_km', [ $this, 'handle_actualizar_km' ] );
		add_action( 'admin_post_wpcvh_fe_mant_guardar',  [ $this, 'handle_mant_guardar' ] );
		add_action( 'admin_post_wpcvh_fe_mant_eliminar', [ $this, 'handle_mant_eliminar' ] );
	}

	public function sidebar_item( array $menu ): array {
		if ( ! current_user_can( 'manage_options' ) ) return $menu;
		$menu['wpcvh-menu'] = [
			'page-id'   => wpcvh_get_frontend_page_id(),
			'label'     => __( 'Vehículos', 'wp-cargo-vehiculos' ),
			'permalink' => wpcvh_frontend_url(),
			'icon'      => 'fa-truck',
		];
		return $menu;
	}

	public function encolar_assets(): void {
		if ( (int) get_queried_object_id() !== wpcvh_get_frontend_page_id() ) return;
		wp_enqueue_style( 'wpcvh-frontend', WPCVH_URL . 'admin/assets/css/frontend.css', [], WPCVH_VERSION );
	}

	public function render_shortcode(): string {
		if ( ! current_user_can( 'manage_options' ) ) {
			return '<p>' . esc_html__( 'Acceso restringido.', 'wp-cargo-vehiculos' ) . '</p>';
		}
		ob_start();
		$action = sanitize_key( $_GET['wpcvh'] ?? '' );
		if ( in_array( $action, [ 'add', 'edit' ], true ) ) {
			$this->render_form();
		} elseif ( $action === 'historial' ) {
			$this->render_historial();
		} else {
			$this->render_list();
		}
		return ob_get_clean();
	}

	/* ── Vistas ────────────────────────────────────────────── */

	private function render_list(): void {
		$estado    = sanitize_text_field( wp_unslash( $_GET['estado'] ?? '' ) );
		$buscar    = sanitize_text_field( wp_unslash( $_GET['buscar'] ?? '' ) );
		$mensaje   = sanitize_key( $_GET['msg'] ?? '' );
		$vehiculos = WPCVH_Vehiculo::obtener_todos( compact( 'estado', 'buscar' ) );
		$page_url  = wpcvh_frontend_url();
		wpcvh_include_template( 'frontend/list.tpl.php', compact( 'vehiculos', 'estado', 'buscar', 'mensaje', 'page_url' ) );
	}

	private function render_form(): void {
		$id       = (int) ( $_GET['id'] ?? 0 );
		$vehiculo = $id ? WPCVH_Vehiculo::obtener_por_id( $id ) : null;
		$mants    = $id ? WPCVH_Mantenimiento::obtener_por_vehiculo( $id ) : [];
		$km_log   = $id ? WPCVH_Vehiculo::km_log( $id ) : [];
		$page_url = wpcvh_frontend_url();
		$token    = wp_get_session_token();
		$flash    = get_transient( 'wpcvh_flash_' . $token );
		if ( $flash ) delete_transient( 'wpcvh_flash_' . $token );
		$error   = $flash['error']   ?? '';
		$mensaje = $flash['mensaje'] ?? sanitize_key( $_GET['msg'] ?? '' );
		wpcvh_include_template( 'frontend/form.tpl.php', compact( 'id', 'vehiculo', 'mants', 'km_log', 'error', 'mensaje', 'page_url' ) );
	}

	private function render_historial(): void {
		$vehiculo_id = (int) ( $_GET['vehiculo_id'] ?? 0 );
		$desde       = sanitize_text_field( wp_unslash( $_GET['desde'] ?? '' ) );
		$hasta       = sanitize_text_field( wp_unslash( $_GET['hasta'] ?? '' ) );
		$vehiculos   = WPCVH_Vehiculo::obtener_todos();
		$mants       = WPCVH_Mantenimiento::obtener_todos( compact( 'vehiculo_id', 'desde', 'hasta' ) );
		$page_url    = wpcvh_frontend_url();
		wpcvh_include_template( 'frontend/historial.tpl.php', compact( 'mants', 'vehiculos', 'vehiculo_id', 'desde', 'hasta', 'page_url' ) );
	}

	/* ── Handlers admin-post ───────────────────────────────── */

	private function flash_redirect( string $url, array $flash = [], string $redirect = '' ): void {
		if ( $flash ) {
			set_transient( 'wpcvh_flash_' . wp_get_session_token(), $flash, 60 );
		}
		wp_safe_redirect( $redirect ?: $url );
		exit;
	}

	public function handle_guardar(): void {
		check_admin_referer( 'wpcvh_fe_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$id    = (int) ( $_POST['id'] ?? 0 );
		$datos = [
			'placa'          => sanitize_text_field( wp_unslash( $_POST['placa']          ?? '' ) ),
			'tipo'           => sanitize_text_field( wp_unslash( $_POST['tipo']           ?? '' ) ),
			'marca'          => sanitize_text_field( wp_unslash( $_POST['marca']          ?? '' ) ),
			'modelo'         => sanitize_text_field( wp_unslash( $_POST['modelo']         ?? '' ) ),
			'anio'           => $_POST['anio']           ?? '',
			'km_inicial'     => $_POST['km_inicial']     ?? 0,
			'km_limite_mant' => $_POST['km_limite_mant'] ?? 5000,
			'notas'          => sanitize_textarea_field( wp_unslash( $_POST['notas'] ?? '' ) ),
		];
		$result = $id ? WPCVH_Vehiculo::actualizar( $id, $datos ) : WPCVH_Vehiculo::crear( $datos );
		if ( is_wp_error( $result ) ) {
			$params = [ 'wpcvh' => $id ? 'edit' : 'add' ];
			if ( $id ) $params['id'] = $id;
			$this->flash_redirect( '', [ 'error' => $result->get_error_message() ], wpcvh_frontend_url( $params ) );
		} else {
			wp_safe_redirect( wpcvh_frontend_url( [ 'msg' => $id ? 'actualizado' : 'guardado' ] ) );
			exit;
		}
	}

	public function handle_estado(): void {
		check_admin_referer( 'wpcvh_fe_estado_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		WPCVH_Vehiculo::cambiar_estado(
			(int) ( $_POST['id']     ?? 0 ),
			sanitize_text_field( wp_unslash( $_POST['estado'] ?? '' ) )
		);
		wp_safe_redirect( wpcvh_frontend_url( [ 'msg' => 'estado_actualizado' ] ) );
		exit;
	}

	public function handle_actualizar_km(): void {
		check_admin_referer( 'wpcvh_fe_km_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$id       = (int)   ( $_POST['id']        ?? 0 );
		$km_nuevo = (int)   ( $_POST['km_nuevo']  ?? 0 );
		$descr    = sanitize_text_field( wp_unslash( $_POST['descripcion'] ?? '' ) );
		$result   = WPCVH_Vehiculo::actualizar_km( $id, $km_nuevo, $descr );
		$url      = wpcvh_frontend_url( [ 'wpcvh' => 'edit', 'id' => $id ] );
		if ( is_wp_error( $result ) ) $this->flash_redirect( '', [ 'error' => $result->get_error_message() ], $url );
		else { $this->flash_redirect( '', [ 'mensaje' => 'km_actualizado' ], $url ); }
	}

	public function handle_mant_guardar(): void {
		check_admin_referer( 'wpcvh_fe_mant_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$veh_id = (int) ( $_POST['vehiculo_id'] ?? 0 );
		$datos  = [
			'vehiculo_id'   => $veh_id,
			'tipo_mant'     => sanitize_text_field( wp_unslash( $_POST['tipo_mant']    ?? '' ) ),
			'km_al_momento' => (int)   ( $_POST['km_al_momento'] ?? 0 ),
			'costo'         => (float) ( $_POST['costo']         ?? 0 ),
			'descripcion'   => sanitize_textarea_field( wp_unslash( $_POST['descripcion'] ?? '' ) ),
			'realizado_en'  => sanitize_text_field( wp_unslash( $_POST['realizado_en']  ?? '' ) ),
		];
		$result = WPCVH_Mantenimiento::crear( $datos );
		$url    = wpcvh_frontend_url( [ 'wpcvh' => 'edit', 'id' => $veh_id ] );
		if ( is_wp_error( $result ) ) $this->flash_redirect( '', [ 'error' => $result->get_error_message() ], $url );
		else { $this->flash_redirect( '', [ 'mensaje' => 'mant_registrado' ], $url ); }
	}

	public function handle_mant_eliminar(): void {
		check_admin_referer( 'wpcvh_fe_mant_del_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$veh_id = (int) ( $_POST['vehiculo_id'] ?? 0 );
		WPCVH_Mantenimiento::eliminar( (int) ( $_POST['mant_id'] ?? 0 ) );
		$url = wpcvh_frontend_url( [ 'wpcvh' => 'edit', 'id' => $veh_id ] );
		$this->flash_redirect( '', [ 'mensaje' => 'mant_eliminado' ], $url );
	}
}

new WPCVH_Frontend();
