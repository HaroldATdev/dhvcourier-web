<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WCFIN_Frontend {

    public function __construct() {
        add_shortcode( 'wcfin-finanzas',          [ $this, 'render_shortcode' ] );
        add_filter(    'wpcfe_after_sidebar_menus',[ $this, 'sidebar_item'    ], 30, 1 );
        add_action(    'wp_enqueue_scripts',       [ $this, 'encolar_assets'  ], 100 );

        // Handlers de formularios frontend (admin_post funciona para admins logueados)
        add_action( 'admin_post_wcfin_fe_confirmar', [ $this, 'handle_confirmar' ] );
    }

    // ── Menú del sidebar de WPCargo ───────────────────────────────────────────

    public function sidebar_item( array $menu ): array {
        if ( ! wcfin_es_admin() ) return $menu;
        $menu['wcfin-menu'] = [
            'page-id'   => wcfin_get_frontend_page_id(),
            'label'     => 'Finanzas',
            'permalink' => wcfin_frontend_url(),
            'icon'      => 'fa-line-chart',
        ];
        return $menu;
    }

    // ── Assets ────────────────────────────────────────────────────────────────

    public function encolar_assets(): void {
        if ( (int) get_queried_object_id() !== wcfin_get_frontend_page_id() ) return;
        // Sin CSS propio — usamos el CSS del tema WPCargo que ya está cargado
    }

    // ── Shortcode principal ───────────────────────────────────────────────────

    public function render_shortcode(): string {
        if ( ! wcfin_es_admin() ) {
            return '<div class="alert alert-warning">Acceso restringido.</div>';
        }
        ob_start();
        $vista = sanitize_key( $_GET['wcfin_vista'] ?? 'reportes' );
        match ( $vista ) {
            'condiciones' => $this->render_condiciones(),
            'metodos'     => $this->render_metodos(),
            'penalidades' => $this->render_penalidades(),
            default       => $this->render_reportes(),
        };
        return ob_get_clean();
    }

    // ── Vistas frontend ───────────────────────────────────────────────────────

    private function render_reportes(): void {
        $desde   = sanitize_text_field( wp_unslash( $_GET['desde'] ?? date('Y-m-01') ) );
        $hasta   = sanitize_text_field( wp_unslash( $_GET['hasta'] ?? date('Y-m-d') ) );
        $datos   = WCFIN_Motor::get_resumen_periodo($desde, $hasta);
        $cuentas = WCFIN_Database::CUENTAS;
        $actores = WCFIN_Database::ACTORES;
        $page_url= wcfin_frontend_url();
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

    // ── Handler: confirmar pago desde frontend ────────────────────────────────

    public function handle_confirmar(): void {
        $trans_id    = intval($_GET['trans_id']    ?? 0);
        $shipment_id = intval($_GET['shipment_id'] ?? 0);
        check_admin_referer('wcfin_confirmar_'.$trans_id);
        if ( ! wcfin_es_admin() ) wp_die('Sin permisos.');
        WCFIN_Motor::confirmar($trans_id);
        wp_safe_redirect( get_edit_post_link($shipment_id,'') );
        exit;
    }
}

new WCFIN_Frontend();
