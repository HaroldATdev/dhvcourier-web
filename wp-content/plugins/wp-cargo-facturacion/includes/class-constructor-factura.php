<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPC_Facturacion_Constructor {

	public static function emitir( $user_id, $envios_ids, $tipo, $doc_num, $nombre, $direccion, $forma_pago ) {
		global $wpdb;

		// 1. Obtener RUC emisor de settings
		$ruc_emisor          = get_option( 'wpcfact_ruc_emisor' );
		$razon_social_emisor = get_option( 'wpcfact_razon_social_emisor' );
		$direccion_emisor    = get_option( 'wpcfact_direccion_emisor', '' );
		$codigo_local        = get_option( 'wpcfact_codigo_local', '0000' ); // Código de local anexo SUNAT
		if ( empty( $codigo_local ) ) {
			$codigo_local = '0000';
		}
		if ( empty( $direccion_emisor ) ) {
			$direccion_emisor = '-';
		}
		if ( empty( $ruc_emisor ) ) {
			return new WP_Error( 'config_error', 'Falta configurar el RUC emisor.' );
		}

		// 2. Definir Serie
		$serie = ( $tipo === '01' ) ? get_option( 'wpcfact_serie_factura', 'F001' ) : get_option( 'wpcfact_serie_boleta', 'B001' );

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

			// Leer monto desde costo_envio (cotización original), fallback a monto y luego wpcargo_total_freight
			$freight_raw = get_post_meta( $envio_id, 'costo_envio', true );
			if ( empty( $freight_raw ) ) {
				$freight_raw = get_post_meta( $envio_id, 'monto', true );
			}
			if ( empty( $freight_raw ) ) {
				$freight_raw = get_post_meta( $envio_id, 'wpcargo_total_freight', true );
			}
			$monto = floatval( preg_replace( '/[^0-9.]/', '', $freight_raw ) );
			
			if ( $monto <= 0 ) continue;

			$total_general += $monto;

			$envios_datos[] = array(
				'id'          => $envio_id,
				'tracking'    => $post->post_title,
				'monto'       => $monto,
			);
		}

		if ( empty( $envios_datos ) || $total_general <= 0 ) {
			return new WP_Error( 'data_error', 'No hay envíos válidos para facturar.' );
		}

		// Variables para totales globales acumulados desde las líneas
		$monto_base_total = 0;
		$igv_total = 0;
		
		// Cálculos por línea (pre-cálculo) para obtener sumatorias exactas
		foreach ( $envios_datos as &$envio ) {
			$precio_unitario_con_igv = $envio['monto'];
			$precio_unitario_sin_igv = round( $precio_unitario_con_igv / 1.18, 5 );
			$valor_venta_linea = round( $precio_unitario_sin_igv * 1, 2 );
			$igv_linea = round( $precio_unitario_con_igv - $valor_venta_linea, 2 );
			
			$envio['valor_venta_linea'] = $valor_venta_linea;
			$envio['igv_linea'] = $igv_linea;
			$envio['precio_unitario_sin_igv'] = $precio_unitario_sin_igv;
			
			$monto_base_total += $valor_venta_linea;
			$igv_total += $igv_linea;
		}
		unset($envio);
		
		// Ajuste para asegurar que la suma exacta cuadre con el total general ingresado si hay diferencias de 1 céntimo
		$total_calculado = $monto_base_total + $igv_total;
		if ( abs($total_general - $total_calculado) > 0.001 ) {
		    // SUNAT requiere que la suma de líneas cuadre con los totales
		    // Generalmente esto ya cuadra por nuestra forma de calcular el IGV por línea
		}

		$monto_letras = wpcfact_numero_a_letras( $total_general );
		$fecha_emision = current_time( 'Y-m-d\TH:i:sP' );

		$scheme_id = ( strlen( $doc_num ) === 11 ) ? '6' : ( strlen( $doc_num ) === 8 ? '1' : '4' );

		// 5. Construir JSON (documentBody)
		$document_body = array(
			'cbc:UBLVersionID' => array( '_text' => '2.1' ),
			'cbc:CustomizationID' => array( '_text' => '2.0' ),
			'cbc:ID' => array( '_text' => $numero_documento ),
			'cbc:IssueDate' => array( '_text' => current_time( 'Y-m-d' ) ),
			'cbc:IssueTime' => array( '_text' => current_time( 'H:i:s' ) ),
			'cbc:InvoiceTypeCode' => array(
				'_attributes' => array( 'listID' => '0101' ),
				'_text' => $tipo
			),
			'cbc:Note' => array(
				array(
					'_attributes' => array( 'languageLocaleID' => '1000' ),
					'_text' => $monto_letras
				)
			),
			'cbc:DocumentCurrencyCode' => array( '_text' => 'PEN' ),
			
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
							'_attributes' => array( 'schemeID' => $scheme_id ),
							'_text' => $doc_num
						)
					),
					'cac:PartyLegalEntity' => array(
						'cbc:RegistrationName' => array( '_text' => $nombre ),
						'cac:RegistrationAddress' => array(
							'cac:AddressLine' => array(
								'cbc:Line' => array( '_text' => $direccion )
							)
						)
					)
				)
			),

			'cac:PaymentTerms' => array(
				array(
					'cbc:ID' => array( '_text' => 'FormaPago' ),
					'cbc:PaymentMeansID' => array( '_text' => $forma_pago )
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
				'cbc:LineExtensionAmount' => array( '_attributes' => array( 'currencyID' => 'PEN' ), '_text' => $monto_base_total ),
				'cbc:TaxInclusiveAmount' => array( '_attributes' => array( 'currencyID' => 'PEN' ), '_text' => $total_general ),
				'cbc:PayableAmount' => array( '_attributes' => array( 'currencyID' => 'PEN' ), '_text' => $total_general )
			),

			'cac:InvoiceLine' => array()
		);

		// Líneas de detalle
		$line_id = 1;
		foreach ( $envios_datos as $envio ) {
			$precio_unitario_con_igv = $envio['monto'];
			$precio_unitario_sin_igv = $envio['precio_unitario_sin_igv'];
			$valor_venta_linea = $envio['valor_venta_linea'];
			$igv_linea = $envio['igv_linea'];

			$document_body['cac:InvoiceLine'][] = array(
				'cbc:ID' => array( '_text' => $line_id ),
				'cbc:InvoicedQuantity' => array( '_attributes' => array( 'unitCode' => 'ZZ' ), '_text' => 1 ),
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
		$user_data = get_userdata( $user_id );
		$customer_email = $user_data ? $user_data->user_email : '';

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
			'cliente_doc_tipo' => $scheme_id,
			'cliente_doc_num'  => $doc_num,
			'cliente_nombre'   => $nombre,
			'monto_base'       => $monto_base_total,
			'igv'              => $igv_total,
			'total'            => $total_general,
			'emitido_en'       => $fecha_emision,
		);

		$comprobante_id = WPC_Facturacion_Comprobante::crear( $datos_guardar, $envios_datos );

		if ( is_wp_error( $comprobante_id ) ) {
			return $comprobante_id;
		}

		return array(
			'id'          => $comprobante_id,
			'serie'       => $serie,
			'correlativo' => $correlativo,
			'estado'      => $api_response['status'],
		);
	}
}
