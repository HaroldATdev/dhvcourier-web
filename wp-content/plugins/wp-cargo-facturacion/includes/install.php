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
	$table_ubigeos = $wpdb->prefix . 'hEhUP_ubigeos';
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
	
	// ===== TABLAS ANTERIORES (mantenerlas para compatibilidad) =====
	$sql_departamentos = "
		CREATE TABLE IF NOT EXISTS `{$table_departamentos}` (
			`id` INT AUTO_INCREMENT PRIMARY KEY,
			`codigo` VARCHAR(5) UNIQUE NOT NULL,
			`nombre` VARCHAR(100) NOT NULL,
			`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
		) $charset_collate;
	";
	
	// Tabla de provincias
	$table_provincias = $wpdb->prefix . 'hEhUP_provincias';
	$sql_provincias = "
		CREATE TABLE IF NOT EXISTS `{$table_provincias}` (
			`id` INT AUTO_INCREMENT PRIMARY KEY,
			`codigo` VARCHAR(5) UNIQUE NOT NULL,
			`departamento_codigo` VARCHAR(5) NOT NULL,
			`nombre` VARCHAR(100) NOT NULL,
			`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			FOREIGN KEY (`departamento_codigo`) REFERENCES `{$table_departamentos}`(`codigo`)
		) $charset_collate;
	";
	
	// Tabla de distritos
	$table_distritos = $wpdb->prefix . 'hEhUP_distritos';
	$sql_distritos = "
		CREATE TABLE IF NOT EXISTS `{$table_distritos}` (
			`id` INT AUTO_INCREMENT PRIMARY KEY,
			`codigo` VARCHAR(5) UNIQUE NOT NULL,
			`provincia_codigo` VARCHAR(5) NOT NULL,
			`departamento_codigo` VARCHAR(5) NOT NULL,
			`nombre` VARCHAR(100) NOT NULL,
			`ubigeo` VARCHAR(6),
			`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			FOREIGN KEY (`provincia_codigo`) REFERENCES `{$table_provincias}`(`codigo`),
			FOREIGN KEY (`departamento_codigo`) REFERENCES `{$table_departamentos}`(`codigo`)
		) $charset_collate;
	";
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql_departamentos );
	dbDelta( $sql_provincias );
	dbDelta( $sql_distritos );
	
	// Verificar si ya hay datos
	$count = $wpdb->get_var( "SELECT COUNT(*) FROM `{$table_departamentos}`" );
	if ( $count === 0 ) {
		wpfc_insert_ubicaciones_data();
	}
}

/**
 * Inserta datos en la tabla unificada wp_hEhUP_ubigeos
 * Lee del array antiguo de ubigeos.php (que devuelve datos de compatibilidad)
 */
function wpfc_insert_ubigeos_desde_array() {
	global $wpdb;
	
	$table = $wpdb->prefix . 'hEhUP_ubigeos';
	
	// Incluir array de compatibilidad desde ubigeos.php
	$ubigeos_data = include( WPC_FACTURACION_PATH . 'includes/ubigeos.php' );
	
	// Si el array está vacío, usar datos de ejemplo
	if ( empty( $ubigeos_data ) ) {
		// Datos de ejemplo: el array de compatibilidad aún devuelve estos datos
		// No haremos nada, la tabla se llenará cuando el usuario use los selects
		return;
	}
	
	// Iterar y insertar en la tabla plana
	foreach ( $ubigeos_data as $dept_codigo => $dept_info ) {
		// Insertar entrada de departamento (provincia='00', distrito='00')
		$wpdb->insert(
			$table,
			array(
				'departamento' => $dept_codigo,
				'provincia' => '00',
				'distrito' => '00',
				'nombre' => $dept_info['nombre']
			),
			array( '%s', '%s', '%s', '%s' )
		);
		
		// Insertar provincias y distritos
		if ( ! empty( $dept_info['provincias'] ) ) {
			foreach ( $dept_info['provincias'] as $prov_codigo => $prov_info ) {
				// Insertar entrada de provincia (distrito='00')
				$wpdb->insert(
					$table,
					array(
						'departamento' => $dept_codigo,
						'provincia' => $prov_codigo,
						'distrito' => '00',
						'nombre' => $prov_info['nombre']
					),
					array( '%s', '%s', '%s', '%s' )
				);
				
				// Insertar distritos
				if ( ! empty( $prov_info['distritos'] ) ) {
					foreach ( $prov_info['distritos'] as $dist_codigo => $dist_nombre ) {
						$wpdb->insert(
							$table,
							array(
								'departamento' => $dept_codigo,
								'provincia' => $prov_codigo,
								'distrito' => $dist_codigo,
								'nombre' => $dist_nombre
							),
							array( '%s', '%s', '%s', '%s' )
						);
					}
				}
			}
		}
	}
}

/**
 * Inserta los datos de ubicaciones (del archivo ubigeos.php)
 */
function wpfc_insert_ubicaciones_data() {
	global $wpdb;
	
	$table_departamentos = $wpdb->prefix . 'hEhUP_departamentos';
	$table_provincias = $wpdb->prefix . 'hEhUP_provincias';
	$table_distritos = $wpdb->prefix . 'hEhUP_distritos';
	
	// Obtener datos del archivo ubigeos.php
	$ubigeos_data = include( WPC_FACTURACION_PATH . 'includes/ubigeos.php' );
	
	foreach ( $ubigeos_data as $dept_codigo => $dept_info ) {
		// Insertar departamento
		$wpdb->insert(
			$table_departamentos,
			array(
				'codigo' => $dept_codigo,
				'nombre' => $dept_info['nombre']
			),
			array( '%s', '%s' )
		);
		
		// Insertar provincias y distritos
		foreach ( $dept_info['provincias'] as $prov_codigo => $prov_info ) {
			$wpdb->insert(
				$table_provincias,
				array(
					'codigo' => $prov_codigo,
					'departamento_codigo' => $dept_codigo,
					'nombre' => $prov_info['nombre']
				),
				array( '%s', '%s', '%s' )
			);
			
			// Insertar distritos
			foreach ( $prov_info['distritos'] as $dist_codigo => $dist_nombre ) {
				$ubigeo = $dept_codigo . $prov_codigo . $dist_codigo;
				$wpdb->insert(
					$table_distritos,
					array(
						'codigo' => $dist_codigo,
						'provincia_codigo' => $prov_codigo,
						'departamento_codigo' => $dept_codigo,
						'nombre' => $dist_nombre,
						'ubigeo' => $ubigeo
					),
					array( '%s', '%s', '%s', '%s', '%s' )
				);
			}
		}
	}
}

/**
 * Consulta departamentos desde la BD
 */
function wpfc_get_departamentos() {
	global $wpdb;
	$table = $wpdb->prefix . 'hEhUP_departamentos';
	return $wpdb->get_results( "SELECT codigo, nombre FROM `{$table}` ORDER BY nombre", ARRAY_A );
}

/**
 * Consulta provincias por departamento desde la BD
 */
function wpfc_get_provincias( $dept_codigo ) {
	global $wpdb;
	$table = $wpdb->prefix . 'hEhUP_provincias';
	$dept_codigo = sanitize_text_field( $dept_codigo );
	return $wpdb->get_results( 
		$wpdb->prepare( 
			"SELECT codigo, nombre FROM `{$table}` WHERE departamento_codigo = %s ORDER BY nombre",
			$dept_codigo
		),
		ARRAY_A
	);
}

/**
 * Consulta distritos por provincia y departamento desde la BD
 */
function wpfc_get_distritos( $prov_codigo, $dept_codigo = '' ) {
	global $wpdb;
	$table = $wpdb->prefix . 'hEhUP_distritos';
	$prov_codigo = sanitize_text_field( $prov_codigo );
	
	if ( $dept_codigo ) {
		$dept_codigo = sanitize_text_field( $dept_codigo );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT codigo, nombre, ubigeo FROM `{$table}` WHERE provincia_codigo = %s AND departamento_codigo = %s ORDER BY nombre",
				$prov_codigo,
				$dept_codigo
			),
			ARRAY_A
		);
	} else {
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT codigo, nombre, ubigeo FROM `{$table}` WHERE provincia_codigo = %s ORDER BY nombre",
				$prov_codigo
			),
			ARRAY_A
		);
	}
}

// Ejecutar al activar el plugin
register_activation_hook( WPC_FACTURACION_FILE, 'wpfc_create_ubicaciones_tables' );
