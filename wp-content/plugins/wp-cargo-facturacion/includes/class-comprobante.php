<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPC_Facturacion_Comprobante {

	public static function get_table() {
		global $wpdb;
		return $wpdb->prefix . 'facturacion_comprobantes';
	}

	public static function get_envios_table() {
		global $wpdb;
		return $wpdb->prefix . 'facturacion_comprobante_envios';
	}

	public static function crear( $datos, $envios = array() ) {
		global $wpdb;

		$datos['created_at'] = current_time( 'mysql' );
		
		$inserted = $wpdb->insert( self::get_table(), $datos );
		if ( ! $inserted ) {
			return new WP_Error( 'db_error', 'Error al insertar en la base de datos.' );
		}

		$comprobante_id = $wpdb->insert_id;

		// Guardar relación con envíos
		foreach ( $envios as $envio ) {
			$wpdb->insert(
				self::get_envios_table(),
				array(
					'comprobante_id' => $comprobante_id,
					'shipment_id'    => $envio['id'],
					'monto'          => $envio['monto'],
				)
			);
		}

		return $comprobante_id;
	}

	public static function actualizar( $id, $datos ) {
		global $wpdb;
		return $wpdb->update(
			self::get_table(),
			$datos,
			array( 'id' => $id )
		);
	}

	public static function obtener( $id ) {
		global $wpdb;
		$table = self::get_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
	}

	public static function obtener_por_document_id( $document_id ) {
		global $wpdb;
		$table = self::get_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE document_id = %s", $document_id ) );
	}

	public static function get_comprobantes_pendientes() {
		global $wpdb;
		$table = self::get_table();
		// Evitar consultar comprobantes muy antiguos en PENDIENTE
		return $wpdb->get_results( "SELECT * FROM {$table} WHERE estado = 'PENDIENTE' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) LIMIT 50" );
	}

	public static function obtener_envios_de_comprobante( $comprobante_id ) {
		global $wpdb;
		$table_envios = self::get_envios_table();
		$table_posts = $wpdb->posts;
		return $wpdb->get_results( $wpdb->prepare( "
			SELECT e.*, p.post_title as tracking
			FROM {$table_envios} e
			JOIN {$table_posts} p ON e.shipment_id = p.ID
			WHERE e.comprobante_id = %d
		", $comprobante_id ) );
	}
	
	public static function comprobante_de_envio( $shipment_id ) {
		global $wpdb;
		$t_comp = self::get_table();
		$t_env = self::get_envios_table();
		
		return $wpdb->get_row( $wpdb->prepare( "
			SELECT c.* FROM {$t_comp} c
			JOIN {$t_env} e ON c.id = e.comprobante_id
			WHERE e.shipment_id = %d
			LIMIT 1
		", $shipment_id ) );
	}
}
