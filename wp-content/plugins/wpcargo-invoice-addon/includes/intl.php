<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function wpcinvoice_number_label(){
    return apply_filters( 'wpcinvoice_number_label', __('Invoice Number', 'wpcargo-invoice') );
}
function wpcinvoice_shipment_label(){
    return apply_filters( 'wpcinvoice_shipment_label', __('Shipment Number', 'wpcargo-invoice') );
}
function wpcinvoice_shipment_type_label(){
    return apply_filters( 'wpcinvoice_shipment_type_label', __('Shipment Type', 'wpcargo-invoice') );
}
function wpcinvoice_order_no_label(){
    return apply_filters( 'wpcinvoice_order_no_label', __('Order No.', 'wpcargo-invoice') );
}
function wpcinvoice_status_label(){
    return apply_filters( 'wpcinvoice_status_label', __('Status', 'wpcargo-invoice') );
}
function wpcinvoice_invoice_status_label(){
    return apply_filters( 'wpcinvoice_invoice_status_label', __('Invoice Status', 'wpcargo-invoice') );
}
function wpcinvoice_shipment_id_label(){
    return apply_filters( 'wpcinvoice_shipment_id_label', __('Shipment ID', 'wpcargo-invoice') );
}
function wpcinvoice_activate_license_message(){
    return __('Please activate license for WPCargo Invoice Addon plugin.', 'wpcargo-invoice');
}
function wpcinvoice_invoice_setting_label(){
    return __( 'Invoice Settings', 'wpcargo-invoice' );
}
function wpcinvoice_subtotal_label(){
	$currency = !empty( wpcinvoice_currency() )? '('.wpcinvoice_currency().')' : '';
    return  apply_filters( 'wpcinvoice_subtotal_label', __('Subtotal '.$currency, 'wpcargo-invoice') );
}
function wpcinvoice_tax_label(){
	global $wpcargo;
    return  apply_filters( 'wpcinvoice_subtotal_label', __('Tax ('.$wpcargo->tax.'%)', 'wpcargo-invoice') );
}
function wpcinvoice_total_label(){
	$currency = !empty( wpcinvoice_currency() )? '('.wpcinvoice_currency().')' : '';
    return  apply_filters( 'wpcinvoice_subtotal_label', __('Total '.$currency, 'wpcargo-invoice') );
}
/**
 * integration labels start
 */
function wpcinvoice_shipping_cost_label(){
	$currency = !empty( wpcinvoice_currency() )? '('.wpcinvoice_currency().')' : '';
    return  apply_filters( 'wpcinvoice_shipping_cost_label', __('Shipping Cost '.$currency, 'wpcargo-invoice') );
}
function wpcinvoice_pdf_shipping_cost_label(){
    return  apply_filters( 'wpcinvoice_pdf_shipping_cost_label', __('Shipping Cost:', 'wpcargo-invoice') );
}
function wpcinvoice_delivery_charge_label(){
	$currency = !empty( wpcinvoice_currency() )? '('.wpcinvoice_currency().')' : '';
    return  apply_filters( 'wpcinvoice_delivery_charge_label', __('Delivery Charge '.$currency, 'wpcargo-invoice') );
}
function wpcinvoice_pdf_delivery_charge_label(){
    return  apply_filters( 'wpcinvoice_pdf_delivery_charge_label', __('Delivery Charge:', 'wpcargo-invoice') );
}
function wpcinvoice_multipack_header_shcon_label(){
    return  apply_filters( 'wpcinvoice_multipack_header_shcon_label', __('Consolidation Information', 'wpcargo-invoice') );
}
function wpcinvoice_multipack_header_delivery_data_label(){
    return  apply_filters( 'wpcinvoice_multipack_header_delivery_data_label', __('Delivery Information', 'wpcargo-invoice') );
}
/**
 * integration labels end
 */
function wpcinvoice_unit_price_key(){
    return apply_filters( 'wpcinvoice_unit_price_key', 'unit-price' );
}
function wpcinvoice_unit_amount_key(){
    return apply_filters( 'wpcinvoice_unit_amount_key', 'unit-amount' );
}
function wpcinvoice_no_result_label(){
    return esc_html__( 'No Result Found!', 'wpc-import-export' );
}
function wpcinvoice_export_heading_label(){
    return apply_filters( 'wpcinvoice_export_heading_label', __( 'Export Invoice', 'wpcargo-invoice' ));
}
function wpcinvoice_registered_shipper_label(){
    return apply_filters( 'wpcinvoice_registered_shipper_label', __( 'Registered Shipper', 'wpcargo-invoice' ));
}
function wpcinvoice_pq_tax_label() {
    return apply_filters( 'wpcinvoice_pq_tax_label', __( 'Parcel Quotation Tax', 'wpcargo-invoice' ) );
}
function wpcinvoice_pq_subtotal_label() {
    return apply_filters( 'wpcinvoice_pq_subtotal_label', __( 'Parcel Quotation Subtotal', 'wpcargo-invoice' ) );
}
