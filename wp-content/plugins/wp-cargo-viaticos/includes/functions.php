<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wpcv_get_frontend_page_id(): int {
	$saved = (int) get_option( 'wpcv_frontend_page_id' );
	if ( $saved && get_post_status( $saved ) === 'publish' ) {
		return $saved;
	}

	global $wpdb;
	$id = (int) $wpdb->get_var(
		"SELECT ID FROM {$wpdb->prefix}posts
		 WHERE post_content LIKE '%[wpcv-viaticos]%'
		   AND post_status = 'publish' LIMIT 1"
	);

	if ( ! $id ) {
		$id = (int) wp_insert_post( [
			'post_title'   => 'Viáticos',
			'post_content' => '[wpcv-viaticos]',
			'post_status'  => 'publish',
			'post_type'    => 'page',
		] );
	}

	if ( $id ) {
		update_post_meta( $id, '_wp_page_template', 'dashboard.php' );
		update_post_meta( $id, 'wpcfe_menu_icon', 'fa fa-money mr-3' );
		update_option( 'wpcv_frontend_page_id', $id, false );
	}

	return $id;
}

function wpcv_frontend_url( array $extra = [] ): string {
	$url = get_permalink( wpcv_get_frontend_page_id() ) ?: home_url( '/viaticos/' );
	return $extra ? add_query_arg( $extra, $url ) : $url;
}

function wpcv_include_template( string $file, array $data = [] ): void {
	$path = WPCV_PATH . "admin/templates/{$file}";
	if ( ! file_exists( $path ) ) wp_die( "Plantilla no encontrada: {$file}" );
	if ( $data ) extract( $data, EXTR_SKIP ); // phpcs:ignore
	include $path;
}

function wpcv_badge( string $estado ): string {
	$c = $estado === 'activo' ? 'success' : 'secondary';
	$l = $estado === 'activo' ? 'Activo'  : 'Cerrado';
	return '<span class="badge badge-' . esc_attr( $c ) . '">' . esc_html( $l ) . '</span>';
}

function wpcv_monto( float $monto ): string {
	return 'S/ ' . number_format( $monto, 2, '.', ',' );
}

function wpcv_current_action(): string {
	return sanitize_key( $_GET['wpcv'] ?? '' );
}

/**
 * Retorna el transportista_id vinculado al usuario actual si es WPCargo Driver.
 * Retorna 0 si no es driver o no tiene transportista.
 */
function wpcv_driver_transportista_id(): int {
	$user = wp_get_current_user();
	if ( ! $user || ! in_array( 'wpcargo_driver', (array) $user->roles, true ) ) {
		return 0;
	}
	// Buscar en la tabla de transportistas
	global $wpdb;
	$tabla = $wpdb->prefix . 'wpcc_transportistas';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tabla}'" ) !== $tabla ) return 0; // phpcs:ignore
	$id = (int) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
		"SELECT id FROM {$tabla} WHERE user_id = %d AND estado = 'activo' LIMIT 1",
		$user->ID
	) );
	return $id;
}

/**
 * ¿El usuario actual es admin de viáticos?
 */
function wpcv_es_admin(): bool {
	return current_user_can( 'manage_options' );
}

/**
 * ¿El usuario actual es driver con transportista activo?
 */
function wpcv_es_driver(): bool {
	return wpcv_driver_transportista_id() > 0;
}
