<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wpco_get_frontend_page_id(): int {
	$saved = (int) get_option( 'wpco_frontend_page_id' );
	if ( $saved && get_post_status( $saved ) === 'publish' ) return $saved;
	global $wpdb;
	$id = (int) $wpdb->get_var(
		"SELECT ID FROM {$wpdb->prefix}posts WHERE post_content LIKE '%[wpco-ordenes]%' AND post_status = 'publish' LIMIT 1"
	);
	if ( ! $id ) {
		$id = (int) wp_insert_post( [ 'post_title' => 'Órdenes de Servicio', 'post_content' => '[wpco-ordenes]', 'post_status' => 'publish', 'post_type' => 'page' ] );
	}
	if ( $id ) {
		update_post_meta( $id, '_wp_page_template', 'dashboard.php' );
		update_post_meta( $id, 'wpcfe_menu_icon', 'fa fa-file-text mr-3' );
		update_option( 'wpco_frontend_page_id', $id, false );
	}
	return $id;
}

function wpco_frontend_url( array $extra = [] ): string {
	$url = get_permalink( wpco_get_frontend_page_id() ) ?: home_url( '/ordenes/' );
	return $extra ? add_query_arg( $extra, $url ) : $url;
}

function wpco_include_template( string $file, array $data = [] ): void {
	$path = WPCO_PATH . "admin/templates/{$file}";
	if ( ! file_exists( $path ) ) wp_die( "Plantilla no encontrada: {$file}" );
	if ( $data ) extract( $data, EXTR_SKIP );
	include $path;
}
