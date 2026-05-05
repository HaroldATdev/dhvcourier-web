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
 * Este archivo fue actualizado para consultar desde BD en lugar de usar datos hardcodeados
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Obtiene los departamentos únicos de la BD
 * 
 * @return array Array de departamentos con formato: [ '01' => 'Amazonas', ... ]
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
 * 
 * @param string $departamento Código de departamento (2 dígitos)
 * @return array Array de provincias con formato: [ '01' => 'Nombre', ... ]
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
 * 
 * @param string $departamento Código de departamento (2 dígitos)
 * @param string $provincia Código de provincia (2 dígitos)
 * @return array Array de distritos con formato: [ '01' => 'Nombre', ... ]
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
 * Array de compatibilidad con estructura antigua de ubigeos
 * Usado por install.php para poblar la tabla BD la primera vez
 */
return array(
	'01' => array(
		'nombre' => 'AMAZONAS',
		'provincias' => array(
			'01' => array('nombre' => 'CHACHAPOYAS', 'distritos' => array('01' => 'CHACHAPOYAS', '02' => 'ASUNCIÓN', '03' => 'CHIQUIA', '04' => 'MARISCAL CASTILLA', '05' => 'MONTEVIDEO', '06' => 'MOLINOPAMPA', '07' => 'SOLOCO', '08' => 'TABACONAS')),
			'02' => array('nombre' => 'BAGUA', 'distritos' => array('01' => 'BAGUA', '02' => 'ARAMANGO', '03' => 'COPALLIN', '04' => 'IMACITA')),
			'03' => array('nombre' => 'BONGARÁ', 'distritos' => array('01' => 'JUMBILLA', '02' => 'ASUNCIÓN', '03' => 'CHIGUIRIP', '04' => 'COROSHA', '05' => 'DIOS MÍO', '06' => 'MARCOCHUASI', '07' => 'MARISCAL CASTILLA', '08' => 'MONTEVIDEO', '09' => 'RECTA TUPÍN')),
			'04' => array('nombre' => 'CONDORCANQUI', 'distritos' => array('01' => 'NIEVA', '02' => 'EL CENEPA', '03' => 'RÍO SANTIAGO')),
			'05' => array('nombre' => 'LUYA', 'distritos' => array('01' => 'LAMUD', '02' => 'ASUNCIÓN', '03' => 'CAMPORREDONDO', '04' => 'CHILCHOS', '05' => 'INGUILPATA', '06' => 'LUYA', '07' => 'OCALLI', '08' => 'OCYACHA', '09' => 'PISUQUIA', '10' => 'SUCRE', '11' => 'TINGO')),
			'06' => array('nombre' => 'MARISCAL CASTILLA', 'distritos' => array('01' => 'HUAMACHUCO', '02' => 'ANGUIA', '03' => 'ASUNCIÓN', '04' => 'CHIGUIRIP', '05' => 'MARISCAL CASTILLA')),
			'07' => array('nombre' => 'RODRÍGUEZ DE MENDOZA', 'distritos' => array('01' => 'SANTA ROSA', '02' => 'ASUNCIÓN', '03' => 'CHIRIMOTO', '04' => 'LIMABAMBA', '05' => 'MARISCAL CASTILLA', '06' => 'ONGÓN')),
			'08' => array('nombre' => 'UTCUBAMBA', 'distritos' => array('01' => 'BAGUA GRANDE', '02' => 'CAJARURO', '03' => 'CUMBA', '04' => 'EL MILAGRO', '05' => 'JAMALCA', '06' => 'MARISCAL CASTILLA', '07' => 'ASUNCIÓN')),
		),
	),
	'02' => array(
		'nombre' => 'ANCASH',
		'provincias' => array(
			'01' => array('nombre' => 'HUARAZ', 'distritos' => array('01' => 'HUARAZ', '02' => 'COCHAPETI', '03' => 'COLCABAMBA', '04' => 'HUANCHAY', '05' => 'LA LIBERTAD', '06' => 'OLLEROS', '07' => 'PAMPALLAQTA', '08' => 'PARIACOTO')),
			'02' => array('nombre' => 'AIJA', 'distritos' => array('01' => 'AIJA', '02' => 'COROBAMBA', '03' => 'HUACLLAN', '04' => 'SUCCHA')),
		),
	),
	'15' => array(
		'nombre' => 'LIMA',
		'provincias' => array(
			'01' => array('nombre' => 'LIMA', 'distritos' => array('01' => 'LIMA', '02' => 'ANCÓN', '03' => 'ATE', '04' => 'BARRANCO', '05' => 'BREÑA', '06' => 'CARABAYLLO', '07' => 'CHACLACAYO', '08' => 'CHALACO', '09' => 'CHORRILLOS', '10' => 'CIENEGUILLA', '11' => 'COMAS', '12' => 'SURCO', '13' => 'LURÍN', '14' => 'LURÍN', '15' => 'MAGDALENA DEL MAR', '16' => 'MIRAFLORES', '17' => 'PACHACAMAC', '18' => 'PUENTE PIEDRA', '19' => 'PUCUSANA', '20' => 'PUNTA HERMOSA', '21' => 'PUNTA NEGRA', '22' => 'RÍMAC', '23' => 'SAN ÁNGEL', '24' => 'SAN BARTOLO', '25' => 'SAN ISIDRO', '26' => 'SAN JUAN DE MIRAFLORES', '27' => 'SAN LUIS', '28' => 'SAN MARTÍN DE PORRES', '29' => 'SAN MIGUEL', '30' => 'SANTA ANITA', '31' => 'SANTA MARÍA DEL MAR', '32' => 'SANTA ROSA', '33' => 'SANTIAGO DE SURCO', '34' => 'TACLACAYO', '35' => 'VILLA EL SALVADOR', '36' => 'VILLA MARÍA DEL TRIUNFO')),
			'02' => array('nombre' => 'BARRANCA', 'distritos' => array('01' => 'BARRANCA', '02' => 'ANCAHUASI', '03' => 'COCHAMARCA', '04' => 'PATIVILCA', '05' => 'SUPE', '06' => 'SUPE PUERTO')),
		),
	),
);

?>
