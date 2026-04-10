<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPCV_Database {

	public static function tabla(): string {
		global $wpdb;
		return $wpdb->prefix . 'wpcv_viaticos';
	}

	public static function tabla_gastos(): string {
		global $wpdb;
		return $wpdb->prefix . 'wpcv_gastos';
	}

	public static function tabla_transportistas(): ?string {
		global $wpdb;
		$nombre    = $wpdb->prefix . 'wpcc_transportistas';
		$resultado = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $nombre ) );
		if ( ! empty( $resultado ) && strtolower( $resultado ) === strtolower( $nombre ) ) {
			return $nombre;
		}
		return null;
	}

	public static function crear_tablas(): void {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Tabla viáticos
		$tv = self::tabla();
		dbDelta( "CREATE TABLE {$tv} (
			id                 BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			transportista_id   BIGINT(20) UNSIGNED NOT NULL,
			ruta               VARCHAR(200)        NOT NULL,
			monto_asignado     DECIMAL(10,2)       NOT NULL,
			monto_usado        DECIMAL(10,2)       NOT NULL DEFAULT 0.00,
			fecha_asignacion   DATE                NOT NULL,
			estado             ENUM('activo','cerrado') NOT NULL DEFAULT 'activo',
			notas              TEXT                         DEFAULT NULL,
			creado_por         BIGINT(20) UNSIGNED NOT NULL,
			fecha_creacion     DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			fecha_update       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_transportista (transportista_id),
			KEY idx_estado (estado),
			KEY idx_fecha (fecha_asignacion)
		) {$charset};" );

		// Tabla gastos (Historia 1.3)
		$tg = self::tabla_gastos();
		dbDelta( "CREATE TABLE {$tg} (
			id             BIGINT(20) UNSIGNED  NOT NULL AUTO_INCREMENT,
			viatico_id     BIGINT(20) UNSIGNED  NOT NULL,
			tipo           VARCHAR(50)          NOT NULL,
			monto          DECIMAL(10,2)        NOT NULL,
			descripcion    VARCHAR(300)                  DEFAULT NULL,
			sustento_url   VARCHAR(500)                  DEFAULT NULL,
			sustento_tipo  ENUM('imagen','pdf','otro')   DEFAULT NULL,
			registrado_por BIGINT(20) UNSIGNED  NOT NULL,
			fecha_gasto    DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_viatico (viatico_id),
			KEY idx_fecha   (fecha_gasto)
		) {$charset};" );

		update_option( 'wpcv_db_version', WPCV_VERSION );
	}
}
