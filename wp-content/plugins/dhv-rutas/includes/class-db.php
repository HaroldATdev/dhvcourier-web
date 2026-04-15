<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DHV_Rutas_DB {

    // ─── RECOJO ───────────────────────────────────────────────────────────────

    public static function get_recojo_shipments( $driver_id ) {
        global $wpdb;

        $pickup_statuses = array( 'Pendiente', 'En espera', 'Recogido' );
        $placeholders    = implode( ',', array_fill( 0, count( $pickup_statuses ), '%s' ) );

        $query = $wpdb->prepare(
            "SELECT
                p.ID,
                p.post_title          AS tracking,
                pm_status.meta_value  AS estado,
                pm_remitente.meta_value AS remitente,
                pm_dir.meta_value       AS direccion,
                pm_lugar.meta_value     AS lugar,
                pm_tel.meta_value       AS telefono
            FROM {$wpdb->prefix}posts AS p
            INNER JOIN {$wpdb->prefix}postmeta AS pm_driver
                ON pm_driver.post_id = p.ID AND pm_driver.meta_key = 'wpcargo_driver'
            INNER JOIN {$wpdb->prefix}postmeta AS pm_status
                ON pm_status.post_id = p.ID AND pm_status.meta_key = 'wpcargo_status'
            LEFT JOIN {$wpdb->prefix}postmeta AS pm_remitente
                ON pm_remitente.post_id = p.ID AND pm_remitente.meta_key = 'remitente'
            LEFT JOIN {$wpdb->prefix}postmeta AS pm_dir
                ON pm_dir.post_id = p.ID AND pm_dir.meta_key = 'direccion_destinatario'
            LEFT JOIN {$wpdb->prefix}postmeta AS pm_lugar
                ON pm_lugar.post_id = p.ID AND pm_lugar.meta_key = 'lugar_destino'
            LEFT JOIN {$wpdb->prefix}postmeta AS pm_tel
                ON pm_tel.post_id = p.ID AND pm_tel.meta_key = 'telefono_destinatario'
            WHERE p.post_type    = 'wpcargo_shipment'
              AND p.post_status   = 'publish'
              AND pm_driver.meta_value = %d
              AND pm_status.meta_value IN ($placeholders)
            ORDER BY remitente ASC, p.post_title ASC",
            array_merge( array( $driver_id ), $pickup_statuses )
        );

        $rows    = $wpdb->get_results( $query );
        $grouped = array();

        foreach ( $rows as $row ) {
            $cliente = ! empty( $row->remitente ) ? $row->remitente : __( 'Sin remitente', 'dhv-rutas' );
            if ( ! isset( $grouped[ $cliente ] ) ) $grouped[ $cliente ] = array();
            $grouped[ $cliente ][] = array(
                'id'        => $row->ID,
                'tracking'  => $row->tracking,
                'estado'    => $row->estado,
                'direccion' => $row->direccion,
                'lugar'     => $row->lugar,
                'telefono'  => $row->telefono,
            );
        }

        ksort( $grouped );
        return $grouped;
    }

    // ─── ENTREGA ──────────────────────────────────────────────────────────────

    public static function get_entrega_shipments( $driver_id ) {
        global $wpdb;

        $delivery_statuses = array( 'En ruta', 'Entregado', 'Devuelto', 'Reprogramado', 'Anulado' );
        $placeholders      = implode( ',', array_fill( 0, count( $delivery_statuses ), '%s' ) );

        $query = $wpdb->prepare(
            "SELECT
                p.ID,
                p.post_title            AS tracking,
                pm_status.meta_value    AS estado,
                pm_dest.meta_value      AS destinatario,
                pm_dir.meta_value       AS direccion,
                pm_lugar.meta_value     AS lugar,
                pm_tel.meta_value       AS telefono
            FROM {$wpdb->prefix}posts AS p
            INNER JOIN {$wpdb->prefix}postmeta AS pm_driver
                ON pm_driver.post_id = p.ID AND pm_driver.meta_key = 'driver_entrega_id'
            INNER JOIN {$wpdb->prefix}postmeta AS pm_status
                ON pm_status.post_id = p.ID AND pm_status.meta_key = 'wpcargo_status'
            LEFT JOIN {$wpdb->prefix}postmeta AS pm_dest
                ON pm_dest.post_id = p.ID AND pm_dest.meta_key = 'destinatario'
            LEFT JOIN {$wpdb->prefix}postmeta AS pm_dir
                ON pm_dir.post_id = p.ID AND pm_dir.meta_key = 'direccion_destinatario'
            LEFT JOIN {$wpdb->prefix}postmeta AS pm_lugar
                ON pm_lugar.post_id = p.ID AND pm_lugar.meta_key = 'lugar_destino'
            LEFT JOIN {$wpdb->prefix}postmeta AS pm_tel
                ON pm_tel.post_id = p.ID AND pm_tel.meta_key = 'telefono_destinatario'
            WHERE p.post_type    = 'wpcargo_shipment'
              AND p.post_status   = 'publish'
              AND pm_driver.meta_value = %d
              AND pm_status.meta_value IN ($placeholders)
            ORDER BY destinatario ASC, p.post_title ASC",
            array_merge( array( $driver_id ), $delivery_statuses )
        );

        $rows    = $wpdb->get_results( $query );
        $grouped = array();

        foreach ( $rows as $row ) {
            $dest = ! empty( $row->destinatario ) ? $row->destinatario : __( 'Sin destinatario', 'dhv-rutas' );
            if ( ! isset( $grouped[ $dest ] ) ) $grouped[ $dest ] = array();
            $grouped[ $dest ][] = array(
                'id'        => $row->ID,
                'tracking'  => $row->tracking,
                'estado'    => $row->estado,
                'direccion' => $row->direccion,
                'lugar'     => $row->lugar,
                'telefono'  => $row->telefono,
            );
        }

        ksort( $grouped );
        return $grouped;
    }

    // ─── UPDATE STATUS ────────────────────────────────────────────────────────

    public static function update_status( $shipment_id, $new_status, $driver_id, $mode = 'recojo' ) {
        $meta_key = ( $mode === 'entrega' ) ? 'driver_entrega_id' : 'wpcargo_driver';
        $assigned = get_post_meta( $shipment_id, $meta_key, true );
        if ( (int) $assigned !== (int) $driver_id ) return false;

        $allowed = ( $mode === 'entrega' )
            ? array( 'En ruta', 'Entregado', 'Devuelto', 'Reprogramado', 'Anulado' )
            : array( 'Pendiente', 'En espera', 'Recogido' );

        if ( ! in_array( $new_status, $allowed, true ) ) return false;

        update_post_meta( $shipment_id, 'wpcargo_status', $new_status );
        return true;
    }

    public static function bulk_update_status( $shipment_ids, $new_status, $driver_id, $mode = 'recojo' ) {
        $results = array();
        foreach ( $shipment_ids as $id ) {
            $results[ $id ] = self::update_status( (int) $id, $new_status, $driver_id, $mode );
        }
        return $results;
    }
}
