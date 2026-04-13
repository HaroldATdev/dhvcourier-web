<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DHV_Rutas_DB {

    public static function get_recojo_shipments( $driver_id ) {
        global $wpdb;

        $pickup_statuses = array( 'Pendiente', 'En espera' );
        $placeholders    = implode( ',', array_fill( 0, count( $pickup_statuses ), '%s' ) );

        $query = $wpdb->prepare(
            "SELECT
                p.ID,
                p.post_title         AS tracking,
                pm_status.meta_value  AS estado,
                pm_remitente.meta_value AS remitente
            FROM {$wpdb->prefix}posts AS p
            INNER JOIN {$wpdb->prefix}postmeta AS pm_driver
                ON pm_driver.post_id = p.ID AND pm_driver.meta_key = 'wpcargo_driver'
            INNER JOIN {$wpdb->prefix}postmeta AS pm_status
                ON pm_status.post_id = p.ID AND pm_status.meta_key = 'wpcargo_status'
            LEFT JOIN {$wpdb->prefix}postmeta AS pm_remitente
                ON pm_remitente.post_id = p.ID AND pm_remitente.meta_key = 'remitente'
            WHERE p.post_type    = 'wpcargo_shipment'
              AND p.post_status   = 'publish'
              AND pm_driver.meta_value = %d
              AND pm_status.meta_value IN ($placeholders)
            ORDER BY remitente ASC, p.post_title ASC",
            array_merge( array( $driver_id ), $pickup_statuses )
        );

        $rows = $wpdb->get_results( $query );

        $grouped = array();
        foreach ( $rows as $row ) {
            $cliente = ! empty( $row->remitente )
                ? $row->remitente
                : __( 'Sin remitente', 'dhv-rutas' );

            if ( ! isset( $grouped[ $cliente ] ) ) {
                $grouped[ $cliente ] = array();
            }
            $grouped[ $cliente ][] = array(
                'id'       => $row->ID,
                'tracking' => $row->tracking,
                'estado'   => $row->estado,
            );
        }

        ksort( $grouped );
        return $grouped;
    }

    public static function update_status( $shipment_id, $new_status, $driver_id ) {
        $assigned_driver = get_post_meta( $shipment_id, 'wpcargo_driver', true );
        if ( (int) $assigned_driver !== (int) $driver_id ) {
            return false;
        }
        $allowed = array( 'Pendiente', 'En espera', 'Devuelto', 'Entregado' );
        if ( ! in_array( $new_status, $allowed, true ) ) {
            return false;
        }
        update_post_meta( $shipment_id, 'wpcargo_status', $new_status );
        return true;
    }

    public static function bulk_update_status( $shipment_ids, $new_status, $driver_id ) {
        $results = array();
        foreach ( $shipment_ids as $id ) {
            $results[ $id ] = self::update_status( (int) $id, $new_status, $driver_id );
        }
        return $results;
    }
}
