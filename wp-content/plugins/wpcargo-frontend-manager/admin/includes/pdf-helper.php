<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
require_once( WPCFE_PATH.'admin/includes/dompdf/autoload.inc.php' );
// require_once( WPCFE_PATH.'admin/includes/dompdf/lib/php-font-lib/src/FontLib/Autoloader.php' );
require_once( WPCFE_PATH.'admin/includes/dompdf/src/Options.php' );
// require_once( WPCFE_PATH.'admin/includes/dompdf/src/Autoloader.php' );

// Dompdf\WPCFE_Autoloader::register();
use Dompdf\Dompdf;
// use Dompdf\WPCFE_Options;
// Function helper
function wpcfe_print_template_path_helper_callback( $template_path, $print_type ){
    global $wpcargo_print_admin, $wpcargo_cf_form_builder;
    if( !file_exists( $template_path ) ){
        $waybill_template = class_exists( 'WPCargo_CF_Form_Builder' ) ? $wpcargo_cf_form_builder->print_label_url_callback( $template_path ) : $wpcargo_print_admin->print_label_template_callback();
        $template_path = $print_type == 'waybill' ? $waybill_template : WPCFE_PATH.'templates/print/'.$print_type.'.php'; 
    }
    

    return $template_path;
}

// Bulk Print AJAX handler
add_action( 'wp_ajax_wpcfe_bulkprint', 'wpcfe_bulkprint_ajax_callback' );
function wpcfe_bulkprint_ajax_callback(){
    global $wpdb, $WPCCF_Fields, $wpcargo;
    $directory    = WPCFE_PATH.'admin/includes/file-container/';
    // Clean directory before adding new file
    foreach( glob($directory.'*.pdf') as $pdf_file){
        unlink($pdf_file);
    }
    $wpcfe_pdf_dpi  = apply_filters( 'wpcfe_pdf_dpi', 160 );
    $shipment_ids_raw = isset( $_POST['selectedShipment'] ) ? $_POST['selectedShipment'] : array();
    // Accept CSV string or array
    if ( is_string( $shipment_ids_raw ) ) {
        $shipment_ids = array_filter( array_map( 'intval', explode( ',', $shipment_ids_raw ) ) );
    } elseif ( is_array( $shipment_ids_raw ) ) {
        $shipment_ids = array_map( 'intval', $shipment_ids_raw );
    } else {
        $shipment_ids = array();
    }
    $print_type     = isset( $_POST['printType'] ) ? sanitize_text_field( $_POST['printType'] ) : 'waybill';
    // Increase resources for bulk generation and render a single PDF containing all shipments
    if ( function_exists( 'set_time_limit' ) ) {
        @set_time_limit(0);
    }
    @ini_set('memory_limit', '768M');

    if ( empty( $shipment_ids ) ){
        wp_send_json_error( array( 'message' => __( 'No shipments selected.', 'wpcargo-frontend-manager' ) ) );
    }

    $waybill_title 	= $print_type.'-'.time();
    $print_paper    = wpcfe_print_paper()[$print_type];
    $log_file = $directory.'wpcfe_bulkprint_error.log';
    // General entry log (PHP error log)
    @error_log( "[wpcfe_bulkprint] called for printType={$print_type}\n" );

    // Register shutdown function to capture fatal errors that cause HTTP 500
    $log_file_shutdown = $log_file;
    register_shutdown_function(function() use ($log_file_shutdown){
        $err = error_get_last();
        if ( $err ) {
            @error_log( "[wpcfe_bulkprint][shutdown] last_error=" . print_r( $err, true ) . "\n", 3, $log_file_shutdown );
        }
    });

    // Attempt to write to plugin log file for diagnostics
    $test_msg = "[wpcfe_bulkprint] entry for shipments_raw=" . ( is_array( $_POST['selectedShipment'] ?? null ) ? implode(',', $_POST['selectedShipment']) : ($_POST['selectedShipment'] ?? '') ) . "\n";
    @file_put_contents( $log_file, $test_msg, FILE_APPEND );

    // If debug_diag requested, try to create a test file and report back JSON
    if ( isset( $_POST['debug_diag'] ) && intval( $_POST['debug_diag'] ) === 1 ) {
        $diag_results = array();
        // can we create plugin directory file?
        $test_file = $directory.'wpcfe_diag_test_'.time().'.txt';
        $w = @file_put_contents( $test_file, "diag test" );
        $diag_results['can_write_file'] = $w ? true : false;
        $diag_results['test_file'] = $w ? WPCFE_URL.'admin/includes/file-container/'.basename($test_file) : '';
        // check if dompdf class exists
        $diag_results['dompdf_exists'] = class_exists('Dompdf\\Dompdf') || class_exists('\\Dompdf\\Dompdf');
        // dump some environment
        $diag_results['memory_limit'] = ini_get('memory_limit');
        $diag_results['max_execution_time'] = ini_get('max_execution_time');
        // raw POST and input
        $diag_results['raw_post'] = $_POST;
        $diag_results['php_sapi'] = php_sapi_name();
        wp_send_json_success( $diag_results );
    }

    try{
        $html = wpcfe_bulkprint_template_path( $shipment_ids, $waybill_title, $print_type );
        @error_log( "[wpcfe_bulkprint] render html bytes=" . strlen( $html ) . " shipments=" . count($shipment_ids) . "\n", 3, $log_file );

        // Dump combined HTML to system temp directory for inspection (should be writable)
        $temp_dir = sys_get_temp_dir();
        $temp_file = $temp_dir . DIRECTORY_SEPARATOR . 'wpcfe_bulk_' . $waybill_title . '.html';
        @file_put_contents( $temp_file, $html );
        @error_log( "[wpcfe_bulkprint] temp_html=" . $temp_file . "\n", 3, $log_file );

        // Log whether Dompdf class is available
        $dompdf_exists = class_exists('Dompdf\\Dompdf') || class_exists('\\Dompdf\\Dompdf');
        @error_log( "[wpcfe_bulkprint] dompdf_exists=" . ($dompdf_exists ? '1' : '0') . "\n", 3, $log_file );

            // If debug_html=1 is provided, return the combined HTML (diagnostic) instead of generating PDF
            if ( isset( $_POST['debug_html'] ) && intval( $_POST['debug_html'] ) === 1 ) {
                // save diagnostic HTML so user can inspect it in browser
                $debug_file = $directory.$waybill_title.'-debug.html';
                @file_put_contents( $debug_file, $html );
                wp_send_json_success( array( 'debug_html' => WPCFE_URL.'admin/includes/file-container/'.$waybill_title.'-debug.html', 'shipments' => $shipment_ids ) );
            }
        @error_log( "[wpcfe_bulkprint] render html bytes=" . strlen( $html ) . " shipments=" . count($shipment_ids) . "\n", 3, $log_file );

        $dompdf = new Dompdf();
        $dompdf->set_option('isRemoteEnabled', true);
        $dompdf->set_option('isHtml5ParserEnabled', true);
        $dompdf->loadHtml( $html );
        $paper_size = isset($print_paper['size']) ? $print_paper['size'] : 'A4';
        $paper_orient = isset($print_paper['orient']) ? $print_paper['orient'] : 'portrait';
        $dompdf->setPaper( $paper_size, $paper_orient );
        $dompdf->render();
        wpcfe_pdf_pagination( $dompdf, $print_type );
        $output = $dompdf->output();
        $data_info = array();
        if ( ! empty( $output ) && file_put_contents( $directory.$waybill_title.'.pdf', $output ) ) {
            $data_info = array(
                'file_url' => WPCFE_URL.'admin/includes/file-container/'.$waybill_title.'.pdf',
                'file_name' => $waybill_title
            );
            wp_send_json_success( $data_info );
        }
        @error_log( "[wpcfe_bulkprint] failed to write combined pdf\n", 3, $log_file );
        wp_send_json_error( array( 'message' => __( 'Failed to write combined PDF file.', 'wpcargo-frontend-manager' ) ) );
    }catch( Exception $e ){
        @error_log( "[wpcfe_bulkprint] Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", 3, $log_file );
        wp_send_json_error( array( 'message' => __( 'Error generating PDF. Revisa el log en el directorio del plugin.', 'wpcargo-frontend-manager' ) ) );
    }
}
function wpcfe_bulkprint_template_path( $shipment_ids, $waybill_title, $print_type ){
    ob_start();
    global $WPCCF_Fields, $wpcargo, $wpcargo_print_admin;
    if( wpcfe_enable_label_multiple_print() && $print_type == 'label' ){
        $print_type         = $print_type.'-packages';
    }
    $custom_template_path   = get_stylesheet_directory() .'/wpcargo/'. $print_type.'.tpl.php';
    $mp_settings            = get_option('wpc_mp_settings');
    $setting_options        = get_option('wpcargo_option_settings');
    $logo                   = '';
    if( !empty( $setting_options['settings_shipment_ship_logo'] ) ){
        $logo 		= '<img style="width: 180px;" id="logo" src="'.$setting_options['settings_shipment_ship_logo'].'">';
    }
    if( get_option('wpcargo_label_header') ){
        $siteInfo = get_option('wpcargo_label_header');
    }else{
        $siteInfo  = $logo;
        $siteInfo .= '<h2 style="margin:0;padding:0;">'.get_bloginfo('name').'</h2>';
        $siteInfo .= '<p style="margin:0;padding:0;font-size: 14px;">'.get_bloginfo('description').'</p>';
        $siteInfo .= '<p style="margin:0;padding:0;font-size: 8px;">'.get_bloginfo('wpurl').'</p>';
    }
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?> <?php echo is_rtl() ? 'dir="rtl"' : '' ; ?>>
        <head>
            <title><?php echo $waybill_title; ?></title>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <?php do_action( 'wpcfe_print_html_head' ); ?>
            <style type="text/css">
                div.copy-section { border: 2px solid #000; margin-bottom: 18px; }
                .copy-section table { border-collapse: collapse; }
                .copy-section table td.align-center{ text-align: center; }
                .copy-section table td { border: 1px solid #000; }
                table tr td{ padding:6px; }
            </style>
        </head>
        <body>
            <?php
            if( !empty( $shipment_ids ) ){
                $counter        = 1;
                $shipment_num   = count( $shipment_ids );
                foreach ( $shipment_ids as $shipment_id ) {
                    $shipmentID             = $shipment_id;
                    // ensure global post context for template functions
                    $post = get_post( $shipmentID );
                    if ( $post ) {
                        setup_postdata( $post );
                    }
                    $packages               = maybe_unserialize( get_post_meta( $shipmentID,'wpc-multiple-package', TRUE) );
                    $shipmentDetails 	= array(
                        'shipmentID'	=> $shipment_id,
                        'barcode'		=> $wpcargo->barcode( $shipment_id ),
                        'packageSettings'	=> $mp_settings,
                        'cargoSettings'	=> $setting_options,
                        'packages'		=> $packages,
                        'logo'			=> $logo,
                        'siteInfo'		=> $siteInfo
                    );
                    $template_path = wpcfe_print_template_path_helper_callback( $custom_template_path, $print_type );
                    $template_path = apply_filters( 'wpcfe_print_template_path_'.wpcfe_to_slug($print_type), $template_path, $shipment_id );
                    include( $template_path );
                    do_action( 'wpcfe_after_bulkprint_template', $counter, $shipment_num, $print_type );
                    if ( $post ) {
                        wp_reset_postdata();
                    }
                    $counter++;
                }   
            }
            ?>
        </body>
    </html>
    <?php
    $output = ob_get_clean();
	return $output;
}
// Print Shipment Functionality - Print Button with dropdown
add_action( 'wp_ajax_wpcfe_print_shipment', 'wpcfe_print_shipment_ajax_callback' );
add_action( 'wp_ajax_nopriv_wpcfe_print_shipment', 'wpcfe_print_shipment_ajax_callback' );
function wpcfe_print_shipment_ajax_callback(){
    global $wpdb, $WPCCF_Fields, $wpcargo;
    // Variables
    $wpcfe_pdf_dpi  = apply_filters( 'wpcfe_pdf_dpi', 160 );
    $shipment_id    = $_POST['shipmentID'];
    $print_type     = $_POST['printType'];

    $print_paper    = wpcfe_print_paper()[$print_type];
    $directory      = WPCFE_PATH.'admin/includes/file-container/';
    // Clean directory before adding new file
    foreach( glob($directory.'*.pdf') as $pdf_file){
  //      unlink($pdf_file);
    }
    $waybill_title  = $print_type.'-'.preg_replace("/[^A-Za-z0-9 ]/", '', get_the_title($shipment_id) ).'-'.time();

    // instantiate and use the dompdf class
    // $options 		= new WPCFE_Options();
    // $options->setDpi( $wpcfe_pdf_dpi );
    
    $dompdf 		= new Dompdf( );
    $dompdf->set_option('isRemoteEnabled', true);
    $dompdf->loadHtml( wpcfe_print_shipment_template_path( $shipment_id, $waybill_title, $print_type ) );
    
    // (Optional) Setup the paper size and orientation
    $dompdf->setPaper( $print_paper['size'], $print_paper['orient']);

    // Render the HTML as PDF
    $dompdf->render();
    wpcfe_pdf_pagination( $dompdf, $print_type );
    // // Output the generated PDF to Browser
    $output = $dompdf->output();

 $data_info = array();
    if( file_put_contents( $directory.$waybill_title.'.pdf', $output) ){
        $data_info = array(
            'file_url' => WPCFE_URL.'admin/includes/file-container/'.$waybill_title.'.pdf',
            'file_name' => $waybill_title
        );  
    }
 
    echo json_encode( $data_info );
    wp_die();
}




// Template Path
function wpcfe_print_shipment_template_path( $shipment_id, $waybill_title, $print_type ){
    ob_start();
    global $WPCCF_Fields, $wpcargo;
    $shipmentID             = $shipment_id;
    $packages               = maybe_unserialize( get_post_meta( $shipmentID,'wpc-multiple-package', TRUE) );
    if( !empty( $packages ) && wpcfe_enable_label_multiple_print() && $print_type == 'label' ){
        $print_type         = $print_type.'-packages';
    }
    
    $custom_template_path   = get_stylesheet_directory() .'/wpcargo/'. $print_type.'.tpl.php';
    $mp_settings            = get_option('wpc_mp_settings');
    $setting_options        = get_option('wpcargo_option_settings');
    $logo                   = '';
    if( !empty( $setting_options['settings_shipment_ship_logo'] ) ){
        $logo 		= '<img style="width: 180px;" id="logo" src="'.$setting_options['settings_shipment_ship_logo'].'">';
    }
    if( get_option('wpcargo_label_header') ){
        $siteInfo = get_option('wpcargo_label_header');
    }else{
        $siteInfo  = $logo;
        $siteInfo .= '<p style="margin:0;padding:0;font-size: 18px;">'.get_bloginfo('name').'</p>';
        $siteInfo .= '<p style="margin:0;padding:0;font-size: 14px;">'.get_bloginfo('description').'</p>';
        $siteInfo .= '<p style="margin:0;padding:0;font-size: 8px;">'.get_bloginfo('wpurl').'</p>';
    }
    $shipmentDetails 	= array(
        'shipmentID'	=> $shipment_id,
        'barcode'		=> $wpcargo->barcode( $shipment_id ),
        'packageSettings'	=> $mp_settings,
        'cargoSettings'	=> $setting_options,
        'packages'		=> $packages,
        'logo'			=> $logo,
        'siteInfo'		=> $siteInfo
    );
  ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?> <?php echo is_rtl() ? 'dir="rtl"' : '' ; ?>>
        <head>
            <title><?php echo $waybill_title; ?></title>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <?php do_action( 'wpcfe_print_html_head' ); ?>
            <style type="text/css">
                div.copy-section { border: 2px solid #000; margin-bottom: 18px; }
                .copy-section table { border-collapse: collapse; }
                .copy-section table td.align-center{ text-align: center; }
                .copy-section table td { border: 1px solid #000; }
                table tr td{ padding:6px; }
            </style>
        </head>
        <body>
            <?php
            
            
            $template_path = wpcfe_print_template_path_helper_callback( $custom_template_path, $print_type );
            $template_path = apply_filters( 'wpcfe_print_template_path_'.wpcfe_to_slug($print_type), $template_path, $shipment_id );
           


        
       include_once( $template_path );
            
       
            ?>
        </body>
    </html>
    <?php
    $output = ob_get_clean();    
	return $output;
}