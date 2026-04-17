<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// Table Header
function wpcinvoice_table_header_invoice(){
    ?><th class="wpcinv_header-invoice"><?php echo wpcinvoice_number_label(); ?></th><?php
}
function wpcinvoice_table_header_shipment(){
    ?><th class="wpcinv_header-shipment"><?php echo wpcinvoice_shipment_label(); ?></th><?php
}
function wpcinvoice_table_header_shipment_type(){
    ?><th class="wpcinv_header-shipment-type"><?php echo wpcinvoice_shipment_type_label(); ?></th><?php
}
function wpcinvoice_table_header_orderno(){
    ?><th class="wpcinv_header-orderno"><?php echo wpcinvoice_order_no_label(); ?></th><?php
}
function wpcinvoice_table_header_status(){
    ?><th class="wpcinv_header-status"><?php echo wpcinvoice_status_label(); ?></th><?php
}
function wpcinvoice_table_header_update(){
    ?><th class="wpcinv_header-update text-center"><?php echo __('Update', 'wpcargo-invoice'); ?></th><?php
}
function wpcinvoice_table_header_print(){
    $print_options = wpcfe_print_options();
    if( empty( $print_options ) ) return false;
    ?><th class="wpcinv_header-print text-center"><?php echo __('Print', 'wpcargo-invoice'); ?></th><?php
}
// Table Data
function wpcinvoice_table_data_invoice( $shipment_id, $invoice_id ){
    $invoice_id     = get_post_meta( $shipment_id, '__wpcinvoice_id', true );
    $invoice_number = $invoice_id ? get_the_title( $invoice_id ) : '';
    ?><td class="wpcinv_data-invoice font-weight-bold"><?php echo $invoice_number; ?></td><?php
}
function wpcinvoice_table_data_shipment( $shipment_id, $invoice_id ){
    $page_url       = get_the_permalink( wpcfe_admin_page() );
    $ship_number    = get_the_title( $shipment_id );
    ?><td class="wpcinv_data-shipment"><a href="<?php echo $page_url; ?>?wpcfe=track&num=<?php echo $ship_number; ?>" target="_blank" class="text-primary"><?php echo $ship_number; ?></a></td><?php
}
function wpcinvoice_table_data_shipment_type( $shipment_id, $invoice_id ){
    $shipment_type  = wpcfe_get_shipment_type( $shipment_id  );
    ?><td class="wpcinv_data-shipment"><?php echo $shipment_type; ?></td><?php
}
function wpcinvoice_table_data_orderno( $shipment_id, $invoice_id ){
    $order_id = wpcinvoice_get_invoice_order( $shipment_id );
    ?>
    <td class="wpcinv_data-orderno">
        <?php echo $order_id ? '#'.$order_id : ''; ?><br/>
        <?php echo $order_id ? wpcinvoice_get_order_data( $order_id )->status : ''; ?>
    </td>
    <?php
}
function wpcinvoice_table_data_status( $shipment_id, $invoice_id ){
    $invoice_id     = get_post_meta( $shipment_id, '__wpcinvoice_id', true );
    $invoice_num    = get_the_title( $invoice_id );
    ?>
    <td class="wpcinv_data-status">
        <a href="#" data-id="<?php echo $invoice_id; ?>" data-sid="<?php echo $shipment_id; ?>" data-number="<?php echo $invoice_num; ?>" class="wpcinvoice-update btn btn-info btn-sm py-1 px-2 mr-2"data-toggle="modal" data-target="#invoiceUpdateModal" title="<?php echo __('Edit', 'wpcargo-invoice'); ?>"><i class="fa fa-edit text-white"></i></a>
        <?php echo wpcinvoice_status( $invoice_id ); ?> 
    </td>
    <?php
}
function wpcinvoice_table_data_update( $shipment_id, $invoice_id ){
    $page_url       = get_the_permalink( wpcinvoice_dashboard_page() );
    ?><td class="wpcinv_data-update text-center"><a href="<?php echo $page_url; ?>?wpcinvoice=update&id=<?php echo $shipment_id; ?>" class="text-primary" title="<?php echo __('Update', 'wpcargo-invoice'); ?>"><i class="fa fa-edit text-info"></i></a></td><?php
}
function wpcinvoice_table_data_print( $shipment_id, $invoice_id ){
    $print_options = wpcfe_print_options();
    if( empty( $print_options ) ) return false;  
    // wpci-paid
    $status  = get_post_meta( $invoice_id, '__wpcinvoice_status', true );
    if( $status != 'wpci-paid' ){
        unset( $print_options['waybill'] );
    }
    ?>
    <td class="text-center print-shipment">
        <div class="dropdown" style="display:inline-block !important;">
            <!--Trigger-->
            <button class="btn btn-default btn-sm dropdown-toggle m-0 py-1 px-2" type="button" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false"><i class="fa fa-print"></i></button>
            <!--Menu-->
            <div class="dropdown-menu dropdown-primary">
                <?php foreach( $print_options as $print_key => $print_label ): ?>
                    <a class="dropdown-item print-<?php echo $print_key; ?> py-1" data-id="<?php echo $shipment_id; ?>" data-type="<?php echo $print_key; ?>" data-status="<?php echo $status; ?>" href="#"><?php echo $print_label; ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </td>
    <?php
}
function wpcinvoice_package_fields_callback( $fields ){   
    $wpcinvoice =isset($_REQUEST['wpcinvoice']) ? $_REQUEST['wpcinvoice'] : false;
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;
    $currency = !empty( wpcinvoice_currency() )? '('.wpcinvoice_currency().')' : '';
   if($wpcinvoice == false  && $action==false ){
       return $fields; 
   } 
  // wpcie_save_records & wpcie_export_data Import Exxport  Suport 
  // wpcfe_print_shipment default print support 
  // wpcfe_bulkprint invoice addon  print
  //  $wpcinvoice == 'update'  invoice update page 
 if($wpcinvoice == 'update' || $action=='wpcfe_bulkprint' ||  $action=='wpcie_save_records' || $action=='wpcie_export_data' || $action=='wpcfe_print_shipment'){
       
      $fields['unit-price'] = array(
          'label' =>  __('Shipping Cost '.$currency, 'wpcargo-invoice'),
          'field' => 'text',
          'required' => '',
          'options' => array()
      );
      $fields['unit-amount'] = array(
          'label' => __('Cost '.$currency, 'wpcargo-invoice'),
          'field' => 'text',
          'required' => '',
          'options' => array()
      );
       
}
  return $fields;
}
function wpcinvoice_shipment_sections_callback( $formatted_section ){
    $exclude_sections = array(
        'shipper_info',
        'shipment_info'
    );
    foreach( $exclude_sections as $section ){
        if( array_key_exists( $section , $formatted_section ) ){
            unset( $formatted_section[$section] );
        }
    }
    return $formatted_section;
}
function after_wpcinvoice_shipment_form_fields_callback( $shipment_id ){
    $wpcargo_settings = !empty( get_option('wpc_mp_settings') ) ? get_option('wpc_mp_settings') : array();
    if( !array_key_exists( 'wpc_mp_enable_admin', $wpcargo_settings ) ){
        return false;
    }
    $user_roles = wpcfe_current_user_role();
    if( can_wpcinvoice_access_package()){
        $shipment       = new stdClass();
        $shipment->ID   = $shipment_id;
        $template = wpcinvoice_locate_template( 'multiple-package.tpl' );
        require_once( $template );
    }
}
function wpcinvoice_after_package_details_callback( $shipment ){
    global $post, $wpcargo;
    if( !empty( $shipment ) && wpcinvoice_dashboard_page() == $post->ID ){
        $shipment_type  = wpcfe_get_shipment_type( $shipment->ID );
        $current_user   = wp_get_current_user();
        $user_roles     = $current_user->roles;
        $colspan        = count( wpcargo_package_fields() ) - 1;
        $pkg_totals     = wpcinvoice_get_total_value( $shipment->ID );   
        $form_control   = is_admin() ? '' : 'form-control';
        $text_color     = '';
        $td_width       = '';
        $nonpq_shiptype = array( 'Shipping Rate', 'Default', 'Delivery', 'Shipment Consolidation' );
        $nonins_shiptype = array( 'Default', 'Delivery', 'Shipment Consolidation' );
        $wpcinvoice_total_fields = wpcinvoice_total_fields();
        // unset some array fields based on shipment types
        if( $shipment_type == 'Parcel Quotation' ) {
            unset( $wpcinvoice_total_fields['insurance'] );
        }
        if( in_array( $shipment_type, $nonpq_shiptype ) ) {
            unset( $wpcinvoice_total_fields['freight'] );
            unset( $wpcinvoice_total_fields['fuel'] );
            unset( $wpcinvoice_total_fields['stops'] );
            unset( $wpcinvoice_total_fields['layover'] );
            if( in_array( $shipment_type, $nonins_shiptype ) ) {
                unset( $wpcinvoice_total_fields['insurance'] );
            }
        }
        if( $shipment_type == 'Delivery' || $shipment_type == 'Shipment Consolidation' ) {
            $td_width = 100;
        }
        if(can_wpcinvoice_access_package()){
            if( !empty( $wpcinvoice_total_fields ) ){
                foreach( $wpcinvoice_total_fields as $field_key => $fields ){

                    $invoice_total_field_keys = array_keys( $wpcinvoice_total_fields );

                    $value = array_key_exists( $field_key, $pkg_totals )? $pkg_totals[$field_key] : 0;
                    $readonly = ( $fields['readonly'] || in_array( $field_key, $invoice_total_field_keys ) ) ? 'readonly' : '';
                    $input_id = $field_key;
                    // change labels based on shipment types
                    $currency       = !empty( wpcinvoice_currency() )? '('.wpcinvoice_currency().')' : '';
                    $subtotal_label = 'Subtotal '.$currency;
                    $label          = $fields['label'];
                    if( $label == $subtotal_label && $shipment_type == 'Shipping Rate' ) {
                        $label = wpcinvoice_shipping_cost_label();
                    } elseif ( $label == $subtotal_label && $shipment_type == 'Delivery' ) {
                        $label = wpcinvoice_delivery_charge_label();
                    }
                    if( $field_key == 'total' ){
                        $text_color = ' text-danger';
                    }
                    ?>
                    <tr class="total-detail">
                        <td class="label <?php echo $text_color; ?>" colspan="<?php echo $colspan; ?>" align="right" style="vertical-align: middle;"><strong><?php echo $label; ?></strong></td>
                        <td class="value" colspan="1" width="<?php echo $td_width; ?>">
                            <?php
                                printf(
                                    '<input type="%s" id="%s" class="number %s" name="%s" value="%s" %s />',
                                    $fields['field'],
                                    $input_id,
                                    $form_control.$text_color,
                                    $field_key,
                                    wpcinvoice_format_value( $value, false ),
                                    $readonly
                                );
                            ?>
                        </td>
                        <?php if( !is_admin() ): ?>
                          <!--  <td>&nbsp;</td>-->
                        <?php endif; ?>
                    </tr>
                    <?php
                }
            }
            do_action( 'wpcinvoice_additional_details_script' );
        }
    }
}
function wpcinvoice_form_shipment_title( $shipment_id ){
    global $wpcargo;
    if( !array_key_exists( 'wpcargo_title_prefix_action', $wpcargo->settings ) || !(int)$shipment_id ){
        return false;
    }
    $invoice_id     = get_post_meta( $shipment_id, '__wpcinvoice_id', true );
    $status         = get_post_meta( $invoice_id, '__wpcinvoice_status', true );
    ?>
    <div class="col-md-12 mb-3">
        <div class="card">
            <div class="row">
                <div class="col-md-1 p-0 card-header"></div>
                <div class="col-md-10 p-0">
                    <div class="card-header text-center">
						<?php 
                          $invoice_id     = get_post_meta( $shipment_id, '__wpcinvoice_id', true );
	 						$page_url       = get_the_permalink( wpcfe_admin_page() );
    						$ship_number    = get_the_title( $shipment_id );
  
                         echo '<h5>'.apply_filters( 'wpcinvoice_invoice_number_label', __('INVOICE # ', 'wpcargo-invoice' ) ).get_the_title( $invoice_id ).'</h5>';
                    ?>
                        <p><?php echo apply_filters( 'wpcinvoice_shipment_number_label', __('Tracking # ', 'wpcargo-invoice' ) ) ; ?><a href="<?php echo $page_url; ?>?wpcfe=update&id=<?php echo $shipment_id; ?>" target="_blank" class="text-primary"><?php echo $ship_number; ?></a></p>
                     
                    </div>
                </div>
                <div class="col-md-1 p-0 pt-4 card-header text-center">
                    <div id="print-invoice" class="wpcinvoice-print" data-id="<?php echo $shipment_id; ?>" data-status="<?php echo $status; ?>"><i class="fa fa-print" title="<?php esc_html_e('Print Invoice', 'wpcargo-invoice');?>"></i></div>
                </div>
            </div>          
        </div>
    </div>
    <?php
}
// Save WPCargo Custom fields
function wpcinvoice_update_shipment(){
    global $WPCCF_Fields;
    if ( isset( $_POST['wpcinvoice_form_fields'] ) && wp_verify_nonce( $_POST['wpcinvoice_form_fields'], 'wpcinvoice_edit_action' ) && isset( $_POST['shipment_id'] ) &&                    is_wpcinvoice_shipment( $_POST['shipment_id'] ) ) {
        wpcinvoice_save_shipment( $_POST, $_POST['shipment_id'] ); 

    }
}
// Save Multiple Package data
function wpcinvoice_shipment_multipackage_save( $post_id, $data ){
    if( empty( $data ) || !is_array( $data ) ){
        return false;
    }
   $packages = array_key_exists( 'wpc-multiple-package', $data ) ? maybe_serialize( $data['wpc-multiple-package'] ) : maybe_serialize( array() );
   update_post_meta( $post_id, 'wpc-multiple-package', $packages );
    $invoice_id = get_post_meta($post_id, "__wpcinvoice_id", true)?:'';
   if($invoice_id){
        update_post_meta( $invoice_id, 'wpc-multiple-package', $packages );
   }
}

// Save Multiple Package data
function wpc_shipment_multipackage_save( $post_id, $data ){

    if( empty( $data ) || !is_array( $data ) ){
        return false;
    }
    $updated_data = array();
    $packages = array_key_exists( 'wpc-multiple-package', $data ) ?  $data['wpc-multiple-package']  :  array();
    $invoice_id = get_post_meta($post_id, "__wpcinvoice_id", true)?:'';

    if ( $invoice_id ) {
        // Retrieve the existing in_packages metadata
        $in_packages =  maybe_unserialize(get_post_meta( $invoice_id, 'wpc-multiple-package', true ))?:array();

        // Ensure $in_packages is an array
        if ( !is_array( $in_packages ) ) {
            $in_packages = [];
        }
        
		if(!empty($in_packages)){
			// Override in_packages with packages by key
			foreach ( $packages as $key => $package ) {
				foreach ($package as $packagekey => $packagevalue) {
					if($packagekey === 'wpc-pm-qty'){
						$in_packages[ $key ]['unit-amount'] = ($in_packages[ $key ]['unit-price'] * $packagevalue);
					}
				}
				$in_packages[ $key ] = array_merge( $in_packages[ $key ] ?? [], $package );
			}
			$updated_data = !empty($in_packages) ? maybe_serialize($in_packages ) : maybe_serialize( array() );
			update_post_meta( $post_id, 'wpc-multiple-package', $updated_data );
		}


    }

}

add_action("after_wpcfe_save_shipment", "wpc_shipment_multipackage_save", 99, 2);

function wpcinvoice_template_path_callback( $template_path ){
    return WPC_INVOICE_PATH.'templates/invoice.tpl.php';
}
function wpcinvoice_hooks_filters_callback(){
    // Wordpres hooks
    add_action( 'wp', 'wpcinvoice_update_shipment' );
    // WPCargo Free
    add_filter( 'wpcargo_package_fields', 'wpcinvoice_package_fields_callback' ,999);
    add_action( 'wpcinvoice_after_package_table_row', 'wpcinvoice_after_package_details_callback', 20, 2 );
    // Invoice table data hooks - Header
    add_action( 'wpcinvoice_table_header', 'wpcinvoice_table_header_invoice' );
    add_action( 'wpcinvoice_table_header', 'wpcinvoice_table_header_shipment' );
    add_action( 'wpcinvoice_table_header', 'wpcinvoice_table_header_shipment_type' );
    if ( class_exists( 'WooCommerce' ) ) {
        add_action( 'wpcinvoice_table_header', 'wpcinvoice_table_header_orderno', 10, 2 );
    }
    add_action( 'wpcinvoice_table_header', 'wpcinvoice_table_header_status' );
    add_action( 'wpcinvoice_table_header', 'wpcinvoice_table_header_update' );
    add_action( 'wpcinvoice_table_header', 'wpcinvoice_table_header_print' );
    // Invoice table data hooks - Data
    add_action( 'wpcinvoice_table_data', 'wpcinvoice_table_data_invoice', 10, 2 );
    add_action( 'wpcinvoice_table_data', 'wpcinvoice_table_data_shipment', 10, 2 );
    add_action( 'wpcinvoice_table_data', 'wpcinvoice_table_data_shipment_type', 10, 2 );
    if ( class_exists( 'WooCommerce' ) ) {
        add_action( 'wpcinvoice_table_data', 'wpcinvoice_table_data_orderno', 10, 2 );
    }
    add_action( 'wpcinvoice_table_data', 'wpcinvoice_table_data_status', 10, 2 );
    add_action( 'wpcinvoice_table_data', 'wpcinvoice_table_data_update', 10, 2 );
    add_action( 'wpcinvoice_table_data', 'wpcinvoice_table_data_print', 10, 2 );
    // FM Hooks
    // add_filter( 'invoice_template_path', 'wpcinvoice_template_path_callback', 999 );
    add_filter( 'wpcfe_print_template_path_invoice', 'wpcinvoice_template_path_callback', 999 );
    add_filter( 'wpcinvoice_shipment_sections', 'wpcinvoice_shipment_sections_callback', 10, 1 );
    add_action( 'after_wpcinvoice_shipment_form_fields', 'after_wpcinvoice_shipment_form_fields_callback', 10, 1 );
    // Invoice Hooks
    add_action( 'after_wpcinvoice_save_shipment', 'wpcinvoice_shipment_multipackage_save', 10, 2 );
    add_action( 'before_wpcinvoice_shipment_form_fields', 'wpcinvoice_form_shipment_title', 1 );
    // FM Scripts
    add_filter( 'wpcfe_registered_styles', 'wpcinvoice_registered_styles' );
    add_filter( 'wpcfe_registered_scripts', 'wpcinvoice_registered_scripts' );
}
add_action( 'plugins_loaded', 'wpcinvoice_hooks_filters_callback' );

// additional hooks for parcel quote integration if Parcel Quotation Plugin is installed

/**
 * merge Invoice Addon total fields with Parcel Quotation's additional charges fields
 */

function wpcinvoice_total_fields_callback( $array ) {

    if( class_exists( 'WPCargo_Parcel_Quotation' ) ) { // parcel quote integration

        // get parcel quote's additional charges fields
        $wpcpq_add_charges = wpcpq_package_additional_charges();
    
        // unset tax and subtotal from parcel quote's additional charges fields since it's already present in invoice
        if( array_key_exists( 'wpcpq_subtotal', $wpcpq_add_charges ) || array_key_exists( 'tax-cost', $wpcpq_add_charges ) ) {
    
            unset( $wpcpq_add_charges['tax-cost'] );
            unset( $wpcpq_add_charges['wpcpq_subtotal'] );
    
        }
    
        // add aditional key-value pair on parcel quote's additional charges fields
        $new_array = array();
        foreach( $wpcpq_add_charges as $key => $value ) {
    
            // add new key value pair for each field in order to avoid parsing errors with invoice's total fields when merging
            $value['readonly'] = 1;
            $new_array[$key] = $value;
        }
    
        // merge the invoice total fields and parcel quote additional charges fields
        $array = array_slice($array, 0, 2, true) + $new_array + array_slice($array, 2, count($array) - 1, true);
    }
    if( class_exists( 'WPCSR_Core' ) ) { // shipping rate integration
        $new_array = array(
            'insurance' => array(
                'label' => apply_filters( 'wpcinv_insurance_label', __( 'Insurance', 'wpcargo-invoice' ) ),
                'field' => 'text',
                'required' => false,
                'readonly' => true
            )
        );

        $array = array_slice($array, 0, 2, true) + $new_array + array_slice($array, 2, count($array) - 1, true);
    }
    
    
    // return merged fields
    return $array;

}
add_filter('wpcinvoice_total_fields', 'wpcinvoice_total_fields_callback', 10, 1 );

function wpcinvoice_get_total_value_ccallback( $total_values, $shipment_id ) {
    global $wpcargo;
    // get wpcargo tax
    $wpcargo_tax = ( $wpcargo->tax / 100 );

	// get shipment type
	$shipment_type        = wpcfe_get_shipment_type( $shipment_id );

    // declare additional global variables
    $result               = array();
    $invoice_total_fields = wpcinvoice_total_fields();
    $add_charges          = array();
    $values_total         = 0;

    /**
     * integrations based on shipment type starts here
     * 
     * @parcel quotation
     * 
     * @shipping rate
     * 
     * @vehicle rate
     * 
     * @shipment consolidation
     */

    if( $shipment_type == 'Parcel Quotation' ) { // parcel quotation integration
        
        // unset unnecessary fields
        if( array_key_exists( 'sub_total', $invoice_total_fields ) || array_key_exists( 'tax', $invoice_total_fields ) || array_key_exists( 'total', $invoice_total_fields ) ) {
            unset( $invoice_total_fields['sub_total'] );
            unset( $invoice_total_fields['tax'] );
            unset( $invoice_total_fields['total'] );
        }

        // loop additional charges fields
        if( !empty( $invoice_total_fields ) && is_array( $invoice_total_fields ) ) {
            foreach( $invoice_total_fields as $meta_key => $val ) {
                $_value = get_post_meta( $shipment_id, $meta_key, true );
                $value  = is_numeric( $_value ) ? (float)$_value : 0;
                $values_total += (float)$value;
                $add_charges[$meta_key] = $value;
            }
        }
        
        // modify invoice's total with parcel quote's total additional charges
        $total_values['total'] += $values_total;
        
        // merge parcel quote's additional charges fields with invoice's total fields
        $result = $total_values + $add_charges;

    } elseif ( $shipment_type == 'Shipping Rate' ) { // shipping rate integration
        // get rate id and protection
        $rate_id         = get_post_meta( $shipment_id, 'shipment_rate', true );
        $rate_protection = get_post_meta( $shipment_id, 'wpcsr_rate_proctection', true );

        // retrieve package info rate details
        $packages       = array();
        $package_info   = array();
        $cost_breakdown = array();
        if( function_exists( 'wpcargo_get_package_data' ) ) {
            $packages = wpcargo_get_package_data( $shipment_id );
        }
        if( function_exists( 'wpcsr_calculate_package_information' ) ) {
            $package_info = wpcsr_calculate_package_information( $packages );
        }
        if( function_exists( 'wpcsr_get_shipment_rate' ) ) {
            $ins_cost = get_post_meta( $shipment_id, 'ins_cost', true ) ? get_post_meta( $shipment_id, 'ins_cost', true ) : 0.00;
            $insurance_enabled = get_option( 'wpcsr_insurance' );

            if( !$insurance_enabled ){
                $cost_breakdown = wpcsr_get_shipment_rate( $rate_id, $package_info, $rate_protection, $shipment_id );
            }else{
                $cost_breakdown = wpcsr_get_shipment_rate( $rate_id, $package_info, 0, $shipment_id );
                $cost_breakdown['protect'] = $ins_cost;
            }
        }
        if( !empty( $cost_breakdown ) ) {
            $sub_total = $cost_breakdown['cost'];
            $tax       = $cost_breakdown['tax'];
            $insurance = $cost_breakdown['protect'];
            //add pickup rate
            $pickup_rate = get_post_meta( $shipment_id, '_pickup_rate', true );
            $total     = ( $sub_total + $tax + $insurance + $pickup_rate );
            if( $pickup_rate ){
                $total_values['__pickup'] = $pickup_rate;
            }
            // modify invoice's total fields with shipping rate's rate cost information
            $total_values['sub_total'] = $sub_total;
            $total_values['tax'] = $tax;
            $total_values['insurance'] = $insurance;
            
            $total_values['total'] = $total;

            $result = $total_values;
        }
    } elseif( $shipment_type == 'Delivery' ) { // vehicle rate integration
        // get delivery info
        $delivery_data = get_post_meta( $shipment_id, 'delivery_data', true );
        $delivery_data = maybe_unserialize( $delivery_data );
        if( !empty( $delivery_data ) ) {
            $delivery_charge = $delivery_data['delivery-charge']['value'];
            $tax = ( $delivery_charge * $wpcargo_tax );
            $total = ( $delivery_charge + $tax );
            // modify invoice's total fields with vehicle rate's delivery charge information
            $total_values['sub_total'] = $delivery_charge;
            $total_values['tax']       = $tax;
            $total_values['total']     = $total;
    
            $result = $total_values;
        }
    } elseif( $shipment_type == 'Shipment Consolidation' ) { // shipment consolidation integration
        if( function_exists( 'wpcshcon_get_consolidation_cost' ) ) {
            $subtotal = wpcshcon_get_consolidation_cost( $shipment_id );
            $tax      = 0;
            if( function_exists( 'wpcshcon_format_number' ) ) {
                $tax = wpcshcon_format_number( get_post_meta( $shipment_id, 'wpcshcon_tax_fee', true ) );
            }

            $total_values['sub_total'] = ( $subtotal - $tax );
            $total_values['tax']       = $tax;
            $total_values['total']     = $subtotal;
    
            $result = $total_values;
        }
    } else {
        return $total_values;
    }
	
	return $result;
	
}
add_filter( 'wpcinvoice_get_total_value', 'wpcinvoice_get_total_value_ccallback', 10, 2 );

/**
 * include parcel quote additional charges fields on printable PDF
 */

function wpcinvoice_after_invoice_tax_ccallback( $total_info, $shipment_id, $shipment_type ) {
    /**
     * integrations start here
     * 
     * @parcel quote
     * 
     * @shipping rate
     */

    if( $shipment_type == 'Parcel Quotation' ) { // parcel quote integration
        if( function_exists( 'wpcpq_package_additional_charges' ) ) {
    
            // get parcel quote's additional charges fields
            $wpcpq_add_charges = wpcpq_package_additional_charges();
        
            // unset tax and subtotal from additional charges fields
            unset( $wpcpq_add_charges['tax-cost'] );
            unset( $wpcpq_add_charges['wpcpq_subtotal'] );
        
            foreach( $wpcpq_add_charges as $key => $value ) {
                ?>
                <tr align = "center" ">
                    <td class="no-padding" style = "padding:0 5px !important;" align = "left"><?php echo sprintf( __( '%s', 'wpcargo-invoice' ), $value['label'] ); ?></td>
                    <td class="no-padding"><?php echo wpcinvoice_format_value( $total_info[$key], true ); ?></td>
                    <td class="no-padding">&nbsp;</td>
                    <td class="no-padding"><?php echo wpcinvoice_format_value( 0.00, true ); ?></td>
                    <td class="no-padding"><?php echo wpcinvoice_format_value( $total_info[$key], true ); ?></td>
                </tr>
                <?php
            }
        }
    } elseif ( $shipment_type == 'Shipping Rate' ) { // shipping rate integration
        
        // add insurance
        $label = apply_filters( 'wpcinv_insurance_pdf_label', __( 'Insurance', 'wpcargo-invoice' ) );
        $value = $total_info['insurance'];

        //price
        $pickup_label = apply_filters( 'wpcinv_pickup_label', __( 'Pickup Rate', 'wpcargo-invoice' ) );
        $_pickup = isset( $total_info['__pickup'] ) ? $total_info['__pickup'] : '';
        ?>
        <tr align = "center" style = "padding:0 5px !important;">
            <td class="no-padding" align = "left" style = "padding:0 5px !important;"><?php echo $label; ?></td>
            <td class="no-padding"><?php echo wpcinvoice_format_value( $value, true ); ?></td>
            <td class="no-padding">&nbsp;</td>
            <td class="no-padding"><?php echo wpcinvoice_format_value( 0.00, true ); ?></td>
            <td class="no-padding"><?php echo wpcinvoice_format_value( $value, true ); ?></td>
        </tr>
        <?php if(isset( $total_info['__pickup'] ) ) :?>
        <tr align = "center" style = "padding:0 5px !important;">
            <td class="no-padding" align = "left" style = "padding:0 5px !important;"><?php echo $pickup_label; ?></td>
            <td class="no-padding"><?php echo wpcinvoice_format_value( $_pickup, true ); ?></td>
            <td class="no-padding">&nbsp;</td>
            <td class="no-padding"><?php echo wpcinvoice_format_value( 0.00, true ); ?></td>
            <td class="no-padding"><?php echo wpcinvoice_format_value( $_pickup, true ); ?></td>
        </tr>
        <?php
        endif;
    }
}
add_action( 'wpcinvoice_after_invoice_tax', 'wpcinvoice_after_invoice_tax_ccallback', 10, 3 );



function wpcinv_delivery_data_template( $shipment){

		$shipment_type	= wpcfe_get_shipment_type( $shipment->ID);
		 $shipment_id    = $shipment->ID;

		if( $shipment_type == 'Delivery' ){ if( function_exists( 'wpcvr_delivery_data_template' ) ) {
			$delivery_data	= get_post_meta( $shipment->ID, 'delivery_data', true );
			$rate_id		= $delivery_data['wpcvr_id']['value'];
			$rate_data		= get_wpcvr_rate_data( $rate_id );
			$wpcvr_fields	= wpcvr_rate_fields();
		//	$template = wpcvr_include_template( 'delivery-data.tpl' );
		//	include_once( $template );
		
		?>
			<section class="card-body">
			<div class="row">
				<div class="col-md-6">
					<p class="font-weight-bold"><?php echo apply_filters( 'wpcvr_vehicle_table_header_label', __('Vehicle', 'wpcargo-vehicle-rate') ); ?></p>
					<div id="vehicle-rate-data" class="table-responsive">
						<table id="vehicle-rate" class="wpc-vehicle-rate table table-hover table-sm" style="width:100%">
							<?php foreach( $wpcvr_fields as $rate_field_meta => $rate_field_data ): ?>
								<tr>
									<th class="tbl-sh-<?php echo $rate_field_meta; ?>"><strong><?php echo $rate_field_data['label']; ?></strong></th>
									<td><?php echo $rate_data->$rate_field_meta; ?></td>
								</tr>
							<?php endforeach; ?>
						</table>
					</div>
				</div>
				<div class="col-md-6">
					<p class="font-weight-bold"><?php echo apply_filters( 'wpcvr_location_table_header_label', __('Location', 'wpcargo-vehicle-rate') ); ?></p>
					<div id="shipment-delivery-data" class="table-responsive">
						<table id="shipment-delivery" class="wpc-shipment-delivery table table-hover table-sm" style="width:100%">
							<?php foreach( $delivery_data as $delivery_field_meta => $delivery_field_data ): ?>
								<?php if( $delivery_field_meta != 'wpcvr_id' && $delivery_field_meta != 'delivery-number' ): ?>
									<tr>
										<th class="tbl-sh-<?php echo $delivery_field_meta; ?>"><strong><?php echo $delivery_field_data['label']; ?></strong></th>
										<td><?php echo $delivery_field_data['value']; ?></td>
									</tr>
								<?php endif; ?>
							<?php endforeach; ?>
						</table>
					</div>
				</div>
			</div>
		</section>
		<?php
	 } }elseif ( $shipment_type == 'Shipment Consolidation' ) {

        $currency_symbol = '';
        if( function_exists( 'get_woocommerce_currency_symbol' ) ) {
            $currency_symbol = get_woocommerce_currency_symbol();
        }
        $total_cost  = 0;
        $method_meta = '';
        $weight_unit = '';
		$shipping_method = get_post_meta( $shipment_id, 'wpcshcon_shipping_method', true );
        if( function_exists( 'wpcshcon_get_consolidation_cost' ) ) {
            $total_cost = wpcshcon_get_consolidation_cost( $shipment_id );
        }
        if( function_exists( 'wpcshcon_text_to_meta' ) ) {
            $method_meta = 'cost_'.wpcshcon_text_to_meta( $shipping_method );
        }
        if( function_exists( 'wpcshcon_weight_unit' ) ) {
            $weight_unit = wpcshcon_weight_unit();
        }

		// Shipping Method
		$shipping_cost  = wpcshcon_format_number( get_post_meta( $shipment_id, $method_meta, true ) );
		$tax_cost       = wpcshcon_format_number( get_post_meta( $shipment_id, 'wpcshcon_tax_fee', true ) );
        
		$ordered_on     = get_the_date( 'F j, Y', $shipment_id );
		$consolidate_no = get_the_title( $shipment_id );
		$status         = get_post_meta( $shipment_id, 'wpcargo_status', true );
		$shipments	    = maybe_unserialize( get_post_meta( $shipment_id, 'wpcshcon_shipments', true ) );
        ?>
           <div id="shipment-details-section" class="wpcargo-container">
				<h5><?php _e('Shipment Details:', 'wpc-shipment-consoldation'); ?></h5>
				<table class="table table-hover table-sm wpcargo-table" style="width:100%;">
					<thead>
						<tr>
							<th><?php _e('Date', 'wpc-shipment-consoldation'); ?></th>
							<th><?php _e('Tracking Number', 'wpc-shipment-consoldation'); ?></th>
							<th><?php _e('Sender', 'wpc-shipment-consoldation'); ?></th>
							<th><?php _e('Weight', 'wpc-shipment-consoldation'); ?>(<?php  echo $weight_unit; ?>)</th>
							<th><?php _e('Status', 'wpc-shipment-consoldation'); ?></th>
							<th><?php _e('Free Storage', 'wpc-shipment-consoldation'); ?></th>
							<th><?php _e('Cost', 'wpc-shipment-consoldation'); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php
					if( !empty( $shipments ) ){
						foreach( $shipments as $shipment ){
							$shipment_weight = wpcshcon_shipment_weight( $shipment );
							?>
							<tr>
								<td><?php echo get_the_date( 'F j, Y', $shipment ); ?></td>
								<td><?php echo get_the_title(  $shipment ); ?></td>
								<td><?php echo get_post_meta( $shipment, 'wpcshcon_store', true ); ?></td>
								<td><?php echo $shipment_weight; ?></td>
								<td><?php echo get_post_meta( $shipment, 'wpcargo_status', true ); ?></td>
								<td><?php echo get_shipment_storage_left( $shipment ); ?> <?php _e('Day(s)', 'wpc-shipment-consoldation'); ?></td>
								<td style="text-align: right;"><?php echo $currency_symbol.number_format((float)get_post_meta( $shipment_id, 'shipments_cost_'.$shipment, true ), 2, '.', '') ; ?></td>
							</tr>
							<?php
						}
					}else{
						?><tr class="no-consolidation"><td colspan="7"><?php _e('No Consolidated Shipment Found', 'wpc-shipment-consoldation'); ?></td></tr><?php
					}
					?>
					</tbody>
				<tfoot>
                    <tr>
                        <td colspan="5" style="padding:0!important">
                           
                            <table  style="width: 100%;">
                                <tr>
                                    <td class="no-padding" style='vertical-align:bottom;'><strong><?php _e('Ordered On:', 'wpc-shipment-consoldation'); ?></strong></td>
                                    <td class="no-padding"><?php echo $ordered_on; ?></td>
                                </tr>
                                <tr>
                                    <td class="no-padding" style='vertical-align:bottom;'><strong><?php _e('Consolidate No.:', 'wpc-shipment-consoldation'); ?></strong></td>
                                    <td class="no-padding"><?php echo $consolidate_no; ?></td>
                                </tr>
                                <tr>
                                    <td class="no-padding" style='vertical-align:bottom;'><strong><?php _e('Shipping Method:', 'wpc-shipment-consoldation'); ?></strong></td>
                                    <td class="no-padding"><?php echo $shipping_method; ?></td>
                                </tr>
                                <tr>
                                    <td class="no-padding" style='vertical-align:bottom;'><strong><?php _e('Status:', 'wpc-shipment-consoldation'); ?></strong></td>
                                    <td class="no-padding"><?php echo $status; ?></td>
                                </tr>
                                <tr>
                                    <td class="no-padding" style='vertical-align:bottom;'><strong><?php _e('Total Shipment:', 'wpc-shipment-consoldation'); ?></strong></td>
                                    <td class="no-padding"><?php echo count( $shipments ); ?></td>
                                </tr>
                            </table>
                        </td>
                        <td colspan="2" style="padding:0!important">
                            <table style="width: 100%;">
				
                                <?php 
                                if( function_exists( 'wpcshcon_additional_fees' ) ) {
                                    $wpcshcon_additional_fees = wpcshcon_additional_fees();
                                    if( !empty( $wpcshcon_additional_fees ) ) {
                                        foreach( $wpcshcon_additional_fees as $key => $label ) {
                                            $cost_meta = 'wpcshcon_'.$key.'_cost';
                                            $cost = 0;
                                  
                                            if( function_exists( 'wpcshcon_get_meta_cost' ) ) {
                                                $cost = wpcshcon_get_meta_cost( $shipment_id, $cost_meta );
                                            }
                                            ?>
                                            <tr>
                                                <td class="no-padding" style='vertical-align:bottom; text-align: right;'><strong><?php echo $label.':'; ?></strong></td>
                                                <td class="no-padding" style="text-align: right;"><?php echo $currency_symbol.$cost; ?></td>
                                            </tr>
                                            <?php
                                            
                                        }
                                    }
                                }
                                ?>
                            </table>
                        </td>
                    </tr>
                    </tfoot>
                
            </table>
            </div>
        <?php
    }
		
	
	
}

add_action( 'wpcinvoice_before_package_table_row', 'wpcinv_delivery_data_template', 10, 2 );

function wpcinv_delivery_data_print_template( $shipmentDetails ){
    $shipment_id    = $shipmentDetails['shipmentID'];
    $shipment_type	= wpcfe_get_shipment_type( $shipment_id );
    if( $shipment_type == 'Delivery' ) {
        if( function_exists( 'wpcvr_delivery_data_template' ) ) {
                   $delivery_data	= get_post_meta( $shipment_id , 'delivery_data', true );
                   $rate_id		= $delivery_data['wpcvr_id']['value'];
                   $rate_data		= get_wpcvr_rate_data( $rate_id );
                   $wpcvr_fields	= wpcvr_rate_fields();
                   //    $template = wpcvr_include_template( 'delivery-data.tpl' );
                   //	include_once( $template );
               
               ?><h3 class="section-header"><?php echo apply_filters( 'wpcinvoice_package_header', __('DELIVERY INFORMATION', 'wpcargo-invoice' ) ); ?></h3>
                   <section class="card-body">
                   <div class="row">
                       <div class="col-md-6">
                           
                           <div id="vehicle-rate-data" class="table-responsive">
                       <table id="vehicle-rate" class="wpc-vehicle-rate table table-hover table-sm wpcargo-table-bordered" style="width:100%">	<tr>
                               <td><!--<h3 class="font-weight-bold"><?php echo apply_filters( 'wpcvr_vehicle_table_header_label', __('Vehicle', 'wpcargo-vehicle-rate') ); ?></h3>	-->	
                               <table class="wpcargo-table wpcargo-table-bordered" style="width:100%">
                                   <?php foreach( $wpcvr_fields as $rate_field_meta => $rate_field_data ): ?>
                                       <tr>
                                           <td class="no-padding tbl-sh-<?php echo $rate_field_meta; ?>"><strong><?php echo $rate_field_data['label']; ?></strong></td>
                                           <td class="no-padding"><?php echo $rate_data->$rate_field_meta; ?></td>
                                       </tr>
                                   <?php endforeach; ?>
                               </table>
                   </td><td><!--<h3 class="font-weight-bold"><?php echo apply_filters( 'wpcvr_vehicle_table_header_label', __('Location', 'wpcargo-vehicle-rate') ); ?></h3>-->
                               <table  class="wpcargo-table wpcargo-table-bordered" style="width:100%">
                                   <?php foreach( $delivery_data as $delivery_field_meta => $delivery_field_data ): ?>
                                       <?php if( $delivery_field_meta != 'wpcvr_id' && $delivery_field_meta != 'delivery-number' ): ?>
                                           <tr>
                                               <td class="no-padding tbl-sh-<?php echo $delivery_field_meta; ?>"><strong><?php echo $delivery_field_data['label']; ?></strong></td>
                                               <td class="no-padding"><?php echo $delivery_field_data['value']; ?></td>
                                           </tr>
                                       <?php endif; ?>
                                   <?php endforeach; ?>
                               </table></td>	</tr></table>
                           </div>
                       </div>
                   </div>
               </section>
               <?php
           }
    } elseif ( $shipment_type == 'Shipment Consolidation' ) {
        $currency_symbol = '';
        if( function_exists( 'get_woocommerce_currency_symbol' ) ) {
            $currency_symbol = get_woocommerce_currency_symbol();
        }
        $total_cost  = 0;
        $method_meta = '';
        $weight_unit = '';
		$shipping_method = get_post_meta( $shipment_id, 'wpcshcon_shipping_method', true );
        if( function_exists( 'wpcshcon_get_consolidation_cost' ) ) {
            $total_cost = wpcshcon_get_consolidation_cost( $shipment_id );
        }
        if( function_exists( 'wpcshcon_text_to_meta' ) ) {
            $method_meta = 'cost_'.wpcshcon_text_to_meta( $shipping_method );
        }
        if( function_exists( 'wpcshcon_weight_unit' ) ) {
            $weight_unit = wpcshcon_weight_unit();
        }

		// Shipping Method
		$shipping_cost  = wpcshcon_format_number( get_post_meta( $shipment_id, $method_meta, true ) );
		$tax_cost       = wpcshcon_format_number( get_post_meta( $shipment_id, 'wpcshcon_tax_fee', true ) );
        
		$ordered_on     = get_the_date( 'F j, Y', $shipment_id );
		$consolidate_no = get_the_title( $shipment_id );
		$status         = get_post_meta( $shipment_id, 'wpcargo_status', true );
		$shipments	    = maybe_unserialize( get_post_meta( $shipment_id, 'wpcshcon_shipments', true ) );
        ?>
            <h3 class="section-header"><?php echo apply_filters( 'wpcinvoice_shcon_package_header', __('CONSOLIDATION INFORMATION', 'wpcargo-invoice' ) ); ?></h3>
				<table class="table table-hover table-sm wpcargo-table wpcargo-table-bordered" style="width:100%; padding-top:8px;">
					<thead>
						<tr>
							<th  class="no-padding"><?php _e('Date', 'wpc-shipment-consoldation'); ?></th>
							<th  class="no-padding"><?php _e('Tracking #', 'wpc-shipment-consoldation'); ?></th>
							<th  class="no-padding"><?php _e('Sender', 'wpc-shipment-consoldation'); ?></th>
							<th  class="no-padding"><?php _e('Weight', 'wpc-shipment-consoldation'); ?>(<?php  echo $weight_unit; ?>)</th>
							<th  class="no-padding"><?php _e('Status', 'wpc-shipment-consoldation'); ?></th>
							<th  class="no-padding"><?php _e('Free Storage', 'wpc-shipment-consoldation'); ?></th>
							<th  class="no-padding"><?php _e('Cost', 'wpc-shipment-consoldation'); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php
					if( !empty( $shipments ) ){
						foreach( $shipments as $shipment ){
							$shipment_weight = wpcshcon_shipment_weight( $shipment );
							?>
							<tr>
								<td  class="no-padding"><?php echo get_the_date( 'F j, Y', $shipment ); ?></td>
								<td  class="no-padding"><?php echo get_the_title(  $shipment ); ?></td>
								<td  class="no-padding"><?php echo get_post_meta( $shipment, 'wpcshcon_store', true ); ?></td>
								<td  class="no-padding"><?php echo $shipment_weight; ?></td>
								<td  class="no-padding"><?php echo get_post_meta( $shipment, 'wpcargo_status', true ); ?></td>
								<td  class="no-padding"><?php echo get_shipment_storage_left( $shipment ); ?> <?php _e('Day(s)', 'wpc-shipment-consoldation'); ?></td>
								<td  class="no-padding" style="text-align: right;"><?php echo $currency_symbol.number_format((float)get_post_meta( $shipment_id, 'shipments_cost_'.$shipment, true ), 2, '.', '') ; ?></td>
							</tr>
							<?php
						}
					}else{
						?><tr class="no-consolidation"><td colspan="7"><?php _e('No Consolidated Shipment Found', 'wpc-shipment-consoldation'); ?></td></tr><?php
					}
					?>
					</tbody>
				<tfoot>
                    <tr>
                        <td colspan="5" style="padding:0!important">
                           
                            <table  style="width: 100%;">
                                <tr>
                                    <td class="no-padding" style='vertical-align:bottom;'><strong><?php _e('Ordered On:', 'wpc-shipment-consoldation'); ?></strong></td>
                                    <td class="no-padding"><?php echo $ordered_on; ?></td>
                                </tr>
                                <tr>
                                    <td class="no-padding" style='vertical-align:bottom;'><strong><?php _e('Consolidate No.:', 'wpc-shipment-consoldation'); ?></strong></td>
                                    <td class="no-padding"><?php echo $consolidate_no; ?></td>
                                </tr>
                                <tr>
                                    <td class="no-padding" style='vertical-align:bottom;'><strong><?php _e('Shipping Method:', 'wpc-shipment-consoldation'); ?></strong></td>
                                    <td class="no-padding"><?php echo $shipping_method; ?></td>
                                </tr>
                                <tr>
                                    <td class="no-padding" style='vertical-align:bottom;'><strong><?php _e('Status:', 'wpc-shipment-consoldation'); ?></strong></td>
                                    <td class="no-padding"><?php echo $status; ?></td>
                                </tr>
                                <tr>
                                    <td class="no-padding" style='vertical-align:bottom;'><strong><?php _e('Total Shipment:', 'wpc-shipment-consoldation'); ?></strong></td>
                                    <td class="no-padding"><?php echo count( $shipments ); ?></td>
                                </tr>
                            </table>
                        </td>
                        <td colspan="2" style="padding:0!important">
                            <table style="width: 100%;">
				
                                <?php 
                                if( function_exists( 'wpcshcon_additional_fees' ) ) {
                                    $wpcshcon_additional_fees = wpcshcon_additional_fees();
                                    if( !empty( $wpcshcon_additional_fees ) ) {
                                        foreach( $wpcshcon_additional_fees as $key => $label ) {
                                            $cost_meta = 'wpcshcon_'.$key.'_cost';
                                            $cost = 0;
                                  
                                            if( function_exists( 'wpcshcon_get_meta_cost' ) ) {
                                                $cost = wpcshcon_get_meta_cost( $shipment_id, $cost_meta );
                                            }
                                            ?>
                                            <tr>
                                                <td class="no-padding" style='vertical-align:bottom; text-align: right;'><strong><?php echo $label.':'; ?></strong></td>
                                                <td class="no-padding" style="text-align: right;"><?php echo $currency_symbol.$cost; ?></td>
                                            </tr>
                                            <?php
                                            
                                        }
                                    }
                                }
                                ?>
                            </table>
                        </td>
                    </tr>
                    </tfoot>
                
            </table>
        <?php
    }
}

add_action( 'wpcinvoice_package_info', 'wpcinv_delivery_data_print_template', 10, 3 );
