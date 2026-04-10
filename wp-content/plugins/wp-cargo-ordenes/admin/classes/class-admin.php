<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPCO_Admin {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'registrar_menu' ] );
		add_action( 'admin_post_wpco_guardar',        [ $this, 'handle_guardar' ] );
		add_action( 'admin_post_wpco_cambiar_estado', [ $this, 'handle_estado' ] );
	}

	public function registrar_menu(): void {
		add_menu_page(
			'Órdenes de Servicio', 'Órdenes de Serv.', 'manage_options',
			'wp-cargo-ordenes', [ $this, 'render_lista' ],
			'dashicons-clipboard', 34
		);
		add_submenu_page( 'wp-cargo-ordenes', 'Nueva Orden', 'Nueva Orden', 'manage_options', 'wpco-nueva', [ $this, 'render_form' ] );
	}

	public function render_lista(): void {
		$estado  = sanitize_text_field( wp_unslash( $_GET['estado'] ?? '' ) );
		$buscar  = sanitize_text_field( wp_unslash( $_GET['buscar'] ?? '' ) );
		$mensaje = sanitize_key( $_GET['mensaje'] ?? '' );
		$ordenes = WPCO_Orden::obtener_todos( compact( 'estado', 'buscar' ) );
		$transportistas = WPCO_Orden::obtener_transportistas();
		wpco_include_template( 'admin/list.tpl.php', compact( 'ordenes', 'estado', 'buscar', 'mensaje', 'transportistas' ) );
	}

	public function render_form(): void {
		$id    = (int) ( $_GET['id'] ?? 0 );
		$orden = $id ? WPCO_Orden::obtener_por_id( $id ) : null;
		$error = sanitize_text_field( wp_unslash( urldecode( $_GET['error'] ?? '' ) ) );
		$transportistas = WPCO_Orden::obtener_transportistas();
		wpco_include_template( 'admin/form.tpl.php', compact( 'id', 'orden', 'error', 'transportistas' ) );
	}

	public function handle_guardar(): void {
		check_admin_referer( 'wpco_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$id    = (int) ( $_POST['id'] ?? 0 );
		$datos = [
			'cliente'          => $_POST['cliente']          ?? '',
			'origen'           => $_POST['origen']           ?? '',
			'destino'          => $_POST['destino']          ?? '',
			'peso'             => $_POST['peso']             ?? 0,
			'cantidad'         => $_POST['cantidad']         ?? 1,
			'costo'            => $_POST['costo']            ?? 0,
			'transportista_id' => $_POST['transportista_id'] ?? '',
			'estado'           => $_POST['estado']           ?? 'Registrado',
			'notas'            => $_POST['notas']            ?? '',
		];
		$result = $id ? WPCO_Orden::actualizar( $id, $datos ) : WPCO_Orden::crear( $datos );
		if ( is_wp_error( $result ) ) {
			$params = [ 'page' => 'wpco-nueva', 'error' => rawurlencode( $result->get_error_message() ) ];
			if ( $id ) $params['id'] = $id;
			wp_redirect( add_query_arg( $params, admin_url( 'admin.php' ) ) );
		} else {
			wp_redirect( add_query_arg( [ 'page' => 'wp-cargo-ordenes', 'mensaje' => $id ? 'actualizado' : 'guardado' ], admin_url( 'admin.php' ) ) );
		}
		exit;
	}

	public function handle_estado(): void {
		check_admin_referer( 'wpco_estado_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$id     = (int) ( $_GET['id']     ?? 0 );
		$estado = sanitize_text_field( wp_unslash( $_GET['estado'] ?? '' ) );
		if ( $id && in_array( $estado, WPCO_Orden::$estados, true ) ) {
			global $wpdb;
			$wpdb->update( WPCO_Database::tabla(), [ 'estado' => $estado ], [ 'id' => $id ], [ '%s' ], [ '%d' ] );
		}
		wp_redirect( add_query_arg( [ 'page' => 'wp-cargo-ordenes', 'mensaje' => 'estado_actualizado' ], admin_url( 'admin.php' ) ) );
		exit;
	}
}

new WPCO_Admin();
