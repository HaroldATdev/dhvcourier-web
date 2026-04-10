<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPCO_Database {

	public static function tabla(): string {
		global $wpdb;
		return $wpdb->prefix . 'wpco_ordenes';
	}

	public static function tabla_transportistas(): ?string {
		global $wpdb;
		$nombre    = $wpdb->prefix . 'wpcc_transportistas';
		$resultado = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $nombre ) );
		if ( ! empty( $resultado ) && strtolower( $resultado ) === strtolower( $nombre ) ) return $nombre;
		return null;
	}

	public static function crear_tablas(): void {
		global $wpdb;
		$tabla   = self::tabla();
		$charset = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( "CREATE TABLE {$tabla} (
			id               BIGINT(20) UNSIGNED  NOT NULL AUTO_INCREMENT,
			codigo           VARCHAR(20)          NOT NULL UNIQUE,
			cliente          VARCHAR(200)         NOT NULL,
			origen           VARCHAR(200)         NOT NULL,
			destino          VARCHAR(200)         NOT NULL,
			peso             DECIMAL(10,3)        NOT NULL DEFAULT 0.000,
			cantidad         INT(10) UNSIGNED     NOT NULL DEFAULT 1,
			costo            DECIMAL(10,2)        NOT NULL DEFAULT 0.00,
			transportista_id BIGINT(20) UNSIGNED           DEFAULT NULL,
			estado           ENUM('Registrado','En transito','Entregado','Cancelado') NOT NULL DEFAULT 'Registrado',
			notas            TEXT                          DEFAULT NULL,
			creado_por       BIGINT(20) UNSIGNED  NOT NULL,
			fecha_creacion   DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP,
			fecha_update     DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uk_codigo (codigo),
			KEY idx_estado (estado),
			KEY idx_transportista (transportista_id),
			KEY idx_fecha (fecha_creacion)
		) {$charset};" );
		update_option( 'wpco_db_version', WPCO_VERSION );
	}
}
