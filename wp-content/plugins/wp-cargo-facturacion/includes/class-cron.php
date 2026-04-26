<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPC_Facturacion_Cron {

	public static function init() {
		add_filter( 'cron_schedules', array( __CLASS__, 'add_cron_intervals' ) );
		add_action( 'wpcfact_polling_estado_sunat', array( __CLASS__, 'polling_estado_comprobantes' ) );

		if ( ! wp_next_scheduled( 'wpcfact_polling_estado_sunat' ) ) {
			wp_schedule_event( time(), 'fifteen_minutes', 'wpcfact_polling_estado_sunat' );
		}
	}

	public static function add_cron_intervals( $schedules ) {
		$schedules['fifteen_minutes'] = array(
			'interval' => 15 * 60,
			'display'  => __( 'Cada 15 minutos' )
		);
		return $schedules;
	}

	public static function polling_estado_comprobantes() {
		$pendientes = WPC_Facturacion_Comprobante::get_comprobantes_pendientes();

		foreach ( $pendientes as $comp ) {
			if ( empty( $comp->document_id ) ) continue;

			$api_response = WPC_Facturacion_APISunat::get_by_id( $comp->document_id );

			if ( ! is_wp_error( $api_response ) && isset( $api_response['status'] ) ) {
				$status = $api_response['status'];
				
				if ( $status !== 'PENDIENTE' ) {
					$update_data = array(
						'estado' => $status,
					);

					if ( isset( $api_response['xml'] ) ) {
						$update_data['xml_url'] = $api_response['xml'];
					}
					if ( isset( $api_response['cdr'] ) ) {
						$update_data['cdr_url'] = $api_response['cdr'];
					}
					if ( isset( $api_response['faults'] ) && ! empty( $api_response['faults'] ) ) {
						$update_data['faults'] = wp_json_encode( $api_response['faults'] );
					}

					WPC_Facturacion_Comprobante::actualizar( $comp->id, $update_data );
				}
			}
		}
	}
}
