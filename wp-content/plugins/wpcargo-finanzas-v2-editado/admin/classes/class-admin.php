<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WCFIN_Admin {

    public function __construct() {
        add_action( 'admin_menu',                          [ $this, 'registrar_menu'     ] );
        add_action( 'admin_notices',                       [ $this, 'mostrar_notice'     ] );
        // Handlers de formularios
        add_action( 'admin_post_wcfin_guardar_condicion',  [ $this, 'handle_condicion'   ] );
        add_action( 'admin_post_wcfin_eliminar_condicion', [ $this, 'handle_del_condicion'] );
        add_action( 'admin_post_wcfin_guardar_metodo',     [ $this, 'handle_metodo'      ] );
        add_action( 'admin_post_wcfin_eliminar_metodo',    [ $this, 'handle_del_metodo'  ] );
        add_action( 'admin_post_wcfin_guardar_penalidad',  [ $this, 'handle_penalidad'   ] );
        add_action( 'admin_post_wcfin_eliminar_penalidad', [ $this, 'handle_del_penalidad'] );
        add_action( 'admin_post_wcfin_toggle_penalidad',   [ $this, 'handle_toggle_pen'  ] );
        add_action( 'admin_post_wcfin_confirmar_pago',     [ $this, 'handle_confirmar'   ] );
    }

    public function registrar_menu(): void {
        add_menu_page(
            'Finanzas', 'Finanzas', 'manage_options',
            'wcfin-reportes', [ $this, 'pagina_reportes' ],
            'dashicons-chart-line', 56
        );
        add_submenu_page('wcfin-reportes','Reportes Financieros','Reportes','manage_options','wcfin-reportes',[$this,'pagina_reportes']);
        add_submenu_page('wcfin-reportes','Condiciones de Pago',  'Condiciones de Pago','manage_options','wcfin-condiciones', [$this,'pagina_condiciones']);
        add_submenu_page('wcfin-reportes','Métodos de Pago',      'Métodos de Pago',    'manage_options','wcfin-metodos',     [$this,'pagina_metodos']);
        add_submenu_page('wcfin-reportes','Penalidades',          'Penalidades',         'manage_options','wcfin-penalidades', [$this,'pagina_penalidades']);
    }

    /* ── PÁGINAS ─────────────────────────────────────────────────────── */

    public function pagina_reportes(): void {
        if ( ! current_user_can('manage_options') ) wp_die('Sin permisos.');
        $desde   = sanitize_text_field( wp_unslash( $_GET['desde'] ?? date('Y-m-01') ) );
        $hasta   = sanitize_text_field( wp_unslash( $_GET['hasta'] ?? date('Y-m-d') ) );
        $datos   = WCFIN_Motor::get_resumen_periodo($desde, $hasta);
        $cuentas = WCFIN_Database::CUENTAS;
        $actores = WCFIN_Database::ACTORES;
        wcfin_tpl('reportes/main.tpl.php', compact('desde','hasta','datos','cuentas','actores'));
    }

    public function pagina_condiciones(): void {
        if ( ! current_user_can('manage_options') ) wp_die('Sin permisos.');
        $edit_id     = intval($_GET['editar'] ?? 0);
        $condicion   = $edit_id ? WCFIN_Condicion::obtener_por_id($edit_id) : null;
        $componentes = $edit_id ? WCFIN_Condicion::obtener_componentes($edit_id) : [];
        $lista       = WCFIN_Condicion::obtener_todas();
        $actores     = WCFIN_Database::ACTORES;
        wcfin_tpl('condiciones/main.tpl.php', compact('edit_id','condicion','componentes','lista','actores'));
    }

    public function pagina_metodos(): void {
        if ( ! current_user_can('manage_options') ) wp_die('Sin permisos.');
        $edit_id     = intval($_GET['editar'] ?? 0);
        $metodo      = $edit_id ? WCFIN_Metodo::obtener_por_id($edit_id) : null;
        $reglas      = $edit_id ? WCFIN_Metodo::obtener_reglas($edit_id) : [];
        $lista       = WCFIN_Metodo::obtener_todos();
        $condiciones = WCFIN_Condicion::obtener_activas();
        $actores     = WCFIN_Database::ACTORES;
        $cuentas     = WCFIN_Database::CUENTAS;
        $tipos_medio = ['efectivo'=>'Efectivo','digital'=>'Digital (YAPE/PLIN)','banco'=>'Depósito bancario','pos'=>'POS / Tarjeta','prepago'=>'Prepago'];
        $vars_base   = ['monto_total'=>'Monto total','monto_servicio'=>'Costo del servicio','monto_producto'=>'Valor del producto','monto_extras'=>'Cargos adicionales','monto_contraentrega'=>'Monto contraentrega'];
        wcfin_tpl('metodos/main.tpl.php', compact('edit_id','metodo','reglas','lista','condiciones','actores','cuentas','tipos_medio','vars_base'));
    }

    public function pagina_penalidades(): void {
        if ( ! current_user_can('manage_options') ) wp_die('Sin permisos.');
        $edit_id   = intval($_GET['editar'] ?? 0);
        $penalidad = $edit_id ? WCFIN_Penalidad::obtener_por_id($edit_id) : null;
        $lista     = WCFIN_Penalidad::obtener_todas();
        $actores   = WCFIN_Database::ACTORES;
        $cuentas   = WCFIN_Database::CUENTAS;
        wcfin_tpl('penalidades/main.tpl.php', compact('edit_id','penalidad','lista','actores','cuentas'));
    }

    /* ── HANDLERS ────────────────────────────────────────────────────── */

    public function handle_condicion(): void {
        check_admin_referer('wcfin_condicion_nonce');
        if ( ! current_user_can('manage_options') ) wp_die('Sin permisos.');
        $id   = intval($_POST['condicion_id'] ?? 0);
        $comp = [];
        foreach ( ($_POST['comp_variable'] ?? []) as $i => $v ) {
            $comp[] = ['variable'=>sanitize_key($v),'label'=>sanitize_text_field($_POST['comp_label'][$i]??$v),'obligatorio'=>isset($_POST['comp_obligatorio'][$i])?1:0];
        }
        $datos = [
            'nombre'      => sanitize_text_field(wp_unslash($_POST['nombre'] ?? '')),
            'slug'        => sanitize_key($_POST['slug'] ?? ''),
            'cobrar_a'    => sanitize_key($_POST['cobrar_a'] ?? 'remitente'),
            'descripcion' => sanitize_textarea_field(wp_unslash($_POST['descripcion'] ?? '')),
            'componentes' => $comp,
        ];
        $r = WCFIN_Condicion::guardar($datos, $id);
        wcfin_redirect('wcfin-condiciones', is_wp_error($r) ? 'error_req' : 'guardado');
    }

    public function handle_del_condicion(): void {
        check_admin_referer('wcfin_condicion_nonce');
        WCFIN_Condicion::eliminar(intval($_POST['condicion_id'] ?? 0));
        wcfin_redirect('wcfin-condiciones','eliminado');
    }

    public function handle_metodo(): void {
        check_admin_referer('wcfin_metodo_nonce');
        if ( ! current_user_can('manage_options') ) wp_die('Sin permisos.');
        $id     = intval($_POST['metodo_id'] ?? 0);
        $reglas = [];
        foreach ( ($_POST['regla_cuenta'] ?? []) as $i => $cuenta ) {
            $reglas[] = [
                'cuenta'       => sanitize_key($cuenta),
                'base'         => sanitize_key($_POST['regla_base'][$i]    ?? 'monto_total'),
                'signo'        => intval($_POST['regla_signo'][$i]         ?? 1),
                'condicion_id' => intval($_POST['regla_condicion'][$i]     ?? 0),
                'descripcion'  => sanitize_text_field($_POST['regla_descripcion'][$i] ?? ''),
            ];
        }
        $datos = [
            'nombre'        => sanitize_text_field(wp_unslash($_POST['nombre'] ?? '')),
            'slug'          => sanitize_key($_POST['slug'] ?? ''),
            'actor_destino' => sanitize_key($_POST['actor_destino'] ?? 'empresa'),
            'tipo'          => sanitize_key($_POST['tipo'] ?? 'efectivo'),
            'requiere_conf' => isset($_POST['requiere_conf']) ? 1 : 0,
            'reglas'        => $reglas,
        ];
        $r = WCFIN_Metodo::guardar($datos, $id);
        wcfin_redirect('wcfin-metodos', is_wp_error($r) ? 'error_req' : 'guardado');
    }

    public function handle_del_metodo(): void {
        check_admin_referer('wcfin_metodo_nonce');
        WCFIN_Metodo::eliminar(intval($_POST['metodo_id'] ?? 0));
        wcfin_redirect('wcfin-metodos','eliminado');
    }

    public function handle_penalidad(): void {
        check_admin_referer('wcfin_penalidad_nonce');
        if ( ! current_user_can('manage_options') ) wp_die('Sin permisos.');
        $id    = intval($_POST['penalidad_id'] ?? 0);
        $datos = [
            'nombre'          => sanitize_text_field(wp_unslash($_POST['nombre'] ?? '')),
            'descripcion'     => sanitize_textarea_field(wp_unslash($_POST['descripcion'] ?? '')),
            'tipo_monto'      => sanitize_key($_POST['tipo_monto'] ?? 'fijo'),
            'monto_default'   => floatval($_POST['monto_default'] ?? 0),
            'aplica_a'        => sanitize_key($_POST['aplica_a'] ?? 'motorizado'),
            'cuenta_afectada' => sanitize_key($_POST['cuenta_afectada'] ?? 'balance_motorizado'),
            'signo'           => intval($_POST['signo'] ?? -1),
        ];
        $r = WCFIN_Penalidad::guardar($datos, $id);
        wcfin_redirect('wcfin-penalidades', is_wp_error($r) ? 'error_req' : 'guardado');
    }

    public function handle_del_penalidad(): void {
        check_admin_referer('wcfin_penalidad_nonce');
        WCFIN_Penalidad::eliminar(intval($_POST['penalidad_id'] ?? 0));
        wcfin_redirect('wcfin-penalidades','eliminado');
    }

    public function handle_toggle_pen(): void {
        check_admin_referer('wcfin_penalidad_nonce');
        WCFIN_Penalidad::toggle_activo(intval($_POST['penalidad_id'] ?? 0));
        wcfin_redirect('wcfin-penalidades');
    }

    public function handle_confirmar(): void {
        $trans_id    = intval($_GET['trans_id']    ?? 0);
        $shipment_id = intval($_GET['shipment_id'] ?? 0);
        check_admin_referer('wcfin_confirmar_'.$trans_id);
        WCFIN_Motor::confirmar($trans_id);
        wp_redirect(get_edit_post_link($shipment_id,''));
        exit;
    }

    public function mostrar_notice(): void {
        $key = sanitize_key($_GET['wcfin_msg'] ?? '');
        if ( ! $key ) return;
        $msgs = [
            'guardado'  => ['success','Guardado correctamente.'],
            'eliminado' => ['success','Eliminado correctamente.'],
            'error_req' => ['error',  'Faltan campos obligatorios.'],
        ];
        if ( isset($msgs[$key]) ) {
            [$t,$m] = $msgs[$key];
            echo "<div class='notice notice-{$t} is-dismissible'><p>{$m}</p></div>";
        }
    }
}

new WCFIN_Admin();
