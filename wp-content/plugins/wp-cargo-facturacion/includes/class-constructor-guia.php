<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'ubigeos.php';

class WPC_Facturacion_Constructor_Guia {

	public static function emitir( $user_id, $envios_ids, $tipo, $doc_num, $nombre, $direccion, $peso, $motivo, $modalidad, $remitente_doc = '', $remitente_nombre = '', $conductor_dni = '', $conductor_nombre = '', $conductor_licencia = '', $vehiculo_placa = '', $ubigeo_partida = '150101', $ubigeo_llegada = '150131', $transportista_09_ruc = '', $transportista_09_nombre = '', $conductor_09_dni = '', $conductor_09_nombre = '', $conductor_09_licencia = '', $vehiculo_09_placa = '', $partida_09_departamento = '', $partida_09_provincia = '', $partida_09_distrito = '', $llegada_09_departamento = '', $llegada_09_provincia = '', $llegada_09_distrito = '', $partida_departamento = '', $partida_provincia = '', $partida_distrito = '', $llegada_departamento = '', $llegada_provincia = '', $llegada_distrito = '', $mtc_autorizacion = '', $mtc_09_autorizacion = '', $ind_retorno_vacio = false, $ind_retorno_envases = false, $ind_transbordo = false, $ind_m1l = false, $ind_datos_transportista = false, $ind_traslado_total_31 = false, $ind_subcontratado = false, $ind_subcontratado_empresa_nombre = '', $ind_subcontratado_empresa_ruc = '', $ind_flete_pagador = 'Remitente', $ind_flete_tercero_nombre = '', $ind_flete_tercero_doc_tipo = '6', $ind_flete_tercero_doc_num = '' ) {
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

			$envios_datos[] = array(
				'id'       => $envio_id,
				'tracking' => $post->post_title,
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

		// 5a. Construir Shipment según el tipo de guía
		// Construir SpecialInstructions dinámicamente
		$special_instructions = array();
		if ( $ind_retorno_vacio )   $special_instructions[] = array( '_text' => 'SUNAT_Envio_IndicadorRetornoVehiculoVacio' );
		if ( $ind_retorno_envases ) $special_instructions[] = array( '_text' => 'SUNAT_Envio_IndicadorRetornoVehiculoConEnvaseVacio' );
		if ( $ind_transbordo )      $special_instructions[] = array( '_text' => 'SUNAT_Envio_IndicadorTransbordoProgramado' );
		// Indicadores exclusivos tipo 09
		if ( $tipo === '09' && $ind_m1l )                 $special_instructions[] = array( '_text' => 'SUNAT_Envio_IndicadorTrasladoVehiculoM1L' );
		if ( $tipo === '09' && $ind_datos_transportista ) $special_instructions[] = array( '_text' => 'SUNAT_Envio_IndicadorVehiculoConductoresTransp' );
		// Indicadores exclusivos tipo 31
		if ( $tipo === '31' && $ind_traslado_total_31 ) $special_instructions[] = array( '_text' => 'SUNAT_Envio_IndicadorTrasladoTotal' );
		if ( $tipo === '31' && $ind_subcontratado )    $special_instructions[] = array( '_text' => 'SUNAT_Envio_IndicadorTrasporteSubcontratado' );
		// Pagador de flete (tipo 31 siempre lo incluye)
		if ( $tipo === '31' ) {
			if ( $ind_flete_pagador === 'Tercero' )           $special_instructions[] = array( '_text' => 'SUNAT_Envio_IndicadorPagadorFlete_Tercero' );
			elseif ( $ind_flete_pagador === 'Subcontratador' ) $special_instructions[] = array( '_text' => 'SUNAT_Envio_IndicadorPagadorFlete_Subcontratador' );
			else                                               $special_instructions[] = array( '_text' => 'SUNAT_Envio_IndicadorPagadorFlete_Remitente' );
		}

		if ( $tipo === '31' ) {
			// Construir cac:OriginatorCustomerParty y cac:Consignment si hay subcontratado
			$originator_party = null;
			$consignment      = null;
			if ( $ind_subcontratado && $ind_subcontratado_empresa_ruc ) {
				$originator_party = array(
					'cac:Party' => array(
						'cac:PartyIdentification' => array(
							'cbc:ID' => array( '_attributes' => array( 'schemeID' => '6' ), '_text' => $ind_subcontratado_empresa_ruc )
						),
						'cac:PartyLegalEntity' => array(
							'cbc:RegistrationName' => array( '_text' => $ind_subcontratado_empresa_nombre )
						)
					)
				);
				$consignment = array(
					'cbc:ID' => array( '_text' => 'SUNAT_Envio' ),
					'cac:LogisticsOperatorParty' => array(
						'cac:PartyIdentification' => array(
							'cbc:ID' => array( '_attributes' => array( 'schemeID' => '6' ), '_text' => $ind_subcontratado_empresa_ruc )
						),
						'cac:PartyLegalEntity' => array(
							'cbc:RegistrationName' => array( '_text' => $ind_subcontratado_empresa_nombre )
						)
					)
				);
			}
			// Guía de Transportista (tipo 31)
			// Siempre tiene CarrierParty + DriverPerson (como array)
			$shipment = array(
				'cbc:ID'                  => array( '_text' => 'SUNAT_Envio' ),
				'cbc:GrossWeightMeasure'  => array( '_attributes' => array( 'unitCode' => 'KGM' ), '_text' => number_format( $peso, 3, '.', '' ) ),
				'cbc:SpecialInstructions' => $special_instructions,
			);
			// Insertar cac:Consignment si hay subcontratado
			if ( isset( $consignment ) && $consignment ) {
				$shipment['cac:Consignment'] = $consignment;
			}
			$shipment += array(
				'cac:ShipmentStage' => array(
					'cac:TransitPeriod' => array( 'cbc:StartDate' => array( '_text' => $fecha_emision ) ),
					'cac:CarrierParty'  => array(
						'cac:PartyIdentification' => array(
							'cbc:ID' => array( '_attributes' => array( 'schemeID' => '6' ), '_text' => $ruc_emisor )
						),
						'cac:PartyLegalEntity' => array(
							'cbc:CompanyID' => array( '_text' => $mtc_autorizacion ?: get_option( 'wpcfact_mtc_autorizacion', '0' ) )
						)
					),
					'cac:DriverPerson' => array(
						array(
							'cbc:ID'        => array( '_attributes' => array( 'schemeID' => '1' ), '_text' => $conductor_dni ),
							'cbc:FirstName' => array( '_text' => $conductor_nombre ?: ' ' ),
							'cbc:FamilyName' => array( '_text' => '-' ),
							'cbc:JobTitle'  => array( '_text' => 'Principal' ),
							'cac:IdentityDocumentReference' => array(
								'cbc:ID' => array( '_text' => $conductor_licencia ?: '00000' )
							),
						)
					),
				),
				'cac:Delivery' => array(
					'cac:DeliveryAddress' => array(
						'cbc:ID'          => array( '_text' => wpcfact_build_ubigeo_code( $llegada_departamento, $llegada_provincia, $llegada_distrito ) ),
						'cac:AddressLine' => array( 'cbc:Line' => array( '_text' => $direccion ) )
					),
					'cac:Despatch' => array(
						'cac:DespatchAddress' => array(
							'cbc:ID'          => array( '_text' => wpcfact_build_ubigeo_code( $partida_departamento, $partida_provincia, $partida_distrito ) ),
							'cac:AddressLine' => array( 'cbc:Line' => array( '_text' => $direccion_emisor ) )
						),
						'cac:DespatchParty' => array(
							'cac:PartyIdentification' => array(
								'cbc:ID' => array(
									'_attributes' => array( 'schemeID' => ( strlen( $remitente_doc ) === 11 ? '6' : '1' ) ),
									'_text'       => $remitente_doc ?: $ruc_emisor
								)
							),
							'cac:PartyLegalEntity' => array(
								'cbc:RegistrationName' => array( '_text' => $remitente_nombre ?: $razon_social_emisor )
							)
						)
					)
				),
				'cac:TransportHandlingUnit' => array(
					'cac:TransportEquipment' => array(
						'cbc:ID' => array( '_text' => $vehiculo_placa ),
						'cac:ApplicableTransportMeans' => array(
							'cbc:RegistrationNationalityID' => array( '_text' => $vehiculo_placa )
						),
						'cac:ShipmentDocumentReference' => array(
							'cbc:ID' => array( '_attributes' => array( 'schemeID' => '06' ), '_text' => ( $mtc_autorizacion ?: get_option( 'wpcfact_mtc_autorizacion', '0' ) ) )
						)
					)
				)
			);
			// Insertar OriginatorCustomerParty y Consignment en el documento si aplica
			// (se agregan al document_body más adelante)
		} else {
			// Guía de Remisión Remitente (tipo 09)
			// Modalidad 01 (Transporte Público):  CarrierParty solamente
			// Modalidad 02 (Transporte Privado):  DriverPerson (array) + TransportHandlingUnit, sin CarrierParty
			if ( $modalidad === '02' ) {
				// Modalidad 02 (Privado): DriverPerson + TransportHandlingUnit, sin CarrierParty
				$shipment_stage_09 = array(
					'cbc:TransportModeCode' => array( '_text' => $modalidad ),
					'cac:TransitPeriod'     => array( 'cbc:StartDate' => array( '_text' => $fecha_emision ) ),
					'cac:DriverPerson' => array(
						array(
							'cbc:ID'         => array( '_attributes' => array( 'schemeID' => '1' ), '_text' => $conductor_09_dni ),
							'cbc:FirstName'  => array( '_text' => $conductor_09_nombre ?: ' ' ),
							'cbc:FamilyName' => array( '_text' => '-' ),
							'cbc:JobTitle'   => array( '_text' => 'Principal' ),
							'cac:IdentityDocumentReference' => array(
								'cbc:ID' => array( '_text' => $conductor_09_licencia ?: '00000' )
							),
						)
					),
				);
				$shipment_transporte = array(
					'cac:TransportHandlingUnit' => array(
						'cac:TransportEquipment' => array(
							'cbc:ID' => array( '_text' => $vehiculo_09_placa ),
							'cac:ApplicableTransportMeans' => array(
								'cbc:RegistrationNationalityID' => array( '_text' => $vehiculo_09_placa )
							),
							'cac:ShipmentDocumentReference' => array(
								'cbc:ID' => array( '_attributes' => array( 'schemeID' => '06' ), '_text' => $mtc_09_autorizacion ?: '0' )
							)
						)
					)
				);
			} elseif ( $ind_datos_transportista ) {
				// Modalidad 01 + Datos del Transportista: CarrierParty + DriverPerson + TransportHandlingUnit
				$shipment_stage_09 = array(
					'cbc:TransportModeCode' => array( '_text' => $modalidad ),
					'cac:TransitPeriod'     => array( 'cbc:StartDate' => array( '_text' => $fecha_emision ) ),
					'cac:CarrierParty' => array(
						'cac:PartyIdentification' => array(
							'cbc:ID' => array( '_attributes' => array( 'schemeID' => '6' ), '_text' => $transportista_09_ruc ?: $ruc_emisor )
						),
						'cac:PartyLegalEntity' => array(
							'cbc:RegistrationName' => array( '_text' => $transportista_09_nombre ?: $razon_social_emisor ),
							'cbc:CompanyID'        => array( '_text' => $mtc_09_autorizacion ?: get_option( 'wpcfact_mtc_autorizacion', '0' ) )
						)
					),
					'cac:DriverPerson' => array(
						array(
							'cbc:ID'         => array( '_attributes' => array( 'schemeID' => '1' ), '_text' => $conductor_09_dni ),
							'cbc:FirstName'  => array( '_text' => $conductor_09_nombre ?: ' ' ),
							'cbc:FamilyName' => array( '_text' => '-' ),
							'cbc:JobTitle'   => array( '_text' => 'Principal' ),
							'cac:IdentityDocumentReference' => array(
								'cbc:ID' => array( '_text' => $conductor_09_licencia ?: '00000' )
							),
						)
					),
				);
				$shipment_transporte = array(
					'cac:TransportHandlingUnit' => array(
						'cac:TransportEquipment' => array(
							'cbc:ID' => array( '_text' => $vehiculo_09_placa ),
							'cac:ApplicableTransportMeans' => array(
								'cbc:RegistrationNationalityID' => array( '_text' => $vehiculo_09_placa )
							),
							'cac:ShipmentDocumentReference' => array(
								'cbc:ID' => array( '_attributes' => array( 'schemeID' => '06' ), '_text' => $mtc_09_autorizacion ?: '0' )
							)
						)
					)
				);
			} else {
				// Modalidad 01 (Público) sin datos de transportista: solo CarrierParty
				$shipment_stage_09 = array(
					'cbc:TransportModeCode' => array( '_text' => $modalidad ),
					'cac:TransitPeriod'     => array( 'cbc:StartDate' => array( '_text' => $fecha_emision ) ),
					'cac:CarrierParty' => array(
						'cac:PartyIdentification' => array(
							'cbc:ID' => array( '_attributes' => array( 'schemeID' => '6' ), '_text' => $transportista_09_ruc ?: $ruc_emisor )
						),
						'cac:PartyLegalEntity' => array(
							'cbc:RegistrationName' => array( '_text' => $transportista_09_nombre ?: $razon_social_emisor )
						)
					),
				);
				$shipment_transporte = array();
			}

			$shipment_09_base = array(
					'cbc:ID'                 => array( '_text' => 'SUNAT_Envio' ),
					'cbc:HandlingCode'       => array( '_text' => $motivo ),
					'cbc:GrossWeightMeasure' => array( '_attributes' => array( 'unitCode' => 'KGM' ), '_text' => number_format( $peso, 3, '.', '' ) ),
				);
				if ( ! empty( $special_instructions ) ) {
					$shipment_09_base['cbc:SpecialInstructions'] = $special_instructions;
				}
			$shipment = array_merge(
				$shipment_09_base,
				array(
					'cac:ShipmentStage' => $shipment_stage_09,
					'cac:Delivery' => array(
						'cac:DeliveryAddress' => array(
							'cbc:ID'          => array( '_text' => wpcfact_build_ubigeo_code( $llegada_09_departamento, $llegada_09_provincia, $llegada_09_distrito ) ),
							'cac:AddressLine' => array( 'cbc:Line' => array( '_text' => $direccion ) )
						),
						'cac:Despatch' => array(
							'cac:DespatchAddress' => array(
								'cbc:ID'          => array( '_text' => wpcfact_build_ubigeo_code( $partida_09_departamento, $partida_09_provincia, $partida_09_distrito ) ),
								'cac:AddressLine' => array( 'cbc:Line' => array( '_text' => $direccion_emisor ) )
							)
						)
					),
				),
				$shipment_transporte
			);
		}

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
			'cac:Shipment' => $shipment,
		'cac:DespatchLine' => array()
	);
		// Insertar OriginatorCustomerParty (tipo 31 con subcontratado)
		if ( isset( $originator_party ) && $originator_party ) {
			$document_body['cac:OriginatorCustomerParty'] = $originator_party;
		}
		// Líneas de la guía
		$line_id = 1;
		foreach ( $envios_datos as $envio ) {
			$document_body['cac:DespatchLine'][] = array(
				'cbc:ID'                => array( '_text' => $line_id ),
				'cbc:DeliveredQuantity' => array( '_attributes' => array( 'unitCode' => 'NIU' ), '_text' => 1 ),
				'cac:OrderLineReference' => array(
					'cbc:LineID' => array( '_text' => $line_id )
				),
				'cac:Item' => array(
					'cbc:Description' => array( '_text' => 'Encomienda - Tracking: ' . $envio['tracking'] )
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
