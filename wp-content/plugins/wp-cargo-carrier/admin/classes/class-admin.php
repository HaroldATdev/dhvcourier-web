<?php
/**
 * WP Admin nativo para Transportistas.
 * Los transportistas se crean automáticamente al crear usuario WPDriver.
 * Aquí solo se visualizan y editan.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class WPCC_Admin {

	public function __construct() {
		add_action( 'admin_menu',           [ $this, 'registrar_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'encolar_assets' ] );
		add_action( 'admin_post_wpcc_guardar',       [ $this, 'manejar_guardar' ] );
		add_action( 'admin_post_wpcc_cambiar_estado', [ $this, 'manejar_estado' ] );
	}

	/* ------------------------------------------------------------------ */
	/*  Menú                                                                */
	/* ------------------------------------------------------------------ */

	public function registrar_menu(): void {
		add_menu_page(
			__( 'Transportistas', 'wp-cargo-carrier' ),
			__( 'Transportistas', 'wp-cargo-carrier' ),
			'manage_options',
			'wp-cargo-carrier',
			[ $this, 'pagina_listado' ],
			'dashicons-id-alt',
			32
		);
		// Submenú solo listado — no existe "Nuevo Transportista" manual
		add_submenu_page( 'wp-cargo-carrier', 'Transportistas', 'Transportistas', 'manage_options', 'wp-cargo-carrier', [ $this, 'pagina_listado' ] );
		// Submenú edición (oculto del menú, accesible por URL)
		add_submenu_page( 'wp-cargo-carrier', 'Editar Transportista', 'Editar', 'manage_options', 'wpcc-editar', [ $this, 'pagina_formulario' ] );
	}

	/* ------------------------------------------------------------------ */
	/*  Assets                                                              */
	/* ------------------------------------------------------------------ */

	public function encolar_assets( string $hook ): void {
		$pages = [ 'toplevel_page_wp-cargo-carrier', 'transportistas_page_wpcc-editar' ];
		if ( ! in_array( $hook, $pages, true ) ) return;
		wp_enqueue_style( 'wpcc-admin', WPCC_URL . 'admin/assets/css/admin.css', [], WPCC_VERSION );
	}

	/* ------------------------------------------------------------------ */
	/*  Páginas                                                             */
	/* ------------------------------------------------------------------ */

	public function pagina_listado(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$estado         = sanitize_text_field( wp_unslash( $_GET['estado']  ?? '' ) );
		$buscar         = sanitize_text_field( wp_unslash( $_GET['buscar']  ?? '' ) );
		$mensaje        = sanitize_text_field( wp_unslash( $_GET['mensaje'] ?? '' ) );
		$transportistas = WPCC_Transportista::obtener_todos( compact( 'estado', 'buscar' ) );
		wpcc_include_template( 'admin/list.tpl.php', compact( 'transportistas', 'estado', 'buscar', 'mensaje' ) );
	}

	public function pagina_formulario(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$id = (int) ( $_GET['id'] ?? 0 );
		if ( ! $id ) {
			wp_redirect( admin_url( 'admin.php?page=wp-cargo-carrier' ) );
			exit;
		}
		$transportista = WPCC_Transportista::obtener_por_id( $id );
		if ( ! $transportista ) {
			wp_die( 'Transportista no encontrado.' );
		}
		$error = sanitize_text_field( wp_unslash( urldecode( $_GET['error'] ?? '' ) ) );
		wpcc_include_template( 'admin/form.tpl.php', compact( 'id', 'transportista', 'error' ) );
	}

	/* ------------------------------------------------------------------ */
	/*  Acciones POST                                                       */
	/* ------------------------------------------------------------------ */

	public function manejar_guardar(): void {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'wpcc_nonce' ) ) {
			wp_die( 'No autorizado.' );
		}
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );

		$id = (int) ( $_POST['id'] ?? 0 );
		if ( ! $id ) {
			wp_die( 'ID de transportista requerido.' );
		}

		$datos = [
			'nombres'  => sanitize_text_field( wp_unslash( $_POST['nombres']   ?? '' ) ),
			'apellidos'=> sanitize_text_field( wp_unslash( $_POST['apellidos'] ?? '' ) ),
			'dni'      => sanitize_text_field( wp_unslash( $_POST['dni']       ?? '' ) ),
			'brevete'  => sanitize_text_field( wp_unslash( $_POST['brevete']   ?? '' ) ),
			'telefono' => sanitize_text_field( wp_unslash( $_POST['telefono']  ?? '' ) ),
			'email'    => sanitize_email(      wp_unslash( $_POST['email']     ?? '' ) ),
		];

		$resultado = WPCC_Transportista::actualizar( $id, $datos );

		if ( is_wp_error( $resultado ) ) {
			$params = [ 'page' => 'wpcc-editar', 'id' => $id, 'error' => rawurlencode( $resultado->get_error_message() ) ];
			wp_redirect( add_query_arg( $params, admin_url( 'admin.php' ) ) );
		} else {
			wp_redirect( add_query_arg( [ 'page' => 'wp-cargo-carrier', 'mensaje' => 'actualizado' ], admin_url( 'admin.php' ) ) );
		}
		exit;
	}

	public function manejar_estado(): void {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'wpcc_estado_nonce' ) ) {
			wp_die( 'No autorizado.' );
		}
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$result = WPCC_Transportista::cambiar_estado(
			(int) ( $_GET['id'] ?? 0 ),
			sanitize_text_field( wp_unslash( $_GET['estado'] ?? '' ) )
		);
		$msg = is_wp_error( $result ) ? urlencode( $result->get_error_message() ) : 'estado_actualizado';
		wp_redirect( add_query_arg( [ 'page' => 'wp-cargo-carrier', 'mensaje' => $msg ], admin_url( 'admin.php' ) ) );
		exit;
	}
}

new WPCC_Admin();
