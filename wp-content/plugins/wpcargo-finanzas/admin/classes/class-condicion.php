<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WCFIN_Condicion {

    public static function obtener_todas(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT c.*, COUNT(cc.id) as num_comp
             FROM {$wpdb->prefix}wcfin_condiciones c
             LEFT JOIN {$wpdb->prefix}wcfin_condicion_componentes cc ON cc.condicion_id=c.id
             GROUP BY c.id ORDER BY c.orden, c.id"
        ) ?: [];
    }

    public static function obtener_activas(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT c.*, GROUP_CONCAT(cc.variable ORDER BY cc.orden SEPARATOR ',') as variables
             FROM {$wpdb->prefix}wcfin_condiciones c
             LEFT JOIN {$wpdb->prefix}wcfin_condicion_componentes cc ON cc.condicion_id=c.id
             WHERE c.activo=1 GROUP BY c.id ORDER BY c.orden"
        ) ?: [];
    }

    public static function obtener_por_id( int $id ): ?object {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wcfin_condiciones WHERE id=%d", $id
        )) ?: null;
    }

    public static function obtener_componentes( int $condicion_id ): array {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wcfin_condicion_componentes WHERE condicion_id=%d ORDER BY orden",
            $condicion_id
        )) ?: [];
    }

    public static function guardar( array $datos, int $id = 0 ): true|\WP_Error {
        global $wpdb;
        if ( empty($datos['nombre']) || empty($datos['slug']) ) return new \WP_Error('req','Campos obligatorios.');
        $row = ['nombre'=>$datos['nombre'],'slug'=>$datos['slug'],'cobrar_a'=>$datos['cobrar_a'],'descripcion'=>$datos['descripcion']??''];
        if ( $id ) {
            $wpdb->update("{$wpdb->prefix}wcfin_condiciones", $row, ['id'=>$id]);
        } else {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}wcfin_condiciones WHERE slug=%s",$datos['slug']));
            if ( $exists ) return new \WP_Error('slug','Slug duplicado.');
            $wpdb->insert("{$wpdb->prefix}wcfin_condiciones", array_merge($row,['activo'=>1,'orden'=>0]));
            $id = $wpdb->insert_id;
        }
        // Componentes
        $wpdb->delete("{$wpdb->prefix}wcfin_condicion_componentes",['condicion_id'=>$id]);
        foreach ( $datos['componentes'] ?? [] as $i => $comp ) {
            if ( empty($comp['variable']) ) continue;
            $wpdb->insert("{$wpdb->prefix}wcfin_condicion_componentes",[
                'condicion_id'=>$id,'variable'=>sanitize_key($comp['variable']),
                'label'=>sanitize_text_field($comp['label']??$comp['variable']),
                'obligatorio'=>intval($comp['obligatorio']??1),'orden'=>$i,
            ]);
        }
        return true;
    }

    public static function eliminar( int $id ): void {
        global $wpdb;
        $wpdb->delete("{$wpdb->prefix}wcfin_condicion_componentes",['condicion_id'=>$id]);
        $wpdb->delete("{$wpdb->prefix}wcfin_condiciones",['id'=>$id]);
    }
}
