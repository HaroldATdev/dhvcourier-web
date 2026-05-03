<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ─── Helpers de template ──────────────────────────────────────────────────────

function wcfin_tpl( string $tpl, array $vars = [] ): void {
    $file = WCFIN_PATH . 'admin/templates/' . $tpl;
    if ( ! file_exists( $file ) ) {
        echo '<div class="alert alert-danger">Template no encontrado: ' . esc_html($tpl) . '</div>';
        return;
    }
    extract( $vars, EXTR_SKIP );
    require $file;
}

function wcfin_url( string $page, array $extra = [] ): string {
    return add_query_arg( array_merge( ['page' => $page], $extra ), admin_url('admin.php') );
}

function wcfin_redirect( string $page, string $msg = '', array $extra = [] ): void {
    $params = array_merge( ['page' => $page], $extra );
    if ( $msg ) $params['wcfin_msg'] = $msg;
    wp_redirect( add_query_arg( $params, admin_url('admin.php') ) );
    exit;
}

// ─── Roles ───────────────────────────────────────────────────────────────────

function wcfin_es_admin(): bool {
    if ( ! is_user_logged_in() ) return false;
    return current_user_can('manage_options')
        || ( function_exists('wpcfe_is_super_admin') && wpcfe_is_super_admin() );
}

function wcfin_es_cliente(): bool {
    if ( ! is_user_logged_in() ) return false;
    $roles = (array) wp_get_current_user()->roles;
    return in_array('wpcargo_client', $roles, true);
}

function wcfin_es_driver(): bool {
    if ( ! is_user_logged_in() ) return false;
    $roles = (array) wp_get_current_user()->roles;
    return in_array('wpcargo_driver', $roles, true);
}

// ─── Página admin finanzas (shortcode [wcfin-finanzas]) ───────────────────────

function wcfin_get_frontend_page_id(): int {
    $saved = (int) get_option('wcfin_frontend_page_id');
    if ( $saved && get_post_status($saved) === 'publish' ) return $saved;
    global $wpdb;
    $id = (int) $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}posts WHERE post_content LIKE '%[wcfin-finanzas]%' AND post_status='publish' LIMIT 1");
    if ( ! $id ) $id = (int) wp_insert_post(['post_title'=>'Finanzas','post_content'=>'[wcfin-finanzas]','post_status'=>'publish','post_type'=>'page']);
    if ( $id ) {
        update_post_meta($id, '_wp_page_template', 'dashboard.php');
        update_post_meta($id, 'wpcfe_menu_icon',   'fa fa-line-chart mr-3');
        update_option('wcfin_frontend_page_id', $id, false);
    }
    return $id;
}

function wcfin_frontend_url( array $extra = [] ): string {
    $url = get_permalink(wcfin_get_frontend_page_id()) ?: home_url('/finanzas/');
    return $extra ? add_query_arg($extra, $url) : $url;
}

// ─── Página cliente (shortcode [wcfin-mi-cuenta]) ────────────────────────────

function wcfin_get_cliente_page_id(): int {
    $saved = (int) get_option('wcfin_cliente_page_id');
    if ( $saved && get_post_status($saved) === 'publish' ) return $saved;
    global $wpdb;
    $id = (int) $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}posts WHERE post_content LIKE '%[wcfin-mi-cuenta]%' AND post_status='publish' LIMIT 1");
    if ( ! $id ) $id = (int) wp_insert_post(['post_title'=>'Mi Cuenta','post_content'=>'[wcfin-mi-cuenta]','post_status'=>'publish','post_type'=>'page']);
    if ( $id ) {
        update_post_meta($id, '_wp_page_template', 'dashboard.php');
        update_post_meta($id, 'wpcfe_menu_icon',   'fa fa-money mr-3');
        update_option('wcfin_cliente_page_id', $id, false);
    }
    return $id;
}

function wcfin_cliente_url( array $extra = [] ): string {
    $url = get_permalink(wcfin_get_cliente_page_id()) ?: home_url('/mi-cuenta/');
    return $extra ? add_query_arg($extra, $url) : $url;
}

// ─── Página driver (shortcode [wcfin-mi-caja]) ───────────────────────────────

function wcfin_get_driver_page_id(): int {
    $saved = (int) get_option('wcfin_driver_page_id');
    if ( $saved && get_post_status($saved) === 'publish' ) return $saved;
    global $wpdb;
    $id = (int) $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}posts WHERE post_content LIKE '%[wcfin-mi-caja]%' AND post_status='publish' LIMIT 1");
    if ( ! $id ) $id = (int) wp_insert_post(['post_title'=>'Mi Caja','post_content'=>'[wcfin-mi-caja]','post_status'=>'publish','post_type'=>'page']);
    if ( $id ) {
        update_post_meta($id, '_wp_page_template', 'dashboard.php');
        update_post_meta($id, 'wpcfe_menu_icon',   'fa fa-money mr-3');
        update_option('wcfin_driver_page_id', $id, false);
    }
    return $id;
}

function wcfin_driver_url( array $extra = [] ): string {
    $url = get_permalink(wcfin_get_driver_page_id()) ?: home_url('/mi-caja/');
    return $extra ? add_query_arg($extra, $url) : $url;
}

// ── Métodos de pago y vouchers POD en vista de tracking ───────────────────
add_action( 'wpcargo_after_package_totals', 'wcfin_track_metodos_y_vouchers', 20, 1 );
function wcfin_track_metodos_y_vouchers( $shipment ): void {
    $shipment_id = is_object( $shipment ) && isset( $shipment->ID )
        ? (int) $shipment->ID
        : (int) $shipment;

    if ( $shipment_id <= 0 ) {
        return;
    }

    $payment_mode = trim( (string) get_post_meta( $shipment_id, 'payment_wpcargo_mode_field', true ) );
    $pod_rows     = get_post_meta( $shipment_id, 'wpcargo-pod-payments', true );

    if ( ! is_array( $pod_rows ) ) {
        $pod_rows = [];
    }

    if ( $payment_mode === '' && empty( $pod_rows ) ) {
        return;
    }

    // Paleta de colores que rota por índice
    $palette = [ '#1a6faf', '#2e7d32', '#b45309', '#7b1fa2', '#c62828', '#00695c' ];
    $total   = array_sum( array_map( fn( $r ) => (float) ( $r['amount'] ?? 0 ), $pod_rows ) );
    ?>
    <div id="wcfin-track-pagos" class="wpcargo-row detail-section" style="margin-top:20px;">

        <div class="wpcargo-col-md-12" style="margin-bottom:10px;">
            <p class="header-title" style="display:flex;align-items:center;gap:8px;margin-bottom:0;">
                <span style="display:inline-block;width:4px;height:18px;background:#1a6faf;border-radius:2px;"></span>
                <strong>METODO DE PAGO Y VOUCHERS</strong>
            </p>
        </div>

        <?php foreach ( $pod_rows as $i => $row ) :
            $method_name = sanitize_text_field( (string) ( $row['method_name'] ?? 'Metodo' ) );
            $amount      = number_format( (float) ( $row['amount'] ?? 0 ), 2 );
            $image_url   = esc_url( (string) ( $row['image_url'] ?? '' ) );
            $color       = $palette[ $i % count( $palette ) ];
        ?>
            <div class="wpcargo-col-md-12" style="margin-bottom:8px;">
                <div style="
                    background:#fff;
                    border:1px solid #e0e0e0;
                    border-left:4px solid <?php echo esc_attr( $color ); ?>;
                    border-radius:4px;
                    padding:10px 16px;
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                ">
                    <div>
                        <div style="font-weight:700;font-size:13px;color:#333;"><?php echo esc_html( $method_name ); ?></div>
                        <div style="font-size:1.1em;font-weight:700;color:<?php echo esc_attr( $color ); ?>;margin-top:2px;">
                            S/. <?php echo esc_html( $amount ); ?>
                        </div>
                    </div>
                    <div style="font-size:12px;color:#888;text-align:right;">
                        <?php if ( ! empty( $image_url ) ) : ?>
                            <a href="<?php echo $image_url; ?>" target="_blank" rel="noopener noreferrer"
                               style="color:#1a6faf;font-weight:600;text-decoration:none;">
                                Ver voucher
                            </a>
                        <?php else : ?>
                            <em>Sin comprobante adjunto</em>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ( ! empty( $pod_rows ) ) : ?>
            <div class="wpcargo-col-md-12" style="margin-top:4px;">
                <div style="
                    background:#1a3a5c;
                    border-radius:4px;
                    padding:12px 16px;
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                ">
                    <span style="font-size:11px;font-weight:700;color:#a0bfd8;text-transform:uppercase;letter-spacing:.5px;">
                        TOTAL RECAUDADO
                    </span>
                    <span style="font-size:1.4em;font-weight:700;color:#fff;">
                        S/. <?php echo esc_html( number_format( $total, 2 ) ); ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>

    </div>
    <?php
}
