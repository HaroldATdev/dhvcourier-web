<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPC_Facturacion_Ajax {

	public function __construct() {
		add_action( 'wp_ajax_wpcfact_buscar_cliente', array( $this, 'buscar_cliente' ) );
		add_action( 'wp_ajax_wpcfact_buscar_envios_ocasionales', array( $this, 'buscar_envios_ocasionales' ) );
		add_action( 'wp_ajax_wpcfact_consultar_doc', array( $this, 'consultar_doc' ) );
		add_action( 'wp_ajax_wpcfact_obtener_envios', array( $this, 'obtener_envios' ) );
		add_action( 'wp_ajax_wpcfact_emitir_comprobante', array( $this, 'emitir_comprobante' ) );
		add_action( 'wp_ajax_wpcfact_anular_comprobante', array( $this, 'anular_comprobante' ) );
		add_action( 'wp_ajax_wpcfact_emitir_nota_credito', array( $this, 'emitir_nota_credito' ) );
		add_action( 'wp_ajax_wpcfact_cargar_ubicaciones', array( $this, 'cargar_ubicaciones' ) );
	}

	private function get_envio_monto( $shipment_id ) {
		$freight_raw = get_post_meta( $shipment_id, 'costo_envio', true );
		if ( empty( $freight_raw ) ) {
			$freight_raw = get_post_meta( $shipment_id, 'monto', true );
		}
		if ( empty( $freight_raw ) ) {
			$freight_raw = get_post_meta( $shipment_id, 'wpcargo_total_freight', true );
		}

		$freight_raw = is_string( $freight_raw ) ? $freight_raw : (string) $freight_raw;
		return floatval( preg_replace( '/[^0-9.]/', '', $freight_raw ) );
	}

	private function get_envio_ruta( $shipment_id ) {
		$origen = get_post_meta( $shipment_id, 'lugar_origen', true );
		if ( empty( $origen ) ) {
			$origen = get_post_meta( $shipment_id, 'wpcargo_origin_field', true );
		}

		$destino = get_post_meta( $shipment_id, 'lugar_destino', true );
		if ( empty( $destino ) ) {
			$destino = get_post_meta( $shipment_id, 'wpcargo_destination', true );
		}

		return trim( (string) $origen ) . ' → ' . trim( (string) $destino );
	}

	private function get_envio_remitente_data( $shipment_id ) {
		$nombre = trim( (string) get_post_meta( $shipment_id, 'remitente', true ) );
		$doc = trim( (string) get_post_meta( $shipment_id, 'dni_remitente', true ) );
		$direccion = trim( (string) get_post_meta( $shipment_id, 'direccion_remitente', true ) );

		if ( empty( $direccion ) ) {
			$direccion = trim( (string) get_post_meta( $shipment_id, 'shipper_address', true ) );
		}

		return array(
			'shipper_name'    => $nombre,
			'shipper_doc'     => $doc,
			'shipper_address' => $direccion,
		);
	}

	public function buscar_envios_ocasionales() {
		check_ajax_referer( 'wpcfact_wizard_nonce', 'nonce' );

		$query = sanitize_text_field( wp_unslash( $_POST['q'] ?? '' ) );
		global $wpdb;

		$like = '%' . $wpdb->esc_like( $query ) . '%';
		$tabla_envios_comp = $wpdb->prefix . 'facturacion_comprobante_envios';
		$tabla_comprobantes = $wpdb->prefix . 'facturacion_comprobantes';

		$sql = $wpdb->prepare(
			"SELECT p.ID, p.post_title, p.post_date
			 FROM {$wpdb->posts} p
			 WHERE p.post_type = 'wpcargo_shipment'
			   AND p.post_status = 'publish'
			   AND NOT EXISTS (
				   SELECT 1
				   FROM {$tabla_envios_comp} fce
				   LEFT JOIN {$tabla_comprobantes} fc ON fc.id = fce.comprobante_id
				   WHERE fce.shipment_id = p.ID					 AND fc.tipo NOT IN ('09', '31')					 AND ( fc.id IS NULL OR UPPER(TRIM(COALESCE(fc.estado, ''))) <> 'ANULADO' )
			   )
			   AND NOT EXISTS (
				   SELECT 1 FROM {$wpdb->postmeta} pm1
				   WHERE pm1.post_id = p.ID
					 AND pm1.meta_key IN ('registered_shipper', 'wpcargo_cliente')
					 AND CAST(TRIM(COALESCE(pm1.meta_value, '0')) AS UNSIGNED) > 0
			   )
			   AND (
				   %s = ''
				   OR CAST(p.ID AS CHAR) LIKE %s
				   OR p.post_title LIKE %s
				   OR EXISTS (
					   SELECT 1 FROM {$wpdb->postmeta} pm2
					   WHERE pm2.post_id = p.ID
						 AND pm2.meta_key = 'remitente'
						 AND pm2.meta_value LIKE %s
				   )
			   )
			 ORDER BY p.post_date DESC
			 LIMIT 100",
			$query,
			$like,
			$like,
			$like
		);

		$envios = $wpdb->get_results( $sql );
		$resultados = array();

		foreach ( $envios as $envio ) {
			$monto = $this->get_envio_monto( $envio->ID );
			if ( $monto <= 0 ) {
				continue;
			}

			$remitente = $this->get_envio_remitente_data( $envio->ID );

			$resultados[] = array_merge(
				array(
					'id'    => (int) $envio->ID,
					'title' => (string) $envio->post_title,
					'date'  => gmdate( 'd/m/Y', strtotime( $envio->post_date ) ),
					'ruta'  => $this->get_envio_ruta( $envio->ID ),
					'monto' => $monto,
				),
				$remitente
			);
		}

		wp_send_json_success( $resultados );
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
			AND NOT EXISTS (
				SELECT 1
				FROM {$wpdb->prefix}facturacion_comprobante_envios fce
				LEFT JOIN {$wpdb->prefix}facturacion_comprobantes fc ON fc.id = fce.comprobante_id
				WHERE fce.shipment_id = p.ID
				  AND fc.tipo NOT IN ('09', '31')
				  AND (fc.id IS NULL OR UPPER(TRIM(COALESCE(fc.estado, ''))) <> 'ANULADO')
			)
			ORDER BY p.post_date DESC
			LIMIT 100
		", strval( $user_id ) );

		$envios = $wpdb->get_results( $sql );

		$resultados = array();

		foreach ( $envios as $envio ) {
			$freight = $this->get_envio_monto( $envio->ID );

			if ( $freight <= 0 ) {
				continue;
			}

			$remitente = $this->get_envio_remitente_data( $envio->ID );

			$resultados[] = array_merge(array(
				'id'      => $envio->ID,
				'title'   => $envio->post_title,  // Número de tracking (ej: DHV-0000051)
				'date'    => gmdate( 'd/m/Y', strtotime( $envio->post_date ) ),
				'ruta'    => $this->get_envio_ruta( $envio->ID ),
				'monto'   => $freight,
			), $remitente);
		}

		wp_send_json_success( $resultados );
	}

	public function emitir_comprobante() {
		check_ajax_referer( 'wpcfact_wizard_nonce', 'nonce' );

		$modo       = sanitize_key( $_POST['modo'] ?? 'registrado' );
		$user_id    = intval( $_POST['user_id'] ?? 0 );
		$envios     = array_map( 'intval', $_POST['envios'] ?? array() );
		$tipo       = sanitize_text_field( $_POST['tipo'] ?? '01' );
		$doc_num    = sanitize_text_field( $_POST['doc_num'] ?? '' );
		$nombre     = sanitize_text_field( $_POST['nombre'] ?? '' );
		$direccion  = sanitize_text_field( $_POST['direccion'] ?? '' );
		$forma_pago = sanitize_text_field( $_POST['forma_pago'] ?? 'Contado' );
		$guia_peso                    = sanitize_text_field( $_POST['guia_peso'] ?? '1.00' );
		$guia_motivo                  = sanitize_text_field( $_POST['guia_motivo'] ?? '01' );
		$guia_modalidad               = sanitize_text_field( $_POST['guia_modalidad'] ?? '01' );
		// Campos específicos tipo 09
		$guia_09_transportista_ruc    = sanitize_text_field( $_POST['guia_09_transportista_ruc'] ?? '' );
		$guia_09_transportista_nombre = sanitize_text_field( $_POST['guia_09_transportista_nombre'] ?? '' );
		$guia_09_conductor_dni        = sanitize_text_field( $_POST['guia_09_conductor_dni'] ?? '' );
		$guia_09_conductor_nombre     = sanitize_text_field( $_POST['guia_09_conductor_nombre'] ?? '' );
		$guia_09_conductor_licencia   = sanitize_text_field( $_POST['guia_09_conductor_licencia'] ?? '' );
		$guia_09_vehiculo_placa       = sanitize_text_field( $_POST['guia_09_vehiculo_placa'] ?? '' );
		$guia_09_partida_departamento = sanitize_text_field( $_POST['guia_09_partida_departamento'] ?? '' );
		$guia_09_partida_provincia    = sanitize_text_field( $_POST['guia_09_partida_provincia'] ?? '' );
		$guia_09_partida_distrito     = sanitize_text_field( $_POST['guia_09_partida_distrito'] ?? '' );
		$guia_09_llegada_departamento = sanitize_text_field( $_POST['guia_09_llegada_departamento'] ?? '' );
		$guia_09_llegada_provincia    = sanitize_text_field( $_POST['guia_09_llegada_provincia'] ?? '' );
		$guia_09_llegada_distrito     = sanitize_text_field( $_POST['guia_09_llegada_distrito'] ?? '' );
		// Campos para ambos tipos
		$guia_ubigeo_partida          = sanitize_text_field( $_POST['guia_ubigeo_partida'] ?? '150101' );
		$guia_ubigeo_llegada          = sanitize_text_field( $_POST['guia_ubigeo_llegada'] ?? '150131' );
		// Campos específicos tipo 31 (departamento/provincia/distrito)
		$guia_31_partida_departamento = sanitize_text_field( $_POST['guia_31_partida_departamento'] ?? '' );
		$guia_31_partida_provincia    = sanitize_text_field( $_POST['guia_31_partida_provincia'] ?? '' );
		$guia_31_partida_distrito     = sanitize_text_field( $_POST['guia_31_partida_distrito'] ?? '' );
		$guia_31_llegada_departamento = sanitize_text_field( $_POST['guia_31_llegada_departamento'] ?? '' );
		$guia_31_llegada_provincia    = sanitize_text_field( $_POST['guia_31_llegada_provincia'] ?? '' );
		$guia_31_llegada_distrito     = sanitize_text_field( $_POST['guia_31_llegada_distrito'] ?? '' );
		// Campos específicos tipo 31
		$guia_remitente_doc           = sanitize_text_field( $_POST['guia_remitente_doc'] ?? '' );
		$guia_remitente_nombre        = sanitize_text_field( $_POST['guia_remitente_nombre'] ?? '' );
		$guia_conductor_dni           = sanitize_text_field( $_POST['guia_conductor_dni'] ?? '' );
		$guia_conductor_nombre        = sanitize_text_field( $_POST['guia_conductor_nombre'] ?? '' );
		$guia_conductor_licencia      = sanitize_text_field( $_POST['guia_conductor_licencia'] ?? '' );
		$guia_vehiculo_placa          = sanitize_text_field( $_POST['guia_vehiculo_placa'] ?? '' );

		// Modo libre: líneas manuales
		$lineas_libres = array();
		if ( 'libre' === $modo ) {
			$lineas_raw = json_decode( wp_unslash( $_POST['lineas'] ?? '[]' ), true );
			if ( ! is_array( $lineas_raw ) || empty( $lineas_raw ) ) {
				wp_send_json_error( 'Debe agregar al menos una línea al comprobante.' );
			}
			foreach ( $lineas_raw as $linea ) {
				$desc  = sanitize_text_field( $linea['descripcion'] ?? '' );
				$cant  = floatval( $linea['cantidad'] ?? 1 );
				$precio = floatval( $linea['precio_unitario'] ?? 0 );
				if ( $precio <= 0 || $cant <= 0 ) continue;
				$lineas_libres[] = array(
					'descripcion'    => $desc ?: 'Servicio',
					'cantidad'       => $cant,
					'precio_unitario' => $precio,
				);
			}
			if ( empty( $lineas_libres ) ) {
				wp_send_json_error( 'Las líneas no tienen montos válidos.' );
			}
		}

		if ( 'libre' !== $modo && ( empty( $envios ) || empty( $doc_num ) || empty( $nombre ) ) ) {
			wp_send_json_error( 'Faltan datos requeridos.' );
		}

		if ( 'libre' === $modo && ( empty( $doc_num ) || empty( $nombre ) ) ) {
			wp_send_json_error( 'Faltan datos del receptor.' );
		}

		if ( 'registrado' === $modo && ! $user_id ) {
			wp_send_json_error( 'Debe seleccionar un cliente registrado.' );
		}

		// Validar ubicaciones para guías
		if ( '09' === $tipo ) {
			if ( empty( $guia_09_partida_departamento ) || empty( $guia_09_llegada_departamento ) ) {
				wp_send_json_error( 'Para guía tipo 09 debe seleccionar departamentos de partida y llegada.' );
			}
		} elseif ( '31' === $tipo ) {
			if ( empty( $guia_31_partida_departamento ) || empty( $guia_31_partida_provincia ) || empty( $guia_31_partida_distrito ) ) {
				wp_send_json_error( 'Para guía tipo 31 debe completar ubicación de partida (departamento, provincia, distrito).' );
			}
			if ( empty( $guia_31_llegada_departamento ) || empty( $guia_31_llegada_provincia ) || empty( $guia_31_llegada_distrito ) ) {
				wp_send_json_error( 'Para guía tipo 31 debe completar ubicación de llegada (departamento, provincia, distrito).' );
			}
		}

		// Guardar user_meta para reutilización
		if ( $user_id > 0 ) {
			update_user_meta( $user_id, 'wpcfact_doc_num', $doc_num );
			update_user_meta( $user_id, 'wpcfact_razon_social', $nombre );
			update_user_meta( $user_id, 'wpcfact_direccion', $direccion );
		}

		if ( $tipo === '00' ) {
			if ( ! class_exists( 'WPC_Facturacion_Constructor_NotaVenta' ) ) {
				require_once dirname( __FILE__ ) . '/../../includes/class-constructor-notaventa.php';
			}
			$resultado = WPC_Facturacion_Constructor_NotaVenta::emitir( $user_id, $envios, $doc_num, $nombre, $direccion, $forma_pago );
		} elseif ( $tipo === '09' || $tipo === '31' ) {
			if ( ! class_exists( 'WPC_Facturacion_Constructor_Guia' ) ) {
				require_once dirname( __FILE__ ) . '/../../includes/class-constructor-guia.php';
			}
			$resultado = WPC_Facturacion_Constructor_Guia::emitir( $user_id, $envios, $tipo, $doc_num, $nombre, $direccion, $guia_peso, $guia_motivo, $guia_modalidad, $guia_remitente_doc, $guia_remitente_nombre, $guia_conductor_dni, $guia_conductor_nombre, $guia_conductor_licencia, $guia_vehiculo_placa, $guia_ubigeo_partida, $guia_ubigeo_llegada, $guia_09_transportista_ruc, $guia_09_transportista_nombre, $guia_09_conductor_dni, $guia_09_conductor_nombre, $guia_09_conductor_licencia, $guia_09_vehiculo_placa, $guia_09_partida_departamento, $guia_09_partida_provincia, $guia_09_partida_distrito, $guia_09_llegada_departamento, $guia_09_llegada_provincia, $guia_09_llegada_distrito, $guia_31_partida_departamento, $guia_31_partida_provincia, $guia_31_partida_distrito, $guia_31_llegada_departamento, $guia_31_llegada_provincia, $guia_31_llegada_distrito );
		} elseif ( 'libre' === $modo ) {
			$resultado = WPC_Facturacion_Constructor::emitir_libre( $user_id, $lineas_libres, $tipo, $doc_num, $nombre, $direccion, $forma_pago );
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
			require_once dirname( __FILE__ ) . '/../../includes/class-constructor-notacredito.php';
		}

		$resultado = WPC_Facturacion_Constructor_NotaCredito::emitir( $comprobante, $motivo, $codigo_motivo );

		if ( is_wp_error( $resultado ) ) {
			wp_send_json_error( $resultado->get_error_message() );
		}

		wp_send_json_success( $resultado );
	}

	public function consultar_doc() {
		check_ajax_referer( 'wpcfact_wizard_nonce', 'nonce' ); // dentro de la clase
		$numero = sanitize_text_field( $_POST['numero'] ?? '' );
		$numero = preg_replace( '/\D/', '', $numero );

		if ( strlen( $numero ) === 8 ) {
			wp_send_json_error( 'Consulta de DNI deshabilitada por normativa. Complete nombre y direccion manualmente.' );
			return;
		} elseif ( strlen( $numero ) === 11 ) {
			$tipo = 'ruc';
		} else {
			wp_send_json_error( 'Número de documento inválido.' );
			return;
		}

		if ( ! class_exists( 'WPC_Facturacion_APISunat' ) ) {
			require_once dirname( __FILE__ ) . '/../../includes/class-apisunat.php';
		}

		$resultado = WPC_Facturacion_APISunat::consultar_doc( $tipo, $numero );

		if ( is_wp_error( $resultado ) ) {
			wp_send_json_error( $resultado->get_error_message() );
			return;
		}

		if ( empty( $resultado['nombre'] ) ) {
			wp_send_json_error( 'No se pudo obtener el nombre desde APIsPeru.' );
			return;
		}

		wp_send_json_success( $resultado );
	}

	public function cargar_ubicaciones() {
		check_ajax_referer( 'wpcfact_wizard_nonce', 'nonce' );

		$tipo = sanitize_text_field( $_POST['tipo'] ?? '' );
		$departamento = sanitize_text_field( $_POST['departamento'] ?? '' );

		// Asegúrate de que ubigeos.php esté cargado
		if ( ! function_exists( 'wpcfact_get_departamentos' ) ) {
			require_once( WPC_FACTURACION_PATH . 'includes/ubigeos.php' );
		}

		if ( $tipo === 'departamentos' ) {
			$departamentos = wpcfact_get_departamentos();
			if ( empty( $departamentos ) ) {
				wp_send_json_error( 'No se encontraron departamentos.' );
			}
			$response = array();
			foreach ( $departamentos as $codigo => $nombre ) {
				$response[] = array(
					'codigo' => $codigo,
					'nombre' => $nombre
				);
			}
			wp_send_json_success( $response );
		} elseif ( $tipo === 'provincias' && ! empty( $departamento ) ) {
			$provincias = wpcfact_get_provincias( $departamento );
			if ( empty( $provincias ) ) {
				wp_send_json_error( 'No se encontraron provincias para este departamento.' );
			}
			$response = array();
			foreach ( $provincias as $codigo => $nombre ) {
				$response[] = array(
					'codigo' => $codigo,
					'nombre' => $nombre
				);
			}
			wp_send_json_success( $response );
		} elseif ( $tipo === 'distritos' && ! empty( $departamento ) ) {
			$provincia = sanitize_text_field( $_POST['provincia'] ?? '' );
			if ( empty( $provincia ) ) {
				wp_send_json_error( 'Provincia no especificada.' );
			}
			$distritos = wpcfact_get_distritos( $departamento, $provincia );
			if ( empty( $distritos ) ) {
				wp_send_json_error( 'No se encontraron distritos para esta provincia.' );
			}
			$response = array();
			foreach ( $distritos as $codigo => $nombre ) {
				$response[] = array(
					'codigo' => $codigo,
					'nombre' => $nombre
				);
			}
			wp_send_json_success( $response );
		}

		wp_send_json_error( 'Parámetros inválidos.' );
	}
}

new WPC_Facturacion_Ajax();
