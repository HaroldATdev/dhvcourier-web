<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
	
/**
 * Register a custom post type called "book".
 *
 * @see get_post_type_labels() for label keys.
 */
function wpcinvoice_shipment_invoice_type_init() {
    $labels = array(
        'name'                  => _x( 'Shipment Invoice', 'Post type general name', 'wpcargo-invoice' ),
        'singular_name'         => _x( 'Shipment Invoice', 'Post type singular name', 'wpcargo-invoice' ),
        'menu_name'             => _x( 'Shipment Invoice', 'Admin Menu text', 'wpcargo-invoice' ),
        'name_admin_bar'        => _x( 'Shipment Invoice', 'Add New on Toolbar', 'wpcargo-invoice' ),
        'add_new'               => __( 'Add Invoice', 'wpcargo-invoice' ),
        'add_new_item'          => __( 'Add Invoice', 'wpcargo-invoice' ),
        'new_item'              => __( 'Invoice', 'wpcargo-invoice' ),
        'edit_item'             => __( 'Edit Invoice', 'wpcargo-invoice' ),
        'view_item'             => __( 'View Invoice', 'wpcargo-invoice' ),
        'all_items'             => __( 'All shipment invoice', 'wpcargo-invoice' ),
        'search_items'          => __( 'Search shipment invoice', 'wpcargo-invoice' ),
        'parent_item_colon'     => __( 'Parent shipment invoice:', 'wpcargo-invoice' ),
        'not_found'             => __( 'No shipment invoice found.', 'wpcargo-invoice' ),
        'not_found_in_trash'    => __( 'No shipment invoice found in Trash.', 'wpcargo-invoice' ),
        'featured_image'        => _x( 'Invoice Cover Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'wpcargo-invoice' ),
        'set_featured_image'    => _x( 'Set cover image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'wpcargo-invoice' ),
        'remove_featured_image' => _x( 'Remove cover image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'wpcargo-invoice' ),
        'use_featured_image'    => _x( 'Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'wpcargo-invoice' ),
        'archives'              => _x( 'Invoice archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'wpcargo-invoice' ),
        'insert_into_item'      => _x( 'Insert into Invoice', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'wpcargo-invoice' ),
        'uploaded_to_this_item' => _x( 'Uploaded to this Invoice', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'wpcargo-invoice' ),
        'filter_items_list'     => _x( 'Filter shipment invoice list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'wpcargo-invoice' ),
        'items_list_navigation' => _x( 'Shipment invoice list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'wpcargo-invoice' ),
        'items_list'            => _x( 'Shipment invoice list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'wpcargo-invoice' ),
    );
 
    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => false,
        'show_in_menu'       => false,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'shipment-invoice' ),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title', 'editor', 'author' ),
    );
 
    register_post_type( 'wpcargo_invoice', $args );
}
add_action( 'init', 'wpcinvoice_shipment_invoice_type_init' );