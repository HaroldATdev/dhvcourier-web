<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPCO_Orden {

	public static array $estados = [ 'Registrado', 'En transito', 'Entregado', 'Cancelado' ];

	public static function obtener_todos( array $args = [] ): array {
		global $wpdb;
		$defaults = [ 'estado' => '', 'buscar' => '', 'transportista_id' => 0, 'limite' => 50, 'offset' => 0 ];
		$args     = wp_parse_args( $args, $defaults );
		$tabla    = WPCO_Database::tabla();
		$t_trans  = WPCO_Database::tabla_transportistas();
		$where    = [ '1=1' ];
		$params   = [];

		if ( ! empty( $args['estado'] ) ) {
			$where[] = 'o.estado = %s'; $params[] = $args['estado'];
		}
		if ( ! empty( $args['transportista_id'] ) ) {
			$where[] = 'o.transportista_id = %d'; $params[] = (int) $args['transportista_id'];
		}
		if ( ! empty( $args['buscar'] ) ) {
			$like = '%' . $wpdb->esc_like( $args['buscar'] ) . '%';
			$where[] = '(o.codigo LIKE %s OR o.cliente LIKE %s OR o.origen LIKE %s OR o.destino LIKE %s)';
			$params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
		}

		$where_sql = implode( ' AND ', $where );
		$params[]  = (int) $args['limite'];
		$params[]  = (int) $args['offset'];

		if ( $t_trans ) {
			$query = "SELECT o.*, t.nombre AS transportista_nombre FROM {$tabla} o LEFT JOIN {$t_trans} t ON t.id = o.transportista_id WHERE {$where_sql} ORDER BY o.fecha_creacion DESC LIMIT %d OFFSET %d";
		} else {
			$query = "SELECT o.* FROM {$tabla} o WHERE {$where_sql} ORDER BY o.fecha_creacion DESC LIMIT %d OFFSET %d";
		}
		$results = $wpdb->get_results( $wpdb->prepare( $query, ...$params ) ); // phpcs:ignore
		return is_array( $results ) ? $results : [];
	}

	public static function obtener_por_id( int $id ): ?object {
		global $wpdb;
		$tabla   = WPCO_Database::tabla();
		$t_trans = WPCO_Database::tabla_transportistas();
		if ( $t_trans ) {
			return $wpdb->get_row( $wpdb->prepare(
				"SELECT o.*, t.nombre AS transportista_nombre FROM {$tabla} o LEFT JOIN {$t_trans} t ON t.id = o.transportista_id WHERE o.id = %d LIMIT 1", $id // phpcs:ignore
			) ) ?: null;
		}
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tabla} WHERE id = %d LIMIT 1", $id ) ) ?: null; // phpcs:ignore
	}

	public static function crear( array $datos ): int|\WP_Error {
		global $wpdb;
		$error = self::validar( $datos );
		if ( is_wp_error( $error ) ) return $error;

		$codigo = self::generar_codigo();
		$wpdb->insert( WPCO_Database::tabla(), [
			'codigo'           => $codigo,
			'cliente'          => sanitize_text_field( $datos['cliente'] ),
			'origen'           => sanitize_text_field( $datos['origen'] ),
			'destino'          => sanitize_text_field( $datos['destino'] ),
			'peso'             => (float) $datos['peso'],
			'cantidad'         => (int)   $datos['cantidad'],
			'costo'            => (float) $datos['costo'],
			'transportista_id' => ! empty( $datos['transportista_id'] ) ? (int) $datos['transportista_id'] : null,
			'estado'           => 'Registrado',
			'notas'            => sanitize_textarea_field( $datos['notas'] ?? '' ) ?: null,
			'creado_por'       => (int) get_current_user_id(),
		], [ '%s', '%s', '%s', '%s', '%f', '%d', '%f', '%d', '%s', '%s', '%d' ] );

		if ( ! $wpdb->insert_id ) return new \WP_Error( 'db_error', 'Error al guardar la orden.' );
		return (int) $wpdb->insert_id;
	}

	public static function actualizar( int $id, array $datos ): true|\WP_Error {
		global $wpdb;
		$error = self::validar( $datos );
		if ( is_wp_error( $error ) ) return $error;

		$estado = sanitize_text_field( $datos['estado'] ?? 'Registrado' );
		if ( ! in_array( $estado, self::$estados, true ) ) $estado = 'Registrado';

		$res = $wpdb->update( WPCO_Database::tabla(), [
			'cliente'          => sanitize_text_field( $datos['cliente'] ),
			'origen'           => sanitize_text_field( $datos['origen'] ),
			'destino'          => sanitize_text_field( $datos['destino'] ),
			'peso'             => (float) $datos['peso'],
			'cantidad'         => (int)   $datos['cantidad'],
			'costo'            => (float) $datos['costo'],
			'transportista_id' => ! empty( $datos['transportista_id'] ) ? (int) $datos['transportista_id'] : null,
			'estado'           => $estado,
			'notas'            => sanitize_textarea_field( $datos['notas'] ?? '' ) ?: null,
		], [ 'id' => $id ], [ '%s', '%s', '%s', '%f', '%d', '%f', '%d', '%s', '%s' ], [ '%d' ] );

		if ( false === $res ) return new \WP_Error( 'db_error', 'Error al actualizar.' );
		return true;
	}

	public static function obtener_transportistas(): array {
		global $wpdb;
		$tabla = WPCO_Database::tabla_transportistas();
		if ( ! $tabla ) return [];
		$wpdb->suppress_errors( true );
		$results = $wpdb->get_results(
			"SELECT id, nombre, codigo FROM {$tabla} WHERE estado = 'activo' ORDER BY nombre ASC" // phpcs:ignore
		);
		$wpdb->suppress_errors( false );
		return is_array( $results ) && ! $wpdb->last_error ? $results : [];
	}

	private static function generar_codigo(): string {
		global $wpdb;
		$tabla = WPCO_Database::tabla();
		$year  = date( 'Y' );
		do {
			$num    = str_pad( (string) wp_rand( 1, 99999 ), 5, '0', STR_PAD_LEFT );
			$codigo = "OS-{$year}-{$num}";
			$existe = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tabla} WHERE codigo = %s", $codigo ) ); // phpcs:ignore
		} while ( $existe > 0 );
		return $codigo;
	}

	private static function validar( array $datos ): true|\WP_Error {
		if ( empty( trim( $datos['cliente']  ?? '' ) ) ) return new \WP_Error( 'cliente_req', 'El cliente es obligatorio.' );
		if ( empty( trim( $datos['origen']   ?? '' ) ) ) return new \WP_Error( 'origen_req',  'El origen es obligatorio.' );
		if ( empty( trim( $datos['destino']  ?? '' ) ) ) return new \WP_Error( 'destino_req', 'El destino es obligatorio.' );
		if ( (float) ( $datos['peso']     ?? 0 ) <= 0 ) return new \WP_Error( 'peso_req',    'El peso debe ser mayor a cero.' );
		if ( (int)   ( $datos['cantidad'] ?? 0 ) <= 0 ) return new \WP_Error( 'cant_req',    'La cantidad debe ser mayor a cero.' );
		if ( (float) ( $datos['costo']    ?? 0 ) <= 0 ) return new \WP_Error( 'costo_req',   'El costo debe ser mayor a cero.' );
		return true;
	}
}
