<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WCFIN_Caja — Calcula balances por usuario (driver o cliente/remitente).
 *
 * LÓGICA DE BALANCES:
 *
 * DRIVER:
 *   balance_bruto   = SUM movimientos WHERE cuenta='balance_motorizado' AND shipment.driver = driver_id
 *   liquidado       = SUM liquidaciones WHERE driver_id = driver_id
 *   saldo_pendiente = balance_bruto - liquidado  → lo que el driver todavía le debe a DHV
 *
 * CLIENTE (remitente):
 *   dhv_debe_cliente = SUM movimientos WHERE cuenta='deuda_a_remitente'  AND shipment.registered_shipper = user_id
 *                      MINUS pagos aprobados direction='dhv_a_cliente'
 *   cliente_debe_dhv = SUM movimientos WHERE cuenta='deuda_de_remitente' AND shipment.registered_shipper = user_id
 *                      MINUS pagos aprobados direction='cliente_a_dhv'
 */
class WCFIN_Caja {

    // ── DRIVERS ──────────────────────────────────────────────────────────────

    /**
     * Balance bruto que el driver ha acumulado (lo que cobró a destinatarios).
     */
    public static function balance_driver( int $driver_id ): float {
        global $wpdb;
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(m.monto * m.signo), 0)
             FROM {$wpdb->prefix}wcfin_movimientos m
             INNER JOIN {$wpdb->prefix}posts p ON p.ID = m.shipment_id
             INNER JOIN {$wpdb->prefix}postmeta pm ON pm.post_id = p.ID
             WHERE m.cuenta = 'balance_motorizado'
               AND pm.meta_key = 'wpcargo_driver'
               AND pm.meta_value = %d",
            $driver_id
        ));
        return (float) $result;
    }

    /**
     * Total liquidado por el driver a DHV.
     */
    public static function liquidado_driver( int $driver_id ): float {
        global $wpdb;
        return (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(monto), 0) FROM {$wpdb->prefix}wcfin_liquidaciones WHERE driver_id = %d",
            $driver_id
        ));
    }

    /**
     * Saldo pendiente del driver (lo que aún le debe a DHV).
     */
    public static function saldo_pendiente_driver( int $driver_id ): float {
        return self::balance_driver($driver_id) - self::liquidado_driver($driver_id);
    }

    /**
     * Detalle de envíos del driver con sus montos.
     */
    public static function envios_driver( int $driver_id, int $limit = 50, int $offset = 0 ): array {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID as shipment_id, p.post_title as tracking,
                    COALESCE(SUM(m.monto * m.signo), 0) as monto_driver,
                    MAX(m.fecha) as fecha,
                    MAX(t.estado) as estado_pago,
                    MAX(cp.nombre) as condicion
             FROM {$wpdb->prefix}posts p
             INNER JOIN {$wpdb->prefix}postmeta pm ON pm.post_id = p.ID AND pm.meta_key = 'wpcargo_driver' AND pm.meta_value = %d
             LEFT JOIN {$wpdb->prefix}wcfin_movimientos m ON m.shipment_id = p.ID AND m.cuenta = 'balance_motorizado'
             LEFT JOIN {$wpdb->prefix}wcfin_transacciones t ON t.shipment_id = p.ID
             LEFT JOIN {$wpdb->prefix}wcfin_condiciones cp ON cp.id = t.condicion_id
             WHERE p.post_type = 'wpcargo_shipment' AND p.post_status = 'publish'
             GROUP BY p.ID
             HAVING monto_driver > 0
             ORDER BY fecha DESC
             LIMIT %d OFFSET %d",
            $driver_id, $limit, $offset
        )) ?: [];
    }

    /**
     * Historial de liquidaciones de un driver.
     */
    public static function liquidaciones_driver( int $driver_id ): array {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, u.display_name as admin_nombre
             FROM {$wpdb->prefix}wcfin_liquidaciones l
             LEFT JOIN {$wpdb->prefix}users u ON u.ID = l.registrado_por
             WHERE l.driver_id = %d ORDER BY l.fecha DESC",
            $driver_id
        )) ?: [];
    }

    /**
     * Lista de todos los drivers con su balance.
     */
    public static function todos_los_drivers(): array {
        global $wpdb;
        // Obtener todos los users con rol wpcargo_driver que tienen envíos con movimientos
        $drivers = get_users([
            'role'    => 'wpcargo_driver',
            'orderby' => 'display_name',
            'order'   => 'ASC',
            'number'  => 200,
        ]);
        $lista = [];
        foreach ( $drivers as $u ) {
            $balance    = self::balance_driver($u->ID);
            $liquidado  = self::liquidado_driver($u->ID);
            $saldo      = $balance - $liquidado;
            $lista[] = [
                'user'      => $u,
                'balance'   => $balance,
                'liquidado' => $liquidado,
                'saldo'     => $saldo,
                'n_envios'  => (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT m.shipment_id) FROM {$wpdb->prefix}wcfin_movimientos m
                     INNER JOIN {$wpdb->prefix}postmeta pm ON pm.post_id = m.shipment_id
                     WHERE m.cuenta='balance_motorizado' AND pm.meta_key='wpcargo_driver' AND pm.meta_value=%d",
                    $u->ID
                )),
            ];
        }
        // Ordenar por saldo descendente (más deuda primero)
        usort($lista, fn($a,$b) => $b['saldo'] <=> $a['saldo']);
        return $lista;
    }

    // ── CLIENTES / REMITENTES ─────────────────────────────────────────────────

    /**
     * Lo que DHV le debe a un cliente (ej: producto en contraentrega).
     */
    public static function dhv_debe_a_cliente( int $user_id ): float {
        global $wpdb;
        $acumulado = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(m.monto * m.signo), 0)
             FROM {$wpdb->prefix}wcfin_movimientos m
             INNER JOIN {$wpdb->prefix}postmeta pm ON pm.post_id = m.shipment_id
             WHERE m.cuenta = 'deuda_a_remitente'
               AND pm.meta_key = 'registered_shipper'
               AND pm.meta_value = %d",
            $user_id
        ));
        $pagado = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(monto), 0) FROM {$wpdb->prefix}wcfin_pagos_remitente
             WHERE user_id = %d AND direccion = 'dhv_a_cliente' AND estado = 'aprobado'",
            $user_id
        ));
        return max(0, $acumulado - $pagado);
    }

    /**
     * Lo que el cliente le debe a DHV (ej: servicio a crédito sin pagar).
     */
    public static function cliente_debe_a_dhv( int $user_id ): float {
        global $wpdb;
        $acumulado = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(m.monto * m.signo), 0)
             FROM {$wpdb->prefix}wcfin_movimientos m
             INNER JOIN {$wpdb->prefix}postmeta pm ON pm.post_id = m.shipment_id
             WHERE m.cuenta = 'deuda_de_remitente'
               AND pm.meta_key = 'registered_shipper'
               AND pm.meta_value = %d",
            $user_id
        ));
        $pagado = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(monto), 0) FROM {$wpdb->prefix}wcfin_pagos_remitente
             WHERE user_id = %d AND direccion = 'cliente_a_dhv' AND estado = 'aprobado'",
            $user_id
        ));
        return max(0, $acumulado - $pagado);
    }

    /**
     * Detalle de envíos del cliente relevantes para finanzas.
     */
    public static function envios_cliente( int $user_id, int $limit = 50, int $offset = 0 ): array {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID as shipment_id, p.post_title as tracking,
                    MAX(t.monto_total) as monto_total,
                    MAX(t.monto_servicio) as monto_servicio,
                    MAX(cp.nombre) as condicion,
                    MAX(cp.cobrar_a) as cobrar_a,
                    COALESCE(SUM(CASE WHEN m.cuenta='deuda_a_remitente'  THEN m.monto*m.signo ELSE 0 END),0) as dhv_debe,
                    COALESCE(SUM(CASE WHEN m.cuenta='deuda_de_remitente' THEN m.monto*m.signo ELSE 0 END),0) as cliente_debe,
                    MAX(t.estado) as estado_pago,
                    MAX(t.fecha_creacion) as fecha
             FROM {$wpdb->prefix}posts p
             INNER JOIN {$wpdb->prefix}postmeta pm ON pm.post_id = p.ID AND pm.meta_key='registered_shipper' AND pm.meta_value = %d
             LEFT JOIN {$wpdb->prefix}wcfin_transacciones t ON t.shipment_id = p.ID
             LEFT JOIN {$wpdb->prefix}wcfin_condiciones cp ON cp.id = t.condicion_id
             LEFT JOIN {$wpdb->prefix}wcfin_movimientos m ON m.shipment_id = p.ID
               AND m.cuenta IN ('deuda_a_remitente','deuda_de_remitente')
             WHERE p.post_type='wpcargo_shipment' AND p.post_status='publish'
             GROUP BY p.ID
             HAVING (dhv_debe > 0 OR cliente_debe > 0)
             ORDER BY fecha DESC
             LIMIT %d OFFSET %d",
            $user_id, $limit, $offset
        )) ?: [];
    }

    /**
     * Historial de pagos de/a un cliente.
     */
    public static function pagos_cliente( int $user_id, string $direccion = '' ): array {
        global $wpdb;
        $where = $wpdb->prepare("WHERE pr.user_id = %d", $user_id);
        if ( $direccion ) $where .= $wpdb->prepare(" AND pr.direccion = %s", $direccion);
        return $wpdb->get_results(
            "SELECT pr.*, u.display_name as emisor_nombre, u2.display_name as admin_nombre
             FROM {$wpdb->prefix}wcfin_pagos_remitente pr
             LEFT JOIN {$wpdb->prefix}users u  ON u.ID  = pr.enviado_por
             LEFT JOIN {$wpdb->prefix}users u2 ON u2.ID = pr.revisado_por
             {$where} ORDER BY pr.fecha_envio DESC"
        ) ?: [];
    }

    /**
     * Lista de todos los clientes con sus balances.
     */
    public static function todos_los_clientes(): array {
        global $wpdb;
        // Obtener clientes que tienen envíos con movimientos relevantes
        $user_ids = $wpdb->get_col(
            "SELECT DISTINCT pm.meta_value
             FROM {$wpdb->prefix}postmeta pm
             INNER JOIN {$wpdb->prefix}wcfin_movimientos m ON m.shipment_id = pm.post_id
             WHERE pm.meta_key = 'registered_shipper'
               AND m.cuenta IN ('deuda_a_remitente','deuda_de_remitente')"
        );
        $lista = [];
        foreach ( $user_ids as $uid ) {
            $u = get_userdata((int)$uid);
            if ( ! $u ) continue;
            $dhv_debe    = self::dhv_debe_a_cliente((int)$uid);
            $cliente_debe= self::cliente_debe_a_dhv((int)$uid);
            if ( $dhv_debe <= 0 && $cliente_debe <= 0 ) continue;
            $lista[] = [
                'user'         => $u,
                'dhv_debe'     => $dhv_debe,
                'cliente_debe' => $cliente_debe,
                'saldo_neto'   => $cliente_debe - $dhv_debe, // positivo = cliente debe
                'n_envios'     => (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT pm.post_id) FROM {$wpdb->prefix}postmeta pm
                     INNER JOIN {$wpdb->prefix}wcfin_movimientos m ON m.shipment_id = pm.post_id
                     WHERE pm.meta_key='registered_shipper' AND pm.meta_value=%d
                       AND m.cuenta IN ('deuda_a_remitente','deuda_de_remitente')",
                    $uid
                )),
            ];
        }
        usort($lista, fn($a,$b) => $b['saldo_neto'] <=> $a['saldo_neto']);
        return $lista;
    }

    // ── LIQUIDACIONES (admin registra pago driver → DHV) ─────────────────────

    public static function registrar_liquidacion( int $driver_id, float $monto, string $metodo, string $notas, string $comprobante_url ): int {
        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}wcfin_liquidaciones", [
            'driver_id'       => $driver_id,
            'monto'           => $monto,
            'metodo'          => sanitize_text_field($metodo),
            'notas'           => sanitize_textarea_field($notas),
            'comprobante_url' => esc_url_raw($comprobante_url),
            'registrado_por'  => get_current_user_id(),
            'fecha'           => current_time('mysql'),
        ]);
        return $wpdb->insert_id;
    }

    // ── PAGOS BILATERALES CLIENTE ↔ DHV ───────────────────────────────────────

    /**
     * El cliente declara un pago a DHV (sube comprobante).
     */
    public static function cliente_declara_pago( int $user_id, float $monto, string $metodo, string $referencia, string $comprobante_url, string $notas ): int {
        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}wcfin_pagos_remitente", [
            'user_id'         => $user_id,
            'direccion'       => 'cliente_a_dhv',
            'monto'           => $monto,
            'metodo'          => sanitize_text_field($metodo),
            'referencia'      => sanitize_text_field($referencia),
            'comprobante_url' => esc_url_raw($comprobante_url),
            'notas_emisor'    => sanitize_textarea_field($notas),
            'estado'          => 'pendiente',
            'enviado_por'     => $user_id,
            'fecha_envio'     => current_time('mysql'),
        ]);
        return $wpdb->insert_id;
    }

    /**
     * DHV declara un pago al cliente (admin sube comprobante).
     */
    public static function dhv_declara_pago( int $user_id, float $monto, string $metodo, string $referencia, string $comprobante_url, string $notas ): int {
        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}wcfin_pagos_remitente", [
            'user_id'         => $user_id,
            'direccion'       => 'dhv_a_cliente',
            'monto'           => $monto,
            'metodo'          => sanitize_text_field($metodo),
            'referencia'      => sanitize_text_field($referencia),
            'comprobante_url' => esc_url_raw($comprobante_url),
            'notas_emisor'    => sanitize_textarea_field($notas),
            'estado'          => 'aprobado', // DHV no necesita aprobación de sí mismo
            'enviado_por'     => get_current_user_id(),
            'revisado_por'    => get_current_user_id(),
            'fecha_envio'     => current_time('mysql'),
            'fecha_revision'  => current_time('mysql'),
        ]);
        return $wpdb->insert_id;
    }

    /**
     * Aprobar o rechazar pago de cliente.
     */
    public static function revisar_pago( int $pago_id, string $estado, string $notas_admin = '' ): void {
        global $wpdb;
        $wpdb->update("{$wpdb->prefix}wcfin_pagos_remitente", [
            'estado'          => $estado,
            'notas_admin'     => sanitize_textarea_field($notas_admin),
            'revisado_por'    => get_current_user_id(),
            'fecha_revision'  => current_time('mysql'),
        ], ['id' => $pago_id]);
    }

    /**
     * Obtener un pago por ID.
     */
    public static function get_pago( int $id ): ?object {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wcfin_pagos_remitente WHERE id=%d", $id
        )) ?: null;
    }

    // ── DASHBOARD GLOBAL ─────────────────────────────────────────────────────

    /**
     * Resumen global del panel de finanzas para el admin.
     */
    public static function resumen_global(): array {
        global $wpdb;

        $cuentas = $wpdb->get_results(
            "SELECT cuenta, SUM(monto*signo) as total
             FROM {$wpdb->prefix}wcfin_movimientos
             GROUP BY cuenta"
        ) ?: [];

        $por_cuenta = [];
        foreach ($cuentas as $r) $por_cuenta[$r->cuenta] = (float)$r->total;

        $pendientes_liquidacion = (float) $wpdb->get_var(
            "SELECT COALESCE(SUM(l.monto),0) FROM {$wpdb->prefix}wcfin_liquidaciones l"
        );
        $pendientes_revision = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wcfin_pagos_remitente WHERE estado='pendiente' AND direccion='cliente_a_dhv'"
        );
        $total_drivers = count(get_users(['role'=>'wpcargo_driver','fields'=>'ID']));
        $total_clientes = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT meta_value) FROM {$wpdb->prefix}postmeta WHERE meta_key='registered_shipper'"
        );

        return compact('por_cuenta','pendientes_liquidacion','pendientes_revision','total_drivers','total_clientes');
    }
}
