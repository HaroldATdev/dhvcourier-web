<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPCVH_Admin {

	public function __construct() {
		add_action( 'admin_menu',                        [ $this, 'registrar_menu' ] );
		add_action( 'admin_enqueue_scripts',             [ $this, 'encolar_assets' ] );
		add_action( 'admin_post_wpcvh_guardar',          [ $this, 'handle_guardar' ] );
		add_action( 'admin_post_wpcvh_estado',           [ $this, 'handle_estado' ] );
		add_action( 'admin_post_wpcvh_actualizar_km',    [ $this, 'handle_actualizar_km' ] );
		add_action( 'admin_post_wpcvh_mant_guardar',     [ $this, 'handle_mant_guardar' ] );
		add_action( 'admin_post_wpcvh_mant_eliminar',    [ $this, 'handle_mant_eliminar' ] );
	}

	/* ── Menú ──────────────────────────────────────────────── */

	public function registrar_menu(): void {
		add_menu_page( 'Vehículos', 'Vehículos', 'manage_options', 'wp-cargo-vehiculos',
			[ $this, 'pagina_listado' ], 'dashicons-car', 34 );
		add_submenu_page( 'wp-cargo-vehiculos', 'Vehículos',       'Listado',          'manage_options', 'wp-cargo-vehiculos', [ $this, 'pagina_listado' ] );
		add_submenu_page( 'wp-cargo-vehiculos', 'Nuevo Vehículo',  '+ Nuevo Vehículo', 'manage_options', 'wpcvh-nuevo',        [ $this, 'pagina_form' ] );
		add_submenu_page( 'wp-cargo-vehiculos', 'Mantenimientos',  'Mantenimientos',   'manage_options', 'wpcvh-mant',         [ $this, 'pagina_mantenimientos' ] );
	}

	public function encolar_assets( string $hook ): void {
		if ( strpos( $hook, 'wp-cargo-vehiculos' ) === false && strpos( $hook, 'wpcvh' ) === false ) return;
		wp_enqueue_style( 'wpcvh-admin', WPCVH_URL . 'admin/assets/css/admin.css', [], WPCVH_VERSION );
	}

	/* ── Páginas ───────────────────────────────────────────── */

	public function pagina_listado(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$estado    = sanitize_text_field( wp_unslash( $_GET['estado'] ?? '' ) );
		$buscar    = sanitize_text_field( wp_unslash( $_GET['buscar'] ?? '' ) );
		$mensaje   = sanitize_key( $_GET['mensaje'] ?? '' );
		$vehiculos = WPCVH_Vehiculo::obtener_todos( compact( 'estado', 'buscar' ) );
		wpcvh_include_template( 'admin/list.tpl.php', compact( 'vehiculos', 'estado', 'buscar', 'mensaje' ) );
	}

	public function pagina_form(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$id        = (int) ( $_GET['id'] ?? 0 );
		$vehiculo  = $id ? WPCVH_Vehiculo::obtener_por_id( $id ) : null;
		$mants     = $id ? WPCVH_Mantenimiento::obtener_por_vehiculo( $id ) : [];
		$km_log    = $id ? WPCVH_Vehiculo::km_log( $id ) : [];
		$error     = sanitize_text_field( urldecode( $_GET['error']   ?? '' ) );
		$mensaje   = sanitize_key( $_GET['mensaje'] ?? '' );
		wpcvh_include_template( 'admin/form.tpl.php', compact( 'id', 'vehiculo', 'mants', 'km_log', 'error', 'mensaje' ) );
	}

	public function pagina_mantenimientos(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$vehiculo_id = (int) ( $_GET['vehiculo_id'] ?? 0 );
		$desde       = sanitize_text_field( wp_unslash( $_GET['desde'] ?? '' ) );
		$hasta       = sanitize_text_field( wp_unslash( $_GET['hasta'] ?? '' ) );
		$vehiculos   = WPCVH_Vehiculo::obtener_todos();
		$mants       = WPCVH_Mantenimiento::obtener_todos( compact( 'vehiculo_id', 'desde', 'hasta' ) );
		wpcvh_include_template( 'admin/mantenimientos.tpl.php', compact( 'mants', 'vehiculos', 'vehiculo_id', 'desde', 'hasta' ) );
	}

	/* ── Handlers ──────────────────────────────────────────── */

	public function handle_guardar(): void {
		check_admin_referer( 'wpcvh_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$id    = (int) ( $_POST['id'] ?? 0 );
		$datos = [
			'placa'          => sanitize_text_field( wp_unslash( $_POST['placa']          ?? '' ) ),
			'tipo'           => sanitize_text_field( wp_unslash( $_POST['tipo']           ?? '' ) ),
			'marca'          => sanitize_text_field( wp_unslash( $_POST['marca']          ?? '' ) ),
			'modelo'         => sanitize_text_field( wp_unslash( $_POST['modelo']         ?? '' ) ),
			'anio'           => $_POST['anio']            ?? '',
			'km_inicial'     => $_POST['km_inicial']      ?? 0,
			'km_limite_mant' => $_POST['km_limite_mant']  ?? 5000,
			'notas'          => sanitize_textarea_field( wp_unslash( $_POST['notas']      ?? '' ) ),
		];
		$result = $id ? WPCVH_Vehiculo::actualizar( $id, $datos ) : WPCVH_Vehiculo::crear( $datos );
		if ( is_wp_error( $result ) ) {
			$params = [ 'page' => 'wpcvh-nuevo', 'error' => rawurlencode( $result->get_error_message() ) ];
			if ( $id ) $params['id'] = $id;
		} else {
			$params = [ 'page' => 'wp-cargo-vehiculos', 'mensaje' => $id ? 'actualizado' : 'guardado' ];
		}
		wp_redirect( add_query_arg( $params, admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_estado(): void {
		check_admin_referer( 'wpcvh_estado_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		WPCVH_Vehiculo::cambiar_estado(
			(int) ( $_GET['id']     ?? 0 ),
			sanitize_text_field( wp_unslash( $_GET['estado'] ?? '' ) )
		);
		wp_redirect( add_query_arg( [ 'page' => 'wp-cargo-vehiculos', 'mensaje' => 'estado_actualizado' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_actualizar_km(): void {
		check_admin_referer( 'wpcvh_km_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$id        = (int)   ( $_POST['id']        ?? 0 );
		$km_nuevo  = (int)   ( $_POST['km_nuevo']  ?? 0 );
		$descr     = sanitize_text_field( wp_unslash( $_POST['descripcion'] ?? '' ) );
		$result    = WPCVH_Vehiculo::actualizar_km( $id, $km_nuevo, $descr );
		$params    = [ 'page' => 'wpcvh-nuevo', 'id' => $id ];
		if ( is_wp_error( $result ) ) $params['error'] = rawurlencode( $result->get_error_message() );
		else $params['mensaje'] = 'km_actualizado';
		wp_redirect( add_query_arg( $params, admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_mant_guardar(): void {
		check_admin_referer( 'wpcvh_mant_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$id   = (int) ( $_POST['vehiculo_id'] ?? 0 );
		$datos = [
			'vehiculo_id'   => $id,
			'tipo_mant'     => sanitize_text_field( wp_unslash( $_POST['tipo_mant']    ?? '' ) ),
			'km_al_momento' => (int)   ( $_POST['km_al_momento'] ?? 0 ),
			'costo'         => (float) ( $_POST['costo']         ?? 0 ),
			'descripcion'   => sanitize_textarea_field( wp_unslash( $_POST['descripcion'] ?? '' ) ),
			'realizado_en'  => sanitize_text_field( wp_unslash( $_POST['realizado_en']  ?? '' ) ),
		];
		$result = WPCVH_Mantenimiento::crear( $datos );
		$params = [ 'page' => 'wpcvh-nuevo', 'id' => $id ];
		if ( is_wp_error( $result ) ) $params['error'] = rawurlencode( $result->get_error_message() );
		else $params['mensaje'] = 'mant_registrado';
		wp_redirect( add_query_arg( $params, admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_mant_eliminar(): void {
		check_admin_referer( 'wpcvh_mant_del_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$veh_id = (int) ( $_POST['vehiculo_id']   ?? 0 );
		WPCVH_Mantenimiento::eliminar( (int) ( $_POST['mant_id'] ?? 0 ) );
		wp_redirect( add_query_arg( [ 'page' => 'wpcvh-nuevo', 'id' => $veh_id, 'mensaje' => 'mant_eliminado' ], admin_url( 'admin.php' ) ) );
		exit;
	}
}

new WPCVH_Admin();
