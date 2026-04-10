<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPCC_Database {

	public static function tabla(): string {
		global $wpdb;
		return $wpdb->prefix . 'wpcc_transportistas';
	}

	public static function crear_tablas(): void {
		global $wpdb;
		$tabla   = self::tabla();
		$charset = $wpdb->get_charset_collate();

		// Sin UNIQUE keys en dni/brevete — la validación la maneja PHP
		// para permitir reactivar transportistas inactivos con el mismo DNI
		$sql = "CREATE TABLE {$tabla} (
			id             BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			nombres        VARCHAR(100)        NOT NULL DEFAULT '',
			apellidos      VARCHAR(100)        NOT NULL DEFAULT '',
			dni            VARCHAR(20)         NOT NULL,
			brevete        VARCHAR(20)         NOT NULL DEFAULT '',
			telefono       VARCHAR(20)                  DEFAULT NULL,
			email          VARCHAR(100)                 DEFAULT NULL,
			estado         ENUM('activo','inactivo')    NOT NULL DEFAULT 'activo',
			user_id        BIGINT(20) UNSIGNED          DEFAULT NULL,
			fecha_creacion DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			fecha_update   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_dni     (dni),
			KEY idx_brevete (brevete),
			KEY idx_estado  (estado),
			KEY idx_user    (user_id)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$tabla}", 0 ); // phpcs:ignore

		// Migración v1: codigo → brevete
		if ( in_array( 'codigo', $cols, true ) && ! in_array( 'brevete', $cols, true ) ) {
			$wpdb->query( "ALTER TABLE {$tabla} CHANGE COLUMN codigo brevete VARCHAR(20) NOT NULL DEFAULT ''" ); // phpcs:ignore
			$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$tabla}", 0 ); // phpcs:ignore
		}

		// Migración v4: nombre → nombres + apellidos
		if ( in_array( 'nombre', $cols, true ) && ! in_array( 'nombres', $cols, true ) ) {
			$wpdb->query( "ALTER TABLE {$tabla} ADD COLUMN nombres VARCHAR(100) NOT NULL DEFAULT '' AFTER id, ADD COLUMN apellidos VARCHAR(100) NOT NULL DEFAULT '' AFTER nombres" ); // phpcs:ignore
			$wpdb->query( "UPDATE {$tabla} SET nombres = SUBSTRING_INDEX(nombre, ' ', 1), apellidos = TRIM(SUBSTRING(nombre, LOCATE(' ', nombre) + 1))" ); // phpcs:ignore
			$wpdb->query( "ALTER TABLE {$tabla} DROP COLUMN nombre" ); // phpcs:ignore
		}

		// MIGRACIÓN CRÍTICA: eliminar UNIQUE keys de dni y brevete si existen
		// Esto SIEMPRE debe ejecutarse porque instalaciones anteriores los tienen
		self::eliminar_unique_keys();

		update_option( 'wpcc_db_version', WPCC_VERSION );
	}

	/**
	 * Elimina los UNIQUE KEYS de dni y brevete si existen.
	 * Se llama en cada carga del plugin (no solo en activación)
	 * para garantizar que instalaciones antiguas queden corregidas.
	 */
	public static function eliminar_unique_keys(): void {
		global $wpdb;
		$tabla = self::tabla();

		// Verificar cada key individualmente y eliminarlo si existe
		foreach ( [ 'uk_dni', 'uk_brevete' ] as $key_name ) {
			$existe = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
				"SELECT COUNT(*) FROM information_schema.statistics
				 WHERE table_schema = DATABASE()
				 AND table_name = %s
				 AND index_name = %s",
				$tabla,
				$key_name
			) );
			if ( $existe ) {
				$wpdb->query( "ALTER TABLE {$tabla} DROP INDEX `{$key_name}`" ); // phpcs:ignore
			}
		}
	}
}
