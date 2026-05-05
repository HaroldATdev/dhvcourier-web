<?php
/**
 * Instalación de tablas personalizadas para ubicaciones
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Crea las tablas de ubicaciones (departamentos, provincias, distritos)
 * Se ejecuta una sola vez al activar el plugin
 */
function wpfc_create_ubicaciones_tables() {
	global $wpdb;
	
	$charset_collate = $wpdb->get_charset_collate();
	
	// ===== NUEVA TABLA UNIFICADA DE UBIGEOS (más simple y directa) =====
	$table_ubigeos = $wpdb->prefix . 'ubigeos';
	$sql_ubigeos = "
		CREATE TABLE IF NOT EXISTS `{$table_ubigeos}` (
			`id` INT AUTO_INCREMENT PRIMARY KEY,
			`departamento` VARCHAR(2) NOT NULL,
			`provincia` VARCHAR(2) NOT NULL,
			`distrito` VARCHAR(2) NOT NULL,
			`nombre` VARCHAR(100) NOT NULL,
			UNIQUE KEY uk_ubicacion (departamento, provincia, distrito),
			KEY idx_dept (departamento),
			KEY idx_prov (departamento, provincia),
			INDEX idx_dist (departamento, provincia, distrito)
		) $charset_collate;
	";
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql_ubigeos );
	
	// Insertar datos si la tabla está vacía
	$count = $wpdb->get_var( "SELECT COUNT(*) FROM `{$table_ubigeos}`" );
	if ( $count == 0 ) {
		wpfc_insert_ubigeos_desde_array();
	}
}

/**
 * Inserta datos en la tabla unificada de ubigeos
 * Lee del array obtenido desde ubigeos.php (con formato plano)
 */
function wpfc_insert_ubigeos_desde_array() {
        global $wpdb;

        $table = $wpdb->prefix . 'ubigeos';
	// Incluir array de ubigeos desde ubigeos.php
	// Ahora devuelve un array plano: array(
	//   array('departamento' => '01', 'provincia' => '01', 'distrito' => '01', 'nombre' => 'Chachapoyas'),
	//   ...
	// )
	$ubigeos_data = include( WPC_FACTURACION_PATH . 'includes/ubigeos.php' );
	
	if ( empty( $ubigeos_data ) || ! is_array( $ubigeos_data ) ) {
		return; // Sin datos para insertar
	}
	
	// Insertar cada registro directo desde el array plano
	foreach ( $ubigeos_data as $ubigeo ) {
		if ( isset( $ubigeo['departamento'], $ubigeo['provincia'], $ubigeo['distrito'], $ubigeo['nombre'] ) ) {
			$wpdb->insert(
				$table,
				array(
					'departamento' => $ubigeo['departamento'],
					'provincia'    => $ubigeo['provincia'],
					'distrito'     => $ubigeo['distrito'],
					'nombre'       => $ubigeo['nombre']
				),
				array( '%s', '%s', '%s', '%s' )
			);
		}
	}
}

/**
 * Consulta departamentos desde la BD - DEPRECATED
 * Usar wpcfact_get_departamentos() de ubigeos.php en su lugar
 */
function wpfc_get_departamentos() {
	return wpcfact_get_departamentos();
}

/**
 * Consulta provincias por departamento desde la BD - DEPRECATED
 * Usar wpcfact_get_provincias() de ubigeos.php en su lugar
 */
function wpfc_get_provincias( $dept_codigo ) {
	return wpcfact_get_provincias( $dept_codigo );
}

/**
 * Consulta distritos por provincia y departamento desde la BD - DEPRECATED
 * Usar wpcfact_get_distritos() de ubigeos.php en su lugar
 */
function wpfc_get_distritos( $prov_codigo, $dept_codigo = '' ) {
	return wpcfact_get_distritos( $dept_codigo, $prov_codigo );
}

// Ejecutar al activar el plugin
register_activation_hook( WPC_FACTURACION_FILE, 'wpfc_create_ubicaciones_tables' );
