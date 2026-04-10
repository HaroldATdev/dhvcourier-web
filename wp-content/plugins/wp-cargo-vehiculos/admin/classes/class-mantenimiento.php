<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Historia 2.5 – Control de Mantenimiento por Kilometraje
 */
class WPCVH_Mantenimiento {

	public static array $tipos = [
		'Cambio de aceite',
		'Cambio de filtros',
		'Revisión de frenos',
		'Cambio de llantas',
		'Revisión de suspensión',
		'Mantenimiento general',
		'Correctivo',
		'Otro',
	];

	/* ── Historial ─────────────────────────────────────────── */

	public static function obtener_por_vehiculo( int $vehiculo_id, int $limite = 50 ): array {
		global $wpdb;
		$t = WPCVH_Database::tabla_mantenimientos();
		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$t} WHERE vehiculo_id = %d ORDER BY realizado_en DESC, id DESC LIMIT %d",
			$vehiculo_id, $limite
		) );
		return is_array( $results ) ? $results : [];
	}

	public static function obtener_todos( array $filtros = [] ): array {
		global $wpdb;
		$tm = WPCVH_Database::tabla_mantenimientos();
		$tv = WPCVH_Database::tabla_vehiculos();
		$where  = [ '1=1' ];
		$params = [];

		if ( ! empty( $filtros['vehiculo_id'] ) ) {
			$where[]  = 'm.vehiculo_id = %d';
			$params[] = (int) $filtros['vehiculo_id'];
		}
		if ( ! empty( $filtros['desde'] ) ) {
			$where[]  = 'm.realizado_en >= %s';
			$params[] = $filtros['desde'];
		}
		if ( ! empty( $filtros['hasta'] ) ) {
			$where[]  = 'm.realizado_en <= %s';
			$params[] = $filtros['hasta'];
		}

		$where_sql = implode( ' AND ', $where );
		$sql = "SELECT m.*, v.placa, v.tipo, v.marca, v.modelo
				FROM {$tm} m
				LEFT JOIN {$tv} v ON v.id = m.vehiculo_id
				WHERE {$where_sql}
				ORDER BY m.realizado_en DESC, m.id DESC
				LIMIT 200";

		$results = empty( $params )
			? $wpdb->get_results( $sql ) // phpcs:ignore
			: $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) ); // phpcs:ignore
		return is_array( $results ) ? $results : [];
	}

	/* ── Registrar mantenimiento ───────────────────────────── */

	public static function crear( array $datos ): int|\WP_Error {
		global $wpdb;
		$error = self::validar( $datos );
		if ( is_wp_error( $error ) ) return $error;

		$vehiculo_id  = (int)   $datos['vehiculo_id'];
		$km_momento   = (int)   $datos['km_al_momento'];

		$wpdb->insert( WPCVH_Database::tabla_mantenimientos(), [
			'vehiculo_id'    => $vehiculo_id,
			'tipo_mant'      => sanitize_text_field( $datos['tipo_mant'] ),
			'km_al_momento'  => $km_momento,
			'costo'          => (float) ( $datos['costo'] ?? 0 ),
			'descripcion'    => sanitize_textarea_field( $datos['descripcion'] ?? '' ) ?: null,
			'realizado_en'   => sanitize_text_field( $datos['realizado_en'] ),
			'registrado_por' => get_current_user_id(),
		], [ '%d','%s','%d','%f','%s','%s','%d' ] );

		if ( ! $wpdb->insert_id ) return new \WP_Error( 'db_error', 'Error al registrar el mantenimiento.' );

		// Historia 2.5: actualizar km_ultimo_mant y km_actual si km_momento > km_actual
		$v = WPCVH_Vehiculo::obtener_por_id( $vehiculo_id );
		if ( $v ) {
			$update = [ 'km_ultimo_mant' => $km_momento ];
			if ( $km_momento > (int) $v->km_actual ) {
				$update['km_actual'] = $km_momento;
			}
			$wpdb->update( WPCVH_Database::tabla_vehiculos(), $update, [ 'id' => $vehiculo_id ], [ '%d' ], [ '%d' ] );
		}

		return (int) $wpdb->insert_id;
	}

	public static function eliminar( int $id ): void {
		global $wpdb;
		$wpdb->delete( WPCVH_Database::tabla_mantenimientos(), [ 'id' => $id ], [ '%d' ] );
	}

	public static function costo_total_por_vehiculo( int $vehiculo_id ): float {
		global $wpdb;
		$t = WPCVH_Database::tabla_mantenimientos();
		return (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(costo),0) FROM {$t} WHERE vehiculo_id = %d", $vehiculo_id ) );
	}

	private static function validar( array $datos ): true|\WP_Error {
		if ( empty( $datos['vehiculo_id'] ) )   return new \WP_Error( 'veh_requerido',  'Vehículo requerido.' );
		if ( empty( $datos['tipo_mant'] ) )      return new \WP_Error( 'tipo_requerido', 'Tipo de mantenimiento requerido.' );
		if ( ! isset( $datos['km_al_momento'] ) || (int) $datos['km_al_momento'] < 0 )
			return new \WP_Error( 'km_invalido', 'Kilometraje al momento inválido.' );
		if ( empty( $datos['realizado_en'] ) )   return new \WP_Error( 'fecha_requerida','Fecha de realización requerida.' );
		return true;
	}
}
