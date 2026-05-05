<?php
// Debug script - accede a: http://tudominio.local/wp-content/plugins/wp-cargo-facturacion/test-ubicacion.php

if ( ! defined( 'ABSPATH' ) ) {
	require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';
}

// Cargar función
require_once dirname( __FILE__ ) . '/includes/ubigeos.php';

echo '<h1>Test wpcfact_get_ubicacion_nombre()</h1>';

// Test con los datos del payload
$tests = array(
	array( 'dept' => '15', 'prov' => '01', 'dist' => '16', 'desc' => 'Partida (Lima)' ),
	array( 'dept' => '17', 'prov' => '01', 'dist' => '02', 'desc' => 'Llegada (Ica)' ),
	array( 'dept' => '15', 'prov' => '00', 'dist' => '00', 'desc' => 'Dept 15 solo' ),
	array( 'dept' => '17', 'prov' => '00', 'dist' => '00', 'desc' => 'Dept 17 solo' ),
);

foreach ( $tests as $test ) {
	echo '<hr>';
	echo '<h3>' . $test['desc'] . ' (' . $test['dept'] . '-' . $test['prov'] . '-' . $test['dist'] . ')</h3>';
	
	$result = wpcfact_get_ubicacion_nombre( $test['dept'], $test['prov'], $test['dist'] );
	echo '<p>Resultado: <strong>' . ( empty( $result ) ? '[VACÍO]' : $result ) . '</strong></p>';
	
	// Verificar directamente en BD
	global $wpdb;
	$table = $wpdb->prefix . 'ubigeos';
	$db_result = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM `{$table}` 
		WHERE departamento = %s AND provincia = %s AND distrito = %s",
		$test['dept'],
		$test['prov'],
		$test['dist']
	) );
	
	if ( $db_result ) {
		echo '<p>BD encontró: <strong>' . $db_result->nombre . '</strong></p>';
	} else {
		echo '<p>BD: <strong>NO ENCONTRADO</strong></p>';
	}
}

// Contar totales
echo '<hr>';
echo '<h2>Estadísticas BD</h2>';
$count = $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->prefix}ubigeos`" );
echo '<p>Total ubigeos en BD: <strong>' . $count . '</strong></p>';

$dept_15 = $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->prefix}ubigeos` WHERE departamento = '15'" );
echo '<p>Registros dept 15: <strong>' . $dept_15 . '</strong></p>';

$dept_17 = $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->prefix}ubigeos` WHERE departamento = '17'" );
echo '<p>Registros dept 17: <strong>' . $dept_17 . '</strong></p>';

?>
