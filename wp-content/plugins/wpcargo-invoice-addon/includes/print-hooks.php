<?php
function wpcinvoice_comapany_info_callback( $shipmentDetails ){
	$options 		= get_option('wpcargo_option_settings');
	$color			= get_option( 'wpcargo_option_settings' );
	$baseColor 		= $color ? $color['wpcargo_base_color'] : '#000';
	if( $options ){
		if( array_key_exists('wpcargo_base_color', $options) ){
			$baseColor = ( $options['wpcargo_base_color'] ) ? $options['wpcargo_base_color'] : $baseColor ;
		}
	}
	$invoice_options    = get_option( 'wpcinvoice_settings' );
	$str_find           = array_keys( wpcinvoice_shortcodes_list() );
	$str_replace        = wpcinvoice_replace_shortcodes_list( $shipmentDetails['shipmentID'] );
	$company_name       = get_bloginfo('name');
	$logo_url           = $shipmentDetails['cargoSettings']['settings_shipment_ship_logo'];
	$company_info 		= wpcinvoice_display_options( $invoice_options, 'company_address' );
	if( empty( $company_info ) ){
		$company_info = wpcinvoice_default_company_addresss();
	}
	$header_details = $logo_url ? '<img style="width: 70%;" src="'.$logo_url.'"/>' : '<h3 style="color:'.$baseColor.';font-size: 48px !important; font-weight: 900;" >'.$company_name.'</h3>' ;
	?>
	<td class="no-padding" width="35%" align = "left">
		<table style="width: 100%">
	        <tr>
	            <td  class="no-padding" colspan="2" valign="top" align="left"><?php echo $header_details; ?></td>
	        </tr>
	        <tr>
	            <td  class="no-padding" colspan="2" valign="top" align = "left">
	                <?php echo str_replace( $str_find, $str_replace, $company_info ); ?>
	            </td>
	        </tr>
	    </table>                 
    </td>
	<?php
}
function wpcinvoice_invoicing_info_callback( $shipmentDetails ){
	global $wpcargo;
	$invoice_options    = get_option( 'wpcinvoice_settings' );
	$str_find           = array_keys( wpcinvoice_shortcodes_list() );
	$str_replace        = wpcinvoice_replace_shortcodes_list( $shipmentDetails['shipmentID'] );
	// $company_invoice	= wpcinvoice_display_options( $invoice_options, 'company_invoice' );
	$company_invoice = wpcinvoice_default_company_invoice();
	
	?>
	<td class="no-padding"  width="30%">&nbsp;</td>
	<td  class="no-padding"  width="35%" style = "border-bottom: 1px solid #cecece">	
		<table style="width: 100%">
	        <tr>
	            <td  class="no-padding" colspan="2" valign="top" align="right">
					<img id="frontend-label-barcode" class="label-barcode" style="width:100% !important; text-align:right !important; padding:10px 0 !important;" src="<?php echo $wpcargo->barcode_url( $shipmentDetails['shipmentID'] ); ?>">
				</td>
	        </tr>
	        <tr>
	            <td  class="no-padding" colspan="2" valign="top" align = "center">
					<p style = "text-align:center !important;"><?php echo str_replace( $str_find, $str_replace, $company_invoice ); ?></p>
	            </td>
	        </tr>
	    </table>
	</td>
	<?php	
}

//Assigned client and invoice details information
function wpcinv_invoice_label( $shipmentDetails ){
	?>
	<tr>
		<td class="no-padding" colspan = "2" style = "background-color:#cecece;" align = "center">
			<p style = "color:#000; text-align:center; padding: 10px; font-size: 18px;"><?php echo wpcinvoice_default_label_invoice(); ?></p>
		</td>
	</tr>
	<?php
	
}

function wpcinv_assigned_client_section( $shipmentDetails ){
	$assigned_client	= get_post_meta( $shipmentDetails['shipmentID'], 'registered_shipper', true );
	$firstname			= get_user_meta( $assigned_client, 'billing_first_name', true );
	$lastname			= get_user_meta( $assigned_client, 'billing_last_name', true );
	$address1			= get_user_meta( $assigned_client, 'billing_address_1', true );
	$address2			= get_user_meta( $assigned_client, 'billing_address_2', true );
	$city				= get_user_meta( $assigned_client, 'billing_city', true );
	$billing_postcode	= get_user_meta( $assigned_client, 'billing_postcode', true );
	$billing_state		= get_user_meta( $assigned_client, 'billing_state', true );
	$billing_country	= get_user_meta( $assigned_client, 'billing_country', true );


	//Concatenated Details
	$username		= $firstname. ' ' .$lastname;
	$addressline	= $address1. ' '.$address2;

	?>
		<td align = "center" width = "50%" class="no-padding">
			<table style="width: 100%">
				<tr>
					<td  class="no-padding" colspan="2" valign="top" align="center">
						<p><strong><?php echo $username; ?></strong></p>
						<p><?php echo $addressline; ?></p>
						<p><?php echo $city; ?> <?php echo $billing_postcode; ?></p>
						<p><?php echo $billing_state; ?></p>
						<p><?php echo $billing_country; ?></p>	
					</td>
				</tr>
			</table>	
		</td>
	<?php
	
}

// Invoice details
function wpcinv_invoice_details_section( $shipmentDetails ){
	if( function_exists('wpcsr_get_shipment_order') ){
		$order_id   = wpcsr_get_shipment_order( $shipmentDetails['shipmentID'] ) ? wpcsr_get_shipment_order( $shipmentDetails['shipmentID'] ) : '000000';
	}else{
		$order_id	= '000000';
	}
	
	$invoice_id     = get_post_meta( $shipmentDetails['shipmentID'], '__wpcinvoice_id', true ) ? get_post_meta( $shipmentDetails['shipmentID'], '__wpcinvoice_id', true ) : '000000';
    $invoice_number = $invoice_id ? get_the_title( $invoice_id ) : '000000';
	$date_created	= get_the_date( 'm/d/Y', $shipmentDetails['shipmentID'] );
	?>
	<td align = "center" width = "50%" class="no-padding" style = "border:1px solid #000; margin:10px; padding:10px !important;">
		<table style="width: 100%">
			<tr style = "padding:0 5px !important;">
				<td class="no-padding"><?php esc_html_e( 'Date:', 'wpcargo-invoice' ); ?></td>
				<td class="no-padding"><?php echo $date_created; ?></td>
			</tr>
			<tr style = "padding:0 5px !important;">
				<td class="no-padding"><?php esc_html_e( 'Invoice No.:', 'wpcargo-invoice' ); ?></td>
				<td class="no-padding"><?php echo $invoice_number; ?></td>
			</tr>
			<tr style = "padding:0 5px !important;">
				<td class="no-padding"><?php esc_html_e( 'Order No.:', 'wpcargo-invoice' ); ?></td>
				<td class="no-padding"><?php echo $order_id; ?></td>
			</tr>
			<tr style = "padding:0 5px !important;">
				<td class="no-padding"><?php esc_html_e( 'Waybill No.:', 'wpcargo-invoice' ); ?></td>
				<td class="no-padding"><?php echo get_the_title( $shipmentDetails['shipmentID'] ); ?></td>
			</tr>
		</table>
		
	</td>
	<?php
}

function wpcinvoice_shipper_info_callback( $shipmentDetails ){
	$invoice_options    = get_option( 'wpcinvoice_settings' );
	$str_find           = array_keys( wpcinvoice_shortcodes_list() );
	$str_replace        = wpcinvoice_replace_shortcodes_list( $shipmentDetails['shipmentID'] );
	$shipper_invoice	= wpcinvoice_display_options( $invoice_options, 'shipper_info' );
	if( empty( $shipper_invoice ) ){
		$shipper_invoice = wpcinvoice_default_shipper_invoice();
	}
	?>
	<td class="no-padding" style = "border-top: 1px solid #cecece;">
        <h3 class="section-header-1"><?php echo apply_filters( 'wpcinvoice_shipper_header', __('SHIPPER INFORMATION', 'wpcargo-invoice' ) ); ?></h3>
        <?php echo str_replace( $str_find, $str_replace, $shipper_invoice ); ?>
    </td>
	<?php
	
}
function wpcinvoice_receiver_info_callback( $shipmentDetails ){
	$invoice_options    = get_option( 'wpcinvoice_settings' );
	$str_find           = array_keys( wpcinvoice_shortcodes_list() );
	$str_replace        = wpcinvoice_replace_shortcodes_list( $shipmentDetails['shipmentID'] );
	$receiver_invoice	= wpcinvoice_display_options( $invoice_options, 'receiver_info' );
	if( empty( $receiver_invoice ) ){
		$receiver_invoice = wpcinvoice_default_receiver_invoice();
	}
	?>
	<td class="no-padding" style = "border-top: 1px solid #cecece;">
        <h3 class="section-header-1"><?php echo apply_filters( 'wpcinvoice_receiver_header', __('RECEIVER INFORMATION', 'wpcargo-invoice' ) ); ?></h3>
        <?php echo str_replace( $str_find, $str_replace, $receiver_invoice ); ?>
    </td>
	<?php
	
}
function wpcinvoice_package_info_callback( $shipmentDetails ){
	$shipment_type = wpcfe_get_shipment_type( $shipmentDetails['shipmentID'] );
	$wpcinvoice_package_fields = wpcinvoice_package_fields();
	$package_details = wpcargo_get_package_data( $shipmentDetails['shipmentID'] );
	// remove Shipping Cost and Cost columns from package fields if shipment type is "Shipping Rate"
	if( $shipment_type == 'Shipping Rate' ) {
		unset($wpcinvoice_package_fields['unit-amount']);
		unset($wpcinvoice_package_fields['unit-price']);
	}
	// empty package data if shipment type is "Delivery"
	if( $shipment_type == 'Delivery' ) {
		$package_details = array();
	}
	if( !empty( $package_details ) ):
		?>
		<style>
			.wpcargo-table-bordered{
				border-collapse:collapse !important;
			}
			.wpcargo-table-bordered th{
				background-color:#f3f3f3 !important;
				color: #000 !important;
			}
			.wpcargo-table-bordered th, .wpcargo-table-bordered td{
				border:1px solid #000 !important;
			}
		</style>
	    <h3 class="section-header-1"><?php echo apply_filters( 'wpcinvoice_package_header', __('PACKAGE DETAILS', 'wpcargo-invoice' ) ); ?></h3>
	    <table class="table wpcargo-table wpcargo-table-bordered" style="width:100%; padding-top:10px;">
	        <thead>
	            <tr>
	                <?php foreach ( $wpcinvoice_package_fields as $key => $value): ?>
	                    <?php 
	                    if( 
	                        (in_array( $key, wpcargo_package_dim_meta() ) && !wpcargo_package_settings()->dim_unit_enable)
                        ){ continue; }
	                    ?>
	                    <th align="center"><?php echo $value['label']; ?></th>
	                <?php endforeach; ?>
	            </tr>
	        </thead>
	        <tbody>
	            <?php if(!empty( $package_details )): ?>
	                <?php foreach ( $package_details as $data_key => $data_value): ?>
	                <tr class="package-row">
	                    <?php foreach ( $wpcinvoice_package_fields as $field_key => $field_value): ?>
	                        <?php 
	                        if( 
	                        	(in_array( $field_key, wpcargo_package_dim_meta() ) && !wpcargo_package_settings()->dim_unit_enable)
	                        ){ continue; }
	                        ?>
	                        <td class="package-data <?php echo wpcargo_to_slug( $field_key ); ?>">
	                            <?php 
	                                $package_data = array_key_exists( $field_key, $data_value ) ? $data_value[$field_key] : '' ;
	                                if( is_array( $package_data ) ){
	                                	$package_data = implode(',', $package_data );
	                                }elseif( in_array( $field_key , array( 'wpc-pm-value', wpcinvoice_unit_price_key(), wpcinvoice_unit_amount_key()) ) ){
	                                	// $package_data = wpcinvoice_format_value($package_data);
										$package_data;
	                                }
	                                echo $package_data; 
	                            ?>

	                        </td>
	                    <?php endforeach; ?>
	                </tr>
	                <?php endforeach; ?>
	                <?php do_action( 'wpcinvoice_after_package_row', $shipmentDetails ); ?>
	            <?php else: ?>
	                <tr>
	                    <td class="empty-data" colspan="<?php echo !wpcargo_package_settings()->dim_unit_enable ? count( $wpcinvoice_package_fields ) - count( wpcargo_package_dim_meta() ) : count( $wpcinvoice_package_fields ) ; ?>">
	                        <i><?php esc_html_e( 'Data empty', 'wpcargo' ); ?>.</i>
	                    </td>
	                </tr>
	            <?php endif; ?>
	        </tbody>
	    </table>
	    <?php
	endif;
}
function wpcinvoice_comments_info_callback( $shipmentDetails ){
	$invoice_options    = get_option( 'wpcinvoice_settings' );   
	$comment_invoice	= wpcinvoice_display_options( $invoice_options, 'comment' );
	if( empty( $comment_invoice ) ){
		$comment_invoice = wpcinvoice_default_comment_invoice();
	} 
	?>
	<td style="padding:0;vertical-align: top;">	
		<h3 class="section-header-1"><?php echo apply_filters( 'wpcinvoice_comment_header', __('COMMENTS', 'wpcargo-invoice' ) ); ?></h3>
		<?php echo $comment_invoice; ?>
	</td>
	<?php
}
function wpcinvoice_total_info_callback( $shipmentDetails ){
	$invoice_options  = get_option( 'wpcinvoice_settings' );
	$shipment_id		  = $shipmentDetails['shipmentID'];
	$shipment_type	  =  wpcfe_get_shipment_type( $shipment_id );
	$order_id 			  = wpcinvoice_get_invoice_order( $shipment_id );
	$str_find         = array_keys( wpcinvoice_shortcodes_list() );
	$str_replace      = wpcinvoice_replace_shortcodes_list( $shipment_id );
	$total_info 		  = wpcinvoice_get_total_value( $shipment_id ); 
	$thankyou_invoice	= wpcinvoice_display_options( $invoice_options, 'thankyou_message' );
	$subtotal 			  = $order_id ? wc_price( wpcinvoice_get_order_data( $order_id )->subtotal ) : wpcinvoice_format_value( (float)$total_info['sub_total'], true );
	$total_tax 			  = $order_id ? wc_price( wpcinvoice_get_order_data( $order_id )->total_tax ) : wpcinvoice_format_value( (float)$total_info['tax'], true );
	$total 				    = $order_id ? wc_price( wpcinvoice_get_order_data( $order_id )->total ) : wpcinvoice_format_value( (float)$total_info['total'], true );
	if( empty( $thankyou_invoice ) ){
		$thankyou_invoice = wpcinvoice_default_thankyou_invoice();
	}
	// change labels based on shipment types
	$subtotal_label = apply_filters( 'wpcinvoice_subtotal_label', __('Subtotal', 'wpcargo-invoice' ) );
	if( $shipment_type == 'Shipping Rate' ) {
		$subtotal_label = wpcinvoice_pdf_shipping_cost_label();
	} elseif ( $shipment_type == 'Delivery' ) {
		$subtotal_label = wpcinvoice_pdf_delivery_charge_label();
	}
	$__net 		= (float)$total_info['sub_total'] + (float)$total_info['tax'];
	// wpcargo_option_settings[wpcargo_tax]
	$tax_option = get_option('wpcargo_option_settings');
	$__tax 		= (float)$tax_option['wpcargo_tax'];
	$totalNoTax	= ( array_sum($total_info ) ) - ( (float)$total_info['tax'] + (float)$total_info['total'] );
	?>
	<thead align = "center" style = "background-color:#cecece;">
		<tr style = "padding:0 5px !important;">
			<th><?php echo apply_filters( 'charge_description', __( 'Charge Description', 'wpcargo-invoice') ); ?></th>
			<th><?php echo apply_filters( 'gross_charges', __( 'Gross Charge', 'wpcargo-invoice') ); ?></th>
			<th><?php echo apply_filters( '__tax', __( 'Tax (%)', 'wpcargo-invoice') ); ?></th>
			<th><?php echo apply_filters( 'tax', __( 'Tax', 'wpcargo-invoice') ); ?></th>
			<th><?php echo apply_filters( 'net_charges', __( 'Net Charge', 'wpcargo-invoice') ); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr align = "center" style = "padding:0 5px !important;">
			<td class="no-padding" align = "left" style = "padding:0 5px !important;"><?php echo $subtotal_label; ?></td>
			<td class="no-padding"><?php echo wpcinvoice_format_value( ( (float)$total_info['sub_total'] ?: 0.00 ), true ); ?></td>
			<td class="no-padding"><?php echo $__tax; ?></td>
			<td class="no-padding"><?php echo wpcinvoice_format_value( ( (float)$total_info['tax'] ?: 0.00 ), true ); ?></td>

			<td class="no-padding"><?php echo wpcinvoice_format_value( ( (float)$__net ?: 0.00 ), true ); ?></td>
		</tr>
		<?php do_action( 'wpcinvoice_after_invoice_tax', $total_info, $shipment_id, $shipment_type ); ?>
		<tr align = "center" style = "padding:0 5px !important;">
			<td class="no-padding" align = "left" style = "padding:0 5px !important;"><strong style="font-size: 20px;"><?php echo apply_filters( 'wpcinvoice_total_label', __('Total', 'wpcargo-invoice' ) ); ?></strong></td>
			<td class="no-padding"><span style="font-size: 20px;"><?php echo $totalNoTax; ?></span></td>
			<td class="no-padding">&nbsp;</td>
			<td class="no-padding"><span style="font-size: 20px;"><?php echo wpcinvoice_format_value( ( (float)$total_info['tax'] ?: 0.00 ), true ); ?></span></td>
			<td class="no-padding"><span style="font-size: 20px;"><?php echo wpcinvoice_format_value( ( (float)$total_info['total'] ?: 0.00 ), true ); ?></td>
</span>
		</tr>
	</tbody>
	<?php
}

function wpcinv_invoice_footer_callback( $shipmentDetails){ 
	$invoice_options  = get_option( 'wpcinvoice_settings' );
	$shipment_id		  = $shipmentDetails['shipmentID'];
	$str_find         = array_keys( wpcinvoice_shortcodes_list() );
	$str_replace      = wpcinvoice_replace_shortcodes_list( $shipment_id );
	$thankyou_invoice	= wpcinvoice_display_options( $invoice_options, 'thankyou_message' );
	if( empty( $thankyou_invoice ) ){
		$thankyou_invoice = wpcinvoice_default_thankyou_invoice();
	}
	?>
	<td class="no-padding section" >
		<?php echo str_replace( $str_find, $str_replace, $thankyou_invoice ); ?>
	</td>
	<?php
}
function wpcinvoice_print_hooks_callback(){
	add_action( 'wpcinvoice_comapany_info', 'wpcinvoice_comapany_info_callback', 10, 4 );
	add_action( 'wpcinvoice_invoicing_info', 'wpcinvoice_invoicing_info_callback', 10, 4 );
	add_action( 'wpcinvoice_shipper_info', 'wpcinvoice_shipper_info_callback', 10, 4 );
	add_action( 'wpcinvoice_receiver_info', 'wpcinvoice_receiver_info_callback', 10, 4 );
	add_action( 'wpcinvoice_package_info', 'wpcinvoice_package_info_callback', 10, 4 );
	add_action( 'wpcinvoice_comments_info', 'wpcinvoice_comments_info_callback', 10, 4 );
	add_action( 'wpcinvoice_total_info', 'wpcinvoice_total_info_callback', 10, 4 );
	add_action( 'wpcinvoice_before_shipper_info', 'wpcinv_invoice_label', 10,  4 );
	add_action( 'wpcinvoice_assigned_shipper', 'wpcinv_assigned_client_section', 10,  4 );
	add_action( 'wpcinvoice_invoice_details', 'wpcinv_invoice_details_section', 10,  4 );
	add_action( 'wpcinv_invoice_footer', 'wpcinv_invoice_footer_callback', 10,  4 );
	//wpcinv_invoice_footer
	// wpcinvoice_invoice_details
	//wpcinv_assigned_client_section
	//wpcinvoice_before_shipper_info
}
add_action( 'plugins_loaded', 'wpcinvoice_print_hooks_callback' );