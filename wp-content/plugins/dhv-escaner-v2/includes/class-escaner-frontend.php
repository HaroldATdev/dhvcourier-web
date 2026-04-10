<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DHV_Escaner_Frontend {

    public function __construct() {
        add_filter( 'wpcfe_after_sidebar_menus', [ $this, 'sidebar_item' ], 28, 1 );
    }

    public function sidebar_item( array $menu ): array {
        if ( ! is_user_logged_in() ) return $menu;
        if ( ! dhv_can_scan() ) return $menu;

        $menu['dhv-escaner'] = [
            'page-id'   => dhv_get_frontend_page_id(),
            'label'     => 'Escáner',
            'permalink' => get_permalink( dhv_get_frontend_page_id() ),
            'icon'      => 'fa-barcode',
        ];

        return $menu;
    }
}
