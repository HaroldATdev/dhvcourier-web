<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Hook: cuando el envío es de tipo "puerta a puerta" y se actualiza a un estado
 * que NO sea "Pendiente" ni "Recogido", copiar wpcargo_driver_entrega como conductor visible.
 */
function dhv_escaner_handle_shipment_status_update( $shipment_id, $new_status, $user_id ) {
    if ( ! $shipment_id ) return;

    $status_norm = is_string( $new_status ) ? trim( strtolower( $new_status ) ) : '';
    // Solo procesar si el estado NO es Pendiente ni Recogido
    if ( in_array( $status_norm, [ 'pendiente', 'recogido' ], true ) ) return;

    // Detectar tipo de envío
    $tipo = get_post_meta( $shipment_id, 'wpcte_tipo_envio', true );
    if ( empty( $tipo ) ) $tipo = get_post_meta( $shipment_id, 'tipo_envio', true );
    if ( empty( $tipo ) ) $tipo = get_post_meta( $shipment_id, 'dhv_tipo_envio', true );
    $tipo_norm = is_string( $tipo ) ? strtolower( str_replace( ' ', '_', $tipo ) ) : '';

    // Solo aplica para tipo "puerta a puerta"
    if ( $tipo_norm !== 'puerta_a_puerta' ) return;

    $driver_entrega_id = (int) get_post_meta( $shipment_id, 'wpcargo_driver_entrega', true );
    if ( ! $driver_entrega_id ) return;

    update_post_meta( $shipment_id, 'wpcargo_driver', $driver_entrega_id );
    error_log( "dhv_escaner: shipment {$shipment_id} tipo=puerta_a_puerta status={$new_status} -> set wpcargo_driver={$driver_entrega_id}" );
}

add_action( 'wpcargo_update_shipment_status', 'dhv_escaner_handle_shipment_status_update', 20, 3 );

?>
