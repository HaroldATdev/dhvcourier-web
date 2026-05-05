<?php
/**
 * Función para insertar datos de ubigeos en la base de datos
 * Datos obtenidos del repo: https://github.com/AngelFQC/ubigeo-peru
 */

function wpcfact_insert_ubigeos() {
	global $wpdb;
	
	$table = $wpdb->prefix . 'hEhUP_ubigeos';
	
	// Datos de ubigeos (formato simplificado para inserción rápida)
	// Estructura: departamento|provincia|distrito|nombre
	$ubigeos = array(
		// Amazonas
		'01|00|00|Amazonas',
		'01|01|00|Chachapoyas',
		'01|01|01|Chachapoyas',
		'01|01|02|Asunción',
		'01|01|03|Balsas',
		'01|01|04|Cheto',
		'01|01|05|Chiliquin',
		'01|01|06|Chuquibamba',
		'01|01|07|Granada',
		'01|01|08|Huancas',
		'01|01|09|La Jalca',
		'01|01|10|Leimebamba',
		'01|01|11|Levanto',
		'01|01|12|Magdalena',
		'01|01|13|Mariscal Castilla',
		'01|01|14|Molinopampa',
		'01|01|15|Montevideo',
		'01|01|16|Olleros',
		'01|01|17|Quinjalca',
		'01|01|18|San Francisco de Daguas',
		'01|01|19|San Isidro de Maino',
		'01|01|20|Soloco',
		'01|01|21|Sonche',
		// Lima (principales)
		'14|00|00|Lima',
		'14|01|00|Lima',
		'14|01|01|Lima',
		'14|01|02|Ancón',
		'14|01|03|Ate',
		'14|01|04|Breña',
		'14|01|05|Carabayllo',
		'14|01|06|Comas',
		'14|01|07|Chaclacayo',
		'14|01|08|Chorrillos',
		'14|01|09|La Victoria',
		'14|01|10|La Molina',
		'14|01|11|Lince',
		'14|01|12|Lurigancho',
		'14|01|13|Lurín',
		'14|01|14|Magdalena',
		'14|01|15|Miraflores',
		'14|01|16|Pachacamac',
		'14|01|17|Pucusana',
		'14|01|18|Puente Piedra',
		'14|01|19|Punta Hermosa',
		'14|01|20|Punta Negra',
		'14|01|21|Rímac',
		'14|01|22|San Bartolo',
		'14|01|23|San Isidro',
		'14|01|24|San Juan de Miraflores',
		'14|01|25|San Juan de Lurigancho',
		'14|01|26|San Miguel',
		'14|01|27|Santa María del Mar',
		'14|01|28|Santa Rosa',
		'14|01|29|Santiago de Surco',
		'14|01|30|Surquillo',
		'14|01|31|Villa María del Triunfo',
		'14|01|32|Jesús María',
		'14|01|33|Independencia',
		'14|01|34|El Agustino',
		'14|01|35|San Borja',
		'14|01|36|Villa El Salvador',
		'14|01|37|Los Olivos',
		'14|01|38|Santa Anita',
	);
	
	foreach ( $ubigeos as $ubigeo_str ) {
		$parts = explode( '|', $ubigeo_str );
		if ( count( $parts ) === 4 ) {
			list( $dept, $prov, $dist, $name ) = $parts;
			
			// Verificar si ya existe
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM `{$table}` WHERE departamento = %s AND provincia = %s AND distrito = %s",
				$dept, $prov, $dist
			) );
			
			if ( ! $exists ) {
				$wpdb->insert(
					$table,
					array(
						'departamento' => $dept,
						'provincia'    => $prov,
						'distrito'     => $dist,
						'nombre'       => $name,
					),
					array( '%s', '%s', '%s', '%s' )
				);
			}
		}
	}
}
