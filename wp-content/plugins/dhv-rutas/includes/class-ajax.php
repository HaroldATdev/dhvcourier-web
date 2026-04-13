<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DHV_Rutas_Ajax {

    public function __construct() {
        add_action( 'wp_ajax_dhv_update_recojo_status',      array( $this, 'update_status' ) );
        add_action( 'wp_ajax_dhv_bulk_update_recojo_status', array( $this, 'bulk_update_status' ) );
    }

    private function verify() {
        if ( ! check_ajax_referer( 'dhv_rutas_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Nonce inválido.' ), 403 );
        }
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'No autenticado.' ), 401 );
        }
    }

    public function update_status() {
        $this->verify();

        $shipment_id = isset( $_POST['shipment_id'] ) ? (int) $_POST['shipment_id'] : 0;
        $new_status  = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';
        $driver_id   = get_current_user_id();

        if ( ! $shipment_id || ! $new_status ) {
            wp_send_json_error( array( 'message' => 'Datos incompletos.' ) );
        }

        $result = DHV_Rutas_DB::update_status( $shipment_id, $new_status, $driver_id );

        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Estado actualizado.', 'status' => $new_status ) );
        } else {
            wp_send_json_error( array( 'message' => 'No se pudo actualizar. Verifica permisos.' ) );
        }
    }

    public function bulk_update_status() {
        $this->verify();

        $shipment_ids = isset( $_POST['shipment_ids'] ) ? array_map( 'intval', (array) $_POST['shipment_ids'] ) : array();
        $new_status   = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';
        $driver_id    = get_current_user_id();

        if ( empty( $shipment_ids ) || ! $new_status ) {
            wp_send_json_error( array( 'message' => 'Datos incompletos.' ) );
        }

        $results = DHV_Rutas_DB::bulk_update_status( $shipment_ids, $new_status, $driver_id );
        $ok      = array_filter( $results );

        wp_send_json_success( array(
            'message'  => count( $ok ) . ' pedido(s) actualizado(s).',
            'updated'  => array_keys( $ok ),
            'status'   => $new_status,
        ));
    }
}

new DHV_Rutas_Ajax();
