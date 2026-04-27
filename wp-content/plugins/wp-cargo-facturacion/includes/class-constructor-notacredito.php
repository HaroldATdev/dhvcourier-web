<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPC_Facturacion_Constructor_NotaCredito {

	public static function emitir( $comprobante_original, $motivo, $codigo_motivo ) {
		global $wpdb;

		// 1. Obtener RUC emisor de settings
		$ruc_emisor          = get_option( 'wpcfact_ruc_emisor' );
		$razon_social_emisor = get_option( 'wpcfact_razon_social_emisor' );
		$direccion_emisor    = get_option( 'wpcfact_direccion_emisor', '' );
		$codigo_local        = get_option( 'wpcfact_codigo_local', '0000' ); 
		if ( empty( $codigo_local ) ) $codigo_local = '0000';
		if ( empty( $direccion_emisor ) ) $direccion_emisor = '-';

		if ( empty( $ruc_emisor ) ) {
			return new WP_Error( 'config_error', 'Falta configurar el RUC emisor.' );
		}

		// 2. Definir Serie (Nota de Crédito)
		$es_factura = ( $comprobante_original->tipo === '01' );
		$serie = $es_factura ? get_option( 'wpcfact_serie_nc_factura', 'FC01' ) : get_option( 'wpcfact_serie_nc_boleta', 'BC01' );
		$tipo = '07';

		// 3. Consultar APISUNAT para el último correlativo
		$last_doc = WPC_Facturacion_APISunat::last_document( $tipo, $serie );
		if ( is_wp_error( $last_doc ) ) {
			return $last_doc;
		}

		$correlativo = sprintf( '%08d', intval( $last_doc['suggestedNumber'] ) );
		$numero_documento = $serie . '-' . $correlativo;
		$file_name = $ruc_emisor . '-' . $tipo . '-' . $serie . '-' . $correlativo;

		// 4. Recopilar datos de envíos originales
		$envios_datos = array();
		$total_general = 0;
		$monto_base_total = 0;
		$igv_total = 0;

		$envios_originales = WPC_Facturacion_Comprobante::obtener_envios_de_comprobante( $comprobante_original->id );
		
		if ( empty( $envios_originales ) ) {
			return new WP_Error( 'data_error', 'El comprobante original no tiene envíos asociados.' );
		}

		foreach ( $envios_originales as $envio_db ) {
			$monto = floatval( $envio_db->monto );
			if ( $monto <= 0 ) continue;

			$total_general += $monto;

			$precio_unitario_con_igv = $monto;
			$precio_unitario_sin_igv = round( $precio_unitario_con_igv / 1.18, 5 );
			$valor_venta_linea = round( $precio_unitario_sin_igv * 1, 2 );
			$igv_linea = round( $precio_unitario_con_igv - $valor_venta_linea, 2 );

			$monto_base_total += $valor_venta_linea;
			$igv_total += $igv_linea;

			$envios_datos[] = array(
				'id'                      => $envio_db->shipment_id,
				'tracking'                => $envio_db->tracking,
				'monto'                   => $monto,
				'precio_unitario_sin_igv' => $precio_unitario_sin_igv,
				'valor_venta_linea'       => $valor_venta_linea,
				'igv_linea'               => $igv_linea,
			);
		}

		$monto_letras = wpcfact_numero_a_letras( $total_general );
		$fecha_emision = current_time( 'Y-m-d\TH:i:sP' );

		// 5. Construir JSON (documentBody)
		$document_body = array(
			'cbc:UBLVersionID' => array( '_text' => '2.1' ),
			'cbc:CustomizationID' => array( '_text' => '2.0' ),
			'cbc:ID' => array( '_text' => $numero_documento ),
			'cbc:IssueDate' => array( '_text' => current_time( 'Y-m-d' ) ),
			'cbc:IssueTime' => array( '_text' => current_time( 'H:i:s' ) ),
			'cbc:Note' => array(
				array(
					'_attributes' => array( 'languageLocaleID' => '1000' ),
					'_text' => $monto_letras
				)
			),
			'cbc:DocumentCurrencyCode' => array( '_text' => 'PEN' ),
			
			'cac:DiscrepancyResponse' => array(
				'cbc:ResponseCode' => array( '_text' => $codigo_motivo ),
				'cbc:Description'  => array( '_text' => $motivo )
			),
			'cac:BillingReference' => array(
				'cac:InvoiceDocumentReference' => array(
					'cbc:ID' => array( '_text' => $comprobante_original->serie . '-' . $comprobante_original->correlativo ),
					'cbc:DocumentTypeCode' => array( '_text' => $comprobante_original->tipo )
				)
			),

			'cac:Signature' => array(
				'cbc:ID' => array( '_text' => $ruc_emisor ),
				'cac:SignatoryParty' => array(
					'cac:PartyIdentification' => array(
						'cbc:ID' => array( '_text' => $ruc_emisor )
					),
					'cac:PartyName' => array(
						'cbc:Name' => array( '_text' => $razon_social_emisor )
					)
				),
				'cac:DigitalSignatureAttachment' => array(
					'cac:ExternalReference' => array(
						'cbc:URI' => array( '_text' => '#SIGN-SUNAT' )
					)
				)
			),
			
			'cac:AccountingSupplierParty' => array(
				'cac:Party' => array(
					'cac:PartyIdentification' => array(
						'cbc:ID' => array(
							'_attributes' => array( 'schemeID' => '6' ),
							'_text' => $ruc_emisor
						)
					),
					'cac:PartyLegalEntity' => array(
						'cbc:RegistrationName' => array( '_text' => $razon_social_emisor ),
						'cac:RegistrationAddress' => array(
							'cbc:AddressTypeCode' => array(
								'_text' => $codigo_local
							),
							'cac:AddressLine' => array(
								'cbc:Line' => array( '_text' => $direccion_emisor )
							)
						)
					)
				)
			),

			'cac:AccountingCustomerParty' => array(
				'cac:Party' => array(
					'cac:PartyIdentification' => array(
						'cbc:ID' => array(
							'_attributes' => array( 'schemeID' => $comprobante_original->cliente_doc_tipo ),
							'_text' => $comprobante_original->cliente_doc_num
						)
					),
					'cac:PartyLegalEntity' => array(
						'cbc:RegistrationName' => array( '_text' => $comprobante_original->cliente_nombre ),
						// Asumimos que la direccion puede ser requerida
						'cac:RegistrationAddress' => array(
							'cac:AddressLine' => array(
								'cbc:Line' => array( '_text' => '-' ) // Placeholder si no tenemos la direccion original facil a mano
							)
						)
					)
				)
			),

			'cac:TaxTotal' => array(
				'cbc:TaxAmount' => array( '_attributes' => array( 'currencyID' => 'PEN' ), '_text' => $igv_total ),
				'cac:TaxSubtotal' => array(
					'cbc:TaxableAmount' => array( '_attributes' => array( 'currencyID' => 'PEN' ), '_text' => $monto_base_total ),
					'cbc:TaxAmount' => array( '_attributes' => array( 'currencyID' => 'PEN' ), '_text' => $igv_total ),
					'cac:TaxCategory' => array(
						'cac:TaxScheme' => array(
							'cbc:ID' => array( '_text' => '1000' ),
							'cbc:Name' => array( '_text' => 'IGV' ),
							'cbc:TaxTypeCode' => array( '_text' => 'VAT' )
						)
					)
				)
			),

			'cac:LegalMonetaryTotal' => array(
				'cbc:PayableAmount' => array( '_attributes' => array( 'currencyID' => 'PEN' ), '_text' => $total_general )
			),

			'cac:CreditNoteLine' => array()
		);

		// Líneas de detalle
		$line_id = 1;
		foreach ( $envios_datos as $envio ) {
			$precio_unitario_con_igv = $envio['monto'];
			$precio_unitario_sin_igv = $envio['precio_unitario_sin_igv'];
			$valor_venta_linea = $envio['valor_venta_linea'];
			$igv_linea = $envio['igv_linea'];

			$document_body['cac:CreditNoteLine'][] = array(
				'cbc:ID' => array( '_text' => $line_id ),
				'cbc:CreditedQuantity' => array( '_attributes' => array( 'unitCode' => 'NIU' ), '_text' => 1 ),
				'cbc:LineExtensionAmount' => array( '_attributes' => array( 'currencyID' => 'PEN' ), '_text' => $valor_venta_linea ),
				'cac:PricingReference' => array(
					'cac:AlternativeConditionPrice' => array(
						'cbc:PriceAmount' => array( '_attributes' => array( 'currencyID' => 'PEN' ), '_text' => $precio_unitario_con_igv ),
						'cbc:PriceTypeCode' => array( '_text' => '01' )
					)
				),
				'cac:TaxTotal' => array(
					'cbc:TaxAmount' => array( '_attributes' => array( 'currencyID' => 'PEN' ), '_text' => $igv_linea ),
					'cac:TaxSubtotal' => array(
						'cbc:TaxableAmount' => array( '_attributes' => array( 'currencyID' => 'PEN' ), '_text' => $valor_venta_linea ),
						'cbc:TaxAmount' => array( '_attributes' => array( 'currencyID' => 'PEN' ), '_text' => $igv_linea ),
						'cac:TaxCategory' => array(
							'cbc:Percent' => array( '_text' => 18 ),
							'cbc:TaxExemptionReasonCode' => array( '_text' => '10' ),
							'cac:TaxScheme' => array(
								'cbc:ID' => array( '_text' => '1000' ),
								'cbc:Name' => array( '_text' => 'IGV' ),
								'cbc:TaxTypeCode' => array( '_text' => 'VAT' )
							)
						)
					)
				),
				'cac:Item' => array(
					'cbc:Description' => array( '_text' => 'Servicio de courier - Tracking: ' . $envio['tracking'] ),
				),
				'cac:Price' => array(
					'cbc:PriceAmount' => array( '_attributes' => array( 'currencyID' => 'PEN' ), '_text' => $precio_unitario_sin_igv )
				)
			);
			$line_id++;
		}

		// 6. Enviar a SUNAT
		// Obtener email del cliente buscando el usuario original
		$customer_email = '';
		$user = get_users(array('meta_key' => 'wpcfact_doc_num', 'meta_value' => $comprobante_original->cliente_doc_num));
		if(!empty($user)) {
			$customer_email = $user[0]->user_email;
		}

		$api_response = WPC_Facturacion_APISunat::send_bill( $file_name, $document_body, $customer_email );
		
		if ( is_wp_error( $api_response ) ) {
			return $api_response;
		}

		// 7. Guardar en Base de Datos Local
		$datos_guardar = array(
			'tipo'             => $tipo,
			'serie'            => $serie,
			'correlativo'      => $correlativo,
			'file_name'        => $file_name,
			'document_id'      => $api_response['documentId'],
			'estado'           => $api_response['status'],
			'cliente_doc_tipo' => $comprobante_original->cliente_doc_tipo,
			'cliente_doc_num'  => $comprobante_original->cliente_doc_num,
			'cliente_nombre'   => $comprobante_original->cliente_nombre,
			'monto_base'       => $monto_base_total,
			'igv'              => $igv_total,
			'total'            => $total_general,
			'emitido_en'       => $fecha_emision,
		);

		// Reutilizamos la misma estructura de BD, los envíos se pueden volver a vincular
		$comprobante_id = WPC_Facturacion_Comprobante::crear( $datos_guardar, $envios_datos );

		if ( is_wp_error( $comprobante_id ) ) {
			return $comprobante_id;
		}

		// Opcional: Marcar la factura original como vinculada/anulada internamente
		WPC_Facturacion_Comprobante::actualizar( $comprobante_original->id, array( 'estado' => 'NOTA CREDITO' ) );

		return array(
			'id'          => $comprobante_id,
			'serie'       => $serie,
			'correlativo' => $correlativo,
			'estado'      => $api_response['status'],
		);
	}
}
