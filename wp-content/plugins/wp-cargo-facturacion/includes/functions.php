<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Funciones Helper Globales

/**
 * Convierte un número a letras (para el cbc:Note de SUNAT)
 */
function wpcfact_numero_a_letras( $numero, $moneda = 'SOLES' ) {
	// Implementación básica (puedes reemplazarla con una librería más robusta si es necesario)
	$entero = floor( $numero );
	$decimal = round( ( $numero - $entero ) * 100 );
	
	// Aquí se integraría una clase completa de NumberToWords
	// Por ahora retornamos un placeholder simulado
	return strtoupper( "MONTO EN LETRAS CON $decimal/100 $moneda" );
}
