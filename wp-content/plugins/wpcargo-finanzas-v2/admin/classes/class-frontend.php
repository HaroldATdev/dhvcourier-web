<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registra los sidebar items del dashboard WPCargo para cada rol:
 * - Admin/WPAdmin → "Finanzas" (reportes, condiciones, métodos, penalidades)
 * - wpcargo_client → "Mi Cuenta" (balance, deudas, comprobantes)
 * - wpcargo_driver → "Mi Caja" (cobrado, liquidado, saldo pendiente)
 */
class WCFIN_Frontend {

    public function __construct() {
        add_shortcode('wcfin-finanzas', [$this, 'render_admin']);
        add_filter('wpcfe_after_sidebar_menus', [$this, 'sidebar_items'], 30, 1);

        // Handlers frontend admin
        add_action('admin_post_wcfin_fe_confirmar',       [$this, 'handle_confirmar']);

        // Handlers frontend cliente
        add_action('admin_post_wcfin_fe_cliente_paga',    [$this, 'handle_cliente_paga']);

        // AJAX subida de comprobante (cliente sube su propio)
        add_action('wp_ajax_wcfin_fe_subir_comp',         [$this, 'ajax_subir_comprobante']);

        // Redirigir al frontend después de acciones del panel de cajas (liquidar, pago dhv, revisar)
        add_action('admin_post_wcfin_liquidar',           [$this, 'maybe_redirect_to_frontend'], 1);
        add_action('admin_post_wcfin_pago_dhv_cliente',   [$this, 'maybe_redirect_to_frontend'], 1);
        add_action('admin_post_wcfin_revisar_pago',       [$this, 'maybe_redirect_to_frontend'], 1);
    }

    /**
     * Si el formulario incluye _wcfin_redirect (URL frontend), redirigimos allí en lugar de wp-admin.
     * Se ejecuta con prioridad 1 (antes del handler real) pero solo si el campo está presente.
     * El handler original también corre porque no hacemos exit aquí — en cambio lo reemplazamos
     * sobrescribiendo wp_redirect via hook después del handler.
     *
     * Estrategia: guardamos la URL de redirect en una propiedad y la usamos via
     * wp_redirect justo antes de exit.
     */
    private string $frontend_redirect_url = '';

    public function maybe_redirect_to_frontend(): void {
        $url = esc_url_raw(wp_unslash($_POST['_wcfin_redirect'] ?? $_GET['_wcfin_redirect'] ?? ''));
        if ( $url && strpos($url, home_url()) === 0 ) {
            $this->frontend_redirect_url = $url;
            // Hook into wp_redirect to swap the destination
            add_filter('wp_redirect', function( string $location ) use ( $url ): string {
                // Only replace admin-side redirects from these actions
                if ( strpos($location, admin_url()) !== false ) {
                    return $url;
                }
                return $location;
            }, 99);
        }
    }

    // ── Sidebar: añadir items según rol ──────────────────────────────────────

    public function sidebar_items( array $menu ): array {
        if ( ! is_user_logged_in() ) return $menu;

        if ( wcfin_es_admin() ) {
            // Admin ve "Finanzas" (configuración + reportes)
            $menu['wcfin-finanzas'] = [
                'page-id'   => wcfin_get_frontend_page_id(),
                'label'     => 'Finanzas',
                'permalink' => wcfin_frontend_url(),
                'icon'      => 'fa-line-chart',
            ];
            // Admin también ve "Panel de Cajas" directamente
            $menu['wcfin-cajas'] = [
                'page-id'   => wcfin_get_frontend_page_id(),
                'label'     => 'Panel de Cajas',
                'permalink' => wcfin_frontend_url(['wcfin_vista' => 'cajas']),
                'icon'      => 'fa-briefcase',
            ];
        }

        if ( wcfin_es_cliente() ) {
            // Cliente ve "Mi Cuenta" — siempre, aunque no tenga saldo
            $menu['wcfin-mi-cuenta'] = [
                'page-id'   => wcfin_get_cliente_page_id(),
                'label'     => 'Mi Cuenta',
                'permalink' => wcfin_cliente_url(),
                'icon'      => 'fa-money',
            ];
        }

        if ( wcfin_es_driver() ) {
            // Driver ve "Mi Caja" — siempre
            $menu['wcfin-mi-caja'] = [
                'page-id'   => wcfin_get_driver_page_id(),
                'label'     => 'Mi Caja',
                'permalink' => wcfin_driver_url(),
                'icon'      => 'fa-money',
            ];
        }

        return $menu;
    }

    // ── Shortcode admin: [wcfin-finanzas] ────────────────────────────────────

    public function render_admin(): string {
        if ( ! wcfin_es_admin() ) {
            return '<div class="alert alert-warning"><i class="fa fa-lock mr-2"></i>Acceso restringido a administradores.</div>';
        }
        ob_start();
        $vista = sanitize_key($_GET['wcfin_vista'] ?? 'reportes');
        match ($vista) {
            'condiciones'   => $this->render_condiciones(),
            'metodos'       => $this->render_metodos(),
            'penalidades'   => $this->render_penalidades(),
            'cajas'         => $this->render_cajas_overview(),
            'caja-drivers'  => $this->render_caja_drivers(),
            'caja-clientes' => $this->render_caja_clientes(),
            default         => $this->render_reportes(),
        };
        return ob_get_clean();
    }

    private function render_reportes(): void {
        $desde    = sanitize_text_field(wp_unslash($_GET['desde'] ?? date('Y-m-01')));
        $hasta    = sanitize_text_field(wp_unslash($_GET['hasta'] ?? date('Y-m-d')));
        $datos    = WCFIN_Motor::get_resumen_periodo($desde, $hasta);
        $cuentas  = WCFIN_Database::CUENTAS;
        $actores  = WCFIN_Database::ACTORES;
        $page_url = wcfin_frontend_url();
        wcfin_tpl('frontend/reportes.tpl.php', compact('desde','hasta','datos','cuentas','actores','page_url'));
    }

    private function render_condiciones(): void {
        $edit_id     = intval($_GET['editar'] ?? 0);
        $condicion   = $edit_id ? WCFIN_Condicion::obtener_por_id($edit_id) : null;
        $componentes = $edit_id ? WCFIN_Condicion::obtener_componentes($edit_id) : [];
        $lista       = WCFIN_Condicion::obtener_todas();
        $actores     = WCFIN_Database::ACTORES;
        $page_url    = wcfin_frontend_url();
        wcfin_tpl('frontend/condiciones.tpl.php', compact('edit_id','condicion','componentes','lista','actores','page_url'));
    }

    private function render_metodos(): void {
        $edit_id     = intval($_GET['editar'] ?? 0);
        $metodo      = $edit_id ? WCFIN_Metodo::obtener_por_id($edit_id) : null;
        $reglas      = $edit_id ? WCFIN_Metodo::obtener_reglas($edit_id) : [];
        $lista       = WCFIN_Metodo::obtener_todos();
        $condiciones = WCFIN_Condicion::obtener_activas();
        $actores     = WCFIN_Database::ACTORES;
        $cuentas     = WCFIN_Database::CUENTAS;
        $tipos_medio = ['efectivo'=>'Efectivo','digital'=>'Digital (YAPE/PLIN)','banco'=>'Depósito bancario','pos'=>'POS / Tarjeta','prepago'=>'Prepago'];
        $vars_base   = ['monto_total'=>'Monto total','monto_servicio'=>'Costo del servicio','monto_producto'=>'Valor del producto','monto_extras'=>'Cargos adicionales','monto_contraentrega'=>'Monto contraentrega'];
        $page_url    = wcfin_frontend_url();
        wcfin_tpl('frontend/metodos.tpl.php', compact('edit_id','metodo','reglas','lista','condiciones','actores','cuentas','tipos_medio','vars_base','page_url'));
    }

    private function render_penalidades(): void {
        $edit_id   = intval($_GET['editar'] ?? 0);
        $penalidad = $edit_id ? WCFIN_Penalidad::obtener_por_id($edit_id) : null;
        $lista     = WCFIN_Penalidad::obtener_todas();
        $actores   = WCFIN_Database::ACTORES;
        $cuentas   = WCFIN_Database::CUENTAS;
        $page_url  = wcfin_frontend_url();
        wcfin_tpl('frontend/penalidades.tpl.php', compact('edit_id','penalidad','lista','actores','cuentas','page_url'));
    }

    // ── Handler: confirmar pago (admin) ───────────────────────────────────────

    public function handle_confirmar(): void {
        $trans_id    = intval($_GET['trans_id']    ?? 0);
        $shipment_id = intval($_GET['shipment_id'] ?? 0);
        check_admin_referer('wcfin_confirmar_'.$trans_id);
        if ( ! wcfin_es_admin() ) wp_die('Sin permisos.');
        WCFIN_Motor::confirmar($trans_id);
        wp_safe_redirect(get_edit_post_link($shipment_id,''));
        exit;
    }

    // ── Handler: cliente sube comprobante de pago ────────────────────────────

    public function handle_cliente_paga(): void {
        check_admin_referer('wcfin_fe_pago_nonce');
        if ( ! wcfin_es_cliente() ) wp_die('Sin permisos.');

        $user_id    = get_current_user_id();
        $monto      = floatval(wp_unslash($_POST['monto'] ?? 0));
        $metodo     = sanitize_text_field(wp_unslash($_POST['metodo'] ?? ''));
        $referencia = sanitize_text_field(wp_unslash($_POST['referencia'] ?? ''));
        $notas      = sanitize_textarea_field(wp_unslash($_POST['notas'] ?? ''));

        if ( $monto <= 0 ) {
            wp_safe_redirect(wcfin_cliente_url(['wcfin_msg'=>'error_monto'])); exit;
        }

        // Subir comprobante
        $comprobante_url = '';
        if ( ! empty($_FILES['comprobante']['name']) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $upload = wp_handle_upload($_FILES['comprobante'], ['test_form'=>false]);
            if ( ! isset($upload['error']) ) $comprobante_url = $upload['url'];
        }

        if ( empty($comprobante_url) ) {
            wp_safe_redirect(wcfin_cliente_url(['wcfin_msg'=>'error_req'])); exit;
        }

        WCFIN_Caja::cliente_declara_pago($user_id, $monto, $metodo, $referencia, $comprobante_url, $notas);
        do_action('wcfin_cliente_declaro_pago', $user_id, $monto);
        wp_safe_redirect(wcfin_cliente_url(['wcfin_msg'=>'pago_enviado']));
        exit;
    }

    // ── AJAX: subida de comprobante ───────────────────────────────────────────

    public function ajax_subir_comprobante(): void {
        check_ajax_referer('wcfin_subir_comp', 'nonce');
        if ( ! is_user_logged_in() ) wp_send_json_error('Sin permisos.');
        if ( empty($_FILES['file']) ) wp_send_json_error('Sin archivo.');
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $upload = wp_handle_upload($_FILES['file'], ['test_form'=>false]);
        if ( isset($upload['error']) ) wp_send_json_error($upload['error']);
        wp_send_json_success(['url'=>$upload['url']]);
    }

    // ── Panel de Cajas (frontend) ─────────────────────────────────────────────

    private function render_cajas_overview(): void {
        $resumen    = WCFIN_Caja::resumen_global();
        $drivers    = WCFIN_Caja::todos_los_drivers();
        $clientes   = WCFIN_Caja::todos_los_clientes();
        $pendientes = $this->pagos_pendientes_revision();
        wcfin_tpl('frontend/cajas/overview.tpl.php', compact('resumen', 'drivers', 'clientes', 'pendientes'));
    }

    private function render_caja_drivers(): void {
        $driver_id = intval($_GET['driver'] ?? 0);
        if ( $driver_id ) {
            $driver        = get_userdata($driver_id);
            $balance       = WCFIN_Caja::balance_driver($driver_id);
            $liquidado     = WCFIN_Caja::liquidado_driver($driver_id);
            $saldo         = WCFIN_Caja::saldo_pendiente_driver($driver_id);
            $envios        = WCFIN_Caja::envios_driver($driver_id);
            $liquidaciones = WCFIN_Caja::liquidaciones_driver($driver_id);
            wcfin_tpl('frontend/cajas/driver-detalle.tpl.php', compact('driver', 'balance', 'liquidado', 'saldo', 'envios', 'liquidaciones'));
        } else {
            $drivers = WCFIN_Caja::todos_los_drivers();
            wcfin_tpl('frontend/cajas/drivers-lista.tpl.php', compact('drivers'));
        }
    }

    private function render_caja_clientes(): void {
        $user_id = intval($_GET['cliente'] ?? 0);
        if ( $user_id ) {
            $cliente      = get_userdata($user_id);
            $dhv_debe     = WCFIN_Caja::dhv_debe_a_cliente($user_id);
            $cliente_debe = WCFIN_Caja::cliente_debe_a_dhv($user_id);
            $envios       = WCFIN_Caja::envios_cliente($user_id);
            $pagos        = WCFIN_Caja::pagos_cliente($user_id);
            wcfin_tpl('frontend/cajas/cliente-detalle.tpl.php', compact('cliente', 'dhv_debe', 'cliente_debe', 'envios', 'pagos'));
        } else {
            $clientes   = WCFIN_Caja::todos_los_clientes();
            $pendientes = $this->pagos_pendientes_revision();
            wcfin_tpl('frontend/cajas/clientes-lista.tpl.php', compact('clientes', 'pendientes'));
        }
    }

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
}

new WCFIN_Frontend();
