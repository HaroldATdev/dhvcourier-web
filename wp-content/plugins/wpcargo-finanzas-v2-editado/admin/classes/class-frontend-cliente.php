<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Shortcodes del panel financiero para clientes y drivers.
 *
 * [wcfin-mi-cuenta] → Panel del cliente wpcargo_client
 * [wcfin-mi-caja]   → Panel del motorizado wpcargo_driver
 */
class WCFIN_Frontend_Cliente {

    public function __construct() {
        add_shortcode('wcfin-mi-cuenta', [$this, 'render_cliente']);
        add_shortcode('wcfin-mi-caja',   [$this, 'render_driver']);

        // Handler para que el driver solicite liquidación (envía mensaje al admin)
        add_action('admin_post_wcfin_driver_solicita',     [$this, 'handle_driver_solicita']);

        // Handler para que el driver suba comprobante de liquidación
        add_action('admin_post_wcfin_driver_sube_voucher', [$this, 'handle_driver_sube_voucher']);
    }

    // ── Panel Cliente ─────────────────────────────────────────────────────────

    public function render_cliente(): string {
        if ( ! is_user_logged_in() ) {
            return '<div class="alert alert-warning"><i class="fa fa-lock mr-2"></i>Debes iniciar sesión.</div>';
        }
        // Admins también pueden ver (para revisar cómo ve el cliente)
        if ( ! wcfin_es_cliente() && ! wcfin_es_admin() ) {
            return '<div class="alert alert-warning"><i class="fa fa-lock mr-2"></i>Esta sección es solo para clientes.</div>';
        }

        $user_id  = get_current_user_id();
        $dhv_debe = WCFIN_Caja::dhv_debe_a_cliente($user_id);
        $yo_debo  = WCFIN_Caja::cliente_debe_a_dhv($user_id);
        $envios   = WCFIN_Caja::envios_cliente($user_id, 30);
        $pagos    = WCFIN_Caja::pagos_cliente($user_id);
        $nonce_comp = wp_create_nonce('wcfin_subir_comp');

        ob_start();
        wcfin_tpl('frontend/cliente/panel.tpl.php', compact('dhv_debe','yo_debo','envios','pagos','nonce_comp'));
        return ob_get_clean();
    }

    // ── Panel Driver ──────────────────────────────────────────────────────────

    public function render_driver(): string {
        if ( ! is_user_logged_in() ) {
            return '<div class="alert alert-warning"><i class="fa fa-lock mr-2"></i>Debes iniciar sesión.</div>';
        }
        if ( ! wcfin_es_driver() && ! wcfin_es_admin() ) {
            return '<div class="alert alert-warning"><i class="fa fa-lock mr-2"></i>Esta sección es solo para motorizados.</div>';
        }

        $driver_id    = get_current_user_id();
        $balance      = WCFIN_Caja::balance_driver($driver_id);
        $liquidado    = WCFIN_Caja::liquidado_driver($driver_id);
        $saldo        = WCFIN_Caja::saldo_pendiente_driver($driver_id);
        $envios       = WCFIN_Caja::envios_driver($driver_id, 30);
        $liquidaciones= WCFIN_Caja::liquidaciones_driver($driver_id);

        ob_start();
        wcfin_tpl('frontend/driver/panel.tpl.php', compact('driver_id','balance','liquidado','saldo','envios','liquidaciones'));
        return ob_get_clean();
    }

    // ── Handler: driver solicita revisión de saldo ────────────────────────────

    public function handle_driver_solicita(): void {
        check_admin_referer('wcfin_driver_solicita_nonce');
        if ( ! wcfin_es_driver() ) wp_die('Sin permisos.');

        $driver_id = get_current_user_id();
        $driver    = wp_get_current_user();
        $saldo     = WCFIN_Caja::saldo_pendiente_driver($driver_id);
        $notas     = sanitize_textarea_field(wp_unslash($_POST['notas'] ?? ''));

        // Notificar al admin por email
        $admins = get_users(['role' => 'administrator', 'number' => 5]);
        foreach ($admins as $admin) {
            wp_mail(
                $admin->user_email,
                '[DHV] ' . esc_html($driver->display_name) . ' tiene saldo pendiente de liquidación',
                "El motorizado " . esc_html($driver->display_name) . " tiene un saldo pendiente de S/ " . number_format($saldo, 2) . ".\n\n" .
                "Notas: {$notas}\n\n" .
                "Revisa su caja: " . wcfin_url('wcfin-caja-drivers', ['driver' => $driver_id])
            );
        }

        wp_safe_redirect(wcfin_driver_url(['wcfin_msg' => 'solicitud_enviada']));
        exit;
    }
    // ── Handler: driver sube comprobante de su liquidación ───────────────────

    public function handle_driver_sube_voucher(): void {
        check_admin_referer('wcfin_driver_sube_voucher_nonce');
        if ( ! wcfin_es_driver() ) wp_die('Sin permisos.');

        $driver_id  = get_current_user_id();
        $monto      = floatval(wp_unslash($_POST['monto']     ?? 0));
        $metodo     = sanitize_text_field(wp_unslash($_POST['metodo']    ?? ''));
        $referencia = sanitize_text_field(wp_unslash($_POST['referencia'] ?? ''));
        $notas      = sanitize_textarea_field(wp_unslash($_POST['notas'] ?? ''));

        if ( $monto <= 0 ) {
            wp_safe_redirect(wcfin_driver_url(['wcfin_msg' => 'error_monto'])); exit;
        }

        // Verificar y subir comprobante (requerido)
        $comprobante_url = '';
        if ( ! empty($_FILES['comprobante']['name']) ) {
            $tipo = $_FILES['comprobante']['type'] ?? '';
            $permitidos = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
            if ( ! in_array($tipo, $permitidos, true) ) {
                wp_safe_redirect(wcfin_driver_url(['wcfin_msg' => 'error_archivo'])); exit;
            }
            if ( $_FILES['comprobante']['size'] > 5 * 1024 * 1024 ) {
                wp_safe_redirect(wcfin_driver_url(['wcfin_msg' => 'error_archivo'])); exit;
            }
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $upload = wp_handle_upload($_FILES['comprobante'], ['test_form' => false]);
            if ( ! isset($upload['error']) ) {
                $comprobante_url = $upload['url'];
            }
        }

        if ( empty($comprobante_url) ) {
            wp_safe_redirect(wcfin_driver_url(['wcfin_msg' => 'error_archivo'])); exit;
        }

        // Registrar liquidación en estado "pendiente" (el admin la aprueba)
        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}wcfin_liquidaciones", [
            'driver_id'       => $driver_id,
            'monto'           => $monto,
            'metodo'          => $metodo . ($referencia ? ' · Ref: ' . $referencia : ''),
            'notas'           => $notas,
            'comprobante_url' => esc_url_raw($comprobante_url),
            'registrado_por'  => $driver_id,  // el propio driver
            'fecha'           => current_time('mysql'),
            // campo 'estado' para distinguir auto-subido vs. confirmado por admin
            // Se usa 'estado' si la columna existe (v2.1+), de lo contrario se ignora
        ]);
        $liq_id = $wpdb->insert_id;

        // Intentar guardar el estado pendiente en meta (por si la columna no existe aún)
        update_option("wcfin_liq_{$liq_id}_estado", 'pendiente');

        // Notificar al admin por email
        $driver = wp_get_current_user();
        $saldo  = WCFIN_Caja::saldo_pendiente_driver($driver_id);
        $admins = get_users(['role' => 'administrator', 'number' => 5]);
        foreach ($admins as $admin) {
            wp_mail(
                $admin->user_email,
                '[DHV] ' . esc_html($driver->display_name) . ' subió comprobante de liquidación',
                "El motorizado " . esc_html($driver->display_name) . " subió un comprobante de liquidación.\n\n" .
                "Monto declarado: S/ " . number_format($monto, 2) . "\n" .
                "Método: {$metodo}\n" .
                ($referencia ? "Referencia: {$referencia}\n" : '') .
                ($notas ? "Notas: {$notas}\n" : '') .
                "\nComprobante: {$comprobante_url}\n\n" .
                "Saldo pendiente del motorizado: S/ " . number_format($saldo, 2) . "\n\n" .
                "Revisa su caja: " . wcfin_frontend_url(['wcfin_vista' => 'caja-drivers', 'driver' => $driver_id])
            );
        }

        // Disparar hook para integraciones externas
        do_action('wcfin_driver_subio_voucher', $driver_id, $liq_id, $monto, $comprobante_url);

        wp_safe_redirect(wcfin_driver_url(['wcfin_msg' => 'comprobante_enviado']));
        exit;
    }
}

new WCFIN_Frontend_Cliente();
