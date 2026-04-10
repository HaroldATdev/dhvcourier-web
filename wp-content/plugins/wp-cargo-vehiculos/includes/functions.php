<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wpcvh_get_frontend_page_id(): int {
	$saved = (int) get_option( 'wpcvh_frontend_page_id' );
	if ( $saved && get_post_status( $saved ) === 'publish' ) return $saved;

	global $wpdb;
	$id = (int) $wpdb->get_var(
		"SELECT ID FROM {$wpdb->prefix}posts
		 WHERE post_content LIKE '%[wpcvh-vehiculos]%'
		   AND post_status = 'publish' LIMIT 1"
	);
	if ( ! $id ) {
		$id = (int) wp_insert_post( [
			'post_title'   => 'Vehículos',
			'post_content' => '[wpcvh-vehiculos]',
			'post_status'  => 'publish',
			'post_type'    => 'page',
		] );
	}
	if ( $id ) {
		update_post_meta( $id, '_wp_page_template', 'dashboard.php' );
		update_post_meta( $id, 'wpcfe_menu_icon', 'fa fa-truck mr-3' );
		update_option( 'wpcvh_frontend_page_id', $id, false );
	}
	return $id;
}

function wpcvh_frontend_url( array $extra = [] ): string {
	$url = get_permalink( wpcvh_get_frontend_page_id() ) ?: home_url( '/vehiculos/' );
	return $extra ? add_query_arg( $extra, $url ) : $url;
}

function wpcvh_include_template( string $file, array $data = [] ): void {
	$path = WPCVH_PATH . "admin/templates/{$file}";
	if ( ! file_exists( $path ) ) wp_die( "Plantilla no encontrada: {$file}" );
	if ( $data ) extract( $data, EXTR_SKIP ); // phpcs:ignore
	include $path;
}

function wpcvh_km( int $km ): string {
	return number_format( $km, 0, '.', ',' ) . ' km';
}

function wpcvh_alerta_badge( object $v ): string {
	$alerta = WPCVH_Vehiculo::estado_alerta( $v );
	switch ( $alerta ) {
		case 'vencido': return '<span class="badge badge-danger"><i class="fa fa-exclamation-triangle mr-1"></i>Mantenimiento vencido</span>';
		case 'proximo': return '<span class="badge badge-warning"><i class="fa fa-clock-o mr-1"></i>Mantenimiento próximo</span>';
		default:        return '';
	}
}
