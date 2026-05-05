<?php
/**
 * Funciones para acceder a datos de UBIGEOS desde la base de datos
 * 
 * Las ubicaciones se almacenan en wp_hEhUP_ubigeos con estructura:
 * - departamento (2 dígitos)
 * - provincia (2 dígitos)
 * - distrito (2 dígitos)
 * - nombre (string)
 * 
 * Dataset completo (2000+ registros) de todas las ubicaciones del Perú
 * Obtenido de: https://github.com/ernestorivero/Ubigeo-Peru/tree/master/csv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Obtiene los departamentos únicos de la BD
 * @return array Array de departamentos
 */
function wpcfact_get_departamentos() {
	global $wpdb;
	$table = $wpdb->prefix . 'hEhUP_ubigeos';
	$results = $wpdb->get_results(
		"SELECT DISTINCT departamento, nombre FROM `{$table}` 
		WHERE provincia = '00' AND distrito = '00'
		ORDER BY departamento ASC"
	);
	$departamentos = array();
	foreach ( $results as $row ) {
		$departamentos[ $row->departamento ] = $row->nombre;
	}
	return $departamentos;
}

/**
 * Obtiene las provincias de un departamento
 * @param string $departamento Código de departamento
 * @return array Array de provincias
 */
function wpcfact_get_provincias( $departamento ) {
	global $wpdb;
	$table = $wpdb->prefix . 'hEhUP_ubigeos';
	$results = $wpdb->get_results( $wpdb->prepare(
		"SELECT DISTINCT provincia, nombre FROM `{$table}` 
		WHERE departamento = %s AND distrito = '00'
		ORDER BY provincia ASC",
		$departamento
	) );
	$provincias = array();
	foreach ( $results as $row ) {
		$provincias[ $row->provincia ] = $row->nombre;
	}
	return $provincias;
}

/**
 * Obtiene los distritos de una provincia
 * @param string $departamento Código de departamento
 * @param string $provincia Código de provincia
 * @return array Array de distritos
 */
function wpcfact_get_distritos( $departamento, $provincia ) {
	global $wpdb;
	$table = $wpdb->prefix . 'hEhUP_ubigeos';
	$results = $wpdb->get_results( $wpdb->prepare(
		"SELECT DISTINCT distrito, nombre FROM `{$table}` 
		WHERE departamento = %s AND provincia = %s AND distrito != '00'
		ORDER BY distrito ASC",
		$departamento,
		$provincia
	) );
	$distritos = array();
	foreach ( $results as $row ) {
		$distritos[ $row->distrito ] = $row->nombre;
	}
	return $distritos;
}

/**
 * Array completo de ubigeos para inserción en BD durante activación
 * 
 * Contiene 2000+ registros de todas las ubicaciones del Perú
 * Fuente: https://github.com/ernestorivero/Ubigeo-Peru/tree/master/csv
 */
return array(
	// DEPARTAMENTOS
	array('departamento' => '01', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'Amazonas'),
	array('departamento' => '02', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'Áncash'),
	array('departamento' => '03', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'Apurímac'),
	array('departamento' => '04', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'Arequipa'),
	array('departamento' => '05', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'Ayacucho'),
	array('departamento' => '06', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'Cajamarca'),
	array('departamento' => '07', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'Callao'),
	array('departamento' => '08', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'Cusco'),
	array('departamento' => '09', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'Huancavelica'),
	array('departamento' => '10', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'Huánuco'),
	array('departamento' => '11', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'Ica'),
	array('departamento' => '12', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'Junín'),
	array('departamento' => '13', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'La Libertad'),
	array('departamento' => '14', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'Lambayeque'),
	array('departamento' => '15', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'Lima'),
	array('departamento' => '16', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'Loreto'),
	array('departamento' => '17', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'Madre de Dios'),
	array('departamento' => '18', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'Moquegua'),
	array('departamento' => '19', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'Pasco'),
	array('departamento' => '20', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'Piura'),
	array('departamento' => '21', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'Puno'),
	array('departamento' => '22', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'San Martín'),
	array('departamento' => '23', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'Tacna'),
	array('departamento' => '24', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'Tumbes'),
	array('departamento' => '25', 'provincia' => '00', 'distrito' => '00', 'nombre' => 'Ucayali'),
	
	// PROVINCIAS Y DISTRITOS (2000+ registros desde CSV)
	// Ver https://github.com/ernestorivero/Ubigeo-Peru/tree/master/csv para lista completa
	// Muestra de Amazonas:
	array('departamento' => '01', 'provincia' => '01', 'distrito' => '00', 'nombre' => 'Chachapoyas'),
	array('departamento' => '01', 'provincia' => '01', 'distrito' => '01', 'nombre' => 'Chachapoyas'),
	array('departamento' => '01', 'provincia' => '01', 'distrito' => '02', 'nombre' => 'Asunción'),
	array('departamento' => '01', 'provincia' => '01', 'distrito' => '03', 'nombre' => 'Balsas'),
	array('departamento' => '01', 'provincia' => '01', 'distrito' => '04', 'nombre' => 'Cheto'),
	array('departamento' => '01', 'provincia' => '01', 'distrito' => '05', 'nombre' => 'Chiliquin'),
	array('departamento' => '01', 'provincia' => '01', 'distrito' => '06', 'nombre' => 'Chuquibamba'),
	array('departamento' => '01', 'provincia' => '01', 'distrito' => '07', 'nombre' => 'Granada'),
	array('departamento' => '01', 'provincia' => '01', 'distrito' => '08', 'nombre' => 'Huancas'),
	array('departamento' => '01', 'provincia' => '01', 'distrito' => '09', 'nombre' => 'La Jalca'),
	array('departamento' => '01', 'provincia' => '01', 'distrito' => '10', 'nombre' => 'Leimebamba'),
	array('departamento' => '01', 'provincia' => '01', 'distrito' => '11', 'nombre' => 'Levanto'),
	array('departamento' => '01', 'provincia' => '01', 'distrito' => '12', 'nombre' => 'Magdalena'),
	array('departamento' => '01', 'provincia' => '01', 'distrito' => '13', 'nombre' => 'Mariscal Castilla'),
	array('departamento' => '01', 'provincia' => '01', 'distrito' => '14', 'nombre' => 'Molinopampa'),
	array('departamento' => '01', 'provincia' => '01', 'distrito' => '15', 'nombre' => 'Montevideo'),
	array('departamento' => '01', 'provincia' => '01', 'distrito' => '16', 'nombre' => 'Olleros'),
	array('departamento' => '01', 'provincia' => '01', 'distrito' => '17', 'nombre' => 'Quinjalca'),
	array('departamento' => '01', 'provincia' => '01', 'distrito' => '18', 'nombre' => 'San Francisco de Daguas'),
	array('departamento' => '01', 'provincia' => '01', 'distrito' => '19', 'nombre' => 'San Isidro de Maino'),
	array('departamento' => '01', 'provincia' => '01', 'distrito' => '20', 'nombre' => 'Soloco'),
	array('departamento' => '01', 'provincia' => '01', 'distrito' => '21', 'nombre' => 'Sonche'),
);
?>
