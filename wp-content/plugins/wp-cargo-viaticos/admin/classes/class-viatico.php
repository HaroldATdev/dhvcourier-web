<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPCV_Viatico {

	public static function obtener_todos( array $args = [] ): array {
		global $wpdb;
		$defaults = [ 'transportista_id' => 0, 'estado' => '', 'limite' => 50, 'offset' => 0 ];
		$args     = wp_parse_args( $args, $defaults );
		$t_viat   = WPCV_Database::tabla();
		$t_trans  = WPCV_Database::tabla_transportistas();
		$where    = [ '1=1' ];
		$params   = [];

		if ( ! empty( $args['transportista_id'] ) ) {
			$where[]  = 'v.transportista_id = %d';
			$params[] = (int) $args['transportista_id'];
		}
		if ( ! empty( $args['estado'] ) ) {
			$where[]  = 'v.estado = %s';
			$params[] = $args['estado'];
		}

		$where_sql = implode( ' AND ', $where );
		$params[]  = (int) $args['limite'];
		$params[]  = (int) $args['offset'];

		if ( $t_trans ) {
			$query = "SELECT v.*, CONCAT(t.nombres, ' ', t.apellidos) AS transportista_nombre FROM {$t_viat} v LEFT JOIN {$t_trans} t ON t.id = v.transportista_id WHERE {$where_sql} ORDER BY v.fecha_asignacion DESC LIMIT %d OFFSET %d";
		} else {
			$query = "SELECT v.*, v.transportista_id AS transportista_nombre FROM {$t_viat} v WHERE {$where_sql} ORDER BY v.fecha_asignacion DESC LIMIT %d OFFSET %d";
		}

		$results = $wpdb->get_results( $wpdb->prepare( $query, ...$params ) );
		return is_array( $results ) ? $results : [];
	}

	public static function obtener_por_id( int $id ): ?object {
		global $wpdb;
		$tabla = WPCV_Database::tabla();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tabla} WHERE id = %d LIMIT 1", $id ) ) ?: null;
	}

	public static function saldo( object $v ): float {
		return (float) $v->monto_asignado - (float) $v->monto_usado;
	}

	/**
	 * Obtiene transportistas activos de la tabla wp-cargo-carrier.
	 *
	 * Estrategia en dos pasos:
	 * 1. Usar tabla_transportistas() que verifica con SHOW TABLES.
	 * 2. Si por algún motivo falla, intentar la query directamente
	 *    y capturar el error — si la tabla no existe, MySQL lanzará error
	 *    y $wpdb->last_error tendrá el mensaje; en ese caso devolvemos [].
	 */
	public static function obtener_transportistas(): array {
		global $wpdb;

		// Intento principal: verificar tabla y consultar
		$tabla = WPCV_Database::tabla_transportistas();
		if ( $tabla ) {
			$results = $wpdb->get_results(
				"SELECT id, CONCAT(nombres, ' ', apellidos) AS nombre, brevete FROM {$tabla} WHERE estado = 'activo' ORDER BY apellidos ASC, nombres ASC" // phpcs:ignore
			);
			if ( is_array( $results ) ) return $results;
		}

		// Fallback: intentar directamente por si SHOW TABLES tiene restricciones de permisos
		$tabla_directa = $wpdb->prefix . 'wpcc_transportistas';
		$wpdb->suppress_errors( true );
		$results = $wpdb->get_results(
			"SELECT id, CONCAT(nombres, ' ', apellidos) AS nombre, brevete FROM {$tabla_directa} WHERE estado = 'activo' ORDER BY apellidos ASC, nombres ASC" // phpcs:ignore
		);
		$wpdb->suppress_errors( false );

		if ( $wpdb->last_error ) return [];
		return is_array( $results ) ? $results : [];
	}

	/** ¿La tabla de transportistas (Carrier) está disponible? */
	public static function carrier_activo(): bool {
		global $wpdb;
		// Verificación con SHOW TABLES
		if ( WPCV_Database::tabla_transportistas() !== null ) return true;

		// Fallback: intentar query directa
		$tabla = $wpdb->prefix . 'wpcc_transportistas';
		$wpdb->suppress_errors( true );
		$wpdb->get_var( "SELECT COUNT(*) FROM {$tabla} LIMIT 1" ); // phpcs:ignore
		$error = $wpdb->last_error;
		$wpdb->suppress_errors( false );
		return empty( $error );
	}

	public static function crear( array $datos ): int|\WP_Error {
		global $wpdb;
		$error = self::validar( $datos );
		if ( is_wp_error( $error ) ) return $error;

		$wpdb->insert( WPCV_Database::tabla(), [
			'transportista_id' => (int)   $datos['transportista_id'],
			'ruta'             => sanitize_text_field( $datos['ruta'] ),
			'monto_asignado'   => (float)  $datos['monto_asignado'],
			'monto_usado'      => 0.00,
			'fecha_asignacion' => sanitize_text_field( $datos['fecha_asignacion'] ),
			'estado'           => 'activo',
			'notas'            => sanitize_textarea_field( $datos['notas'] ?? '' ) ?: null,
			'creado_por'       => (int) get_current_user_id(),
		], [ '%d', '%s', '%f', '%f', '%s', '%s', '%s', '%d' ] );

		if ( ! $wpdb->insert_id ) return new \WP_Error( 'db_error', 'Error al guardar el viático.' );
		return (int) $wpdb->insert_id;
	}

	public static function actualizar( int $id, array $datos ): true|\WP_Error {
		global $wpdb;
		$error = self::validar( $datos );
		if ( is_wp_error( $error ) ) return $error;

		$resultado = $wpdb->update( WPCV_Database::tabla(), [
			'transportista_id' => (int)  $datos['transportista_id'],
			'ruta'             => sanitize_text_field( $datos['ruta'] ),
			'monto_asignado'   => (float) $datos['monto_asignado'],
			'fecha_asignacion' => sanitize_text_field( $datos['fecha_asignacion'] ),
			'notas'            => sanitize_textarea_field( $datos['notas'] ?? '' ) ?: null,
		], [ 'id' => $id ], [ '%d', '%s', '%f', '%s', '%s' ], [ '%d' ] );

		if ( false === $resultado ) return new \WP_Error( 'db_error', 'Error al actualizar.' );
		return true;
	}

	public static function cerrar( int $id ): void {
		global $wpdb;
		$wpdb->update( WPCV_Database::tabla(), [ 'estado' => 'cerrado' ], [ 'id' => $id ], [ '%s' ], [ '%d' ] );
	}

	private static function validar( array $datos ): true|\WP_Error {
		if ( empty( $datos['transportista_id'] ) || (int) $datos['transportista_id'] <= 0 )
			return new \WP_Error( 'trans_requerido', 'Debe seleccionar un transportista.' );
		if ( empty( trim( $datos['ruta'] ?? '' ) ) )
			return new \WP_Error( 'ruta_requerida', 'La ruta es obligatoria.' );
		if ( empty( $datos['monto_asignado'] ) || (float) $datos['monto_asignado'] <= 0 )
			return new \WP_Error( 'monto_invalido', 'El monto debe ser mayor a cero.' );
		if ( empty( $datos['fecha_asignacion'] ) )
			return new \WP_Error( 'fecha_requerida', 'La fecha de asignación es obligatoria.' );
		return true;
	}

	public static function ampliar( int $id, float $adicional ): true|\WP_Error {
		global $wpdb;
		if ( $adicional <= 0 ) return new \WP_Error( 'monto_invalido', 'El monto adicional debe ser mayor a cero.' );
		$v = self::obtener_por_id( $id );
		if ( ! $v ) return new \WP_Error( 'no_existe', 'Viático no encontrado.' );
		if ( $v->estado === 'cerrado' ) return new \WP_Error( 'cerrado', 'No se puede ampliar un viático cerrado.' );
		$res = $wpdb->query( $wpdb->prepare(
			"UPDATE " . WPCV_Database::tabla() . " SET monto_asignado = monto_asignado + %f WHERE id = %d",
			$adicional, $id
		) );
		if ( false === $res ) return new \WP_Error( 'db_error', 'Error al ampliar el viático.' );
		return true;
	}

	public static function historial_por_transportista( int $transportista_id, array $filtros = [] ): array {
		global $wpdb;
		$tv = WPCV_Database::tabla();
		$tt = WPCV_Database::tabla_transportistas();
		$where  = [ 'v.transportista_id = %d' ];
		$params = [ $transportista_id ];

		if ( ! empty( $filtros['desde'] ) ) {
			$where[]  = 'v.fecha_asignacion >= %s';
			$params[] = sanitize_text_field( $filtros['desde'] );
		}
		if ( ! empty( $filtros['hasta'] ) ) {
			$where[]  = 'v.fecha_asignacion <= %s';
			$params[] = sanitize_text_field( $filtros['hasta'] );
		}
		if ( ! empty( $filtros['estado'] ) ) {
			$where[]  = 'v.estado = %s';
			$params[] = $filtros['estado'];
		}

		$where_sql  = implode( ' AND ', $where );
		$join_trans = $tt ? "LEFT JOIN {$tt} t ON t.id = v.transportista_id" : '';
		$sel_nombre = $tt
			? "CONCAT(t.nombres, ' ', t.apellidos) AS transportista_nombre"
			: "CONCAT('ID #', v.transportista_id) AS transportista_nombre";

		$sql = "SELECT v.*, {$sel_nombre},
			v.monto_asignado - v.monto_usado AS diferencia
			FROM {$tv} v {$join_trans}
			WHERE {$where_sql}
			ORDER BY v.fecha_asignacion DESC
			LIMIT 500";

		$results = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) ); // phpcs:ignore
		return is_array( $results ) ? $results : [];
	}

	public static function reporte( array $filtros = [] ): array {
		global $wpdb;
		$tv = WPCV_Database::tabla();
		$tt = WPCV_Database::tabla_transportistas();
		$where  = [ '1=1' ];
		$params = [];

		if ( ! empty( $filtros['transportista_id'] ) ) {
			$where[]  = 'v.transportista_id = %d';
			$params[] = (int) $filtros['transportista_id'];
		}
		if ( ! empty( $filtros['ruta'] ) ) {
			$where[]  = 'v.ruta LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $filtros['ruta'] ) . '%';
		}
		if ( ! empty( $filtros['estado'] ) ) {
			$where[]  = 'v.estado = %s';
			$params[] = $filtros['estado'];
		}

		// Período predefinido o rango libre (valores enviados desde template: diario/semanal/mensual)
		$periodo = $filtros['periodo'] ?? '';
		switch ( $periodo ) {
			case 'diario':
				// Fecha de hoy en zona horaria de WordPress (compatible WP 4.x+)
				$hoy      = date_i18n( 'Y-m-d' );
				$where[]  = 'v.fecha_asignacion = %s';
				$params[] = $hoy;
				break;
			case 'semanal': {
				$hoy_ts   = current_time( 'timestamp' );
				// día de la semana: 0=dom..6=sab. Queremos lunes=inicio
				$dow      = (int) date( 'N', $hoy_ts ); // 1=lun .. 7=dom
				$lunes_ts = $hoy_ts - ( $dow - 1 ) * DAY_IN_SECONDS;
				$dom_ts   = $lunes_ts + 6 * DAY_IN_SECONDS;
				$where[]  = 'v.fecha_asignacion >= %s';
				$params[] = date( 'Y-m-d', $lunes_ts );
				$where[]  = 'v.fecha_asignacion <= %s';
				$params[] = date( 'Y-m-d', $dom_ts );
				break;
			}
			case 'mensual': {
				$hoy_ts  = current_time( 'timestamp' );
				$primer  = date( 'Y-m-01', $hoy_ts );
				$ultimo  = date( 'Y-m-t',  $hoy_ts );
				$where[] = 'v.fecha_asignacion >= %s';
				$params[] = $primer;
				$where[] = 'v.fecha_asignacion <= %s';
				$params[] = $ultimo;
				break;
			}
			default:
				// Sin período predefinido: aplicar desde/hasta si existen
				if ( ! empty( $filtros['desde'] ) ) {
					$where[]  = 'v.fecha_asignacion >= %s';
					$params[] = sanitize_text_field( $filtros['desde'] );
				}
				if ( ! empty( $filtros['hasta'] ) ) {
					$where[]  = 'v.fecha_asignacion <= %s';
					$params[] = sanitize_text_field( $filtros['hasta'] );
				}
				break;
		}

		$where_sql  = implode( ' AND ', $where );
		$join_trans = $tt ? "LEFT JOIN {$tt} t ON t.id = v.transportista_id" : '';
		$sel_nombre = $tt
			? "CONCAT(t.nombres, ' ', t.apellidos) AS transportista_nombre"
			: "CONCAT('ID #', v.transportista_id) AS transportista_nombre";

		$sql = "SELECT v.*, {$sel_nombre},
			v.monto_asignado - v.monto_usado AS diferencia
			FROM {$tv} v {$join_trans}
			WHERE {$where_sql}
			ORDER BY v.fecha_asignacion DESC
			LIMIT 500";

		$results = empty( $params )
			? $wpdb->get_results( $sql ) // phpcs:ignore
			: $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) ); // phpcs:ignore
		return is_array( $results ) ? $results : [];
	}

}
