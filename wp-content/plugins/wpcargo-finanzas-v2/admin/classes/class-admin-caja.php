<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Panel de finanzas en wp-admin:
 * - Overview global
 * - Caja por motorizado + registro de liquidaciones
 * - Caja por cliente + pago DHV→cliente + revisión pago cliente→DHV
 */
class WCFIN_Admin_Caja {

    public function __construct() {
        add_action('admin_menu',    [$this, 'registrar_menu']);
        add_action('admin_notices', [$this, 'mostrar_notice']);
        add_action('admin_post_wcfin_liquidar',         [$this, 'handle_liquidar']);
        add_action('admin_post_wcfin_pago_dhv_cliente', [$this, 'handle_pago_dhv_cliente']);
        add_action('admin_post_wcfin_revisar_pago',     [$this, 'handle_revisar_pago']);

        // AJAX para subida de comprobantes del admin
        add_action('wp_ajax_wcfin_subir_comprobante',   [$this, 'ajax_subir_comprobante']);
    }

    public function registrar_menu(): void {
        // Submenú bajo "Finanzas" existente
        add_submenu_page('wcfin-reportes', 'Panel de Cajas', '💼 Panel de Cajas',
            'manage_options', 'wcfin-cajas', [$this, 'pagina_overview']);
        add_submenu_page('wcfin-reportes', 'Caja Motorizados', 'Motorizados',
            'manage_options', 'wcfin-caja-drivers', [$this, 'pagina_drivers']);
        add_submenu_page('wcfin-reportes', 'Caja Clientes', 'Clientes',
            'manage_options', 'wcfin-caja-clientes', [$this, 'pagina_clientes']);
    }

    /* ── Páginas ──────────────────────────────────────────────────────── */

    public function pagina_overview(): void {
        if ( ! current_user_can('manage_options') ) wp_die();
        $resumen     = WCFIN_Caja::resumen_global();
        $cuentas     = WCFIN_Database::CUENTAS;
        $drivers     = WCFIN_Caja::todos_los_drivers();
        $clientes    = WCFIN_Caja::todos_los_clientes();
        $pendientes  = $this->pagos_pendientes_revision();
        wcfin_tpl('admin-caja/overview.tpl.php', compact('resumen','cuentas','drivers','clientes','pendientes'));
    }

    public function pagina_drivers(): void {
        if ( ! current_user_can('manage_options') ) wp_die();
        $driver_id = intval($_GET['driver'] ?? 0);
        if ( $driver_id ) {
            $driver        = get_userdata($driver_id);
            $balance       = WCFIN_Caja::balance_driver($driver_id);
            $liquidado     = WCFIN_Caja::liquidado_driver($driver_id);
            $saldo         = WCFIN_Caja::saldo_pendiente_driver($driver_id);
            $envios        = WCFIN_Caja::envios_driver($driver_id);
            $liquidaciones = WCFIN_Caja::liquidaciones_driver($driver_id);
            wcfin_tpl('admin-caja/driver-detalle.tpl.php', compact('driver','balance','liquidado','saldo','envios','liquidaciones'));
        } else {
            $drivers = WCFIN_Caja::todos_los_drivers();
            wcfin_tpl('admin-caja/drivers-lista.tpl.php', compact('drivers'));
        }
    }

    public function pagina_clientes(): void {
        if ( ! current_user_can('manage_options') ) wp_die();
        $user_id = intval($_GET['cliente'] ?? 0);
        if ( $user_id ) {
            $cliente      = get_userdata($user_id);
            $dhv_debe     = WCFIN_Caja::dhv_debe_a_cliente($user_id);
            $cliente_debe = WCFIN_Caja::cliente_debe_a_dhv($user_id);
            $envios       = WCFIN_Caja::envios_cliente($user_id);
            $pagos        = WCFIN_Caja::pagos_cliente($user_id);
            wcfin_tpl('admin-caja/cliente-detalle.tpl.php', compact('cliente','dhv_debe','cliente_debe','envios','pagos'));
        } else {
            $clientes  = WCFIN_Caja::todos_los_clientes();
            $pendientes= $this->pagos_pendientes_revision();
            wcfin_tpl('admin-caja/clientes-lista.tpl.php', compact('clientes','pendientes'));
        }
    }

    /* ── Handlers ─────────────────────────────────────────────────────── */

    public function handle_liquidar(): void {
        check_admin_referer('wcfin_liquidar_nonce');
        if ( ! current_user_can('manage_options') ) wp_die();

        $driver_id      = intval($_POST['driver_id'] ?? 0);
        $monto          = floatval($_POST['monto'] ?? 0);
        $metodo         = sanitize_text_field(wp_unslash($_POST['metodo'] ?? 'efectivo'));
        $notas          = sanitize_textarea_field(wp_unslash($_POST['notas'] ?? ''));
        $comprobante    = esc_url_raw(wp_unslash($_POST['comprobante_url'] ?? ''));

        if ( ! $driver_id || $monto <= 0 ) {
            wcfin_redirect('wcfin-caja-drivers', 'error_req', ['driver' => $driver_id]);
        }

        WCFIN_Caja::registrar_liquidacion($driver_id, $monto, $metodo, $notas, $comprobante);
        wcfin_redirect('wcfin-caja-drivers', 'liquidacion_ok', ['driver' => $driver_id]);
    }

    public function handle_pago_dhv_cliente(): void {
        check_admin_referer('wcfin_pago_dhv_nonce');
        if ( ! current_user_can('manage_options') ) wp_die();

        $user_id     = intval($_POST['user_id']   ?? 0);
        $monto       = floatval($_POST['monto']   ?? 0);
        $metodo      = sanitize_text_field(wp_unslash($_POST['metodo'] ?? ''));
        $referencia  = sanitize_text_field(wp_unslash($_POST['referencia'] ?? ''));
        $comprobante = esc_url_raw(wp_unslash($_POST['comprobante_url'] ?? ''));
        $notas       = sanitize_textarea_field(wp_unslash($_POST['notas'] ?? ''));

        if ( ! $user_id || $monto <= 0 ) {
            wcfin_redirect('wcfin-caja-clientes', 'error_req', ['cliente' => $user_id]);
        }

        WCFIN_Caja::dhv_declara_pago($user_id, $monto, $metodo, $referencia, $comprobante, $notas);
        wcfin_redirect('wcfin-caja-clientes', 'pago_dhv_ok', ['cliente' => $user_id]);
    }

    public function handle_revisar_pago(): void {
        check_admin_referer('wcfin_revisar_nonce');
        if ( ! current_user_can('manage_options') ) wp_die();

        $pago_id    = intval($_POST['pago_id'] ?? 0);
        $estado     = in_array($_POST['estado'] ?? '', ['aprobado','rechazado']) ? $_POST['estado'] : '';
        $notas      = sanitize_textarea_field(wp_unslash($_POST['notas_admin'] ?? ''));
        $user_id    = intval($_POST['user_id'] ?? 0);

        if ( ! $pago_id || ! $estado ) {
            wcfin_redirect('wcfin-caja-clientes', 'error_req', ['cliente' => $user_id]);
        }
        WCFIN_Caja::revisar_pago($pago_id, $estado, $notas);
        wcfin_redirect('wcfin-caja-clientes', 'revision_ok', ['cliente' => $user_id]);
    }

    /* ── AJAX: subir comprobante ──────────────────────────────────────── */

    public function ajax_subir_comprobante(): void {
        check_ajax_referer('wcfin_subir_comp', 'nonce');
        if ( ! current_user_can('manage_options') && ! is_user_logged_in() ) {
            wp_send_json_error('Sin permisos.');
        }
        if ( empty($_FILES['file']) ) {
            wp_send_json_error('No se recibió archivo.');
        }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $upload = wp_handle_upload($_FILES['file'], ['test_form' => false]);
        if ( isset($upload['error']) ) {
            wp_send_json_error($upload['error']);
        }
        $attach_id = wp_insert_attachment([
            'post_mime_type' => $upload['type'],
            'post_title'     => sanitize_file_name(basename($upload['file'])),
            'post_status'    => 'inherit',
        ], $upload['file']);
        wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $upload['file']));

        wp_send_json_success([
            'url'       => $upload['url'],
            'attach_id' => $attach_id,
        ]);
    }

    /* ── Helpers ──────────────────────────────────────────────────────── */

    private function pagos_pendientes_revision(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT pr.*, u.display_name as cliente_nombre
             FROM {$wpdb->prefix}wcfin_pagos_remitente pr
             LEFT JOIN {$wpdb->prefix}users u ON u.ID = pr.user_id
             WHERE pr.estado = 'pendiente' AND pr.direccion = 'cliente_a_dhv'
             ORDER BY pr.fecha_envio ASC"
        ) ?: [];
    }

    public function mostrar_notice(): void {
        $key = sanitize_key($_GET['wcfin_msg'] ?? '');
        if (!$key) return;
        $msgs = [
            'liquidacion_ok' => ['success','✅ Liquidación registrada correctamente.'],
            'pago_dhv_ok'    => ['success','✅ Pago registrado. El cliente puede verlo en su panel.'],
            'revision_ok'    => ['success','✅ Revisión guardada.'],
            'error_req'      => ['error',  '❌ Faltan campos obligatorios.'],
        ];
        if (isset($msgs[$key])) {
            [$t,$m] = $msgs[$key];
            echo "<div class='notice notice-{$t} is-dismissible'><p>{$m}</p></div>";
        }
    }
}

new WCFIN_Admin_Caja();
