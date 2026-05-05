<?php
// Script para debugging de ubigeos
// Accede a: http://tudominio.local/wp-content/plugins/wp-cargo-facturacion/debug-ubigeos.php

if ( ! defined( 'ABSPATH' ) ) {
	// Cargar WordPress
	require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';
}

global $wpdb;
$table = $wpdb->prefix . 'ubigeos';

echo '<h1>Debug Ubigeos</h1>';
echo '<p>Tabla: ' . $table . '</p>';

// Contar registros
$count = $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
echo '<p>Total registros: ' . $count . '</p>';

// Buscar registro específico (16-02-02)
echo '<h2>Buscando 16-02-02</h2>';
$result = $wpdb->get_row( $wpdb->prepare(
	"SELECT * FROM `{$table}` 
	WHERE departamento = %s AND provincia = %s AND distrito = %s
	LIMIT 1",
	'16',
	'02',
	'02'
) );

if ( $result ) {
	echo '<pre>' . print_r( $result, true ) . '</pre>';
} else {
	echo '<p>No encontrado</p>';
}

// Buscar todos los departamento 16
echo '<h2>Todos en departamento 16</h2>';
$results = $wpdb->get_results( "SELECT * FROM `{$table}` WHERE departamento = '16' LIMIT 20" );
echo '<pre>' . print_r( $results, true ) . '</pre>';

// Buscar todos los con provincia 02 en departamento 16
echo '<h2>Provincia 02 en departamento 16</h2>';
$results = $wpdb->get_results( "SELECT * FROM `{$table}` WHERE departamento = '16' AND provincia = '02' LIMIT 20" );
echo '<pre>' . print_r( $results, true ) . '</pre>';

?>
