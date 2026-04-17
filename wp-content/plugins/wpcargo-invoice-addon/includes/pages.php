<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
function is_wpcinvoice_page_exist( $shortcode ){
    global $wpdb;
    $sql = "SELECT `ID` FROM `{$wpdb->prefix}posts` WHERE `post_status` LIKE 'publish' AND `post_type` LIKE 'page' AND `post_content` LIKE %s LIMIT 1";
    return $wpdb->get_var( $wpdb->prepare( $sql, '%'.$shortcode.'%') );
}
function wpcinvoice_dashboard_page(){
	$wpcinvoice_page = get_option('wpcinvoice_page');
	if( !$wpcinvoice_page ) {
		$page_id = is_wpcinvoice_page_exist( '[wpcargo_invoices]' );
		update_option( 'wpcinvoice_page', $page_id, 'yes' );
		if( $page_id ){
			return $page_id;
		}
		$post_title 	= __('Merchant Create Order', 'wpcargo-merchant' );
		$post_name 		= 'wpcinvoice-dashboard';
		$post_content 	= '[wpcargo_invoices]';
		$page_id = wpcinvoice_generate_page( $post_title, $post_name, $post_content );
		update_option( 'wpcinvoice_page', $page_id, 'yes' );
	}
	return $wpcinvoice_page;
}
function wpcinvoice_generate_page( $post_title, $post_name, $post_content ){
	$page_args    = array(
		'comment_status' => 'closed',
		'ping_status' 	=> 'closed',
		'post_author' 	=> 1,
		'post_date' 	=> date('Y-m-d H:i:s'),
		'post_content' 	=> $post_content,
		'post_name' 	=> $post_name,
		'post_status' 	=> 'publish',
		'post_title' 	=> $post_title,
		'post_type' 	=> 'page',
	);
	$page_id = wp_insert_post( $page_args, false );
	update_post_meta( $page_id, '_wp_page_template', 'dashboard.php' );
	return $page_id;
}
function wpcinvoice_create_default_pages(){
	wpcinvoice_dashboard_page();
}