<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WCFIN_Metodo {

    public static function obtener_todos(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT m.*, COUNT(r.id) as num_reglas
             FROM {$wpdb->prefix}wcfin_metodos_pago m
             LEFT JOIN {$wpdb->prefix}wcfin_reglas r ON r.metodo_id=m.id
             GROUP BY m.id ORDER BY m.orden,m.id"
        ) ?: [];
    }

    public static function obtener_activos(): array {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wcfin_metodos_pago WHERE activo=1 ORDER BY orden") ?: [];
    }

    public static function obtener_por_id( int $id ): ?object {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wcfin_metodos_pago WHERE id=%d",$id)) ?: null;
    }

    public static function obtener_reglas( int $metodo_id ): array {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, c.nombre as condicion_nombre
             FROM {$wpdb->prefix}wcfin_reglas r
             LEFT JOIN {$wpdb->prefix}wcfin_condiciones c ON c.id=r.condicion_id
             WHERE r.metodo_id=%d",
            $metodo_id
        )) ?: [];
    }

    public static function guardar( array $datos, int $id = 0 ): true|\WP_Error {
        global $wpdb;
        if ( empty($datos['nombre']) || empty($datos['slug']) ) return new \WP_Error('req','Campos obligatorios.');
        $row = ['nombre'=>$datos['nombre'],'slug'=>$datos['slug'],'actor_destino'=>$datos['actor_destino'],
                'tipo'=>$datos['tipo'],'requiere_conf'=>intval($datos['requiere_conf']??0)];
        if ( $id ) {
            $wpdb->update("{$wpdb->prefix}wcfin_metodos_pago",$row,['id'=>$id]);
        } else {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}wcfin_metodos_pago WHERE slug=%s",$datos['slug']));
            if ( $exists ) return new \WP_Error('slug','Slug duplicado.');
            $wpdb->insert("{$wpdb->prefix}wcfin_metodos_pago",array_merge($row,['activo'=>1,'orden'=>0]));
            $id = $wpdb->insert_id;
        }
        $wpdb->delete("{$wpdb->prefix}wcfin_reglas",['metodo_id'=>$id]);
        foreach ( $datos['reglas'] ?? [] as $r ) {
            if ( empty($r['cuenta']) ) continue;
            $wpdb->insert("{$wpdb->prefix}wcfin_reglas",[
                'metodo_id'       => $id,
                'condicion_id'    => intval($r['condicion_id']??0) ?: null,
                'cuenta_afectada' => sanitize_key($r['cuenta']),
                'base_calculo'    => sanitize_key($r['base']),
                'signo'           => intval($r['signo']??1),
                'descripcion_tpl' => sanitize_text_field($r['descripcion']??''),
            ]);
        }
        return true;
    }

    public static function eliminar( int $id ): void {
        global $wpdb;
        $wpdb->delete("{$wpdb->prefix}wcfin_reglas",       ['metodo_id'=>$id]);
        $wpdb->delete("{$wpdb->prefix}wcfin_metodos_pago", ['id'=>$id]);
    }
}
