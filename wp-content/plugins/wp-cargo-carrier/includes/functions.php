<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wpcc_get_frontend_page_id(): int {
	$saved = (int) get_option( 'wpcc_frontend_page_id' );
	if ( $saved && get_post_status( $saved ) === 'publish' ) return $saved;

	global $wpdb;
	$id = (int) $wpdb->get_var(
		"SELECT ID FROM {$wpdb->prefix}posts WHERE post_content LIKE '%[wpcc-transportistas]%' AND post_status = 'publish' LIMIT 1"
	);
	if ( ! $id ) {
		$id = (int) wp_insert_post( [ 'post_title' => 'Transportistas', 'post_content' => '[wpcc-transportistas]', 'post_status' => 'publish', 'post_type' => 'page' ] );
	}
	if ( $id ) {
		update_post_meta( $id, '_wp_page_template', 'dashboard.php' );
		update_post_meta( $id, 'wpcfe_menu_icon', 'fa fa-id-card mr-3' );
		update_option( 'wpcc_frontend_page_id', $id, false );
	}
	return $id;
}

function wpcc_frontend_url( array $extra = [] ): string {
	$url = get_permalink( wpcc_get_frontend_page_id() ) ?: home_url( '/transportistas/' );
	return $extra ? add_query_arg( $extra, $url ) : $url;
}

function wpcc_include_template( string $file, array $data = [] ): void {
	$path = WPCC_PATH . "admin/templates/{$file}";
	if ( ! file_exists( $path ) ) wp_die( "Plantilla no encontrada: {$file}" );
	if ( $data ) extract( $data, EXTR_SKIP );
	include $path;
}

function wpcc_badge( string $estado ): string {
	$c = $estado === 'activo' ? 'success' : 'secondary';
	$l = $estado === 'activo' ? 'Activo'  : 'Inactivo';
	return '<span class="badge badge-' . esc_attr( $c ) . '">' . esc_html( $l ) . '</span>';
}

function wpcc_current_action(): string {
	return sanitize_key( $_GET['wpcc'] ?? '' );
}
