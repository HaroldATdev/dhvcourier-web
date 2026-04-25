<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPC_Facturacion_Ajax {

	public function __construct() {
		add_action( 'wp_ajax_wpcfact_buscar_cliente', array( $this, 'buscar_cliente' ) );
		add_action( 'wp_ajax_wpcfact_obtener_envios', array( $this, 'obtener_envios' ) );
		add_action( 'wp_ajax_wpcfact_emitir_comprobante', array( $this, 'emitir_comprobante' ) );
		add_action( 'wp_ajax_wpcfact_anular_comprobante', array( $this, 'anular_comprobante' ) );
	}

	public function buscar_cliente() {
		check_ajax_referer( 'wpcfact_wizard_nonce', 'nonce' );

		$query = sanitize_text_field( wp_unslash( $_POST['q'] ?? '' ) );
		if ( empty( $query ) ) {
			wp_send_json_success( array() );
		}

		// Buscar usuarios (rol wpcargo_client o por nombre)
		$args = array(
			'search'         => '*' . $query . '*',
			'search_columns' => array( 'user_login', 'user_nicename', 'user_email', 'display_name' ),
			'number'         => 10,
		);

		$users = get_users( $args );
		$results = array();

		foreach ( $users as $user ) {
			// Intentar obtener doc_num previo si existe
			$doc_num = get_user_meta( $user->ID, 'wpcfact_doc_num', true );
			$razon_social = get_user_meta( $user->ID, 'wpcfact_razon_social', true );
			$direccion = get_user_meta( $user->ID, 'wpcfact_direccion', true );

			$results[] = array(
				'id'           => $user->ID,
				'name'         => $user->display_name,
				'email'        => $user->user_email,
				'doc_num'      => $doc_num,
				'razon_social' => $razon_social ?: $user->display_name,
				'direccion'    => $direccion,
			);
		}

		wp_send_json_success( $results );
	}

	public function obtener_envios() {
		check_ajax_referer( 'wpcfact_wizard_nonce', 'nonce' );

		$user_id = intval( $_POST['user_id'] ?? 0 );
		if ( ! $user_id ) {
			wp_send_json_error( 'ID de usuario inválido.' );
		}

		global $wpdb;

		// Buscar envíos donde el `registered_shipper` es este usuario
		// y el envío no está ya en `facturacion_comprobante_envios`
		// y tiene `wpcargo_total_freight` > 0
		
		$sql = $wpdb->prepare( "
			SELECT p.ID, p.post_title, p.post_date
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id AND pm.meta_key = 'registered_shipper' AND pm.meta_value = %d)
			WHERE p.post_type = 'wpcargo_shipment'
			AND p.post_status = 'publish'
			AND p.ID NOT IN (
				SELECT shipment_id FROM {$wpdb->prefix}facturacion_comprobante_envios
			)
			ORDER BY p.post_date DESC
			LIMIT 50
		", $user_id );

		$envios = $wpdb->get_results( $sql );
		$resultados = array();

		foreach ( $envios as $envio ) {
			$freight_raw = get_post_meta( $envio->ID, 'wpcargo_total_freight', true );
			// Limpiar formato numérico (ej: $20,000.00 -> 20000.00)
			$freight = floatval( preg_replace( '/[^0-9.]/', '', $freight_raw ) );

			if ( $freight <= 0 ) {
				continue; // Solo envíos con monto
			}

			$origen  = get_post_meta( $envio->ID, 'wpcargo_origin_field', true );
			$destino = get_post_meta( $envio->ID, 'wpcargo_destination', true );

			$resultados[] = array(
				'id'      => $envio->ID,
				'title'   => $envio->post_title,
				'date'    => gmdate( 'd/m/Y', strtotime( $envio->post_date ) ),
				'ruta'    => $origen . ' &rarr; ' . $destino,
				'monto'   => $freight,
			);
		}

		wp_send_json_success( $resultados );
	}

	public function emitir_comprobante() {
		check_ajax_referer( 'wpcfact_wizard_nonce', 'nonce' );

		$user_id    = intval( $_POST['user_id'] ?? 0 );
		$envios     = array_map( 'intval', $_POST['envios'] ?? array() );
		$tipo       = sanitize_text_field( $_POST['tipo'] ?? '01' );
		$doc_num    = sanitize_text_field( $_POST['doc_num'] ?? '' );
		$nombre     = sanitize_text_field( $_POST['nombre'] ?? '' );
		$direccion  = sanitize_text_field( $_POST['direccion'] ?? '' );
		$forma_pago = sanitize_text_field( $_POST['forma_pago'] ?? 'Contado' );

		if ( ! $user_id || empty( $envios ) || empty( $doc_num ) || empty( $nombre ) ) {
			wp_send_json_error( 'Faltan datos requeridos.' );
		}

		// Guardar user_meta para reutilización
		update_user_meta( $user_id, 'wpcfact_doc_num', $doc_num );
		update_user_meta( $user_id, 'wpcfact_razon_social', $nombre );
		update_user_meta( $user_id, 'wpcfact_direccion', $direccion );

		$resultado = WPC_Facturacion_Constructor::emitir( $user_id, $envios, $tipo, $doc_num, $nombre, $direccion, $forma_pago );

		if ( is_wp_error( $resultado ) ) {
			wp_send_json_error( $resultado->get_error_message() );
		}

		wp_send_json_success( $resultado );
	}

	public function anular_comprobante() {
		check_ajax_referer( 'wpcfact_wizard_nonce', 'nonce' );

		$comprobante_id = intval( $_POST['comprobante_id'] ?? 0 );
		$motivo         = sanitize_text_field( $_POST['motivo'] ?? '' );

		if ( ! $comprobante_id || empty( $motivo ) ) {
			wp_send_json_error( 'ID de comprobante y motivo son requeridos.' );
		}

		$comprobante = WPC_Facturacion_Comprobante::obtener( $comprobante_id );
		if ( ! $comprobante ) {
			wp_send_json_error( 'Comprobante no encontrado.' );
		}

		if ( $comprobante->estado !== 'ACEPTADO' ) {
			wp_send_json_error( 'Solo se pueden anular comprobantes ACEPTADOS.' );
		}

		$api_response = WPC_Facturacion_APISunat::void_bill( $comprobante->document_id, $motivo );

		if ( is_wp_error( $api_response ) ) {
			wp_send_json_error( $api_response->get_error_message() );
		}

		WPC_Facturacion_Comprobante::actualizar( $comprobante_id, array( 'estado' => 'ANULADO' ) );

		wp_send_json_success( 'Comprobante anulado correctamente.' );
	}
}

new WPC_Facturacion_Ajax();
