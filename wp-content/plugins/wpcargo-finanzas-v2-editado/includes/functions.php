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

    $has_vouchers = (bool) array_filter( $pod_rows, fn( $r ) => ! empty( $r['image_url'] ) );

    if ( $payment_mode === '' && empty( $pod_rows ) && ! $has_vouchers ) {
        return;
    }
    ?>
    <div id="wcfin-track-pagos" class="wpcargo-row detail-section" style="margin-top:14px;">
        <div class="wpcargo-col-md-12">
            <p class="header-title"><strong>Metodo de pago y vouchers</strong></p>
        </div>

        <?php if ( $payment_mode !== '' ) : ?>
            <div class="wpcargo-col-md-4">
                <p class="wpcargo-label">Metodo principal:</p>
                <p class="wpcargo-label-info"><?php echo esc_html( $payment_mode ); ?></p>
            </div>
        <?php endif; ?>

        <?php foreach ( $pod_rows as $row ) :
            $method_name = sanitize_text_field( (string) ( $row['method_name'] ?? 'Metodo' ) );
            $amount      = number_format( (float) ( $row['amount'] ?? 0 ), 2 );
            $image_url   = esc_url( (string) ( $row['image_url'] ?? '' ) );
        ?>
            <div class="wpcargo-col-md-12" style="margin-bottom:6px;">
                <span class="wpcargo-label-info">
                    <strong><?php echo esc_html( $method_name ); ?></strong>: S/ <?php echo esc_html( $amount ); ?>
                    <?php if ( ! empty( $image_url ) ) : ?>
                        &mdash; <a href="<?php echo $image_url; ?>" target="_blank" rel="noopener noreferrer">Ver voucher</a>
                    <?php endif; ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}
