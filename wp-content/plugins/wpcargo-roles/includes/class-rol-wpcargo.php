<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Gestiona el rol personalizado 'wpcargo_admin'.
 *
 * CARACTERÍSTICAS DEL ROL:
 * - Puede hacer login en WordPress
 * - Puede acceder al dashboard FRONTEND de WPCargo
 * - NO puede acceder a wp-admin (redirigido automáticamente)
 * - Solo ve los módulos que el superadmin le asigne
 *
 * DIFERENCIA CON 'administrator':
 * - administrator → acceso total a wp-admin + dashboard WPCargo
 * - wpcargo_admin → solo dashboard WPCargo (asistentes, operadores)
 */
class WCROL_Rol_WPCargo {

    const SLUG = 'wpcargo_admin';
    const LABEL = 'Administrador WPCargo';

    public static function init(): void {
        // Bloquear acceso a wp-admin para usuarios wpcargo_admin
        add_action('admin_init',   [__CLASS__, 'bloquear_wp_admin']);
        // Redirigir login al dashboard frontend
        add_filter('login_redirect',[__CLASS__, 'redirigir_login'], 10, 3);
        // Asegurar que Frontend Manager lo considere en su redirección por roles
        add_filter('wpcfe_login_redirect_dashboard_role', [__CLASS__, 'agregar_rol_en_redireccion'], 10, 1);
        // Permitir acceso al dashboard aunque la opción de roles no esté sincronizada
        add_filter('can_wpcfe_access_dashboard', [__CLASS__, 'permitir_dashboard_para_rol'], 10, 1);
        // Integrar el rol con la lista de acceso del dashboard WPCargo
        add_action('init', [__CLASS__, 'asegurar_acceso_dashboard'], 20);
        // Ocultar barra de admin en frontend para estos usuarios
        add_action('after_setup_theme', [__CLASS__, 'ocultar_admin_bar']);
    }

    public static function crear_rol(): void {
        // Eliminar y recrear para asegurar capabilities correctas
        remove_role(self::SLUG);
        add_role(self::SLUG, self::LABEL, [
            'read'                    => true,  // requerido para login
            'wpcargo_dashboard_access'=> true,  // cap personalizada
            // NO incluir 'manage_options' ni caps de admin
        ]);

        self::asegurar_acceso_dashboard();
    }

    /**
     * Asegura que el rol wpcargo_admin esté incluido en la lista
     * de roles con acceso al dashboard de WPCargo Frontend Manager.
     */
    public static function asegurar_acceso_dashboard(): void {
        $roles = get_option('wpcfe_access_dashboard_role');
        $roles = is_array($roles) ? $roles : [];

        if ( ! in_array(self::SLUG, $roles, true) ) {
            $roles[] = self::SLUG;
            update_option('wpcfe_access_dashboard_role', array_values(array_unique($roles)), false);
        }
    }

    /**
     * Añade el rol en la lista usada por wpcargo-frontend-manager para login redirect.
     */
    public static function agregar_rol_en_redireccion( array $roles ): array {
        if ( ! in_array(self::SLUG, $roles, true) ) {
            $roles[] = self::SLUG;
        }
        return array_values(array_unique($roles));
    }

    /**
     * Override defensivo del permiso de acceso al dashboard de WPCargo.
     */
    public static function permitir_dashboard_para_rol( bool $result ): bool {
        if ( $result ) return true;
        if ( ! is_user_logged_in() ) return false;

        $user = wp_get_current_user();
        if ( ! $user || ! ($user instanceof \WP_User) ) return false;

        if ( in_array(self::SLUG, (array)$user->roles, true) ) return true;
        if ( user_can($user, 'wpcargo_dashboard_access') ) return true;

        return false;
    }

    /**
     * Bloquear acceso a wp-admin para rol wpcargo_admin.
     * Excepto peticiones AJAX que son necesarias para el frontend.
     */
    public static function bloquear_wp_admin(): void {
        if ( wp_doing_ajax() ) return;
        if ( ! is_user_logged_in() ) return;
        if ( ! wcrol_es_wpcargo_admin() ) return;

        // Redirigir al dashboard frontend de WPCargo
        $destino = wcrol_frontend_url();
        if ( ! $destino ) $destino = home_url('/');
        wp_safe_redirect($destino);
        exit;
    }

    /**
     * Tras el login, redirigir al dashboard frontend si es wpcargo_admin.
     */
    public static function redirigir_login( string $redirect_to, string $requested, \WP_User|\WP_Error $user ): string {
        if ( is_wp_error($user) ) return $redirect_to;
        if ( ! wcrol_es_wpcargo_admin($user->ID) ) return $redirect_to;
        return wcrol_frontend_url();
    }

    /**
     * Ocultar la barra de administración en el frontend
     * para usuarios wpcargo_admin (no la necesitan).
     */
    public static function ocultar_admin_bar(): void {
        if ( ! is_user_logged_in() ) return;
        if ( wcrol_es_wpcargo_admin() ) {
            show_admin_bar(false);
        }
    }

    /**
     * Cambiar el tipo de acceso de un usuario.
     * 
     * @param int    $user_id
     * @param string $tipo  'wordpress_admin' | 'wpcargo_admin'
     */
    public static function cambiar_tipo( int $user_id, string $tipo ): bool {
        $user = get_userdata($user_id);
        if ( ! $user ) return false;

        // Nunca cambiar el tipo del usuario actual (evitar auto-bloqueo)
        if ( $user_id === get_current_user_id() ) return false;

        if ( $tipo === 'wpcargo_admin' ) {
            // Quitar rol administrator, poner wpcargo_admin
            $user->remove_role('administrator');
            $user->remove_role('editor');
            $user->add_role(self::SLUG);
        } else {
            // Revertir a administrator de WordPress
            $user->remove_role(self::SLUG);
            $user->add_role('administrator');
        }
        return true;
    }

    /** Retorna el tipo de acceso como string legible */
    public static function tipo_acceso( int $user_id ): string {
        if ( wcrol_es_wpcargo_admin($user_id) ) return 'wpcargo_admin';
        if ( wcrol_es_wp_admin($user_id) )      return 'wordpress_admin';
        return 'otro';
    }
}

WCROL_Rol_WPCargo::init();
