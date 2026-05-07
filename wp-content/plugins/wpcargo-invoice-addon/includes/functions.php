<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// Hooks Callbacks ##################################
function wpcinvoice_dashboard_side_menu(){
    if( !can_wpcinvoice_access() ){
        return false;
    }
    $invoice_page       = wpcinvoice_dashboard_page();
    ?>
    <a href="<?php echo get_the_permalink( $invoice_page ); ?>" class="list-group-item wpcargo_invoice-page <?php echo get_the_ID() == $invoice_page ? 'active' : ''; ?>"> 
        <i class="fa fa-file-text-o mr-md-3 mr-3" aria-hidden="true"></i><?php echo apply_filters( 'wpcinvoice_page_menu_label', __('Invoices', 'wpcargo-invoice') ); ?>
    </a>
    <?php
}
// Function Helpers ################################
// Access Helpers 
function wpcinvoice_current_user_role(){
    $current_user   = wp_get_current_user();
    $user_roles     = $current_user->roles;
    return $user_roles;
}
function can_wpcinvoice_access( ){
    $roles  = apply_filters( 'can_wpcinvoice_access', array( 'administrator', 'wpcargo_employee' ) );
    if( array_intersect( $roles, wpcinvoice_current_user_role() ) ){
        return true;
    }
    return false;
}

function can_wpcinvoice_access_package( ){
    $roles  = apply_filters( 'can_wpcinvoice_access_package', array( 'administrator', 'wpcargo_employee' ) );
    if( array_intersect( $roles, wpcinvoice_current_user_role() ) ){
        return true;
    }
    return false;
}

// Invoice helper
function wpcinvoice_status_list( ){
    $list = array(
        'wpci-paid'         => __('Paid', 'wpcargo-invoice'),
        'wpci-unpaid'       => __('Unpaid', 'wpcargo-invoice'),
        'wpci-cancelled'    => __('Cancelled', 'wpcargo-invoice'),
        'wpci-return'       => __('Return', 'wpcargo-invoice'),
        'wpci-refund'       => __('Refund', 'wpcargo-invoice'),
    );
    return apply_filters('wpcinvoice_status_list', $list);
}
function wpcinvoice_status_default( ){
    return apply_filters( 'wpcinvoice_status_default', 'wpci-unpaid' );
}
function wpcinvoice_generate_invoice_number( ){
    $opt_invoice_no  = (int)get_option( '__wpcinvoice_number', 0 );
    // restrict to udpate then invoice when the Invoice is already registered
    $opt_new_invoice = $opt_invoice_no + 1 ;
    update_option( '__wpcinvoice_number', $opt_new_invoice );
    return apply_filters( 'wpcinvoice_generate_invoice_number', str_pad(  $opt_new_invoice, 12, "0", STR_PAD_LEFT) );
}
function wpcinvoice_get_shipment_id( $shipment_number ){
    global $wpdb;
    $sql    = "SELECT `ID` FROM {$wpdb->posts} WHERE `post_status` LIKE 'publish' AND `post_type` LIKE 'wpcargo_shipment' AND `post_title` = %s LIMIT 1";
    $result = $wpdb->get_var( $wpdb->prepare( $sql, $shipment_number ) );
    return $result;
}
function is_wpcinvoice_shipment( $id ){
    global $wpdb;
    $sql    = "SELECT `ID` FROM {$wpdb->posts} WHERE `post_status` LIKE 'publish' AND `post_type` LIKE 'wpcargo_shipment' AND `ID` = %d LIMIT 1";
    $result = $wpdb->get_var( $wpdb->prepare( $sql, $id ) );
    return $result;
}
function wpcinvoice_status( $invoice_id ){
    $status         = get_post_meta( $invoice_id, '__wpcinvoice_status', true );
    $status_list    = wpcinvoice_status_list();
    return array_key_exists( "$status", $status_list ) ? $status_list[$status] : '' ;
}
function wpcinvoice_number( $shipment_id ){
    global $wpdb;
    $sql = "SELECT tbl2.meta_value FROM {$wpdb->posts} AS tbl1 INNER JOIN {$wpdb->postmeta} AS tbl2 ON tbl1.ID = tbl2.post_id";
    $sql .= " WHERE tbl1.post_status LIKE 'publish' AND tbl1.post_type LIKE 'wpcargo_shipment' AND tbl1.ID = %d";
    $sql .= " AND tbl2.meta_key LIKE '__wpcinvoice_id' AND tbl2.meta_value IS NOT NULL";
    return $wpdb->get_var( $wpdb->prepare( $sql, $shipment_id ) );
}
function wpcinvoice_shipment_id( $invoice_id ){
    global $wpdb;
    $sql = "SELECT tbl1.ID FROM {$wpdb->posts} AS tbl1 INNER JOIN {$wpdb->postmeta} AS tbl2 ON tbl1.ID = tbl2.post_id";
    $sql .= " WHERE tbl1.post_status LIKE 'publish' AND tbl1.post_type LIKE 'wpcargo_shipment'";
    $sql .= " AND tbl2.meta_key LIKE '__wpcinvoice_id' AND tbl2.meta_value = %d";
    $sql = $wpdb->prepare( $sql, $invoice_id );
    return $wpdb->get_var( $sql );
}
function wpcinvoice_invoice_id( $invoice_number ){
    global $wpdb;
    $sql = "SELECT ID FROM {$wpdb->posts}";
    $sql .= " WHERE `post_status` LIKE 'publish' AND `post_type` LIKE 'wpcargo_invoice' AND `post_title` LIKE %s";
    $sql = $wpdb->prepare( $sql, '%'.trim($invoice_number).'%' );
    return $wpdb->get_col( $sql );
}
function wpcinvoice_get_order_id( $shipment_id, $meta_key ){
    global $wpcargo, $wpdb;
    $shipment_number    = get_the_title( $shipment_id );
    $sql = "SELECT tbl2.order_id FROM `".$wpdb->prefix."woocommerce_order_itemmeta` AS tbl1";
    $sql .= " LEFT JOIN `".$wpdb->prefix."woocommerce_order_items` as tbl2 ON tbl1.order_item_id = tbl2.order_item_id";
    $sql .= " WHERE tbl1.meta_key LIKE %s AND tbl1.meta_value LIKE %s LIMIT 1";
    return $wpdb->get_var( $wpdb->prepare( $sql, $meta_key, $shipment_number ) );
}
function wpcinvoice_get_order_shipment( $order_id ){
    global $wpdb;
    $meta_keys = array_values( wpcinvoice_woo_order_shipment_map() );
    $sql = "SELECT tbl2.meta_value FROM `".$wpdb->prefix."woocommerce_order_items` AS tbl1";
    $sql .= " LEFT JOIN `".$wpdb->prefix."woocommerce_order_itemmeta` as tbl2 ON tbl1.order_item_id = tbl2.order_item_id";
    $sql .= " WHERE tbl1.order_id = %d AND tbl1.order_item_type LIKE 'line_item'";
    $sql .= " AND tbl2.meta_key IN ('".implode("','",$meta_keys)."')";
    return $wpdb->get_var( $wpdb->prepare( $sql, $order_id ) );
}
function wpcinvoice_locate_template( $file_name ){
    $file_slug              = strtolower( preg_replace('/\s+/', '_', trim( str_replace( '.tpl', '', $file_name ) ) ) );
    $file_slug              = preg_replace('/[^A-Za-z0-9_]/', '_', $file_slug );
    $custom_template_path   = get_stylesheet_directory().'/wpcargo/invoice/'.$file_name.'.php';
    if( file_exists( $custom_template_path ) ){
        $template_path = $custom_template_path;
    }else{
        $template_path  = WPC_INVOICE_PATH.'templates/'.$file_name.'.php';
        $template_path  = apply_filters( "wpcinvoice_locate_template_{$file_slug}", $template_path );
    }
	return $template_path;
}
function wpcinvoice_save_history( $invoice_id, $data ){
    $curr_history   = maybe_unserialize( get_post_meta( $invoice_id, '__wpcinvoice_history', true ) );
    $curr_history   = !empty( $curr_history ) && is_array( $curr_history ) ? $curr_history : array();
    $curr_history[] = $data;
    update_post_meta( $invoice_id, '__wpcinvoice_history', maybe_serialize( $curr_history ) );
}
function wpcinvoice_package_fields(){
    return apply_filters( 'wpcinvoice_package_fields', wpcargo_package_fields() );
}
function wpcinvoice_format_value( $value, $html=false ){
    $decimal    = apply_filters( 'wpcinvoice_format_value_decimal', 2 );    
    $separator  = apply_filters( 'wpcinvoice_format_value_seperator', ',' );
    
    $format_value = (float)str_replace(",", "", $value);

    if( $html ){
        $format_value = wpcinvoice_currency().number_format( $format_value, $decimal, '.', $separator);
    }else{
        $format_value = number_format( $format_value, $decimal, '.', $separator);
    }
    return $format_value;
}
function wpcinvoice_display_options( $options, $field ){
    if( empty( $options ) || !array_key_exists( $field, $options ) ){
        return '';
    }
    return $options[$field];
}
function wpcinvoice_default_company_addresss(){
    $company_address = "<p>Company Address.. </p>&#x0a;<p>Company Phone</p>&#x0a;<p>Company Email</p>";
    return apply_filters( 'wpcinvoice_default_company_addresss', $company_address );
}
function wpcinvoice_default_company_invoice(){
    $company_invoice = "<p>{wpcargo_tracking_number}</p> &#x0a;";
    return apply_filters( 'wpcinvoice_default_company_invoice', $company_invoice );
}
function wpcinvoice_default_shipper_invoice(){
    $shipper_invoice = "<p>Shipper Name: {wpcargo_shipper_name}</p>&#x0a;<p>Address: {wpcargo_shipper_address}</p>&#x0a;<p>Phone: {wpcargo_shipper_phone}</p>&#x0a;<p>Email: {wpcargo_shipper_email}</p>";
    return apply_filters( 'wpcinvoice_default_shipper_invoice', $shipper_invoice );
}
function wpcinvoice_default_receiver_invoice(){
    $receiver_invoice = "<p>Receiver Name: {wpcargo_receiver_name}</p>&#x0a;<p>Address: {wpcargo_receiver_address}</p>&#x0a;<p>Phone: {wpcargo_receiver_phone}</p>&#x0a;<p>Email: {wpcargo_receiver_email}</p>";
    return apply_filters( 'wpcinvoice_default_receiver_invoice', $receiver_invoice );
}
function wpcinvoice_default_comment_invoice(){
    $comment_invoice = "<p>1. Payment due in 30 days.</p> &#x0a;<p>2. Please note the invoice number in your payment method.</p> &#x0a;<p>Banking and wire transfer may also be reduced here.</p>";
    return apply_filters( 'wpcinvoice_default_comment_invoice', $comment_invoice );
}
function wpcinvoice_default_thankyou_invoice(){
    $options 		= get_option('wpcargo_option_settings');
	$baseColor 		= '#000';
	if( $options ){
		if( array_key_exists('wpcargo_base_color', $options) ){
			$baseColor = ( $options['wpcargo_base_color'] ) ? $options['wpcargo_base_color'] : $baseColor ;
		}
	}
    $thankyou_invoice = "<h3 style='color: ".$baseColor.";'>Thank you for your business!</h3>";
    return apply_filters( 'wpcinvoice_default_thankyou_invoice', $thankyou_invoice );
}
function wpcinvoice_default_label_invoice(){
    $label_invoice = __('INVOICE','wpcargo-invoice');
    return apply_filters( 'wpcinvoice_default_label_invoice', $label_invoice );
}

function wpcinvoice_shortcodes_list(){
    $tags = array(
        '{wpcargo_tracking_number}' => __('Tracking Number','wpcargo'),
		'{invoice_number}'          => __('Invoice Number','wpcargo'),
        '{admin_email}'             => __('Admin Email','wpcargo'),
        '{status}'                  => __('Shipment Status','wpcargo'),
        '{location}'                => __('Location','wpcargo'),
        '{site_name}'               => __('Website Name','wpcargo'),
        '{site_url}'                => __('Website URL','wpcargo'),
        '{wpcreg_client_email}'     => __('Registered Client Email','wpcargo'),
    );
    $all_fields = wpccf_get_all_fields();
    if( !empty( $all_fields ) ){
        foreach ($all_fields as $value) {
            if( $value['field_key'] == 'wpcargo_status' ){
                continue;
            }
            $tags[ '{'.$value['field_key'].'}' ] = $value['label'];
        }
    }
    $tags   = apply_filters( 'wpcinvoice_shortcode_meta_tags', $tags );
    return $tags;
}
function wpcinvoice_replace_shortcodes_list( $post_id ){
    $delimiter = array("{", "}");
    $replace_shortcodes = array();
    if( !empty( wpcinvoice_shortcodes_list() ) ){
        foreach ( wpcinvoice_shortcodes_list() as $shortcode => $shortcode_label ) {
            $shortcode = trim( str_replace( $delimiter, '', $shortcode ) );
            if( $shortcode == 'wpcargo_tracking_number' ){
                $replace_shortcodes[] = get_the_title($post_id);
			}elseif( $shortcode == 'invoice_number' ){
				$invoice_id     = get_post_meta( $post_id, '__wpcinvoice_id', true );
    			 $replace_shortcodes[] = $invoice_id ? get_the_title( $invoice_id ) : '';
            }elseif( $shortcode == 'admin_email' ){
                $replace_shortcodes[] = get_option('admin_email');
            }elseif( $shortcode == 'site_name' ){
                $replace_shortcodes[] = get_bloginfo('name');
            }elseif( $shortcode == 'site_url' ){
                $replace_shortcodes[] = get_bloginfo('url');
            }elseif( $shortcode == 'status' ){
                $replace_shortcodes[] = get_post_meta( $post_id, 'wpcargo_status', true );
            }elseif( $shortcode == 'wpcreg_client_email' ){
                $reg_shipper = (int)get_post_meta( $post_id, 'registered_shipper', true );
                $user_info   = get_userdata($reg_shipper);
                $reg_email = '';
                if( $user_info ){
                    $reg_email = $user_info->user_email;
                }
                $replace_shortcodes[] = $reg_email;
            }else{
                $meta_value = maybe_unserialize( get_post_meta( $post_id, $shortcode, true ) );
                $meta_value = apply_filters( 'wpcargo_shortcode_meta_value', $meta_value, $shortcode, $post_id );
                if( is_array( $meta_value ) ){
                    $meta_value = implode(', ',$meta_value );
                }
                $replace_shortcodes[] = $meta_value;
            }
        }
    }
    return $replace_shortcodes;
}
function wpcinvoice_get_shipment_sections(){
    global $wpdb;
    $sections           = $wpdb->get_col( "SELECT `section` FROM `{$wpdb->prefix}wpcargo_custom_fields` GROUP BY `section` ORDER BY `weight`" );
    $formatted_section  = array();
    $cfsections_opt     = wpcfe_get_cfsections_opt();
    if( !empty( $sections ) ){
        foreach ( $sections as $section ) { 
            
            if( array_key_exists('wpc_cf_disable_shipper', $cfsections_opt) && $section == 'shipper_info' ){
                continue;
            }
            if( array_key_exists('wpc_cf_disable_receiver', $cfsections_opt) && $section == 'receiver_info' ){
                continue;
            }
            if( array_key_exists('wpc_cf_disable_shipment', $cfsections_opt) && $section == 'shipment_info' ){
                continue;
            }
            
            $header = '';
            if( $section == 'shipper_info' ){
                $header = apply_filters( 'wpcfe_shipper_label', __('Shipper Information', 'wpcargo-invoice' ) ); 
            }elseif( $section == 'receiver_info' ){
                $header = apply_filters( 'wpcfe_receiver_label', __('Receiver Information', 'wpcargo-invoice' ) );
            }elseif( $section == 'shipment_info' ){
                $header = apply_filters( 'wpcfe_shipment_label', __('Shipment Information', 'wpcargo-invoice' ) );
            }else{
                $header = ucwords( str_replace('_', ' ', $section  ?? '') );
            } 
            $formatted_section[$section] = $header;
        }
    }
    return apply_filters( 'wpcinvoice_shipment_sections', $formatted_section );
}
function wpcinvoice_currency(){
    if ( !class_exists( 'WooCommerce' ) ) {
        return '';
    }
    return get_woocommerce_currency_symbol();
}
function wpcinvoice_total_fields(){
    $total_fields = array(
        'sub_total' => array(
            'label' => wpcinvoice_subtotal_label(),
            'field' => 'text',
            'required' => false,
            'readonly' => true
        ),
        'tax' => array(
            'label' => wpcinvoice_tax_label(),
            'field' => 'text',
            'required' => false,
            'readonly' => true
        ),
        'total' => array(
            'label' => wpcinvoice_total_label(),
            'field' => 'text',
            'required' => false,
            'readonly' => true
        )
    );
    return apply_filters( 'wpcinvoice_total_fields', $total_fields );
}
function wpcinvoice_ie_fields(){
    $fields = array(
        array(
            'meta_key'  => 'invoice_number',
            'label'     => wpcinvoice_number_label(),
            'fields'    => array()
        ),
        array(
            'meta_key'  => 'invoice_status',
            'label'     => wpcinvoice_invoice_status_label(),
            'fields'    => array()
        ),
        array(
            'meta_key'  => 'registered_shipper',
            'label'     => wpcinvoice_registered_shipper_label(),
            'fields'    => array()
        ),
        array(
            'meta_key'  => 'shipment_title',
            'label'     => wpcinvoice_shipment_label(),
            'fields'    => array()
        )
    );
    return apply_filters( 'wpcinvoice_ie_fields', $fields );
}
function wpcinvoice_export_file_format_list(){
    $extension = array(
        'xls' => ",", 
        'xlt' => ",", 
        'xla' => ",", 
        'xlw' => ",",
        'csv' => ","
    );
    return apply_filters( 'wpcinvoice_export_file_format_list', $extension );
}
function wpcinvoice_data_process_message_label( $number = 0 ){
    return sprintf( '%d %s', $number, __( 'data is being process, Please wait while processing the file to download.', 'wpc-import-export' ) );
}
function wpcinvoice_clean_dir( $directory ){
    $files = glob( $directory.'*'); // get all file names
    foreach($files as $file){ // iterate files
    if(is_file($file))
        unlink($file); // delete file
    }
}
function wpcinvoice_woo_order_shipment_map(){
    $map = array(
        'shipping-rate'             => function_exists( 'wpcsr_shipment_variation_label' ) ? wpcsr_shipment_variation_label() : 'Shipment',
        'parcel-quotation'          => '_shipment_num',
        'shipment-consolidation'    => function_exists('wpcshcon_consolidation_no_label') ? wpcshcon_consolidation_no_label() : 'Consolidate No.',
        'delivery'                  => function_exists( 'wpcvr_delivery_number_label' ) ? wpcvr_delivery_number_label() : 'Delivery Number',
    );
    return $map;
}
function wpcinvoice_get_invoice_order( $shipment_id ){
    if( !function_exists( 'wpcfe_get_shipment_type' ) ){
        return false;
    }
    $shipment_type  = get_post_meta( $shipment_id, '__shipment_type', true ) ? get_post_meta( $shipment_id, '__shipment_type', true ) : '';
    $meta_key       = '';
    if( $shipment_type == 'shipping-rate' ){
        $meta_key   = function_exists( 'wpcsr_shipment_variation_label' ) ? wpcsr_shipment_variation_label() : 'Shipment' ;
    }elseif( $shipment_type == 'parcel-quotation' ){
        $meta_key   = '_shipment_num' ;
    }elseif( $shipment_type == 'shipment-consolidation' ){
        $meta_key   = function_exists('wpcshcon_consolidation_no_label') ? wpcshcon_consolidation_no_label() : 'Consolidate No.';
    }elseif( $shipment_type == 'delivery' ){
        $meta_key   = function_exists( 'wpcvr_delivery_number_label' ) ? wpcvr_delivery_number_label() : 'Delivery Number' ;
    }
    return wpcinvoice_get_order_id( $shipment_id, $meta_key );
}
function wpcinvoice_get_order_data( $order_id ){
    $order  = wc_get_order( $order_id );
    if(!$order){
        return false;
    }
    $data                   = new stdClass();
    $order_data             = $order->get_data(); 
    $date_created           = date( wc_date_format(), $order_data['date_created']->getTimestamp() );
    $date_completed         = $order_data['date_completed'] ? date( wc_date_format(), $order_data['date_completed']->getTimestamp() ) : '';
    $date_paid              = $order_data['date_paid'] ? date( wc_date_format(), $order_data['date_paid']->getTimestamp() ) : '';
    
    $data->date_created     = $date_created;
    $data->date_completed   = $date_completed;
    $data->date_paid        = $date_paid ;
    $data->created_via      = $order->get_created_via();
    $data->customer_note    = $order->get_customer_note();
    $data->payment_method   = $order->get_payment_method_title();
    $data->subtotal         = $order->get_subtotal();
    $data->total_tax        = $order->get_total_tax();
    $data->total            = $order->get_total();
    $data->status           = wc_get_order_status_name( $order->get_status() );

    return $data;
}

function wpcinvoice_get_total_value( $shipment_id ){
    if( empty( $shipment_id ) ){  
        return array();
    }
    global $wpcargo;
    $wpcargo_tax_value = $wpcargo->tax/100;
    $sub_total = 0.00;
    $total = 0.00;
    if( !empty( wpcinvoice_package_fields() ) ){
        if(!empty(wpcargo_get_package_data( $shipment_id ) ) ) {
            foreach ( wpcargo_get_package_data( $shipment_id ) as $data_key => $data_value){                     
                $item_qty = array_key_exists( 'wpc-pm-qty', $data_value )? (float) $data_value['wpc-pm-qty'] : 0;
                $item_unit_price = array_key_exists(wpcinvoice_unit_price_key(), $data_value )?(float) $data_value[wpcinvoice_unit_price_key()] : 0;
                if( $item_qty && $item_unit_price ){
                    $sub_total += $item_qty*$item_unit_price;
                }
            }
        }
    }
    $tax = $sub_total * $wpcargo_tax_value;  
    $total = $sub_total + $tax;
    $total_values = array( 
        'tax'=> (float)$tax, 
        'sub_total' => (float)$sub_total, 
        'total' => (float)$total 
    );
    return apply_filters( 'wpcinvoice_get_total_value', $total_values, $shipment_id );
}
function wpcinvoice_save_shipment( $data, $post_id ){
    global $wpcargo, $WPCCF_Fields;
    $meta_keys       = $WPCCF_Fields->get_field_key_list();

    if( $post_id && !empty( $meta_keys ) && ( can_wpcfe_update_shipment() || can_wpcfe_add_shipment() ) ){
        foreach ( $data as $key => $value ) {
            // Check if meta key exist
            if( !in_array( $key, $meta_keys ) ){
               continue;
            }
            $value = maybe_serialize( $value );
            update_post_meta( $post_id, $key, sanitize_text_field( $value ) );
        }
        do_action( 'after_wpcinvoice_save_shipment', $post_id, $data );
    }
    if( $post_id ){
        $_POST['wpcfe-notification'] = array(
            'status'    => 'success',
            'icon'      => 'check',
            'message'   => __('Shipment ', 'wpcargo-invoice' ).' '.get_the_title( $post_id ).' '.__(' has been successfully saved.', 'wpcargo-invoice' )
        );
    }else{
        $_POST['wpcfe-notification'] = array(
            'status'    => 'danger',
            'icon'      => 'exclamation',
            'message'   => __('Something went wrong saving your shipment. Please reload and try again', 'wpcargo-invoice' )
        );
    }
         
}
function wpcinvoice_is_frontend_manager_activated(){
    if( in_array( 'wpcargo-frontend-manager/wpcargo-frontend-manager.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ){
        return true;
    }
    return false;
}
function wpcinvoice_include_packages_cost(){
    global $post;
    $result = false;
    if( isset( $_GET['wpcfe'] ) ){
        if( $_GET['wpcfe'] == 'add' ){
            $result =  true;
        }
        if( $_GET['wpcfe'] == 'update' && isset( $_GET['id'] ) ){
            $shipment_type = wpcfe_get_shipment_type( $_GET['id']  );
            if( $shipment_type == 'Default' || $shipment_type == 'wpcargo_default' ){
                $result =  true;
            }
        }
    }
    if( $post && has_shortcode( $post->post_content, 'wpcargo_invoices' ) ){
        $result =  true;
    }
    return apply_filters( 'wpcinvoice_include_packages_cost', $result );
}
// Language Helper
function wpcinvoice_activate_license(){
	return __( 'Please activate your license key for WPCargo Invoice Addon', 'wpcargo-invoice').' <a href="'.admin_url().'admin.php?page=wptaskforce-helper" title="WPCargo Invoice Addon">'.__( 'Activate', 'wpcargo-invoice').'</a>.';
}
function wpcinvoice_license_helper_required(){
	return __('This plugin requires WPTaskForce License Helper plugin to be active!', 'wpcargo-invoice' );
}
function wpcinvoice_wpcargo_required(){
	return __( 'This plugin requires WPCargo plugin to be active!', 'wpcargo-invoice' );
}
function wpcinvoice_customfield_required(){
	return __( 'This plugin requires WPCargo Custom Field Add-ons plugin to be active!', 'wpcargo-invoice' );
}
function wpcinvoice_frontend_required(){
	return __( 'This plugin requires WPCargo Frontend Managers plugin to be active!', 'wpcargo-invoice' );
}
function wpcinvoice_cheating_message(){
	return __( 'Cheating, uh?', 'wpcargo-invoice' );
}