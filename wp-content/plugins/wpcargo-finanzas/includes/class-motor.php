<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WCFIN_Motor {

    public static function calcular_total( int $condicion_id, array $variables ): array {
        global $wpdb;
        $comps = $wpdb->get_results($wpdb->prepare(
            "SELECT variable FROM {$wpdb->prefix}wcfin_condicion_componentes WHERE condicion_id=%d ORDER BY orden",
            $condicion_id
        ));
        $total = 0;
        foreach ( $comps as $c ) $total += floatval( $variables[$c->variable] ?? 0 );
        $servicio       = floatval( $variables['monto_servicio'] ?? 0 );
        $contraentrega  = $total - $servicio;
        return array_merge( $variables, [
            'monto_total'         => $total,
            'monto_servicio'      => $servicio,
            'monto_contraentrega' => max(0, $contraentrega),
        ]);
    }

    public static function procesar_pago( int $shipment_id, int $metodo_id, int $condicion_id, array $variables, string $notas = '' ): int {
        global $wpdb;
        $vars = self::calcular_total( $condicion_id, $variables );

        // Eliminar transacción pendiente anterior si existe
        $anterior = self::get_transaccion( $shipment_id );
        if ( $anterior && $anterior->estado === 'pendiente' ) {
            $wpdb->delete("{$wpdb->prefix}wcfin_movimientos",    ['transaccion_id' => $anterior->id]);
            $wpdb->delete("{$wpdb->prefix}wcfin_transacciones",  ['id'             => $anterior->id]);
        }

        $wpdb->insert("{$wpdb->prefix}wcfin_transacciones", [
            'shipment_id'    => $shipment_id,
            'metodo_id'      => $metodo_id,
            'condicion_id'   => $condicion_id,
            'monto_servicio' => $vars['monto_servicio'],
            'monto_total'    => $vars['monto_total'],
            'variables_json' => wp_json_encode($variables),
            'estado'         => 'pendiente',
            'notas'          => $notas,
            'creado_por'     => get_current_user_id(),
            'fecha_creacion' => current_time('mysql'),
        ]);
        $trans_id = $wpdb->insert_id;

        self::generar_movimientos( $trans_id, $shipment_id, $metodo_id, $condicion_id, $vars );
        return $trans_id;
    }

    private static function generar_movimientos( int $trans_id, int $shipment_id, int $metodo_id, int $condicion_id, array $vars ): void {
        global $wpdb;
        $reglas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wcfin_reglas
             WHERE metodo_id=%d AND (condicion_id=%d OR condicion_id IS NULL)
             ORDER BY condicion_id DESC",
            $metodo_id, $condicion_id
        ));
        $procesadas = [];
        foreach ( $reglas as $r ) {
            if ( in_array($r->cuenta_afectada, $procesadas, true) ) continue;
            $procesadas[] = $r->cuenta_afectada;
            $monto = floatval( $vars[$r->base_calculo] ?? 0 );
            if ( $monto <= 0 ) continue;
            $wpdb->insert("{$wpdb->prefix}wcfin_movimientos", [
                'transaccion_id' => $trans_id,
                'shipment_id'    => $shipment_id,
                'cuenta'         => $r->cuenta_afectada,
                'monto'          => $monto,
                'signo'          => intval($r->signo),
                'descripcion'    => $r->descripcion_tpl ? sprintf($r->descripcion_tpl, number_format($monto,2)) : '',
                'tipo'           => 'transaccion',
                'fecha'          => current_time('mysql'),
            ]);
        }
    }

    public static function confirmar( int $trans_id ): void {
        global $wpdb;
        $wpdb->update("{$wpdb->prefix}wcfin_transacciones",
            ['estado'=>'confirmado','fecha_confirm'=>current_time('mysql')],
            ['id'=>$trans_id]
        );
    }

    public static function aplicar_penalidad( int $shipment_id, int $tipo_id, float $monto, string $notas ): void {
        global $wpdb;
        $tipo = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wcfin_penalidad_tipos WHERE id=%d",$tipo_id));
        if ( ! $tipo ) return;
        $wpdb->insert("{$wpdb->prefix}wcfin_penalidades_aplicadas", [
            'shipment_id'       => $shipment_id,
            'penalidad_tipo_id' => $tipo_id,
            'monto_aplicado'    => $monto,
            'notas'             => $notas,
            'aplicado_por'      => get_current_user_id(),
            'fecha'             => current_time('mysql'),
        ]);
        $wpdb->insert("{$wpdb->prefix}wcfin_movimientos", [
            'transaccion_id' => null,
            'shipment_id'    => $shipment_id,
            'cuenta'         => $tipo->cuenta_afectada,
            'monto'          => $monto,
            'signo'          => intval($tipo->signo),
            'descripcion'    => 'Penalidad: ' . $tipo->nombre . ($notas ? ' — '.$notas : ''),
            'tipo'           => 'penalidad',
            'fecha'          => current_time('mysql'),
        ]);
    }

    public static function get_transaccion( int $shipment_id ): ?object {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT t.*, mp.nombre as metodo_nombre, cp.nombre as condicion_nombre,
                    mp.requiere_conf, mp.actor_destino
             FROM {$wpdb->prefix}wcfin_transacciones t
             LEFT JOIN {$wpdb->prefix}wcfin_metodos_pago mp ON mp.id=t.metodo_id
             LEFT JOIN {$wpdb->prefix}wcfin_condiciones  cp ON cp.id=t.condicion_id
             WHERE t.shipment_id=%d ORDER BY t.id DESC LIMIT 1",
            $shipment_id
        )) ?: null;
    }

    public static function get_movimientos( int $shipment_id ): array {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wcfin_movimientos WHERE shipment_id=%d ORDER BY fecha DESC",
            $shipment_id
        )) ?: [];
    }

    public static function get_resumen_periodo( string $desde, string $hasta ): array {
        global $wpdb;
        $balances = $wpdb->get_results($wpdb->prepare(
            "SELECT cuenta, SUM(monto*signo) as total, COUNT(*) as n
             FROM {$wpdb->prefix}wcfin_movimientos WHERE DATE(fecha) BETWEEN %s AND %s GROUP BY cuenta",
            $desde, $hasta
        )) ?: [];

        $trans = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, mp.nombre as metodo_nombre, cp.nombre as condicion_nombre,
                    p.post_title as envio_titulo, u.display_name as operador
             FROM {$wpdb->prefix}wcfin_transacciones t
             LEFT JOIN {$wpdb->prefix}wcfin_metodos_pago mp ON mp.id=t.metodo_id
             LEFT JOIN {$wpdb->prefix}wcfin_condiciones  cp ON cp.id=t.condicion_id
             LEFT JOIN {$wpdb->prefix}posts               p ON p.ID=t.shipment_id
             LEFT JOIN {$wpdb->prefix}users               u ON u.ID=t.creado_por
             WHERE DATE(t.fecha_creacion) BETWEEN %s AND %s ORDER BY t.fecha_creacion DESC",
            $desde, $hasta
        )) ?: [];

        $pens = $wpdb->get_results($wpdb->prepare(
            "SELECT pa.*, pt.nombre as tipo_nombre, pt.aplica_a, p.post_title as envio_titulo
             FROM {$wpdb->prefix}wcfin_penalidades_aplicadas pa
             LEFT JOIN {$wpdb->prefix}wcfin_penalidad_tipos pt ON pt.id=pa.penalidad_tipo_id
             LEFT JOIN {$wpdb->prefix}posts                  p ON p.ID=pa.shipment_id
             WHERE DATE(pa.fecha) BETWEEN %s AND %s ORDER BY pa.fecha DESC",
            $desde, $hasta
        )) ?: [];

        return compact('balances','trans','pens');
    }
}
