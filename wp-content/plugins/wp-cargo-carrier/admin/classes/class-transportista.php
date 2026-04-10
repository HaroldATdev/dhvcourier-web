<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPCC_Transportista {

	/* ── Helpers ────────────────────────────────────────────────────── */

	public static function nombre_completo( object $t ): string {
		$n = trim( ( $t->nombres ?? '' ) . ' ' . ( $t->apellidos ?? '' ) );
		return $n ?: '(sin nombre)';
	}

	private static function tabla(): string {
		return WPCC_Database::tabla();
	}

	private static function decorar( ?object $r ): ?object {
		if ( ! $r ) return null;
		$r->nombre_completo = self::nombre_completo( $r );
		$r->nombre          = $r->nombre_completo;
		return $r;
	}

	/* ── Consultas ──────────────────────────────────────────────────── */

	public static function obtener_todos( array $args = [] ): array {
		global $wpdb;
		$tabla    = self::tabla();
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
			$where[]  = '(nombres LIKE %s OR apellidos LIKE %s OR dni LIKE %s OR brevete LIKE %s)';
			$params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
		}

		$where_sql = implode( ' AND ', $where );
		$params[]  = (int) $args['limite'];
		$params[]  = (int) $args['offset'];

		$results = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$tabla} WHERE {$where_sql} ORDER BY apellidos ASC, nombres ASC LIMIT %d OFFSET %d", ...$params ) // phpcs:ignore
		) ?: [];

		return array_map( [ __CLASS__, 'decorar' ], $results );
	}

	public static function obtener_por_id( int $id ): ?object {
		global $wpdb;
		return self::decorar(
			$wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::tabla() . " WHERE id = %d LIMIT 1", $id ) ) ?: null // phpcs:ignore
		);
	}

	public static function obtener_por_user_id( int $user_id ): ?object {
		global $wpdb;
		return self::decorar(
			$wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::tabla() . " WHERE user_id = %d LIMIT 1", $user_id ) ) ?: null // phpcs:ignore
		);
	}

	public static function obtener_por_dni( string $dni ): ?object {
		global $wpdb;
		return self::decorar(
			$wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::tabla() . " WHERE dni = %s LIMIT 1", $dni ) ) ?: null // phpcs:ignore
		);
	}

	/* ── CRUD bajo nivel ────────────────────────────────────────────── */

	public static function crear( array $datos ): int|\WP_Error {
		global $wpdb;

		$v = self::validar_formato( $datos );
		if ( is_wp_error( $v ) ) return $v;

		// Duplicados — en creación no hay ID que excluir
		$dup = self::chequear_duplicados( $datos['dni'], $datos['brevete'] ?? '', null );
		if ( is_wp_error( $dup ) ) return $dup;

		$wpdb->insert( self::tabla(), [
			'nombres'  => sanitize_text_field( $datos['nombres'] ),
			'apellidos'=> sanitize_text_field( $datos['apellidos'] ?? '' ),
			'dni'      => sanitize_text_field( $datos['dni'] ),
			'brevete'  => strtoupper( sanitize_text_field( $datos['brevete'] ) ),
			'telefono' => sanitize_text_field( $datos['telefono'] ?? '' ) ?: null,
			'email'    => sanitize_email( $datos['email'] ?? '' ) ?: null,
			'user_id'  => ! empty( $datos['user_id'] ) ? (int) $datos['user_id'] : null,
			'estado'   => 'activo',
		], [ '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' ] );

		if ( ! $wpdb->insert_id ) {
			return new \WP_Error( 'db_error', 'Error al guardar en base de datos.' );
		}
		return (int) $wpdb->insert_id;
	}

	public static function actualizar( int $id, array $datos ): true|\WP_Error {
		global $wpdb;

		$v = self::validar_formato( $datos );
		if ( is_wp_error( $v ) ) return $v;

		// Duplicados — excluir el registro actual
		$dup = self::chequear_duplicados( $datos['dni'], $datos['brevete'] ?? '', $id );
		if ( is_wp_error( $dup ) ) return $dup;

		$row = [
			'nombres'  => sanitize_text_field( $datos['nombres'] ),
			'apellidos'=> sanitize_text_field( $datos['apellidos'] ?? '' ),
			'dni'      => sanitize_text_field( $datos['dni'] ),
			'brevete'  => strtoupper( sanitize_text_field( $datos['brevete'] ) ),
			'telefono' => sanitize_text_field( $datos['telefono'] ?? '' ) ?: null,
			'email'    => sanitize_email( $datos['email'] ?? '' ) ?: null,
		];
		if ( array_key_exists( 'user_id', $datos ) ) {
			$row['user_id'] = ! empty( $datos['user_id'] ) ? (int) $datos['user_id'] : null;
		}
		if ( array_key_exists( 'estado', $datos ) ) {
			$row['estado'] = $datos['estado'];
		}

		$resultado = $wpdb->update( self::tabla(), $row, [ 'id' => $id ],
			array_fill( 0, count( $row ), '%s' ), [ '%d' ] );

		if ( false === $resultado ) {
			return new \WP_Error( 'db_error', 'Error al actualizar.' );
		}

		// Sincronizar usuario WP vinculado
		$t = self::obtener_por_id( $id );
		if ( $t && ! empty( $t->user_id ) ) {
			self::sincronizar_usuario_wp( (int) $t->user_id, $datos );
		}

		return true;
	}

	public static function cambiar_estado( int $id, string $estado ): true|\WP_Error {
		global $wpdb;
		if ( ! in_array( $estado, [ 'activo', 'inactivo' ], true ) ) return true;

		// Si se quiere ACTIVAR: verificar que el DNI no esté activo en OTRO transportista
		if ( $estado === 'activo' ) {
			$t = self::obtener_por_id( $id );
			if ( $t ) {
				$por_dni = self::obtener_por_dni( $t->dni );
				if ( $por_dni && (int) $por_dni->id !== $id && $por_dni->estado === 'activo' ) {
					$nombre = self::nombre_completo( $por_dni );
					return new \WP_Error(
						'dni_activo',
						sprintf( 'El DNI %s ya esta activo en otro transportista: %s.', $t->dni, $nombre )
					);
				}
			}
		}

		$wpdb->update( self::tabla(), [ 'estado' => $estado ], [ 'id' => $id ], [ '%s' ], [ '%d' ] ); // phpcs:ignore
		return true;
	}

	/* ── Sincronizar datos hacia el usuario WP ──────────────────────── */

	public static function sincronizar_usuario_wp( int $user_id, array $datos ): void {
		$nombre_completo = trim( ( $datos['nombres'] ?? '' ) . ' ' . ( $datos['apellidos'] ?? '' ) );
		wp_update_user( [
			'ID'           => $user_id,
			'display_name' => $nombre_completo,
		] );
		if ( ! empty( $datos['dni'] ) )      update_user_meta( $user_id, 'wpcc_dni',      sanitize_text_field( $datos['dni'] ) );
		if ( ! empty( $datos['brevete'] ) )  update_user_meta( $user_id, 'wpcc_brevete',  strtoupper( sanitize_text_field( $datos['brevete'] ) ) );
		if ( isset( $datos['telefono'] ) )   update_user_meta( $user_id, 'wpcc_telefono', sanitize_text_field( $datos['telefono'] ) );
		if ( ! empty( $datos['nombres'] ) )  update_user_meta( $user_id, 'first_name',    sanitize_text_field( $datos['nombres'] ) );
		if ( isset( $datos['apellidos'] ) )  update_user_meta( $user_id, 'last_name',     sanitize_text_field( $datos['apellidos'] ) );
	}

	/* ── sincronizar_desde_usuario ──────────────────────────────────────
	 *
	 * REGLAS DE NEGOCIO:
	 *
	 *  1) El user_id ya tiene transportista ACTIVO vinculado
	 *     -> Actualizar datos.
	 *
	 *  2) El user_id ya tiene transportista INACTIVO vinculado
	 *     -> Reactivar y actualizar datos.
	 *
	 *  3) El DNI existe en otro transportista ACTIVO con usuario vinculado
	 *     -> BLOQUEAR: ese DNI ya esta activo en otra persona.
	 *
	 *  4) El DNI existe en otro transportista INACTIVO
	 *     -> Reactivar ese registro, vincular este user_id, actualizar datos.
	 *        La persona real (el DNI) regreso como driver.
	 *
	 *  5) DNI completamente libre -> crear nuevo registro.
	 * ────────────────────────────────────────────────────────────────── */
	public static function sincronizar_desde_usuario( int $user_id, array $datos ): int|\WP_Error {
		global $wpdb;
		$tabla = self::tabla();
		$dni   = sanitize_text_field( $datos['dni'] ?? '' );

		// REGLAS 1 y 2: este user ya tiene transportista
		$existente = self::obtener_por_user_id( $user_id );
		if ( $existente ) {
			if ( $existente->estado === 'inactivo' ) {
				$wpdb->update( $tabla, [ 'estado' => 'activo' ], [ 'id' => (int) $existente->id ], [ '%s' ], [ '%d' ] ); // phpcs:ignore
			}
			$result = self::actualizar( (int) $existente->id, $datos );
			return $result === true ? (int) $existente->id : $result;
		}

		// REGLAS 3 y 4: existe otro transportista con ese DNI
		$por_dni = self::obtener_por_dni( $dni );
		if ( $por_dni ) {
			$otro_user_id = (int) ( $por_dni->user_id ?? 0 );

			// Verificar si ese user_id referenciado existe en WP Y sigue siendo driver
			$otro_user_wp     = $otro_user_id > 0 ? get_userdata( $otro_user_id ) : false;
			$otro_es_driver   = $otro_user_wp && in_array( 'wpcargo_driver', (array) $otro_user_wp->roles, true );
			$es_usuario_rival = $otro_user_id > 0 && $otro_user_id !== $user_id && $otro_user_wp !== false && $otro_es_driver;

			// REGLA 3: DNI activo + otro usuario WP driver existente -> bloquear
			if ( $es_usuario_rival && $por_dni->estado === 'activo' ) {
				$nombre = self::nombre_completo( $por_dni );
				return new \WP_Error(
					'dni_en_uso',
					sprintf(
						'El DNI %s ya pertenece a %s (activo). No se puede asignar a otro usuario.',
						esc_html( $dni ),
						esc_html( $nombre )
					)
				);
			}

			// REGLA 4: transportista inactivo, sin user, con user eliminado, o user sin rol driver
			// -> reasignar al nuevo usuario
			$wpdb->update(
				$tabla,
				[
					'user_id'  => $user_id,
					'estado'   => 'activo',
					'nombres'  => sanitize_text_field( $datos['nombres']   ?? $por_dni->nombres ),
					'apellidos'=> sanitize_text_field( $datos['apellidos'] ?? $por_dni->apellidos ),
					'brevete'  => strtoupper( sanitize_text_field( $datos['brevete'] ?? $por_dni->brevete ) ),
					'telefono' => sanitize_text_field( $datos['telefono']  ?? $por_dni->telefono ?? '' ) ?: null,
					'email'    => sanitize_email( $datos['email']          ?? $por_dni->email    ?? '' ) ?: null,
				],
				[ 'id' => (int) $por_dni->id ],
				[ '%d', '%s', '%s', '%s', '%s', '%s', '%s' ],
				[ '%d' ]
			);
			self::sincronizar_usuario_wp( $user_id, $datos );
			return (int) $por_dni->id;
		}

		// REGLA 5: DNI libre -> crear
		$datos['user_id'] = $user_id;
		return self::crear( $datos );
	}


	/* ── Validaciones ────────────────────────────────────────────────── */

	/**
	 * Valida solo formato (campos obligatorios, 8 dígitos, etc).
	 * NO valida duplicados — eso lo hace chequear_duplicados().
	 */
	private static function validar_formato( array $datos ): true|\WP_Error {
		if ( empty( trim( $datos['nombres'] ?? '' ) ) )
			return new \WP_Error( 'nombres_requerido', 'Los nombres son obligatorios.' );

		if ( empty( trim( $datos['apellidos'] ?? '' ) ) )
			return new \WP_Error( 'apellidos_requerido', 'Los apellidos son obligatorios.' );

		if ( empty( trim( $datos['dni'] ?? '' ) ) )
			return new \WP_Error( 'dni_requerido', 'El DNI es obligatorio.' );
		if ( ! preg_match( '/^\d{8}$/', $datos['dni'] ) )
			return new \WP_Error( 'dni_formato', 'El DNI debe tener exactamente 8 dígitos numéricos.' );

		$brevete = strtoupper( trim( $datos['brevete'] ?? '' ) );
		if ( empty( $brevete ) )
			return new \WP_Error( 'brevete_requerido', 'El brevete es obligatorio.' );
		if ( ! preg_match( '/^[A-Z]{1,3}-\d{4,5}$/', $brevete ) )
			return new \WP_Error( 'brevete_formato', 'Formato de brevete inválido. Ej: A-2345, Q3-12345.' );

		if ( ! empty( $datos['telefono'] ) && ! preg_match( '/^\d{9}$/', $datos['telefono'] ) )
			return new \WP_Error( 'telefono_formato', 'El teléfono debe tener exactamente 9 dígitos numéricos.' );

		return true;
	}

	/**
	 * Chequea duplicados de DNI y Brevete.
	 * $excluir_id = ID del transportista actual cuando se edita (para no bloquearse a sí mismo).
	 */
	public static function chequear_duplicados( string $dni, string $brevete, ?int $excluir_id ): true|\WP_Error {
		global $wpdb;
		$tabla   = self::tabla();
		$brevete = strtoupper( trim( $brevete ) );

		// Solo bloquear si el duplicado esta ACTIVO
		// Los inactivos pueden reasignarse via sincronizar_desde_usuario
		if ( $dni ) {
			$q   = $excluir_id
				? $wpdb->prepare( "SELECT id, nombres, apellidos FROM {$tabla} WHERE dni = %s AND estado = 'activo' AND id != %d LIMIT 1", $dni, $excluir_id ) // phpcs:ignore
				: $wpdb->prepare( "SELECT id, nombres, apellidos FROM {$tabla} WHERE dni = %s AND estado = 'activo' LIMIT 1", $dni ); // phpcs:ignore
			$dup = $wpdb->get_row( $q );
			if ( $dup ) {
				$nombre = trim( "{$dup->nombres} {$dup->apellidos}" );
				return new \WP_Error( 'dni_duplicado',
					sprintf( 'El DNI %s ya está activo para: %s.', $dni, $nombre )
				);
			}
		}

		if ( $brevete ) {
			$q   = $excluir_id
				? $wpdb->prepare( "SELECT id, nombres, apellidos FROM {$tabla} WHERE brevete = %s AND estado = 'activo' AND id != %d LIMIT 1", $brevete, $excluir_id ) // phpcs:ignore
				: $wpdb->prepare( "SELECT id, nombres, apellidos FROM {$tabla} WHERE brevete = %s AND estado = 'activo' LIMIT 1", $brevete ); // phpcs:ignore
			$dup = $wpdb->get_row( $q );
			if ( $dup ) {
				$nombre = trim( "{$dup->nombres} {$dup->apellidos}" );
				return new \WP_Error( 'brevete_duplicado',
					sprintf( 'El Brevete %s ya está activo para: %s.', $brevete, $nombre )
				);
			}
		}

		return true;
	}
}
