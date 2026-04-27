<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPC_Facturacion_Constructor_NotaVenta {

	public static function emitir( $user_id, $envios_ids, $doc_num, $nombre, $direccion, $forma_pago ) {
		global $wpdb;

		$tipo = '00';
		$serie = get_option( 'wpcfact_serie_nota_venta', 'NV01' );

		// Obtener último correlativo de la base de datos local para esta serie
		$table = WPC_Facturacion_Comprobante::get_table();
		$last_correlativo = $wpdb->get_var( $wpdb->prepare("SELECT MAX(CAST(correlativo AS UNSIGNED)) FROM {$table} WHERE tipo = %s AND serie = %s", $tipo, $serie) );
		$next_correlativo = intval( $last_correlativo ) + 1;

		$correlativo = sprintf( '%08d', $next_correlativo );
		$file_name = get_option('wpcfact_ruc_emisor') . '-' . $tipo . '-' . $serie . '-' . $correlativo;

		// Recopilar datos de envíos
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

		if ( empty( $envios_datos ) || $total_general <= 0 ) {
			return new WP_Error( 'data_error', 'No hay envíos válidos para generar la nota de venta.' );
		}

		$fecha_emision = current_time( 'Y-m-d\TH:i:sP' );
		$scheme_id = ( strlen( $doc_num ) === 11 ) ? '6' : ( strlen( $doc_num ) === 8 ? '1' : '4' );

		// 7. Guardar en Base de Datos Local
		$datos_guardar = array(
			'tipo'             => $tipo,
			'serie'            => $serie,
			'correlativo'      => $correlativo,
			'file_name'        => $file_name,
			'document_id'      => 'LOCAL-' . uniqid(),
			'estado'           => 'ACEPTADO', // Las notas de venta nacen aceptadas porque son locales
			'cliente_doc_tipo' => $scheme_id,
			'cliente_doc_num'  => $doc_num,
			'cliente_nombre'   => $nombre,
			'monto_base'       => $total_general, // En nota de venta puede no desglosarse IGV, todo es base o total.
			'igv'              => 0,
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
			'estado'      => 'ACEPTADO',
		);
	}
}
