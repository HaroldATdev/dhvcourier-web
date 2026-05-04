<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPC_Facturacion_APISunat {

	private static function get_api_url() {
		// Misma URL para DEV y PROD en APISUNAT, se diferencia por las credenciales
		return 'https://back.apisunat.com';
	}

	public static function get_serie( string $tipo ): string {
		if ( $tipo === '01' ) {
			return get_option( 'wpcfact_serie_factura', 'F001' );
		}
		return get_option( 'wpcfact_serie_boleta', 'B001' );
	}

	private static function get_credentials() {
		return array(
			'personaId'    => get_option( 'wpcfact_persona_id' ),
			'personaToken' => get_option( 'wpcfact_persona_token' ),
		);
	}

	public static function last_document( $type, $serie ) {
		$creds = self::get_credentials();
		if ( empty( $creds['personaId'] ) || empty( $creds['personaToken'] ) ) {
			return new WP_Error( 'missing_creds', 'Credenciales de APISUNAT no configuradas.' );
		}

		$body = array(
			'personaId'    => $creds['personaId'],
			'personaToken' => $creds['personaToken'],
			'type'         => $type,
			'serie'        => $serie,
		);

		$url      = self::get_api_url() . '/personas/lastDocument';
		$response = wp_remote_post(
			$url,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $body ),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $response_code !== 200 ) {
			return new WP_Error( 'api_error', 'Error al obtener último documento.', $response_body );
		}

		return $response_body;
	}
	
	public static function send_bill( $file_name, $document_body, $customer_email = '' ) {
		$creds = self::get_credentials();
		if ( empty( $creds['personaId'] ) || empty( $creds['personaToken'] ) ) {
			return new WP_Error( 'missing_creds', 'Credenciales de APISUNAT no configuradas.' );
		}

		$body = array(
			'personaId'     => $creds['personaId'],
			'personaToken'  => $creds['personaToken'],
			'fileName'      => $file_name,
			'documentBody'  => $document_body,
		);

		if ( ! empty( $customer_email ) ) {
			$body['customerEmail'] = $customer_email;
		}

		$url      = self::get_api_url() . '/personas/v1/sendBill';
		$response = wp_remote_post(
			$url,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $body ),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
		
		if ( isset( $response_body['status'] ) && $response_body['status'] === 'ERROR' ) {
			return new WP_Error( 'api_error', 'Error en el envío del comprobante.', $response_body['error'] );
		}

		return $response_body;
	}

	public static function void_bill( $document_id, $reason ) {
		$creds = self::get_credentials();
		if ( empty( $creds['personaId'] ) || empty( $creds['personaToken'] ) ) {
			return new WP_Error( 'missing_creds', 'Credenciales de APISUNAT no configuradas.' );
		}

		$body = array(
			'personaId'    => $creds['personaId'],
			'personaToken' => $creds['personaToken'],
			'documentId'   => $document_id,
			'reason'       => $reason,
		);

		$url      = self::get_api_url() . '/personas/v1/voidBill';
		$response = wp_remote_post(
			$url,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $body ),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
		
		if ( isset( $response_body['status'] ) && $response_body['status'] === 'ERROR' ) {
			return new WP_Error( 'api_error', 'Error al anular comprobante.', $response_body['error'] );
		}

		return $response_body;
	}

	public static function get_by_id( $document_id ) {
		$url      = self::get_api_url() . "/documents/{$document_id}/getById";
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $response_body['error'] ) ) {
			return new WP_Error( 'api_error', 'Error al consultar estado del comprobante.', $response_body['error'] );
		}

		return $response_body;
	}
	
	public static function get_pdf_url( $document_id, $file_name, $format = 'A4' ) {
		return self::get_api_url() . "/documents/{$document_id}/getPDF/{$format}/{$file_name}.pdf";
	}

	/**
	 * Consulta datos fiscales de un DNI (8 dígitos) o RUC (11 dígitos) usando APIsPeru.
	 *
	 * @param string $tipo  'dni' o 'ruc'
	 * @param string $numero Número de documento
	 * @return array|WP_Error  Array con keys: nombre, direccion (ruc), o WP_Error
	 */
	public static function consultar_doc( string $tipo, string $numero ) {
		$token = get_option( 'wpcfact_apisperu_token', '' );
		if ( empty( $token ) ) {
			return new WP_Error( 'missing_token', 'Token de APIsPeru no configurado.' );
		}

		$tipo = strtolower( $tipo );
		if ( $tipo === 'dni' ) {
			$url = 'https://dniruc.apisperu.com/api/v1/dni/' . rawurlencode( $numero ) . '?token=' . rawurlencode( $token );
		} else {
			$url = 'https://dniruc.apisperu.com/api/v1/ruc/' . rawurlencode( $numero ) . '?token=' . rawurlencode( $token );
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code !== 200 || empty( $body ) ) {
			$msg = isset( $body['message'] ) ? $body['message'] : ( isset( $body['detail'] ) ? $body['detail'] : "HTTP {$code}" );
			return new WP_Error( 'not_found', 'APIsPeru: ' . $msg );
		}

		// La API puede devolver HTTP 200 con {"success": false, "message": "..."}
		if ( isset( $body['success'] ) && $body['success'] === false ) {
			$msg = $body['message'] ?? 'Documento no encontrado.';
			return new WP_Error( 'not_found', 'APIsPeru: ' . $msg );
		}

		$result = array( 'nombre' => '', 'direccion' => '' );

		if ( $tipo === 'dni' ) {
			$nombres = trim( ( $body['nombres'] ?? '' ) . ' ' . ( $body['apellidoPaterno'] ?? '' ) . ' ' . ( $body['apellidoMaterno'] ?? '' ) );
			$result['nombre'] = $nombres;
		} else {
			$result['nombre']    = $body['razonSocial'] ?? '';
			$result['direccion'] = $body['direccion'] ?? '';
		}

		if ( empty( $result['nombre'] ) ) {
			return new WP_Error( 'empty_result', 'APIsPeru: no se obtuvo nombre para el documento.' );
		}

		return $result;
	}
}
