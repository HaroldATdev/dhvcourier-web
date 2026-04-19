<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DHV_Escaner_Ajax {

    public function __construct() {
        add_action( 'wp_ajax_dhv_scan_shipment', [ $this, 'scan_shipment' ] );
    }

    public function scan_shipment() {
        check_ajax_referer( 'dhv_nonce', 'nonce' );
        if ( ! dhv_can_scan() ) wp_send_json_error( [ 'message' => 'Sin permisos.' ] );

        $tracking           = sanitize_text_field( $_POST['tracking_number'] ?? '' );
        $status             = sanitize_text_field( $_POST['status'] ?? '' );
        $delivery_driver_id = intval( $_POST['delivery_driver_id'] ?? 0 );
        $location           = sanitize_text_field( $_POST['location'] ?? '' );
        $remarks            = sanitize_textarea_field( $_POST['remarks'] ?? '' );

        if ( empty( $tracking ) ) {
            wp_send_json_error( [ 'message' => 'Ingresa un número de seguimiento.' ] );
        }

        // ── Buscar envío por número de seguimiento (post_title) ───────────
        global $wpdb;
        $shipment_id = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts}
                 WHERE post_title = %s
                   AND post_type = 'wpcargo_shipment'
                   AND post_status = 'publish'
                 LIMIT 1",
                $tracking
            )
        );

        if ( ! $shipment_id ) {
            wp_send_json_error([
                'message' => "Número <strong>{$tracking}</strong> no encontrado.",
                'type'    => 'not_found',
            ]);
        }

        // ── Si no hay nada que actualizar, solo consultar ─────────────────
        if ( empty( $status ) && ! $delivery_driver_id ) {
            wp_send_json_success([
                'type'          => 'info',
                'tracking'      => $tracking,
                'status'        => get_post_meta( $shipment_id, 'wpcargo_status', true ),
                'receiver_name' => get_post_meta( $shipment_id, 'wpcargo_receiver_name', true ),
                'receiver_addr' => get_post_meta( $shipment_id, 'wpcargo_receiver_address', true ),
            ]);
        }

        $status_updated = false;
        $new_status     = get_post_meta( $shipment_id, 'wpcargo_status', true );

        // ── Actualizar estado ─────────────────────────────────────────────
        if ( ! empty( $status ) ) {
            update_post_meta( $shipment_id, 'wpcargo_status', $status );
            $new_status     = $status;
            $status_updated = true;

            // Registrar en historial de WPCargo
            $history = get_post_meta( $shipment_id, 'wpcargo_shipments_update', true );
            if ( ! is_array( $history ) ) $history = [];
            $history[] = [
                'date'         => current_time( 'Y-m-d' ),
                'time'         => current_time( 'H:i' ),
                'status'       => $status,
                'location'     => $location,
                'remarks'      => $remarks,
                'updated-name' => wp_get_current_user()->display_name,
                'updated-by'   => get_current_user_id(),
            ];
            update_post_meta( $shipment_id, 'wpcargo_shipments_update', $history );

            // Disparar hooks de notificación de WPCargo
            do_action( 'wpcargo_update_shipment_status', $shipment_id, $status, get_current_user_id() );
        }

        // ── Asignar conductor de entrega ──────────────────────────────────
        $delivery_driver_updated = false;
        if ( $delivery_driver_id ) {
            $delivery_driver = get_userdata( $delivery_driver_id );
            if ( $delivery_driver && in_array( 'wpcargo_driver', (array) $delivery_driver->roles, true ) ) {
                update_post_meta( $shipment_id, 'wpcargo_driver_entrega', $delivery_driver_id );
                $delivery_driver_updated = true;
                // Si el estado actual o el recién aplicado es "En ruta", también asignar como conductor visible
                if ( strtolower( trim( $new_status ) ) === 'en ruta' ) {
                    update_post_meta( $shipment_id, 'wpcargo_driver', $delivery_driver_id );
                }
            }
        }

        wp_send_json_success([
            'type'                   => 'updated',
            'tracking'               => $tracking,
            'shipment_id'            => $shipment_id,
            'new_status'             => $new_status,
            'status_updated'         => $status_updated,
            'delivery_driver_updated'=> $delivery_driver_updated,
            'receiver_name'          => get_post_meta( $shipment_id, 'wpcargo_receiver_name', true ),
            'receiver_addr'          => get_post_meta( $shipment_id, 'wpcargo_receiver_address', true ),
        ]);
    }
}

