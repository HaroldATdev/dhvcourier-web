<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* ======================================================================
   AJAX
   ====================================================================== */
add_action( 'wp_ajax_wpcte_set_tipo',           'wpcte_ajax_set_tipo' );
add_action( 'wp_ajax_wpcte_get_user_data',      'wpcte_ajax_get_user_data' );
add_action( 'wp_ajax_wpcte_get_tipo',           'wpcte_ajax_get_tipo' );
add_action( 'wp_ajax_nopriv_wpcte_get_tipo',    'wpcte_ajax_get_tipo' );
add_action( 'wp_ajax_wpcte_save_tarifario',     'wpcte_ajax_save_tarifario' );
add_action( 'wp_ajax_wpcte_reset_tarifario',    'wpcte_ajax_reset_tarifario' );

function wpcte_ajax_set_tipo() {
    if ( ! current_user_can('manage_options') ) wp_send_json_error('no_permission');
    $post_id = absint( $_POST['post_id'] ?? 0 );
    $tipo    = sanitize_key( $_POST['tipo'] ?? '' );
    if ( ! $post_id || ! $tipo ) wp_send_json_error('missing');
    update_post_meta( $post_id, 'tipo_envio', $tipo );
    wp_send_json_success( array( 'post_id' => $post_id, 'tipo' => $tipo ) );
}

function wpcte_ajax_get_user_data() {
    if ( ! current_user_can('manage_options') ) wp_send_json_error('no_permission');
    $uid = absint( $_POST['uid'] ?? 0 );
    if ( ! $uid ) wp_send_json_error('no_uid');
    $u = get_userdata( $uid );
    if ( ! $u ) wp_send_json_error('not_found');
    $nombre = trim( $u->first_name . ' ' . $u->last_name ) ?: $u->display_name;
    $dir1   = get_user_meta( $uid, 'billing_address_1', true );
    $dir2   = get_user_meta( $uid, 'billing_address_2', true );
    $dir    = $dir2 ? $dir1 . ', ' . $dir2 : $dir1;
    wp_send_json_success( array(
        'nombre'    => $nombre,
        'telefono'  => get_user_meta( $uid, 'billing_phone', true ),
        'direccion' => $dir,
        'ciudad'    => get_user_meta( $uid, 'billing_city', true ),
    ));
}


function wpcte_ajax_get_tipo() {
    $post_id = absint( $_POST['post_id'] ?? 0 );
    if ( ! $post_id ) wp_send_json_error();
    wp_send_json_success( array( 'tipo' => get_post_meta( $post_id, 'tipo_envio', true ) ) );
}

function wpcte_ajax_save_tarifario() {
    if ( ! current_user_can('manage_options') ) wp_send_json_error('no_permission');
    check_ajax_referer( 'wpcte_admin_nonce', 'nonce' );
    $data = json_decode( stripslashes( $_POST['tarifario'] ?? '{}' ), true );
    if ( ! is_array( $data ) ) wp_send_json_error('invalid_data');
    // Asegurar que items de mercadería sean objetos (no arrays vacíos)
    if ( isset( $data['mercaderia']['categorias'] ) ) {
        foreach ( $data['mercaderia']['categorias'] as $key => &$cat ) {
            if ( isset($cat['items']) && ( !is_array($cat['items']) || array_keys($cat['items']) === range(0, count($cat['items'])-1) ) ) {
                // Si items es un array indexado (no asociativo) o vacío, convertir a objeto
                if ( empty($cat['items']) ) {
                    $cat['items'] = new stdClass(); // se serializará como {}
                }
            }
            if ( !isset($cat['rutas']) ) $cat['rutas'] = [];
        }
        unset($cat);
    }
    update_option( 'wpcte_tarifario', $data );
    wp_send_json_success( 'saved' );
}

function wpcte_ajax_reset_tarifario() {
    if ( ! current_user_can('manage_options') ) wp_send_json_error('no_permission');
    check_ajax_referer( 'wpcte_admin_nonce', 'nonce' );
    delete_option( 'wpcte_tarifario' );
    wp_send_json_success( wpcte_tarifario_default() );
}
