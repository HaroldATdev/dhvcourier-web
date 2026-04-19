<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Hook: cuando cualquier proceso actualiza el estado de un envío a "En ruta",
 * copiar wpcargo_driver_entrega como conductor visible (wpcargo_driver).
 */
function dhv_escaner_handle_shipment_status_update( $shipment_id, $new_status, $user_id ) {
    if ( ! $shipment_id ) return;

    $status_norm = is_string( $new_status ) ? trim( strtolower( $new_status ) ) : '';
    if ( $status_norm !== 'en ruta' ) return;

    $driver_entrega_id = (int) get_post_meta( $shipment_id, 'wpcargo_driver_entrega', true );
    if ( ! $driver_entrega_id ) return;

    update_post_meta( $shipment_id, 'wpcargo_driver', $driver_entrega_id );
    error_log( "dhv_escaner: shipment {$shipment_id} status={$new_status} -> set wpcargo_driver={$driver_entrega_id}" );
}

add_action( 'wpcargo_update_shipment_status', 'dhv_escaner_handle_shipment_status_update', 20, 3 );

?>
