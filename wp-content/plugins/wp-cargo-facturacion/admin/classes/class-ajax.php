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
		add_action( 'wp_ajax_wpcfact_emitir_nota_credito', array( $this, 'emitir_nota_credito' ) );
	}

	public function buscar_cliente() {
		check_ajax_referer( 'wpcfact_wizard_nonce', 'nonce' );

		$query = sanitize_text_field( wp_unslash( $_POST['q'] ?? '' ) );
		if ( empty( $query ) ) {
			wp_send_json_success( array() );
		}

		// Buscar usuarios (rol wpcargo_client o por nombre)
		$args = array(
			'role'   => 'wpcargo_client',
			'number' => 15,
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key'     => 'first_name',
					'value'   => $query,
					'compare' => 'LIKE'
				),
				array(
					'key'     => 'last_name',
					'value'   => $query,
					'compare' => 'LIKE'
				),
				array(
					'key'     => 'wpcfact_doc_num',
					'value'   => $query,
					'compare' => 'LIKE'
				),
				array(
					'key'     => 'dni_remitente',
					'value'   => $query,
					'compare' => 'LIKE'
				)
			)
		);
		
		// También buscar por email o display_name (por defecto de WP)
		$users_by_meta = get_users( $args );
		
		$users_by_standard = get_users( array(
			'search'         => '*' . $query . '*',
			'search_columns' => array( 'user_login', 'user_nicename', 'user_email', 'display_name' ),
			'role'           => 'wpcargo_client',
			'number'         => 15,
		) );

		// Combinar resultados únicos
		$all_users = array();
		$seen = array();
		foreach ( array_merge($users_by_meta, $users_by_standard) as $u ) {
			if ( ! isset($seen[$u->ID]) ) {
				$seen[$u->ID] = true;
				$all_users[] = $u;
			}
		}

		$resultados = array();
		foreach ( $all_users as $user ) {
			$doc_num = get_user_meta( $user->ID, 'wpcfact_doc_num', true );
			if ( empty( $doc_num ) ) {
				$doc_num = get_user_meta( $user->ID, 'dni_remitente', true );
			}

			$razon = get_user_meta( $user->ID, 'wpcfact_razon_social', true );
			if ( empty( $razon ) ) {
				// Intentar buscar nombre completo si no hay razon social
				$first = get_user_meta( $user->ID, 'first_name', true );
				$last = get_user_meta( $user->ID, 'last_name', true );
				$razon = trim( $first . ' ' . $last );
			}

			$dir = get_user_meta( $user->ID, 'wpcfact_direccion', true );

			$resultados[] = array(
				'id'           => $user->ID,
				'email'        => $user->user_email,
				'razon_social' => ! empty( $razon ) ? $razon : $user->display_name,
				'doc_num'      => $doc_num,
				'direccion'    => $dir,
			);
		}

		wp_send_json_success( $resultados );
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
			INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id AND pm.meta_key IN ('registered_shipper', 'wpcargo_cliente') AND pm.meta_value = %s)
			WHERE p.post_type = 'wpcargo_shipment'
			AND p.post_status = 'publish'
			AND p.ID NOT IN (
				SELECT shipment_id FROM {$wpdb->prefix}facturacion_comprobante_envios
			)
			ORDER BY p.post_date DESC
			LIMIT 100
		", strval( $user_id ) );

		$envios = $wpdb->get_results( $sql );

		$resultados = array();

		foreach ( $envios as $envio ) {
			// Leer monto desde costo_envio (cotización original), fallback a monto y luego wpcargo_total_freight
			$freight_raw = get_post_meta( $envio->ID, 'costo_envio', true );
			if ( empty( $freight_raw ) ) {
				$freight_raw = get_post_meta( $envio->ID, 'monto', true );
			}
			if ( empty( $freight_raw ) ) {
				$freight_raw = get_post_meta( $envio->ID, 'wpcargo_total_freight', true );
			}
			$freight = floatval( preg_replace( '/[^0-9.]/', '', is_string($freight_raw) ? $freight_raw : (is_numeric($freight_raw) ? (string)$freight_raw : '') ) );

			if ( $freight <= 0 ) {
				continue;
			}

			$origen  = get_post_meta( $envio->ID, 'lugar_origen', true );
			if ( empty( $origen ) ) $origen = get_post_meta( $envio->ID, 'wpcargo_origin_field', true );

			$destino = get_post_meta( $envio->ID, 'lugar_destino', true );
			if ( empty( $destino ) ) $destino = get_post_meta( $envio->ID, 'wpcargo_destination', true );

			$tracking = get_post_meta( $envio->ID, 'remitente', true );
			if ( empty( $tracking ) ) $tracking = $envio->post_title;

			$resultados[] = array(
				'id'      => $envio->ID,
				'title'   => $envio->post_title,  // Número de tracking (ej: DHV-0000051)
				'date'    => gmdate( 'd/m/Y', strtotime( $envio->post_date ) ),
				'ruta'    => $origen . ' → ' . $destino,
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
		$guia_peso      = sanitize_text_field( $_POST['guia_peso'] ?? '1.00' );
		$guia_motivo    = sanitize_text_field( $_POST['guia_motivo'] ?? '01' );
		$guia_modalidad = sanitize_text_field( $_POST['guia_modalidad'] ?? '01' );

		if ( ! $user_id || empty( $envios ) || empty( $doc_num ) || empty( $nombre ) ) {
			wp_send_json_error( 'Faltan datos requeridos.' );
		}

		// Guardar user_meta para reutilización
		update_user_meta( $user_id, 'wpcfact_doc_num', $doc_num );
		update_user_meta( $user_id, 'wpcfact_razon_social', $nombre );
		update_user_meta( $user_id, 'wpcfact_direccion', $direccion );

		if ( $tipo === '00' ) {
			if ( ! class_exists( 'WPC_Facturacion_Constructor_NotaVenta' ) ) {
				require_once dirname( __FILE__ ) . '/../includes/class-constructor-notaventa.php';
			}
			$resultado = WPC_Facturacion_Constructor_NotaVenta::emitir( $user_id, $envios, $doc_num, $nombre, $direccion, $forma_pago );
		} elseif ( $tipo === '09' || $tipo === '31' ) {
			if ( ! class_exists( 'WPC_Facturacion_Constructor_Guia' ) ) {
				require_once dirname( __FILE__ ) . '/../includes/class-constructor-guia.php';
			}
			$resultado = WPC_Facturacion_Constructor_Guia::emitir( $user_id, $envios, $tipo, $doc_num, $nombre, $direccion, $guia_peso, $guia_motivo, $guia_modalidad );
		} else {
			$resultado = WPC_Facturacion_Constructor::emitir( $user_id, $envios, $tipo, $doc_num, $nombre, $direccion, $forma_pago );
		}

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

		// Validar regla de los 7 días para Facturas (01)
		if ( $comprobante->tipo === '01' ) {
			$emitido_en = strtotime( $comprobante->emitido_en );
			$dias_pasados = floor( ( time() - $emitido_en ) / ( 60 * 60 * 24 ) );
			if ( $dias_pasados > 7 ) {
				wp_send_json_error( 'Han pasado más de 7 días. Debe generar una Nota de Crédito para esta factura.' );
			}
		}

		$api_response = WPC_Facturacion_APISunat::void_bill( $comprobante->document_id, $motivo );

		if ( is_wp_error( $api_response ) ) {
			wp_send_json_error( $api_response->get_error_message() );
		}

		WPC_Facturacion_Comprobante::actualizar( $comprobante_id, array( 'estado' => 'ANULADO' ) );

		wp_send_json_success( 'Comprobante anulado correctamente.' );
	}

	public function emitir_nota_credito() {
		check_ajax_referer( 'wpcfact_wizard_nonce', 'nonce' );

		$comprobante_id = intval( $_POST['comprobante_id'] ?? 0 );
		$motivo         = sanitize_text_field( $_POST['motivo'] ?? '' );
		$codigo_motivo  = sanitize_text_field( $_POST['codigo_motivo'] ?? '01' );

		if ( ! $comprobante_id || empty( $motivo ) ) {
			wp_send_json_error( 'ID de comprobante y motivo son requeridos.' );
		}

		$comprobante = WPC_Facturacion_Comprobante::obtener( $comprobante_id );
		if ( ! $comprobante ) {
			wp_send_json_error( 'Comprobante no encontrado.' );
		}

		if ( $comprobante->estado !== 'ACEPTADO' ) {
			wp_send_json_error( 'Solo se puede emitir Nota de Crédito a comprobantes ACEPTADOS.' );
		}

		// Requeriríamos cargar la clase WPC_Facturacion_Constructor_NotaCredito
		if ( ! class_exists( 'WPC_Facturacion_Constructor_NotaCredito' ) ) {
			require_once dirname( __FILE__ ) . '/../includes/class-constructor-notacredito.php';
		}

		$resultado = WPC_Facturacion_Constructor_NotaCredito::emitir( $comprobante, $motivo, $codigo_motivo );

		if ( is_wp_error( $resultado ) ) {
			wp_send_json_error( $resultado->get_error_message() );
		}

		wp_send_json_success( $resultado );
	}
}

new WPC_Facturacion_Ajax();
