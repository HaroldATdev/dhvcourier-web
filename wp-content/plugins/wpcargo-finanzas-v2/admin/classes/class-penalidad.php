<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WCFIN_Penalidad {

    public static function obtener_todas(): array {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wcfin_penalidad_tipos ORDER BY aplica_a,nombre") ?: [];
    }

    public static function obtener_activas(): array {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wcfin_penalidad_tipos WHERE activo=1 ORDER BY aplica_a,nombre") ?: [];
    }

    public static function obtener_por_id( int $id ): ?object {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wcfin_penalidad_tipos WHERE id=%d",$id)) ?: null;
    }

    public static function guardar( array $datos, int $id = 0 ): true|\WP_Error {
        global $wpdb;
        if ( empty($datos['nombre']) ) return new \WP_Error('req','Nombre obligatorio.');
        $row = [
            'nombre'          => $datos['nombre'],
            'descripcion'     => $datos['descripcion']??'',
            'tipo_monto'      => $datos['tipo_monto']??'fijo',
            'monto_default'   => floatval($datos['monto_default']??0),
            'aplica_a'        => $datos['aplica_a']??'motorizado',
            'cuenta_afectada' => $datos['cuenta_afectada']??'balance_motorizado',
            'signo'           => intval($datos['signo']??-1),
        ];
        if ( $id ) $wpdb->update("{$wpdb->prefix}wcfin_penalidad_tipos",$row,['id'=>$id]);
        else { $wpdb->insert("{$wpdb->prefix}wcfin_penalidad_tipos",array_merge($row,['activo'=>1])); }
        return true;
    }

    public static function toggle_activo( int $id ): void {
        global $wpdb;
        $cur = $wpdb->get_var($wpdb->prepare("SELECT activo FROM {$wpdb->prefix}wcfin_penalidad_tipos WHERE id=%d",$id));
        $wpdb->update("{$wpdb->prefix}wcfin_penalidad_tipos",['activo'=>$cur?0:1],['id'=>$id]);
    }

    public static function eliminar( int $id ): void {
        global $wpdb;
        $wpdb->delete("{$wpdb->prefix}wcfin_penalidad_tipos",['id'=>$id]);
    }
}
