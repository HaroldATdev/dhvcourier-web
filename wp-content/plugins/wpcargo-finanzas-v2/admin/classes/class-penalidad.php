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

    /**
     * Ejecuta (aplica) una penalidad en un envío específico.
     * Delega en WCFIN_Motor::aplicar_penalidad y registra el movimiento.
     *
     * @param int    $shipment_id  ID del envío (post WPCargo)
     * @param int    $tipo_id      ID del tipo de penalidad
     * @param float  $monto        Monto a aplicar (0 = usar monto_default del tipo)
     * @param string $notas        Descripción específica del caso
     * @return array{ok:bool,msg:string,monto:float}
     */
    public static function ejecutar_en_envio( int $shipment_id, int $tipo_id, float $monto = 0.0, string $notas = '' ): array {
        $tipo = self::obtener_por_id( $tipo_id );
        if ( ! $tipo ) return ['ok'=>false,'msg'=>'Tipo de penalidad no encontrado.','monto'=>0];
        if ( ! get_post( $shipment_id ) ) return ['ok'=>false,'msg'=>'Envío no encontrado.','monto'=>0];

        // Si monto = 0, usar el default del tipo
        if ( $monto <= 0 ) {
            $monto = floatval($tipo->monto_default);
        }

        // Para porcentaje: calcular sobre monto_total del envío
        if ( $tipo->tipo_monto === 'porcentaje' ) {
            $trans = WCFIN_Motor::get_transaccion( $shipment_id );
            $base  = $trans ? floatval($trans->monto_total) : floatval(get_post_meta($shipment_id,'monto_total',true));
            $monto = round($base * $monto / 100, 2);
        }

        WCFIN_Motor::aplicar_penalidad( $shipment_id, $tipo_id, $monto, $notas );

        // Notificación al afectado (si tiene email)
        $user_id = 0;
        if ( $tipo->aplica_a === 'motorizado' ) {
            $user_id = (int) get_post_meta($shipment_id, 'assign_driver', true);
        } elseif ( $tipo->aplica_a === 'remitente' ) {
            $post = get_post($shipment_id);
            $user_id = $post ? (int)$post->post_author : 0;
        }
        if ( $user_id ) {
            $user = get_userdata($user_id);
            if ( $user && $user->user_email ) {
                $tracking = get_post_meta($shipment_id,'wpcargo_tracking_number',true) ?: "#$shipment_id";
                wp_mail(
                    $user->user_email,
                    "⚠️ Penalidad aplicada — Envío $tracking",
                    "Hola {$user->display_name},\n\nSe aplicó una penalidad de S/ ".number_format($monto,2)." al envío $tracking.\n"
                    . "Motivo: {$tipo->nombre}" . ($notas ? " — $notas" : '') . "\n\n"
                    . "Consulta tu panel para más detalles."
                );
            }
        }

        return ['ok'=>true,'msg'=>"Penalidad aplicada: S/ ".number_format($monto,2),'monto'=>$monto];
    }
}
