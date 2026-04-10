<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPCVH_Database {

	public static function tabla_vehiculos(): string {
		global $wpdb;
		return $wpdb->prefix . 'wpcvh_vehiculos';
	}

	public static function tabla_mantenimientos(): string {
		global $wpdb;
		return $wpdb->prefix . 'wpcvh_mantenimientos';
	}

	public static function tabla_km_log(): string {
		global $wpdb;
		return $wpdb->prefix . 'wpcvh_km_log';
	}

	public static function instalar(): void {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();

		// Historia 2.4 – Vehículos: placa única, km inicial, tipo, estado
		$tv = self::tabla_vehiculos();
		$sql_v = "CREATE TABLE IF NOT EXISTS {$tv} (
			id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
			placa           VARCHAR(20)  NOT NULL,
			tipo            VARCHAR(50)  NOT NULL,
			marca           VARCHAR(80)  DEFAULT NULL,
			modelo          VARCHAR(80)  DEFAULT NULL,
			anio            SMALLINT     DEFAULT NULL,
			km_inicial      INT UNSIGNED NOT NULL DEFAULT 0,
			km_actual       INT UNSIGNED NOT NULL DEFAULT 0,
			km_limite_mant  INT UNSIGNED NOT NULL DEFAULT 5000,
			km_ultimo_mant  INT UNSIGNED NOT NULL DEFAULT 0,
			estado          ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
			notas           TEXT         DEFAULT NULL,
			fecha_registro  DATE         NOT NULL,
			creado_por      BIGINT UNSIGNED DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY placa (placa),
			KEY estado (estado)
		) {$charset};";

		// Historia 2.5 – Mantenimientos realizados
		$tm = self::tabla_mantenimientos();
		$sql_m = "CREATE TABLE IF NOT EXISTS {$tm} (
			id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
			vehiculo_id    INT UNSIGNED  NOT NULL,
			tipo_mant      VARCHAR(100)  NOT NULL,
			km_al_momento  INT UNSIGNED  NOT NULL,
			costo          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
			descripcion    TEXT          DEFAULT NULL,
			realizado_en   DATE          NOT NULL,
			registrado_por BIGINT UNSIGNED DEFAULT NULL,
			PRIMARY KEY (id),
			KEY vehiculo_id (vehiculo_id),
			KEY realizado_en (realizado_en)
		) {$charset};";

		// Log de actualizaciones de kilometraje
		$tk = self::tabla_km_log();
		$sql_k = "CREATE TABLE IF NOT EXISTS {$tk} (
			id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
			vehiculo_id    INT UNSIGNED NOT NULL,
			km_anterior    INT UNSIGNED NOT NULL,
			km_nuevo       INT UNSIGNED NOT NULL,
			descripcion    VARCHAR(200) DEFAULT NULL,
			fecha          DATE         NOT NULL,
			registrado_por BIGINT UNSIGNED DEFAULT NULL,
			PRIMARY KEY (id),
			KEY vehiculo_id (vehiculo_id),
			KEY fecha (fecha)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_v );
		dbDelta( $sql_m );
		dbDelta( $sql_k );
	}
}
