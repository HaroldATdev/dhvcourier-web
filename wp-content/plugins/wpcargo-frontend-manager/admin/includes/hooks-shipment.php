<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
function wpcfe_shipment_action_rows( $shipment_id ){
    return apply_filters( 'wpcfe_shipment_action_rows', array(), $shipment_id );
}
function wpcfe_shipment_view_action_row( $rows, $shipment_id ){
    $page_url = get_the_permalink( wpcfe_admin_page() ).'?wpcfe=track&num='.urlencode( get_the_title($shipment_id) );
    $rows[] = '<a class="wpcfe-update-shipment text-primary" href="'. esc_url( $page_url ) .'" title="'. esc_html__('View', 'wpcargo-frontend-manager') .'">'. esc_html__('View', 'wpcargo-frontend-manager') .'</a>';
    return $rows;
}
function wpcfe_shipment_update_action_row( $rows, $shipment_id ){
    if( !can_wpcfe_update_shipment() ) return $rows;
    $page_url = get_the_permalink( wpcfe_admin_page() ).'?wpcfe=update&id='. (int)$shipment_id;
    $rows[] = '<a class="wpcfe-update-shipment text-primary" href="'. esc_url( $page_url ) .'" title="'. esc_html__('Edit', 'wpcargo-frontend-manager') .'">'. esc_html__('Edit', 'wpcargo-frontend-manager') .'</a>';
    return $rows;
}
function wpcfe_shipment_delete_action_row( $rows, $shipment_id ){
    if( !can_wpcfe_delete_shipment() ) return $rows;
    $rows[] = '<a href="#" class="wpcfe-delete-shipment text-danger" data-id="'. (int)$shipment_id .'" title="'. esc_html__('Trash', 'wpcargo-frontend-manager') .'">'. esc_html__('Delete', 'wpcargo-frontend-manager') .'</a>';
    return $rows;
}
// Shipment table Callback
function wpcfe_shipment_sucursal_header_callback(){
    ?><th class="no-space"><?php esc_html_e( 'Sucursal', 'wpcargo-frontend-manager' ); ?></th><?php
}
function wpcfe_shipment_sucursal_data_callback( $shipment_id ){
    $branch_id   = get_post_meta( $shipment_id, 'shipment_branch', true );
    $branch_name = $branch_id && function_exists( 'wpcdm_get_branch_info' ) ? wpcdm_get_branch_info( (int) $branch_id ) : '';
    ?><td class="no-space"><?php echo esc_html( $branch_name ); ?></td><?php
}
function wpcfe_shipper_receiver_shipment_header_callback(){
    $shipper_data   = wpcfe_table_header('shipper');
    $receiver_data  = wpcfe_table_header('receiver');
    ?>
    <th class="no-space"><?php echo apply_filters( 'wpcfe_shipper_table_header_label', $shipper_data['label'] ); ?></th>
    <th class="no-space"><?php esc_html_e( 'Lugar Origen', 'wpcargo-frontend-manager' ); ?></th>
	<th class="no-space"><?php echo apply_filters( 'wpcfe_receiver_table_header_label', $receiver_data['label'] ); ?></th>
    <th class="no-space"><?php esc_html_e( 'Lugar Destino', 'wpcargo-frontend-manager' ); ?></th>
    <?php
}
function wpcfe_shipper_receiver_shipment_data_callback( $shipment_id ){
    $shipper_data   = wpcfe_table_header('shipper');
    $receiver_data  = wpcfe_table_header('receiver');
    $shipper_meta 	= apply_filters( 'wpcfe_shipper_table_cell_data', get_post_meta( $shipment_id, $shipper_data['field_key'], true ), $shipment_id );
	$receiver_meta 	= apply_filters( 'wpcfe_receiver_table_cell_data', get_post_meta( $shipment_id, $receiver_data['field_key'], true ), $shipment_id );
    $lugar_origen   = get_post_meta( $shipment_id, 'lugar_origen', true );
    $lugar_destino  = get_post_meta( $shipment_id, 'lugar_destino', true );
    ?>
    <td class="no-space"><?php echo esc_html( $shipper_meta ); ?></td>
    <td class="no-space"><?php echo esc_html( $lugar_origen ); ?></td>
	<td class="no-space"><?php echo esc_html( $receiver_meta ); ?></td>
    <td class="no-space"><?php echo esc_html( $lugar_destino ); ?></td>
    <?php
}
function wpcfe_shipment_number_header_callback(){
    echo '<th>'.apply_filters( 'wpcfe_shipment_number_label', __('Tracking Number', 'wpcargo-frontend-manager' ) ).'</th>';
}
function wpcfe_shipment_number_data_callback( $shipment_id ){
    $current_user   = wp_get_current_user();
    $seen_metakey   = '_wpcfe_seen_'.$current_user->ID;
    $page_url           = get_the_permalink( wpcfe_admin_page() );
    $shipment_title     = get_the_title($shipment_id);
    
    if( wpcfe_disable_unseen() == false ){
        $is_seen            = get_post_meta( $shipment_id, $seen_metakey, true );
        $badge              = !$is_seen ? sprintf( '<span class="badge badge-pill bg-danger align-top">%s</span>', __('New', 'wpcargo-frontend-manager' ) )  : '';
    }
    $action_rows        = wpcfe_shipment_action_rows( $shipment_id );
    $page_url           = !can_wpcfe_update_shipment() ? $page_url.'?wpcfe=track&num='.$shipment_title : $page_url.'?wpcfe=update&id='. (int)$shipment_id ;
    ob_start();
    ?>
        <td>
            <a href="<?php  echo esc_url( $page_url ); ?>" class="text-primary font-weight-bold"><?php echo esc_html($shipment_title) . $badge; ?></a>
            <?php if( $action_rows ): ?>
                <div class="wpcfe-action-row">
                    <?php echo implode(" | ",$action_rows); ?>
                </div>
            <?php endif; ?>
        </td>
    <?php
    echo ob_get_clean();
}
function wpcfe_shipment_table_header_status(){
    ?><th><?php _e('Status', 'wpcargo-frontend-manager' ); ?></th><?php
}

function wpcfe_status_transition_normalize( $value ){
    $value = is_string( $value ) ? trim( $value ) : '';
    $value = strtolower( remove_accents( $value ) );
    $value = str_replace( array('_', '-'), ' ', $value );
    $value = preg_replace('/\s+/', ' ', $value );
    return trim( $value );
}

function wpcfe_status_transition_is_final( $status ){
    $normalized = wpcfe_status_transition_normalize( $status );
    $final = array(
        'entregado',
        'anulado',
        'devuelto',
        'reprogramado'
    );
    return in_array( $normalized, $final, true );
}

function wpcfe_status_transition_shipment_type( $shipment_id ){
    $type = get_post_meta( $shipment_id, 'tipo_envio', true );
    if ( empty( $type ) ) {
        $type = wpcfe_get_shipment_type( $shipment_id );
    }

    $normalized = wpcfe_status_transition_normalize( $type );

    if ( in_array( $normalized, array( 'puerta a puerta', 'puerta puerta', 'puerta', 'door to door' ), true ) ) {
        return 'puerta_a_puerta';
    }
    if ( in_array( $normalized, array( 'agencia', 'agency' ), true ) ) {
        return 'agencia';
    }
    if ( in_array( $normalized, array( 'almacen', 'almacenamiento', 'warehouse' ), true ) ) {
        return 'almacen';
    }
    return $normalized;
}

function wpcfe_status_transition_options( $shipment_id, $current_status ){
    $shipment_type = wpcfe_status_transition_shipment_type( $shipment_id );
    $status = wpcfe_status_transition_normalize( $current_status );

    if ( wpcfe_status_transition_is_final( $current_status ) ) {
        return array();
    }

    if ( $status === 'pendiente' && $shipment_type === 'puerta_a_puerta' ) {
        return array( 'Recogido', 'Anulado' );
    }

    if ( $status === 'recogido' && $shipment_type === 'puerta_a_puerta' ) {
        return array( 'En espera', 'Anulado' );
    }

    if ( $status === 'en espera' ) {
        return array( 'En ruta', 'Anulado' );
    }

    if ( $status === 'en ruta' ) {
        return array( 'Entregado', 'Anulado', 'Devuelto', 'Reprogramado' );
    }

    return array();
}

function wpcfe_status_transition_is_allowed( $shipment_id, $current_status, $new_status ){
    $options = wpcfe_status_transition_options( $shipment_id, $current_status );
    $normalized_new = wpcfe_status_transition_normalize( $new_status );
    foreach ( $options as $option ) {
        if ( wpcfe_status_transition_normalize( $option ) === $normalized_new ) {
            return true;
        }
    }
    return false;
}

function wpcfe_shipment_table_data_status( $shipment_id  ){
    $status = get_post_meta( $shipment_id, 'wpcargo_status', true );
    $current_user = wp_get_current_user();
    $is_client = $current_user instanceof WP_User && in_array( 'wpcargo_client', (array) $current_user->roles, true );
    $options = $is_client ? [] : wpcfe_status_transition_options( $shipment_id, $status );
    ?>
    <td class="shipment-status <?php echo wpcfe_to_slug( $status ); ?>">
        <?php if ( ! empty( $options ) ): ?>
            <select class="form-control browser-default custom-select wpcfe-status-transition-select"
                data-shipment-id="<?php echo (int) $shipment_id; ?>"
                data-current-status="<?php echo esc_attr( $status ); ?>"
                data-current-class="<?php echo esc_attr( wpcfe_to_slug( $status ) ); ?>">
                <option value=""><?php echo esc_html( $status ); ?></option>
                <?php foreach ( $options as $option ): ?>
                    <option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $option ); ?></option>
                <?php endforeach; ?>
            </select>
        <?php else: ?>
            <?php echo esc_html( $status ); ?>
        <?php endif; ?>
    </td>
    <?php
}
function wpcfe_shipment_table_header_type(){
    ?><th><?php _e('Shipment Type', 'wpcargo-frontend-manager' ); ?></th><?php
}
function wpcfe_shipment_table_data_type( $shipment_id ){
    ?><td class="shipment-type <?php echo wpcfe_to_slug( wpcfe_get_shipment_type( $shipment_id ) ); ?>"><?php echo wpcfe_get_shipment_type( $shipment_id ); ?></td><?php
}
function wpcfe_shipment_table_header_action_print(){
    if( empty( wpcfe_print_options() ) ) return false;
    ?>
    <th class="text-center"><?php _e('Print', 'wpcargo-frontend-manager' ); ?></th>
    <?php
}   
function wpcfe_shipment_table_action_print( $shipment_id ){
    $print_options = wpcfe_print_options();
    if( empty( $print_options ) ) return false;
    ?>
    <td class="text-center print-shipment">
        <div class="wpcfe-print-dropdown dropdown" style="display:inline-block !important;">
            <!--Trigger-->
            <button class="btn btn-default btn-sm dropdown-toggle m-0 py-1 px-2" type="button"
                aria-haspopup="true" aria-expanded="false"><i class="fa fa-print"></i></button>
            <!--Menu-->
            <div class="dropdown-menu dropdown-primary">
                <?php foreach( $print_options as $print_key => $print_label ): ?>
                    <a class="dropdown-item print-<?php echo $print_key; ?> py-1" data-id="<?php echo $shipment_id; ?>" data-type="<?php echo $print_key; ?>" href="#"><?php echo $print_label; ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </td>
    <?php
} 

function wpcfe_status_transition_modal_template(){
    ?>
    <div class="modal fade" id="wpcfeStatusTransitionModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php esc_html_e('Confirmar cambio de estado', 'wpcargo-frontend-manager'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="wpcfe-status-transition-form">
                    <div class="modal-body">
                        <p class="mb-2"><?php esc_html_e('Nuevo estado:', 'wpcargo-frontend-manager'); ?> <strong id="wpcfeStatusTransitionTarget">-</strong></p>
                        <input type="hidden" id="wpcfeStatusTransitionShipmentId" value="">
                        <input type="hidden" id="wpcfeStatusTransitionNewStatus" value="">
                        <div class="form-group mb-0">
                            <label for="wpcfeStatusTransitionRemarks"><?php esc_html_e('Observaciones (opcional)', 'wpcargo-frontend-manager'); ?></label>
                            <textarea id="wpcfeStatusTransitionRemarks" class="form-control" rows="4" placeholder="<?php esc_attr_e('Ingrese observaciones', 'wpcargo-frontend-manager'); ?>"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php esc_html_e('Cancelar', 'wpcargo-frontend-manager'); ?></button>
                        <button type="submit" class="btn btn-primary"><?php esc_html_e('Confirmar', 'wpcargo-frontend-manager'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
}

function wpcfe_status_transition_update_ajax(){
    global $wpcargo;

    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
    if ( ! wp_verify_nonce( $nonce, 'wpcfe_status_transition_action' ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'wpcargo-frontend-manager' ) ) );
    }

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'wpcargo-frontend-manager' ) ) );
    }

    $shipment_id = isset( $_POST['shipment_id'] ) ? absint( $_POST['shipment_id'] ) : 0;
    $new_status  = isset( $_POST['new_status'] ) ? sanitize_text_field( $_POST['new_status'] ) : '';
    $remarks     = isset( $_POST['remarks'] ) ? sanitize_textarea_field( $_POST['remarks'] ) : '';

    if ( ! $shipment_id || empty( $new_status ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid shipment data.', 'wpcargo-frontend-manager' ) ) );
    }

    $current_status = get_post_meta( $shipment_id, 'wpcargo_status', true );
    if ( ! wpcfe_status_transition_is_allowed( $shipment_id, $current_status, $new_status ) ) {
        wp_send_json_error( array( 'message' => __( 'Transition is not allowed for this shipment.', 'wpcargo-frontend-manager' ) ) );
    }

    if ( wpcfe_status_transition_normalize( $new_status ) === 'entregado' ) {
        wp_send_json_error( array( 'message' => __( 'Delivered status must be completed through POD signature.', 'wpcargo-frontend-manager' ) ) );
    }

    $new_status = trim( $new_status );

    update_post_meta( $shipment_id, 'wpcargo_status', $new_status );
    if ( function_exists( 'wpcfe_save_report' ) ) {
        wpcfe_save_report( $shipment_id, $current_status, $new_status );
    }

    $history = get_post_meta( $shipment_id, 'wpcargo_shipments_update', true );
    $history = $history ? maybe_unserialize( $history ) : array();
    if ( ! is_array( $history ) ) {
        $history = array();
    }

    $user_id = get_current_user_id();
    $full_name = $wpcargo->user_fullname( $user_id );
    $location = get_post_meta( $shipment_id, 'location', true );

    $history_record = array(
        'date' => current_time( 'Y-m-d' ),
        'time' => current_time( 'H:i' ),
        'location' => $location,
        'status' => $new_status,
        'updated-name' => $full_name,
        'remarks' => $remarks,
    );

    $history[] = $history_record;
    update_post_meta( $shipment_id, 'wpcargo_shipments_update', $history );

    if ( wpcfe_status_transition_normalize( $new_status ) !== wpcfe_status_transition_normalize( $current_status ) ) {
        wpcargo_send_email_notificatio( $shipment_id, $new_status );
        do_action( 'wpcargo_extra_send_email_notification', $shipment_id, $new_status );
        do_action( 'wpc_add_sms_shipment_history', $shipment_id );
    }

    $next_options = wpcfe_status_transition_options( $shipment_id, $new_status );

    wp_send_json_success( array(
        'new_status' => $new_status,
        'new_class' => wpcfe_to_slug( $new_status ),
        'options' => $next_options,
        'is_final' => wpcfe_status_transition_is_final( $new_status ) || empty( $next_options ),
        'message' => __( 'Shipment status updated successfully.', 'wpcargo-frontend-manager' ),
    ) );
}
function wpcfe_seen_shipment_callback(){
    global $post;
    if( !function_exists( 'wpcfe_admin_page' ) || !$post ){
        return false;
    }
    if( $post->ID != wpcfe_admin_page() ){
        return false;
    }
    $shipment_id = null;
    if( isset($_GET['wpcfe']) && $_GET['wpcfe'] == 'track' && isset( $_GET['num'] ) && !empty( $_GET['num'] ) ){
        $shipment_id = wpcfe_get_shipment_id( $_GET['num'] );
    }
    if(  isset($_GET['wpcfe']) && $_GET['wpcfe'] == 'update' && isset( $_GET['id'] ) && (int)$_GET['id'] ){
        $shipment_id = (int)$_GET['id'];
    }
    if( $shipment_id && is_user_logged_in() ){
        $seen_metakey   = '_wpcfe_seen_'.get_current_user_id();
        update_post_meta( $shipment_id, $seen_metakey, current_time( 'mysql' ) );
    }
}

// Update shipment hooks

function wpcfe_initialize_table_hooks(){  
    // Shipment table Hook
    add_action( 'wpcfe_shipment_before_tracking_number_header', 'wpcfe_shipment_number_header_callback', 25 );
    add_action( 'wpcfe_shipment_before_tracking_number_data', 'wpcfe_shipment_number_data_callback', 25 );
    // Sucursal Column (after tracking number, before shipper)
    add_action( 'wpcfe_shipment_after_tracking_number_header', 'wpcfe_shipment_sucursal_header_callback', 20 );
    add_action( 'wpcfe_shipment_after_tracking_number_data', 'wpcfe_shipment_sucursal_data_callback', 20 );
    // Shipment Shipper / Receiver Column (with lugar_origen and lugar_destino)
    add_action( 'wpcfe_shipment_after_tracking_number_header', 'wpcfe_shipper_receiver_shipment_header_callback', 25 );
    add_action( 'wpcfe_shipment_after_tracking_number_data', 'wpcfe_shipper_receiver_shipment_data_callback', 25 );
    // Shipment Type Column
    add_action( 'wpcfe_shipment_table_header', 'wpcfe_shipment_table_header_type', 25 ); 
    add_action( 'wpcfe_shipment_table_data', 'wpcfe_shipment_table_data_type', 25 );
    // Shipment Status Column
    add_action( 'wpcfe_shipment_table_header', 'wpcfe_shipment_table_header_status', 25 ); 
    add_action( 'wpcfe_shipment_table_data', 'wpcfe_shipment_table_data_status', 25 );
    // Shipment Print Column
    add_action( 'wpcfe_shipment_table_header_action', 'wpcfe_shipment_table_header_action_print', 25 ); 
    add_action( 'wpcfe_shipment_table_data_action', 'wpcfe_shipment_table_action_print', 25 );
    add_filter( 'wpcfe_shipment_action_rows', 'wpcfe_shipment_view_action_row', 10, 2 );
    add_filter( 'wpcfe_shipment_action_rows', 'wpcfe_shipment_update_action_row', 10, 2 );
    add_filter( 'wpcfe_shipment_action_rows', 'wpcfe_shipment_delete_action_row', 10, 2 );
    add_action( 'wp_head', 'wpcfe_seen_shipment_callback' );
    add_action( 'wp_ajax_wpcfe_status_transition_update', 'wpcfe_status_transition_update_ajax' );
}
add_action( 'plugins_loaded', 'wpcfe_initialize_table_hooks' );