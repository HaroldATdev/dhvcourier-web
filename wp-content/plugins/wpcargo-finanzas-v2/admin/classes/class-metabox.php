<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WCFIN_Metabox {

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'registrar' ] );
        add_action( 'save_post',      [ $this, 'guardar'   ], 20, 2 );

        // Sincronizar condicion_pago del formulario con finanzas
        add_action( 'wpcargo_after_save_shipment', [ $this, 'sync_condicion_pago' ], 10, 1 );
        add_action( 'save_post_wpcargo',           [ $this, 'sync_condicion_pago' ], 25, 1 );

        // AJAX: aplicar penalidad desde metabox sin recargar
        add_action( 'wp_ajax_wcfin_aplicar_penalidad', [ $this, 'ajax_aplicar_penalidad' ] );
    }

    public function registrar(): void {
        add_meta_box('wcfin_pago','💰 Pago del Envío',[$this,'render'],'wpcargo','normal','high');
    }

    public function render( \WP_Post $post ): void {
        $condiciones = WCFIN_Condicion::obtener_activas();
        $metodos     = WCFIN_Metodo::obtener_activos();
        $penalidades = WCFIN_Penalidad::obtener_activas();
        $trans       = WCFIN_Motor::get_transaccion($post->ID);
        $movimientos = WCFIN_Motor::get_movimientos($post->ID);
        $vars_ex     = $trans ? json_decode($trans->variables_json??'{}',true) : [];
        $cuentas     = WCFIN_Database::CUENTAS;
        $actores     = WCFIN_Database::ACTORES;

        // Autodetectar condición sugerida desde condicion_pago del formulario
        $condicion_pago     = get_post_meta($post->ID,'condicion_pago',true);
        $condicion_sugerida = 0;
        if ( $condicion_pago && ! $trans ) {
            $condicion_sugerida = WCFIN_Motor::condicion_id_desde_pago($condicion_pago);
        }

        // Mapa componentes por condicion_id para JS
        $comp_map = [];
        foreach ( $condiciones as $c ) {
            $comps = WCFIN_Condicion::obtener_componentes((int)$c->id);
            $comp_map[$c->id] = array_map(fn($x)=>['var'=>$x->variable,'label'=>$x->label,'req'=>(bool)$x->obligatorio],$comps);
        }

        wcfin_tpl('envio/metabox.tpl.php', compact(
            'post','condiciones','metodos','penalidades','trans','movimientos','vars_ex',
            'cuentas','actores','comp_map','condicion_pago','condicion_sugerida'
        ));
    }

    public function guardar( int $post_id, \WP_Post $post ): void {
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( $post->post_type !== 'wpcargo' ) return;
        if ( ! isset($_POST['wcfin_nonce']) ) return;
        if ( ! wp_verify_nonce($_POST['wcfin_nonce'],'wcfin_pago_'.$post_id) ) return;
        if ( ! current_user_can('edit_post',$post_id) ) return;

        $metodo_id    = intval($_POST['wcfin_metodo_id']    ?? 0);
        $condicion_id = intval($_POST['wcfin_condicion_id'] ?? 0);
        if ( ! $metodo_id || ! $condicion_id ) return;

        // Recoger variables del formulario
        $variables = [];
        foreach ( $_POST as $k => $v ) {
            if ( strpos($k,'wcfin_var_') === 0 ) {
                $variables[str_replace('wcfin_var_','',$k)] = floatval($v);
            }
        }

        // Si no hay monto_producto explícito, leer del meta del envío
        if ( ! isset($variables['monto_producto']) || $variables['monto_producto'] <= 0 ) {
            $mp = floatval(get_post_meta($post_id,'monto_producto',true));
            if ( $mp > 0 ) $variables['monto_producto'] = $mp;
        }

        $notas = sanitize_textarea_field(wp_unslash($_POST['wcfin_notas'] ?? ''));
        WCFIN_Motor::procesar_pago($post_id, $metodo_id, $condicion_id, $variables, $notas);

        // Penalidades (aplicadas al guardar el metabox)
        foreach ( ($_POST['wcfin_pen'] ?? []) as $tipo_id => $p ) {
            if ( empty($p['aplicar']) ) continue;
            WCFIN_Motor::aplicar_penalidad($post_id, intval($tipo_id), floatval($p['monto']), sanitize_text_field($p['nota']??''));
        }
    }

    public function sync_condicion_pago( int $post_id ): void {
        WCFIN_Motor::sincronizar_condicion_pago($post_id);
    }

    /**
     * AJAX: aplicar penalidad desde el metabox sin recargar página.
     */
    public function ajax_aplicar_penalidad(): void {
        check_ajax_referer('wcfin_ajax_pen','nonce');
        if ( ! current_user_can('manage_options') ) wp_send_json_error('Sin permisos.');

        $shipment_id = intval($_POST['shipment_id'] ?? 0);
        $tipo_id     = intval($_POST['tipo_id']     ?? 0);
        $monto       = floatval($_POST['monto']     ?? 0);
        $notas       = sanitize_text_field(wp_unslash($_POST['notas'] ?? ''));

        if ( ! $shipment_id || ! $tipo_id ) wp_send_json_error('Datos incompletos.');

        $result = WCFIN_Penalidad::ejecutar_en_envio($shipment_id, $tipo_id, $monto, $notas);
        if ( $result['ok'] ) {
            wp_send_json_success(['msg'=>$result['msg'],'monto'=>$result['monto']]);
        } else {
            wp_send_json_error($result['msg']);
        }
    }
}

new WCFIN_Metabox();
