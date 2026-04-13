<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WCFIN_Metabox {

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'registrar' ] );
        add_action( 'save_post',      [ $this, 'guardar'   ], 20, 2 );
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

        // Mapa componentes por condicion_id para JS
        $comp_map = [];
        foreach ( $condiciones as $c ) {
            $comps = WCFIN_Condicion::obtener_componentes((int)$c->id);
            $comp_map[$c->id] = array_map(fn($x)=>['var'=>$x->variable,'label'=>$x->label,'req'=>(bool)$x->obligatorio],$comps);
        }

        wcfin_tpl('envio/metabox.tpl.php', compact(
            'post','condiciones','metodos','penalidades','trans','movimientos','vars_ex','cuentas','actores','comp_map'
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
        $notas = sanitize_textarea_field(wp_unslash($_POST['wcfin_notas'] ?? ''));

        WCFIN_Motor::procesar_pago($post_id, $metodo_id, $condicion_id, $variables, $notas);

        // Penalidades
        foreach ( ($_POST['wcfin_pen'] ?? []) as $tipo_id => $p ) {
            if ( empty($p['aplicar']) ) continue;
            WCFIN_Motor::aplicar_penalidad($post_id, intval($tipo_id), floatval($p['monto']), sanitize_text_field($p['nota']??''));
        }
    }
}

new WCFIN_Metabox();
