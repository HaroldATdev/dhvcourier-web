<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPC_Facturacion_APISunat {

	private static function get_api_url() {
		$ambiente = get_option( 'wpcfact_ambiente', 'DEV' );
		if ( $ambiente === 'PROD' ) {
			return 'https://back.apisunat.com'; // Replace with actual production URL if different
		}
		return 'https://back.apisunat.com'; // Replace with actual DEV URL
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
}
