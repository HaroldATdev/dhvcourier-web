<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPC_Facturacion_Database {

	public static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$table_comprobantes = $wpdb->prefix . 'facturacion_comprobantes';
		$table_envios       = $wpdb->prefix . 'facturacion_comprobante_envios';

		$sql = "CREATE TABLE $table_comprobantes (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			tipo varchar(2) NOT NULL,
			serie varchar(4) NOT NULL,
			correlativo varchar(8) NOT NULL,
			file_name varchar(60) NOT NULL,
			document_id varchar(60) NOT NULL,
			estado varchar(20) NOT NULL DEFAULT 'PENDIENTE',
			cliente_doc_tipo varchar(1) NOT NULL,
			cliente_doc_num varchar(15) NOT NULL,
			cliente_nombre varchar(150) NOT NULL,
			monto_base decimal(10,2) NOT NULL,
			igv decimal(10,2) NOT NULL,
			total decimal(10,2) NOT NULL,
			xml_url text NULL,
			cdr_url text NULL,
			pdf_url text NULL,
			faults text NULL,
			emitido_en datetime NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY document_id (document_id),
			KEY estado (estado)
		) $charset_collate;

		CREATE TABLE $table_envios (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			comprobante_id bigint(20) NOT NULL,
			shipment_id bigint(20) NOT NULL,
			monto decimal(10,2) NOT NULL,
			PRIMARY KEY  (id),
			KEY comprobante_id (comprobante_id),
			KEY shipment_id (shipment_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Crear tabla de ubicaciones (ubigeos)
		require_once WPC_FACTURACION_PATH . 'includes/install.php';
		wpfc_create_ubicaciones_tables();
	}
}
