<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wcfin_tpl( string $tpl, array $vars = [] ): void {
    $file = WCFIN_PATH . 'admin/templates/' . $tpl;
    if ( ! file_exists( $file ) ) {
        echo '<div class="notice notice-error"><p>Template no encontrado: ' . esc_html($tpl) . '</p></div>';
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

// ── Frontend helpers ──────────────────────────────────────────────────────────

function wcfin_get_frontend_page_id(): int {
    $saved = (int) get_option('wcfin_frontend_page_id');
    if ( $saved && get_post_status($saved) === 'publish' ) return $saved;

    global $wpdb;
    $id = (int) $wpdb->get_var(
        "SELECT ID FROM {$wpdb->prefix}posts
         WHERE post_content LIKE '%[wcfin-finanzas]%'
           AND post_status = 'publish' LIMIT 1"
    );

    if ( ! $id ) {
        $id = (int) wp_insert_post([
            'post_title'   => 'Finanzas',
            'post_content' => '[wcfin-finanzas]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }

    if ( $id ) {
        update_post_meta($id, '_wp_page_template', 'dashboard.php');
        update_post_meta($id, 'wpcfe_menu_icon',   'fa fa-chart-line mr-3');
        update_option('wcfin_frontend_page_id', $id, false);
    }

    return $id;
}

function wcfin_frontend_url( array $extra = [] ): string {
    $url = get_permalink(wcfin_get_frontend_page_id()) ?: home_url('/finanzas/');
    return $extra ? add_query_arg($extra, $url) : $url;
}

function wcfin_es_admin(): bool {
    return current_user_can('manage_options');
}
