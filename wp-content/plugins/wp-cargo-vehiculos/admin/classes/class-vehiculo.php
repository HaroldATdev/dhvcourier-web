<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPCVH_Vehiculo {

	// Historia 2.4 – tipos de vehículo
	public static array $tipos = [
		'Camión',
		'Furgón',
		'Camioneta',
		'Moto',
		'Minivan',
		'Otro',
	];

	/* ── Consultas ─────────────────────────────────────────── */

	public static function obtener_todos( array $args = [] ): array {
		global $wpdb;
		$t        = WPCVH_Database::tabla_vehiculos();
		$defaults = [ 'estado' => '', 'buscar' => '', 'limite' => 100, 'offset' => 0 ];
		$args     = wp_parse_args( $args, $defaults );
		$where    = [ '1=1' ];
		$params   = [];

		if ( ! empty( $args['estado'] ) ) {
			$where[]  = 'estado = %s';
			$params[] = $args['estado'];
		}
		if ( ! empty( $args['buscar'] ) ) {
			$like     = '%' . $wpdb->esc_like( $args['buscar'] ) . '%';
			$where[]  = '(placa LIKE %s OR marca LIKE %s OR modelo LIKE %s)';
			$params[] = $like; $params[] = $like; $params[] = $like;
		}

		$where_sql = implode( ' AND ', $where );
		$params[]  = (int) $args['limite'];
		$params[]  = (int) $args['offset'];

		$results = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$t} WHERE {$where_sql} ORDER BY placa ASC LIMIT %d OFFSET %d", ...$params )
		);
		return is_array( $results ) ? $results : [];
	}

	public static function obtener_por_id( int $id ): ?object {
		global $wpdb;
		$t = WPCVH_Database::tabla_vehiculos();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id = %d LIMIT 1", $id ) ) ?: null;
	}

	/* ── Historia 2.5: alertas ─────────────────────────────── */

	/**
	 * Devuelve el estado de alerta de mantenimiento del vehículo.
	 * 'ok'      → sin alerta
	 * 'proximo' → dentro del 10% del límite (zona amarilla)
	 * 'vencido' → km_actual - km_ultimo_mant >= km_limite_mant (zona roja)
	 */
	public static function estado_alerta( object $v ): string {
		$km_desde_mant = (int) $v->km_actual - (int) $v->km_ultimo_mant;
		$limite        = (int) $v->km_limite_mant;
		if ( $limite <= 0 ) return 'ok';
		if ( $km_desde_mant >= $limite ) return 'vencido';
		if ( $km_desde_mant >= $limite * 0.9 ) return 'proximo';
		return 'ok';
	}

	public static function km_para_mant( object $v ): int {
		$km_desde = (int) $v->km_actual - (int) $v->km_ultimo_mant;
		return max( 0, (int) $v->km_limite_mant - $km_desde );
	}

	/* ── CRUD ──────────────────────────────────────────────── */

	public static function crear( array $datos ): int|\WP_Error {
		global $wpdb;
		$error = self::validar( $datos );
		if ( is_wp_error( $error ) ) return $error;

		$km = (int) $datos['km_inicial'];
		$wpdb->insert( WPCVH_Database::tabla_vehiculos(), [
			'placa'          => strtoupper( sanitize_text_field( $datos['placa'] ) ),
			'tipo'           => sanitize_text_field( $datos['tipo'] ),
			'marca'          => sanitize_text_field( $datos['marca']  ?? '' ) ?: null,
			'modelo'         => sanitize_text_field( $datos['modelo'] ?? '' ) ?: null,
			'anio'           => ! empty( $datos['anio'] ) ? (int) $datos['anio'] : null,
			'km_inicial'     => $km,
			'km_actual'      => $km,
			'km_limite_mant' => (int) ( $datos['km_limite_mant'] ?? 5000 ),
			'km_ultimo_mant' => $km,   // inicio: el mant base parte del km inicial
			'estado'         => 'activo',
			'notas'          => sanitize_textarea_field( $datos['notas'] ?? '' ) ?: null,
			'fecha_registro' => date( 'Y-m-d' ),
			'creado_por'     => get_current_user_id(),
		], [ '%s','%s','%s','%s','%d','%d','%d','%d','%d','%s','%s','%s','%d' ] );

		if ( ! $wpdb->insert_id ) return new \WP_Error( 'db_error', 'Error al registrar el vehículo.' );
		return (int) $wpdb->insert_id;
	}

	public static function actualizar( int $id, array $datos ): true|\WP_Error {
		global $wpdb;
		$error = self::validar( $datos, $id );
		if ( is_wp_error( $error ) ) return $error;

		$res = $wpdb->update( WPCVH_Database::tabla_vehiculos(), [
			'placa'          => strtoupper( sanitize_text_field( $datos['placa'] ) ),
			'tipo'           => sanitize_text_field( $datos['tipo'] ),
			'marca'          => sanitize_text_field( $datos['marca']  ?? '' ) ?: null,
			'modelo'         => sanitize_text_field( $datos['modelo'] ?? '' ) ?: null,
			'anio'           => ! empty( $datos['anio'] ) ? (int) $datos['anio'] : null,
			'km_limite_mant' => (int) ( $datos['km_limite_mant'] ?? 5000 ),
			'notas'          => sanitize_textarea_field( $datos['notas'] ?? '' ) ?: null,
		], [ 'id' => $id ], [ '%s','%s','%s','%s','%d','%d','%s' ], [ '%d' ] );

		if ( false === $res ) return new \WP_Error( 'db_error', 'Error al actualizar.' );
		return true;
	}

	public static function cambiar_estado( int $id, string $estado ): void {
		global $wpdb;
		if ( ! in_array( $estado, [ 'activo', 'inactivo' ], true ) ) return;
		$wpdb->update( WPCVH_Database::tabla_vehiculos(), [ 'estado' => $estado ], [ 'id' => $id ], [ '%s' ], [ '%d' ] );
	}

	/** Actualiza km_actual y registra en km_log */
	public static function actualizar_km( int $id, int $km_nuevo, string $descripcion = '' ): true|\WP_Error {
		global $wpdb;
		$v = self::obtener_por_id( $id );
		if ( ! $v ) return new \WP_Error( 'no_existe', 'Vehículo no encontrado.' );
		if ( $km_nuevo < (int) $v->km_actual ) {
			return new \WP_Error( 'km_invalido', 'El nuevo kilometraje no puede ser menor al actual (' . number_format( $v->km_actual ) . ' km).' );
		}

		// Actualizar km_actual
		$wpdb->update( WPCVH_Database::tabla_vehiculos(),
			[ 'km_actual' => $km_nuevo ],
			[ 'id' => $id ],
			[ '%d' ], [ '%d' ]
		);

		// Log
		$wpdb->insert( WPCVH_Database::tabla_km_log(), [
			'vehiculo_id'    => $id,
			'km_anterior'    => (int) $v->km_actual,
			'km_nuevo'       => $km_nuevo,
			'descripcion'    => sanitize_text_field( $descripcion ) ?: null,
			'fecha'          => date( 'Y-m-d' ),
			'registrado_por' => get_current_user_id(),
		], [ '%d','%d','%d','%s','%s','%d' ] );

		return true;
	}

	public static function km_log( int $vehiculo_id, int $limite = 20 ): array {
		global $wpdb;
		$t = WPCVH_Database::tabla_km_log();
		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$t} WHERE vehiculo_id = %d ORDER BY fecha DESC, id DESC LIMIT %d",
			$vehiculo_id, $limite
		) );
		return is_array( $results ) ? $results : [];
	}

	/* ── Validación ────────────────────────────────────────── */

	private static function validar( array $datos, ?int $excluir_id = null ): true|\WP_Error {
		$placa = strtoupper( trim( $datos['placa'] ?? '' ) );
		if ( empty( $placa ) )
			return new \WP_Error( 'placa_requerida', 'La placa es obligatoria.' );
		if ( empty( $datos['tipo'] ) )
			return new \WP_Error( 'tipo_requerido', 'El tipo de vehículo es obligatorio.' );
		if ( ! isset( $datos['km_inicial'] ) && ! $excluir_id )
			return new \WP_Error( 'km_requerido', 'El kilometraje inicial es obligatorio.' );
		if ( (int) ( $datos['km_limite_mant'] ?? 0 ) <= 0 )
			return new \WP_Error( 'limite_invalido', 'El límite de km para mantenimiento debe ser mayor a cero.' );
		if ( self::placa_existe( $placa, $excluir_id ) )
			return new \WP_Error( 'placa_duplicada', "Ya existe un vehículo registrado con la placa {$placa}." );
		return true;
	}

	private static function placa_existe( string $placa, ?int $excluir_id ): bool {
		global $wpdb;
		$t = WPCVH_Database::tabla_vehiculos();
		if ( $excluir_id ) {
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE placa = %s AND id != %d", $placa, $excluir_id ) ) > 0;
		}
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE placa = %s", $placa ) ) > 0;
	}
}
