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

        // Handler: driver solicita revisión de saldo (mensaje al admin)
        add_action('admin_post_wcfin_driver_solicita', [$this, 'handle_driver_solicita']);

        // Handler: driver sube comprobante de liquidación (NEW)
        add_action('admin_post_wcfin_driver_sube_comprobante', [$this, 'handle_driver_sube_comprobante']);

        // Handler: admin revisa (aprueba/rechaza) liquidación de driver (NEW)
        add_action('admin_post_wcfin_revisar_liquidacion', [$this, 'handle_revisar_liquidacion']);
    }

    // ── Panel Cliente ─────────────────────────────────────────────────────────

    public function render_cliente(): string {
        if ( ! is_user_logged_in() ) {
            return '<div class="alert alert-warning"><i class="fa fa-lock mr-2"></i>Debes iniciar sesión.</div>';
        }
        if ( ! wcfin_es_cliente() && ! wcfin_es_admin() ) {
            return '<div class="alert alert-warning"><i class="fa fa-lock mr-2"></i>Esta sección es solo para clientes.</div>';
        }

        $user_id    = get_current_user_id();
        $dhv_debe   = WCFIN_Caja::dhv_debe_a_cliente($user_id);
        $yo_debo    = WCFIN_Caja::cliente_debe_a_dhv($user_id);
        $envios     = WCFIN_Caja::envios_cliente($user_id, 30);
        $pagos      = WCFIN_Caja::pagos_cliente($user_id);
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

        $driver_id     = get_current_user_id();
        $balance       = WCFIN_Caja::balance_driver($driver_id);
        $liquidado     = WCFIN_Caja::liquidado_driver($driver_id);
        $saldo         = WCFIN_Caja::saldo_pendiente_driver($driver_id);
        $envios        = WCFIN_Caja::envios_driver($driver_id, 30);
        $liquidaciones = WCFIN_Caja::liquidaciones_driver($driver_id);

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

        $admins = get_users(['role' => 'administrator', 'number' => 5]);
        foreach ($admins as $admin) {
            wp_mail(
                $admin->user_email,
                '[DHV] ' . esc_html($driver->display_name) . ' tiene saldo pendiente de liquidación',
                "El motorizado " . esc_html($driver->display_name) . " tiene un saldo pendiente de S/ " . number_format($saldo, 2) . ".\n\n"
                . "Notas: {$notas}\n\nRevisa su caja: " . wcfin_url('wcfin-caja-drivers', ['driver' => $driver_id])
            );
        }

        wp_safe_redirect(wcfin_driver_url(['wcfin_msg' => 'solicitud_enviada']));
        exit;
    }

    // ── Handler: driver sube comprobante de liquidación ───────────────────────

    public function handle_driver_sube_comprobante(): void {
        check_admin_referer('wcfin_driver_comp_nonce');
        if ( ! wcfin_es_driver() ) wp_die('Sin permisos.');

        $redirect = wcfin_driver_url();
        $driver_id = get_current_user_id();
        $monto     = floatval(wp_unslash($_POST['monto']     ?? 0));
        $metodo    = sanitize_text_field(wp_unslash($_POST['metodo']    ?? ''));
        $referencia= sanitize_text_field(wp_unslash($_POST['referencia']?? ''));
        $notas     = sanitize_textarea_field(wp_unslash($_POST['notas'] ?? ''));

        if ( $monto <= 0 ) {
            wp_safe_redirect(add_query_arg('wcfin_msg','comp_monto_invalido',$redirect)); exit;
        }

        // Validar y subir archivo
        if ( empty($_FILES['comprobante']['name']) ) {
            wp_safe_redirect(add_query_arg('wcfin_msg','comp_sin_archivo',$redirect)); exit;
        }

        // Validar tipo de archivo
        $allowed_types = ['image/jpeg','image/png','image/webp','application/pdf'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['comprobante']['tmp_name']);
        finfo_close($finfo);
        if ( ! in_array($mime, $allowed_types, true) ) {
            wp_safe_redirect(add_query_arg('wcfin_msg','comp_error',$redirect)); exit;
        }

        // Validar tamaño (5 MB máx)
        if ( $_FILES['comprobante']['size'] > 5 * 1024 * 1024 ) {
            wp_safe_redirect(add_query_arg('wcfin_msg','comp_error',$redirect)); exit;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $upload = wp_handle_upload($_FILES['comprobante'], ['test_form' => false]);
        if ( isset($upload['error']) ) {
            wp_safe_redirect(add_query_arg('wcfin_msg','comp_error',$redirect)); exit;
        }

        WCFIN_Caja::driver_declara_liquidacion($driver_id, $monto, $metodo, $referencia, $upload['url'], $notas);

        wp_safe_redirect(add_query_arg('wcfin_msg','comp_enviado',$redirect));
        exit;
    }

    // ── Handler: admin revisa liquidación de driver ───────────────────────────

    public function handle_revisar_liquidacion(): void {
        $liq_id = intval($_POST['liq_id'] ?? 0);
        check_admin_referer('wcfin_revisar_liq_'.$liq_id);
        if ( ! wcfin_es_admin() ) wp_die('Sin permisos.');

        $estado      = sanitize_key($_POST['estado']      ?? 'rechazado');
        $notas_admin = sanitize_textarea_field(wp_unslash($_POST['notas_admin'] ?? ''));

        if ( ! in_array($estado, ['aprobado','rechazado'], true) ) {
            wp_die('Estado inválido.');
        }

        WCFIN_Caja::revisar_liquidacion($liq_id, $estado, $notas_admin);

        // Redirigir de vuelta al panel de cajas/drivers
        $redirect = esc_url_raw(wp_unslash($_POST['_wcfin_redirect'] ?? ''));
        if ( ! $redirect || strpos($redirect, home_url()) !== 0 ) {
            $redirect = wcfin_frontend_url(['wcfin_vista' => 'caja-drivers']);
        }
        wp_safe_redirect(add_query_arg('wcfin_msg', $estado === 'aprobado' ? 'liq_aprobada' : 'liq_rechazada', $redirect));
        exit;
    }
}

new WCFIN_Frontend_Cliente();
