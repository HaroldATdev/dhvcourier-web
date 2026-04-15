<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Hook: escuchar cambios de estado y aplicar reasignación de driver visible
 * Regla:
 *  - Para `puerta_puerta`: cuando el estado pasa a 'En espera' establecer `wpcargo_driver` = `driver_entrega_id`
 *  - Para `agencia` o `almacen`: siempre asegurar `wpcargo_driver` = `driver_entrega_id` si existe
 */
function dhv_escaner_handle_shipment_status_update( $shipment_id, $new_status, $user_id ) {
    if ( ! $shipment_id ) return;

    // Detectar tipo de envío por varias claves posibles
    $tipo = get_post_meta( $shipment_id, 'wpcte_tipo_envio', true );
    if ( empty( $tipo ) ) $tipo = get_post_meta( $shipment_id, 'tipo_envio', true );
    if ( empty( $tipo ) ) $tipo = get_post_meta( $shipment_id, 'dhv_tipo_envio', true );
    $tipo_norm = is_string( $tipo ) ? strtolower( str_replace( ' ', '_', $tipo ) ) : '';

    $driver_entrega_id = (int) get_post_meta( $shipment_id, 'driver_entrega_id', true );
    if ( ! $driver_entrega_id ) {
        // nada que forzar si no existe driver de entrega
        return;
    }

    // Normalizar estado
    $status_norm = is_string( $new_status ) ? trim( strtolower( $new_status ) ) : '';

    // Para puerta_puerta: solo cuando pasa a 'en espera'
    if ( $tipo_norm === 'puerta_puerta' ) {
        if ( $status_norm === strtolower( 'En espera' ) || $status_norm === 'en espera' ) {
            update_post_meta( $shipment_id, 'wpcargo_driver', $driver_entrega_id );
            error_log( "dhv_escaner: shipment {$shipment_id} tipo={$tipo_norm} status={$new_status} -> set wpcargo_driver={$driver_entrega_id}" );
        }
        return;
    }

    // Para agencia/almacen: forzar entrega siempre
    if ( in_array( $tipo_norm, array( 'agencia', 'almacen' ), true ) ) {
        update_post_meta( $shipment_id, 'wpcargo_driver', $driver_entrega_id );
        error_log( "dhv_escaner: shipment {$shipment_id} tipo={$tipo_norm} -> enforced wpcargo_driver={$driver_entrega_id}" );
    }
}

add_action( 'wpcargo_update_shipment_status', 'dhv_escaner_handle_shipment_status_update', 20, 3 );

?>
