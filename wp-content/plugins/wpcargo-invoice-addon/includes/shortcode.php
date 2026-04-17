<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
function wpcinvoice_dashboard_shortcode_callback( $atts ) {
    global $wpcargo, $WPCCF_Fields;
    $page_url = get_the_permalink( wpcinvoice_dashboard_page() );
    ob_start();
    if( empty( get_option(WPC_INVOICE_BASENAME) ) ){
        echo wpcinvoice_activate_license();
        return ob_get_clean();
    }
	if( !can_wpcinvoice_access() ){
        include_once( wpcinvoice_locate_template( 'restriction' ) );
        return ob_get_clean();
    }
    if( !wpcinvoice_is_frontend_manager_activated() ){
        require_once( WPC_INVOICE_PATH.'templates/no-fm-addon-error.tpl.php');
        return ob_get_clean();
    }
    if( ( isset( $_GET['wpcinvoice'] ) && $_GET['wpcinvoice'] == 'update' ) && ( isset( $_GET['id'] ) && is_wpcinvoice_shipment( $_GET['id'] ) && can_wpcfe_update_shipment() && is_user_shipment( (int)$_GET['id'] ) ) ){
        $shipment       = new stdClass();
        $shipment->ID   = $_GET['id'];
        $user_roles 	= wpcfe_current_user_role();
        include( wpcinvoice_locate_template( 'update-invoice.tpl' ) );
        return ob_get_clean();
    }
    
    if( isset( $_GET['wpcinvoice'] ) && $_GET['wpcinvoice'] == 'export'  ){
    	$client_args = array(
            'meta_key' => 'first_name',
            'orderby'  => 'meta_value',
			'role__in' => array( 'wpcargo_client' ),
            );
        $client_users = get_users( $client_args );
    	$invoice_status = isset( $_POST['invoice_status'] )? $_POST['invoice_status'] : '';
    	$registered_shipper = isset( $_POST['registered_shipper'] )? $_POST['registered_shipper'] : '';
    	// wpcinvoice_export_request();
    	include( wpcinvoice_locate_template( 'invoice-export.tpl' ) );     
        return ob_get_clean();
    }

    $wpcfesort_list     = array( 10, 25, 50, 100 );
    $wpcfesort          = get_user_meta( get_current_user_id(), 'user_wpcfesort', true ) ? : 10 ;
    $paged              = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
    $meta_query         = array();
    $meta_query['__wpcinvoice_id'] = array(
        'key'       => '__wpcinvoice_id',
        'compare'   => 'EXISTS'
    );
    if( isset( $_GET['wpcinvoice'] ) && !empty( $_GET['wpcinvoice'] ) ){
        $_invoice_id = wpcinvoice_invoice_id( $_GET['wpcinvoice'] );
        $meta_query['__wpcinvoice_id'] = array(
            'key'       => '__wpcinvoice_id',
            'value'     => $_invoice_id,
            'compare'   => 'IN'
        );
    }
    $meta_query         = apply_filters( 'wpcinvoice_dashboard_meta_query', $meta_query );
    $wpcinvoice_args    = array(
        'post_type'         => 'wpcargo_shipment',
        'post_status'       => 'publish',
        'posts_per_page'    => $wpcfesort,
        'paged'             => $paged,
        'meta_query' => array(
            'relation' => 'AND',
            $meta_query
        )
    );
    $wpcinvoice_args    = apply_filters( 'wpcinvoice_dashboard_arguments', $wpcinvoice_args );   
    $wpcinvoice_query   = new WP_Query( $wpcinvoice_args );
    $number_records     = $wpcinvoice_query->found_posts;
    $basis              = $paged * $wpcfesort;
    $record_end         = $number_records < $basis ? $number_records : $basis ;
    $record_start       = $basis - ( $wpcfesort - 1 );
    include( wpcinvoice_locate_template( 'dashboard' ) );
    wp_reset_postdata();
    return ob_get_clean(); 
   
}
add_shortcode( 'wpcargo_invoices', 'wpcinvoice_dashboard_shortcode_callback' );