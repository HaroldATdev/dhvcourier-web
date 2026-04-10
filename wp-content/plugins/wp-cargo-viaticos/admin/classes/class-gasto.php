<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPCV_Gasto {

	public static function tipos(): array { return WPCV_Tipos_Gasto::obtener(); }

	public static function obtener_por_viatico( int $viatico_id ): array {
		global $wpdb;
		$t = WPCV_Database::tabla_gastos();
		$results = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$t} WHERE viatico_id = %d ORDER BY fecha_gasto DESC", $viatico_id ) // phpcs:ignore
		);
		return is_array( $results ) ? $results : [];
	}

	public static function crear( int $viatico_id, array $datos, ?array $archivo = null ): int|\WP_Error {
		global $wpdb;

		// Validar viático activo y saldo
		$viatico = WPCV_Viatico::obtener_por_id( $viatico_id );
		if ( ! $viatico ) return new \WP_Error( 'viatico_no_existe', 'El viático no existe.' );
		if ( $viatico->estado !== 'activo' ) return new \WP_Error( 'viatico_cerrado', 'El viático está cerrado.' );

		$monto = (float) ( $datos['monto'] ?? 0 );
		if ( $monto <= 0 ) return new \WP_Error( 'monto_invalido', 'El monto debe ser mayor a cero.' );

		$saldo = WPCV_Viatico::saldo( $viatico );
		if ( $monto > $saldo ) {
			return new \WP_Error( 'saldo_insuficiente',
				sprintf( 'Saldo insuficiente. Disponible: S/ %.2f', $saldo )
			);
		}

		$tipo = sanitize_text_field( $datos['tipo'] ?? '' );
		if ( empty( $tipo ) ) return new \WP_Error( 'tipo_requerido', 'El tipo de gasto es obligatorio.' );

		// Procesar archivo de sustento si viene
		$sustento_url  = null;
		$sustento_tipo = null;
		if ( $archivo && ! empty( $archivo['tmp_name'] ) && $archivo['error'] === UPLOAD_ERR_OK ) {
			$upload = self::subir_sustento( $archivo );
			if ( is_wp_error( $upload ) ) return $upload;
			$sustento_url  = $upload['url'];
			$sustento_tipo = $upload['tipo'];
		} else {
			return new \WP_Error( 'sustento_requerido', 'El sustento (foto o PDF) es obligatorio.' );
		}

		$wpdb->insert( WPCV_Database::tabla_gastos(), [
			'viatico_id'     => $viatico_id,
			'tipo'           => $tipo,
			'monto'          => $monto,
			'descripcion'    => sanitize_text_field( $datos['descripcion'] ?? '' ) ?: null,
			'sustento_url'   => $sustento_url,
			'sustento_tipo'  => $sustento_tipo,
			'registrado_por' => (int) get_current_user_id(),
		], [ '%d', '%s', '%f', '%s', '%s', '%s', '%d' ] );

		if ( ! $wpdb->insert_id ) return new \WP_Error( 'db_error', 'Error al registrar el gasto.' );
		$gasto_id = (int) $wpdb->insert_id;

		// Actualizar monto_usado en el viático
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->prefix}wpcv_viaticos SET monto_usado = monto_usado + %f WHERE id = %d", // phpcs:ignore
			$monto, $viatico_id
		) );

		return $gasto_id;
	}

	public static function eliminar( int $id ): true|\WP_Error {
		global $wpdb;
		$t      = WPCV_Database::tabla_gastos();
		$gasto  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id = %d", $id ) ); // phpcs:ignore
		if ( ! $gasto ) return new \WP_Error( 'no_existe', 'Gasto no encontrado.' );

		// Verificar que el viático esté activo
		$viatico = WPCV_Viatico::obtener_por_id( (int) $gasto->viatico_id );
		if ( $viatico && $viatico->estado !== 'activo' )
			return new \WP_Error( 'viatico_cerrado', 'No se puede eliminar gastos de un viático cerrado.' );

		$wpdb->delete( $t, [ 'id' => $id ], [ '%d' ] );

		// Revertir monto_usado
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->prefix}wpcv_viaticos SET monto_usado = GREATEST(0, monto_usado - %f) WHERE id = %d", // phpcs:ignore
			(float) $gasto->monto, (int) $gasto->viatico_id
		) );

		return true;
	}

	private static function subir_sustento( array $archivo ): array|\WP_Error {
		$tipos_permitidos = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf' ];
		$finfo = new \finfo( FILEINFO_MIME_TYPE );
		$mime  = $finfo->file( $archivo['tmp_name'] );

		if ( ! in_array( $mime, $tipos_permitidos, true ) ) {
			return new \WP_Error( 'tipo_no_permitido', 'Solo se permiten imágenes (JPG, PNG, GIF, WEBP) o PDF.' );
		}

		if ( $archivo['size'] > 5 * 1024 * 1024 ) {
			return new \WP_Error( 'archivo_grande', 'El archivo no debe superar 5 MB.' );
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$resultado = wp_handle_upload( $archivo, [ 'test_form' => false ] );
		if ( isset( $resultado['error'] ) ) {
			return new \WP_Error( 'upload_error', $resultado['error'] );
		}

		$tipo = str_starts_with( $mime, 'image/' ) ? 'imagen' : 'pdf';
		return [ 'url' => $resultado['url'], 'tipo' => $tipo ];
	}
}
// Note: $tipos is now dynamically loaded from wp_options (fallback to defaults)
