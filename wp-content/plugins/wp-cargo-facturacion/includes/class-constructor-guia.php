<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPC_Facturacion_Constructor_Guia {

	public static function emitir( $user_id, $envios_ids, $tipo, $doc_num, $nombre, $direccion, $peso, $motivo, $modalidad ) {
		global $wpdb;

		// 1. Obtener RUC emisor
		$ruc_emisor          = get_option( 'wpcfact_ruc_emisor' );
		$razon_social_emisor = get_option( 'wpcfact_razon_social_emisor' );
		$direccion_emisor    = get_option( 'wpcfact_direccion_emisor', '' );
		$codigo_local        = get_option( 'wpcfact_codigo_local', '0000' ); 

		if ( empty( $ruc_emisor ) ) {
			return new WP_Error( 'config_error', 'Falta configurar el RUC emisor.' );
		}

		if ( empty( $razon_social_emisor ) ) {
			return new WP_Error( 'config_error', 'Falta configurar la razón social del emisor.' );
		}

		if ( empty( $direccion_emisor ) ) {
			return new WP_Error( 'config_error', 'Falta configurar la dirección del emisor para la guía.' );
		}

		// 2. Definir Serie
		$serie = ( $tipo === '09' ) ? get_option( 'wpcfact_serie_guia_remision', 'T001' ) : get_option( 'wpcfact_serie_guia_transp', 'V001' );

		// 3. Consultar APISUNAT para el último correlativo
		$last_doc = WPC_Facturacion_APISunat::last_document( $tipo, $serie );
		if ( is_wp_error( $last_doc ) ) {
			return $last_doc;
		}

		$correlativo = sprintf( '%08d', intval( $last_doc['suggestedNumber'] ) );
		$numero_documento = $serie . '-' . $correlativo;
		$file_name = $ruc_emisor . '-' . $tipo . '-' . $serie . '-' . $correlativo;

		// 4. Recopilar datos de envíos
		$envios_datos = array();
		$total_general = 0;

		foreach ( $envios_ids as $envio_id ) {
			$post = get_post( $envio_id );
			if ( ! $post ) continue;

			$freight_raw = get_post_meta( $envio_id, 'costo_envio', true );
			if ( empty( $freight_raw ) ) $freight_raw = get_post_meta( $envio_id, 'monto', true );
			if ( empty( $freight_raw ) ) $freight_raw = get_post_meta( $envio_id, 'wpcargo_total_freight', true );
			$monto = floatval( preg_replace( '/[^0-9.]/', '', $freight_raw ) );
			
			if ( $monto <= 0 ) continue;

			$total_general += $monto;

			$envios_datos[] = array(
				'id'          => $envio_id,
				'tracking'    => $post->post_title,
				'monto'       => $monto,
			);
		}

		if ( empty( $envios_datos ) ) {
			return new WP_Error( 'data_error', 'No hay envíos válidos para generar la guía.' );
		}

		$fecha_emision = current_time( 'Y-m-d' );
		$hora_emision = current_time( 'H:i:s' );
		$peso = floatval( $peso );
		if ( $peso <= 0 ) {
			return new WP_Error( 'data_error', 'El peso total debe ser mayor a 0 para emitir la guía.' );
		}

		if ( empty( $direccion ) ) {
			return new WP_Error( 'data_error', 'La dirección de llegada es obligatoria para la guía.' );
		}

		$scheme_id = ( strlen( $doc_num ) === 11 ) ? '6' : ( strlen( $doc_num ) === 8 ? '1' : '4' );

		// 5. Construir JSON Básico (DespatchAdvice)
		$document_body = array(
			'cbc:UBLVersionID' => array( '_text' => '2.1' ),
			'cbc:CustomizationID' => array( '_text' => '2.0' ),
			'cbc:ID' => array( '_text' => $numero_documento ),
			'cbc:IssueDate' => array( '_text' => $fecha_emision ),
			'cbc:IssueTime' => array( '_text' => $hora_emision ),
			'cbc:DespatchAdviceTypeCode' => array( '_text' => $tipo ),
			'cac:DespatchSupplierParty' => array(
				'cac:Party' => array(
					'cac:PartyIdentification' => array( 'cbc:ID' => array( '_attributes' => array( 'schemeID' => '6' ), '_text' => $ruc_emisor ) ),
					'cac:PartyLegalEntity' => array(
						'cbc:RegistrationName' => array( '_text' => $razon_social_emisor ),
						'cac:RegistrationAddress' => array(
							'cbc:AddressTypeCode' => array( '_text' => $codigo_local ),
							'cac:AddressLine' => array(
								'cbc:Line' => array( '_text' => $direccion_emisor )
							)
						)
					)
				)
			),
			'cac:DeliveryCustomerParty' => array(
				'cac:Party' => array(
					'cac:PartyIdentification' => array( 'cbc:ID' => array( '_attributes' => array( 'schemeID' => $scheme_id ), '_text' => $doc_num ) ),
					'cac:PartyLegalEntity' => array(
						'cbc:RegistrationName' => array( '_text' => $nombre ),
					)
				)
			),
			'cac:Shipment' => ( $tipo === '31' )
			? array(
				// Guía de Transportista (tipo 31)
				'cbc:ID'                 => array( '_text' => 'SUNAT_Envio' ),
				'cbc:GrossWeightMeasure' => array( '_attributes' => array( 'unitCode' => 'KGM' ), '_text' => number_format( $peso, 3, '.', '' ) ),
				'cbc:SpecialInstructions' => array(
					array( '_text' => 'SUNAT_Envio_IndicadorPagadorFlete_Remitente' )
				),
				'cac:ShipmentStage' => array(
					'cac:TransitPeriod' => array( 'cbc:StartDate' => array( '_text' => $fecha_emision ) ),
					'cac:CarrierParty'  => array(
						'cac:PartyIdentification' => array(
							'cbc:ID' => array( '_attributes' => array( 'schemeID' => '6' ), '_text' => $ruc_emisor )
						),
						'cac:PartyLegalEntity' => array(
							'cbc:CompanyID' => array( '_text' => get_option( 'wpcfact_mtc_autorizacion', $ruc_emisor ) )
						)
					),
				),
				'cac:Delivery' => array(
					'cac:DeliveryAddress' => array(
						'cbc:ID'          => array( '_text' => '150101' ),
						'cac:AddressLine' => array( 'cbc:Line' => array( '_text' => $direccion ) )
					),
					'cac:Despatch' => array(
						'cac:DespatchAddress' => array(
							'cbc:ID'          => array( '_text' => '150101' ),
							'cac:AddressLine' => array( 'cbc:Line' => array( '_text' => $direccion_emisor ) )
						),
						'cac:DespatchParty' => array(
							'cac:PartyIdentification' => array(
								'cbc:ID' => array( '_attributes' => array( 'schemeID' => '6' ), '_text' => $ruc_emisor )
							),
							'cac:PartyLegalEntity' => array(
								'cbc:RegistrationName' => array( '_text' => $razon_social_emisor )
							)
						)
					)
				)
			)
			: array(
				// Guía de Remisión Remitente (tipo 09)
				'cbc:ID'                 => array( '_text' => '1' ),
				'cbc:HandlingCode'       => array( '_text' => $motivo ),
				'cbc:Information'        => array( '_text' => 'Traslado de encomiendas' ),
				'cbc:GrossWeightMeasure' => array( '_attributes' => array( 'unitCode' => 'KGM' ), '_text' => number_format( $peso, 3, '.', '' ) ),
				'cac:ShipmentStage' => array(
					'cbc:TransportModeCode' => array( '_text' => $modalidad ),
					'cac:TransitPeriod'     => array( 'cbc:StartDate' => array( '_text' => $fecha_emision ) ),
				),
				'cac:Delivery' => array(
					'cac:DeliveryAddress' => array(
						'cbc:ID'          => array( '_text' => '150101' ),
						'cac:AddressLine' => array( 'cbc:Line' => array( '_text' => $direccion ) )
					),
					'cac:Despatch' => array(
						'cac:DespatchAddress' => array(
							'cbc:ID'          => array( '_text' => '150101' ),
							'cac:AddressLine' => array( 'cbc:Line' => array( '_text' => $direccion_emisor ) )
						)
					)
				)
			),
			'cac:DespatchLine' => array()
		);

		// Líneas de la guía
		$line_id = 1;
		foreach ( $envios_datos as $envio ) {
			$document_body['cac:DespatchLine'][] = array(
				'cbc:ID' => array( '_text' => $line_id ),
				'cbc:DeliveredQuantity' => array( '_attributes' => array( 'unitCode' => 'NIU' ), '_text' => 1 ),
				'cac:OrderLineReference' => array(
					'cbc:LineID' => array( '_text' => $line_id )
				),
				'cac:Item' => array(
					'cbc:Name' => array( '_text' => 'Servicio de courier - Tracking: ' . $envio['tracking'] )
				)
			);
			$line_id++;
		}

		// 6. Enviar a SUNAT
		$user_data = get_userdata( $user_id );
		$customer_email = $user_data ? $user_data->user_email : '';

		$api_response = WPC_Facturacion_APISunat::send_bill( $file_name, $document_body, $customer_email );
		
		if ( is_wp_error( $api_response ) ) {
			return $api_response;
		}

		if ( empty( $api_response['documentId'] ) || empty( $api_response['status'] ) ) {
			return new WP_Error(
				'api_response_invalid',
				'Respuesta incompleta al emitir guía en APISUNAT.',
				$api_response
			);
		}

		$status_api = strtoupper( trim( (string) $api_response['status'] ) );
		$has_exception = false;
		foreach ( array( 'exceptions', 'exception', 'faults', 'errors', 'error' ) as $k ) {
			if ( isset( $api_response[ $k ] ) && ! empty( $api_response[ $k ] ) ) {
				$has_exception = true;
				break;
			}
		}
		if ( ! $has_exception ) {
			$api_texto = wp_json_encode( $api_response );
			if ( is_string( $api_texto ) && ( stripos( $api_texto, 'ExceptionXsd' ) !== false || stripos( $api_texto, 'exception' ) !== false ) ) {
				$has_exception = true;
			}
		}

		$status_guardar = $has_exception ? 'ERROR' : $status_api;

		// 7. Guardar en Base de Datos Local
		$datos_guardar = array(
			'tipo'             => $tipo,
			'serie'            => $serie,
			'correlativo'      => $correlativo,
			'file_name'        => $file_name,
			'document_id'      => $api_response['documentId'],
			'estado'           => $status_guardar,
			'cliente_doc_tipo' => $scheme_id,
			'cliente_doc_num'  => $doc_num,
			'cliente_nombre'   => $nombre,
			'monto_base'       => 0, // Guía no tiene montos fiscales
			'igv'              => 0,
			'total'            => 0,
			'emitido_en'       => $fecha_emision . ' ' . $hora_emision,
		);

		$comprobante_id = WPC_Facturacion_Comprobante::crear( $datos_guardar, $envios_datos );

		if ( is_wp_error( $comprobante_id ) ) {
			return $comprobante_id;
		}

		return array(
			'id'          => $comprobante_id,
			'serie'       => $serie,
			'correlativo' => $correlativo,
			'estado'      => $status_guardar,
		);
	}
}
