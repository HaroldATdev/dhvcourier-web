<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Gestiona la lista configurable de tipos de gasto.
 * Se guardan en wp_options como JSON.
 */
class WPCV_Tipos_Gasto {

	const OPTION = 'wpcv_tipos_gasto';

	public static $defaults = [
		'Combustible',
		'Peaje',
		'Alimentación',
		'Hospedaje',
		'Mantenimiento menor',
		'Otro',
	];

	public static function obtener(): array {
		$saved = get_option( self::OPTION, null );
		if ( $saved === null ) return self::$defaults;
		$list = json_decode( $saved, true );
		return ( is_array( $list ) && ! empty( $list ) ) ? $list : self::$defaults;
	}

	public static function guardar( array $tipos ): void {
		$limpios = array_values( array_filter(
			array_map( 'sanitize_text_field', $tipos ),
			fn( $t ) => $t !== ''
		) );
		update_option( self::OPTION, wp_json_encode( $limpios ), false );
	}

	public static function agregar( string $tipo ): true|\WP_Error {
		$tipo   = sanitize_text_field( $tipo );
		if ( empty( $tipo ) ) return new \WP_Error( 'vacio', 'El tipo no puede estar vacío.' );
		$actual = self::obtener();
		if ( in_array( $tipo, $actual, true ) ) return new \WP_Error( 'duplicado', 'Ese tipo ya existe.' );
		$actual[] = $tipo;
		self::guardar( $actual );
		return true;
	}

	public static function eliminar( string $tipo ): void {
		$actual = array_filter( self::obtener(), fn( $t ) => $t !== $tipo );
		self::guardar( array_values( $actual ) );
	}
}
