<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
class WPC_Branch_Manager{	
	function __construct(){
		add_action( 'admin_menu', array( $this, 'manage_branch_menu_page_callback' ) );
		add_action( 'admin_init', array( $this, 'admin_initialize_callback' ) );
		//** Metabox
		add_action( 'add_meta_boxes', array( $this, 'branch_meta_boxes' ) );
		//** Save metabox
		add_action('save_post', array( $this, 'save_branch_history' ) );

		add_action('admin_footer', array( $this, 'footer_hook_callback' ) ) ;
		//** AJAX Handlers
		add_action( 'wp_ajax_add_branch', array( $this, 'add_branch_callback' ) ) ;
		add_action( 'wp_ajax_update_branch', array( $this, 'update_branch_callback' ) ) ;
		add_action( 'wp_ajax_delete_branch', array( $this, 'delete_branch_callback' ) );
		add_action( 'wp_ajax_get_branch', array( $this, 'get_branch_callback' ) );	
		add_action( 'wp_ajax_wpcbranch_access', array( $this, 'wpcbranch_access_callback' ) );
		add_action( 'wp_ajax_transfer_branch', array( $this, 'transfer_branch_callback' ) );
		add_action( 'wp_ajax_display_branch_manager', array( $this, 'display_branch_manager' ) );
		add_action( 'wp_ajax_nopriv_display_branch_manager', array( $this, 'display_branch_manager' ) );

		//** Hook Branch to Export Form Field
		add_action('wpc_export_form_field', array( $this, 'ie_branch_field' ) );
		add_filter('wpc_export_form_field_metakey', array( $this, 'ie_branch_field_metakey' ) );
		add_filter('ie_registered_fields', array( $this, 'ie_option_fields' ) );
		add_filter('wpc_export_modify_meta_value', array( $this, 'export_modify_meta_value' ), 10, 3 );
	}
	function display_branch_manager(){
		$branch = $_POST['selectedBranch'];
		$get_branch = wpcdm_get_branch( $branch );

		$branch_managers = maybe_unserialize( $get_branch['branch_manager'] ) ?? array();
		?>
		<option value=""><?php esc_html_e('-- Seleccionar Colaborador --', 'wpcargo-branches'); ?></option>
		<?php
		if(empty(wpcargo_get_branch_managers())){
		    die();
		}
		foreach( wpcargo_get_branch_managers() as $branch_managerID => $branch_manager_name ){
			if( in_array( $branch_managerID, $branch_managers ) ){
				?>
				<option value="<?php echo $branch_managerID; ?>"><?php echo $branch_manager_name; ?></option>
				<?php
			}
	
		}
		die();
	}
	/**
	 * Register meta box(es).
	 */
	function branch_meta_boxes() {
	    add_meta_box( 
	    	'assigned-branch-id', 
	    	wpcdm_shipment_branch_label(), 
	    	array( $this, 'branch_display_callback' ), 
	    	'wpcargo_shipment',
	    	'side',
	    	'high'
	    );
	    add_meta_box( 
	    	'branch-history-id', 
	    	wpcdm_branch_history_label(), 
	    	array( $this, 'branch_history_display_callback' ), 
	    	'wpcargo_shipment'
	    );
	}	
	/**
	 * Meta box display callback.
	 *
	 * @param WP_Post $post Current post object.
	 */
	function branch_display_callback( $post ) {
		if( empty( get_option( WPC_BRANCHES_BASENAME ) ) ){
			?>
			<div class="notice notice-error" style="margin-top:36px;">			
				<p><?php echo wpcdm_activate_license_message(); ?></p>
			</div>			
			<?php			
			delete_option( WPC_BRANCHES_BASENAME );
			return false;
		}
		$shipment_branch = get_post_meta( $post->ID, 'shipment_branch', true );
		?>
		<div class="misc-pub-section misc-pub-post-assigned-branch">
			<?php
			if( empty( can_wpcfe_assign_branch_manager() ) ){
				?><p><strong><?php echo wpcdm_unable_assign_branch_label(); ?></strong></p><?php
			}else{
				?>
				<p><strong><label for="wpc-user-branch"><?php echo wpcdm_assign_branch_label(); ?></label></strong></p>
				<?php
					$all_branch = wpcbm_get_all_branch( -1 );
					if( !empty( $all_branch ) ){
						?>
						<select id="wpc-user-branch" name="shipment_branch">
							<option value=""><?php echo wpcdm_select_branch_label(); ?></option>
							<?php foreach ( $all_branch as $branch ): ?>
								<option value="<?php echo $branch->id; ?>" <?php selected( $shipment_branch, $branch->id ); ?>><?php echo $branch->name; ?></option>
							<?php endforeach; ?>
						</select>
					<?php
				}
			}
			?>
		</div>
		<?php
	}
	function branch_history_display_callback( $post ){
		if( empty( get_option( WPC_BRANCHES_BASENAME ) ) ){
			?>
			<div class="notice notice-error" style="margin-top:36px;">			
				<p><?php echo wpcdm_activate_license_message(); ?></p>
			</div>			
			<?php			
			delete_option( WPC_BRANCHES_BASENAME );
			return false;
		}
		$shipment_branch_history = maybe_unserialize( get_post_meta( $post->ID, 'shipment_branch_history', true ) );
		?>
		<div class="branch-history">
			<?php 
			if( !empty( $shipment_branch_history ) ){
				?><ul id="branch-history-list" ><?php
					foreach ($shipment_branch_history as $history ) {
						$displayname = wpcdm_get_user_displayname( $history['updated-by'] );
						$from 		 = ( wpcdm_get_branch_info( $history['from'] ) ) ? wpcdm_get_branch_info( $history['from'] ) : '--' ;
						$to 		 = ( wpcdm_get_branch_info( $history['to'] ) ) ? wpcdm_get_branch_info( $history['to'] ) : '--' ;
						?><li><?php echo '<strong>'.wpcdm_updated_by_label().'</strong>'.$displayname.' <strong>'.wpcdm_from_label().'</strong> '.$from.' <strong>'.wpcdm_to_label().'</strong> '.$to.' <strong>'.wpcdm_on_label().'</strong> '.$history['time']; ?></li><?php
					}
				?></ul><?php
			}
			?>
		</div>
		<?php
	}
	function save_branch_history( $post_id ){
		// Add nonce for security and authentication.
        $nonce_name   = isset( $_POST['wpc_metabox_nonce'] ) ? $_POST['wpc_metabox_nonce'] : '';
        $nonce_action = 'wpc_metabox_action';
        // Check if nonce is set.
        if ( ! isset( $nonce_name ) ) {
            return;
        }
        // Check if nonce is valid.
        if ( ! wp_verify_nonce( $nonce_name, $nonce_action ) ) {
            return;
        }
        // Check if user has permissions to save data.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        // Check if not an autosave.
        if ( wp_is_post_autosave( $post_id ) ) {
            return;
        }
        $current_user 				= wp_get_current_user();
        $current_branch 			= get_post_meta( $post_id, 'shipment_branch', true );
        $shipment_branch_history 	= maybe_unserialize( get_post_meta( $post_id, 'shipment_branch_history', true ) );
        // Make sure that it is set.
		$new_history = array(
			'time' 			=> current_time( 'mysql' ),
			'updated-by' 	=> $current_user->ID,
			'from'    		=> $current_branch,
			'to'			=> $_POST['shipment_branch']
		);
        if( empty($shipment_branch_history) ){
        	$shipment_branch_history = array();
        }
        
        if( $current_branch != $_POST['shipment_branch'] ){  
        	array_push($shipment_branch_history, $new_history);			
			update_post_meta($post_id, 'shipment_branch_history', maybe_serialize( $shipment_branch_history ) );
    	}elseif( !empty( $_POST['shipment_branch'] ) ){
    		update_post_meta($post_id, 'shipment_branch_history', maybe_serialize( array( $new_history ) ) );
    	}	
	}
	/**
	 * Register a Manage Branch page.
	 */
	function manage_branch_menu_page_callback() {
	    add_menu_page(
	        wpcdm_manage_branches_label(),
	        wpcdm_manage_branches_label(),
	        'manage_options',        
	        'manage_branch',
	        array( $this, 'manage_branch_template'),
	        WPC_BRANCHES_URL.'/admin/assets/images/placeholder.png',
	        6
	    );
	    add_submenu_page( 
			'manage_branch',   
			wpcdm_transfer_shipment_label(),
			wpcdm_transfer_shipment_label(),
			'manage_options',
			'admin.php?page=branch_transfer'
		);
		add_submenu_page(
			'branch_transfer',
			wpcdm_transfer_shipment_label(),
			wpcdm_transfer_shipment_label(),
			'manage_options',
			'branch_transfer',
			array( $this, 'branch_transfer_page_callback' ) 
		);
	}
	function admin_initialize_callback(){
		register_setting( 'wpc_branch_manager_options_group', 'wpcdm_assign_branch_role' ); 
	}
	function branch_transfer_page_callback(){
		?>
		<h1 class="wp-heading-inline"><?php echo wpcdm_branch_transfer_label(); ?> </h1>	
		<hr class="wp-header-end">
		<?php
		if( empty( get_option( WPC_BRANCHES_BASENAME ) ) ){
			?>
			<div class="notice notice-error" style="margin-top:36px;">			
				<p><?php echo wpcdm_activate_license_message(); ?></p>
			</div>			
			<?php			
			delete_option( WPC_BRANCHES_BASENAME );
			return false;
		}
		$can_assign_branch = can_wpcfe_assign_branch_manager();  //can_wpcfe_assign_branch_manager
		if( !$can_assign_branch ){
			?><p><?php echo wpcdm_page_permission_message(); ?></p><?php
			return false;
		}
		$all_branch = wpcbm_get_all_branch( -1 );
		require_once( WPC_BRANCHES_PATH .'/admin/templates/transfer.tpl.php');
	}
	function manage_branch_template(){
		global $wpcargo;
		if( empty( get_option(WPC_BRANCHES_BASENAME) ) ){
			?>
			<div class="notice notice-error" style="margin-top:36px;">			
				<p><?php echo wpcdm_activate_license_message(); ?></p>
			</div>			
			<?php			
			delete_option( WPC_BRANCHES_BASENAME );
			return false;
		}
		$all_branches = wpcbm_get_all_branch( -1 );
		?>
		<h1 class="wp-heading-inline"><?php echo wpcdm_manage_branch_label(); ?> <a id="add-branch" href="#" class="page-title-action button button-secondary"><?php echo wpcdm_add_new_branch_label(); ?></a></h1>	
		<hr class="wp-header-end">			
		<?php
		require_once( WPC_BRANCHES_PATH .'/admin/templates/branch-restriction.tpl.php');
		require_once( WPC_BRANCHES_PATH .'/admin/templates/manage-branch.tpl.php');
	}
	function footer_hook_callback(){
		if( empty( get_option(WPC_BRANCHES_BASENAME) ) ){
			?>
			<div class="notice notice-error" style="margin-top:36px;">			
				<p><?php echo wpcdm_activate_license_message(); ?></p>
			</div>			
			<?php			
			delete_option( WPC_BRANCHES_BASENAME );
			return false;
		}
		$screen = get_current_screen();
		if( $screen->parent_base == 'manage_branch' ){
			require_once( WPC_BRANCHES_PATH .'/admin/templates/add-branch.tpl.php');
			require_once( WPC_BRANCHES_PATH .'/admin/templates/edit-branch.tpl.php');
		}
	}

	//** AJAX Handler
	function add_branch_callback() {
		global $wpdb;
		$table_name 	= $wpdb->prefix . WPC_BRANCHES_TABLE;
		parse_str($_POST['formData'], $output);
		$default_form_keys = array_keys(wpcbm_add_update_branch_fields());
		$formatted_data = array();
		$placeholders = array();
		if(!empty($output) && is_array($output)){
			foreach($default_form_keys as $key){
				$value = "";
				# assign value
				if(array_key_exists($key, $output)){
					$value = $output[$key];
				}
				# clean values
				if($value){
					if(is_array($value)){
						$value = maybe_serialize($value);
					} else {
						$value = sanitize_text_field($value);
					}
				}
				# format values
				if(!array_key_exists($key, $formatted_data)){
					$formatted_data[$key] = $value;
				}
				# add placeholders
				$placeholders[] = '%s';
			}
		}
		# insert into the database
		$result = $wpdb->insert($table_name, $formatted_data, $placeholders);
		echo $result;
		wp_die();
	}
	function update_branch_callback() {
		global $wpdb;
		$table_name 	= $wpdb->prefix . WPC_BRANCHES_TABLE;
		parse_str($_POST['formData'], $output);
		$branchID = (int)$output['branchid'];
		$default_form_keys = array_keys(wpcbm_add_update_branch_fields());
		$formatted_data = array();
		$placeholders = array();
		if(!empty($output) && is_array($output)){
			foreach($default_form_keys as $key){
				$value = "";
				# assign value
				if(array_key_exists($key, $output)){
					$value = $output[$key];
				}
				# clean values
				if($value){
					if(is_array($value)){
						$value = maybe_serialize($value);
					} else {
						$value = sanitize_text_field($value);
					}
				}
				# format values
				if(!array_key_exists($key, $formatted_data)){
					$formatted_data[$key] = $value;
				}
				# add placeholders
				$placeholders[] = '%s';
			}
		}
		# update row data
		$result = $wpdb->update($table_name, $formatted_data, array('id' => $branchID), $placeholders);
		echo $result;
		wp_die();
	}
	function delete_branch_callback() {
		global $wpdb;
		$table_name 	= $wpdb->prefix . WPC_BRANCHES_TABLE;
	    $branchID 		= sanitize_text_field( $_POST['branchID'] );
	    $result 		= $wpdb->delete( $table_name, array( 'id' => $branchID ), array( '%d' ) );
	    echo $result;
	    wp_die();
	}
	function get_branch_callback(){
		$branchID    = sanitize_text_field( $_POST['branchID'] );
		$branch_info = wpcdm_get_branch( $branchID );
		
		if(!empty($branch_info['branch_manager'])){
	    $branch_info['branch_manager'] 	=  maybe_unserialize( $branch_info['branch_manager'] ) ?? array();
		}
	
		if(!empty($branch_info['branch_client'])){
			$branch_info['branch_client'] 	= maybe_unserialize( $branch_info['branch_client'] )  ?? array();
		}
	    
		if(!empty($branch_info['branch_agent'])){
			$branch_info['branch_agent'] 	= maybe_unserialize( $branch_info['branch_agent'] )  ?? array();
		}
		
		if(!empty($branch_info['branch_employee'])){		
    	$branch_info['branch_employee'] = maybe_unserialize( $branch_info['branch_employee'] ) ?? array();
		}
		
		if(!empty($branch_info['branch_driver'])){
			$branch_info['branch_driver'] 	= maybe_unserialize( $branch_info['branch_driver'] ) ?? array();
		}
		echo json_encode($branch_info);
		wp_die();
	}
	function wpcbranch_access_callback(){
		$optValue 	= $_POST['optValue'];
		$optName 	= $_POST['optName'];
		update_option( $optName, $optValue);
		wp_die();
	}
	function transfer_branch_callback(){
		global $wpdb;
		$branch 		= sanitize_text_field( $_POST['branch'] );
		$shipmentNumber = trim(sanitize_text_field( $_POST['shipmentNumber'] ));
		$sql 			= "SELECT `ID` FROM `".$wpdb->prefix."posts` WHERE `post_type` LIKE 'wpcargo_shipment' AND `post_status` LIKE 'publish' AND `post_title` LIKE '".$shipmentNumber."' LIMIT 1";
		$shipmentID 	=  $wpdb->get_var( $sql );
		if( $shipmentID  ){
			$current_user 				= wp_get_current_user();
	        $current_branch 			= get_post_meta( $shipmentID, 'shipment_branch', true );
	        $shipment_branch_history 	= maybe_unserialize( get_post_meta( $shipmentID, 'shipment_branch_history', true ) );
	        // Make sure that it is set.
			$new_history = array(
				'time' 			=> current_time( 'mysql' ),
				'updated-by' 	=> $current_user->ID,
				'from'    		=> $current_branch,
				'to'			=> $branch
			);
	        if( empty($shipment_branch_history) ){
	        	$shipment_branch_history = array();
	        }
	        
	        if( $current_branch != $branch ){  
	        	array_push($shipment_branch_history, $new_history);			
				update_post_meta($shipmentID, 'shipment_branch_history', maybe_serialize( $shipment_branch_history ) );
	    	}else{
	    		update_post_meta($shipmentID, 'shipment_branch_history', maybe_serialize( array( $new_history ) ) );
	    	}
	    	update_post_meta( $shipmentID, 'shipment_branch', $branch );
		}
		echo $shipmentID;
		wp_die();
	}
	function ie_option_fields( $options ){
		if( empty( get_option( WPC_BRANCHES_BASENAME ) ) ){
			?>
			<div class="notice notice-error" style="margin-top:36px;">			
				<p><?php echo wpcdm_activate_license_message(); ?></p>
			</div>			
			<?php			
			delete_option( WPC_BRANCHES_BASENAME );
		}else{
			$options = array_merge( $options, array( 'shipment_branch' => 'Shipment Branch' ) );
		}	
		return $options;
	}
	function ie_branch_field(){
		if( empty( get_option( WPC_BRANCHES_BASENAME ) ) ){
			?>
			<div class="notice notice-error" style="margin-top:36px;">			
				<p><?php echo wpcdm_activate_license_message(); ?></p>
			</div>			
			<?php			
			delete_option( WPC_BRANCHES_BASENAME );
			return false;
		}
		$all_branch = wpcbm_get_all_branch( -1 );
		if( !empty( $all_branch ) ){
			$shipment_branch = isset( $_GET['shipment_branch'] ) ? $_GET['shipment_branch'] : 0 ;
			?><p><strong class="left-lbl"><?php echo wpcdm_select_branch_label(); ?></strong><?php
			?><select id="wpc-user-branch" name="shipment_branch"><?php
				?><option value=""><?php echo wpcdm_select_branch_option_label(); ?></option><?php
				foreach ( $all_branch as $branch ) {
					?><option value="<?php echo $branch->id; ?>" <?php selected( $shipment_branch, $branch->id ); ?>><?php echo $branch->name; ?></option><?php
				}
			?></select></p><?php
		}
	}
	function export_modify_meta_value( $metavalue, $metakey, $post_id ){
		if( empty( get_option( WPC_BRANCHES_BASENAME ) ) ){
			?>
			<div class="notice notice-error" style="margin-top:36px;">			
				<p><?php echo wpcdm_activate_license_message(); ?></p>
			</div>			
			<?php			
			delete_option( WPC_BRANCHES_BASENAME );
		}else{
			if( $metakey == 'shipment_branch' ){
				$metavalue = wpcdm_get_branch_info( get_post_meta( $post_id, 'shipment_branch', true ) );
			}
		}	
		return $metavalue;
	}
	function ie_branch_field_metakey( $metakey ){
		if( empty( get_option( WPC_BRANCHES_BASENAME ) ) ){
			?>
			<div class="notice notice-error" style="margin-top:36px;">			
				<p><?php echo wpcdm_activate_license_message(); ?></p>
			</div>			
			<?php			
			delete_option( WPC_BRANCHES_BASENAME );
		}else{
			$metakey[] = 'shipment_branch';
		}		
		return $metakey;
	}
}
new WPC_Branch_Manager;

//** LICENSE HELPER
class WPCargo_Branch_Manager_Checker_Eai2NsfyvL6pbusj {
	static function license_helper() {
		if ( !in_array( 'wptaskforce-license-helper/wptaskforce-license-helper.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			deactivate_plugins( ABSPATH.'wp-content\plugins\wpcargo-branch-addons\wpcargo-branch.php' );
			$send_error_message = wpcdm_license_helper_plugin_dependent_message();
			die($send_error_message);
		}
	 }
	static function wpcargo_plugin() {
		//** Plugin dependency 
		if ( !in_array( 'wpcargo/wpcargo.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			deactivate_plugins( ABSPATH.'wp-content\plugins\wpcargo-branch-addons\wpcargo-branch.php' );
			$send_error_message = wpcdm_wpcargo_plugin_dependent_message();
			die($send_error_message);
		}
	}
	static function wpcargo_sp_function() {
		if( !function_exists('wpcargo_reset_u2nBYFt5AQEJPPnUN6qUrYN')){
			deactivate_plugins( ABSPATH.'wp-content\plugins\wpcargo-branch-addons\wpcargo-branch.php' );
			$send_error_message = wpcdm_cheating_message();
			die($send_error_message);
		}
	}
}
add_action('admin_init', 'wpcdm_branch_manager_hack_checker');
function wpcdm_branch_manager_hack_checker(){
	if( !function_exists('wpcargo_reset_u2nBYFt5AQEJPPnUN6qUrYN')){
		deactivate_plugins( ABSPATH.'wp-content\plugins\wpcargo-branch-addons\wpcargo-branch.php' );
		delete_option( WPC_BRANCHES_BASENAME );
		return false;
	}
}
register_activation_hook( ABSPATH.'wp-content\plugins\wpcargo-branch-addons\wpcargo-branch.php' , array('WPCargo_Branch_Manager_Checker_Eai2NsfyvL6pbusj', 'wpcargo_plugin') );
register_activation_hook( ABSPATH.'wp-content\plugins\wpcargo-branch-addons\wpcargo-branch.php' , array('WPCargo_Branch_Manager_Checker_Eai2NsfyvL6pbusj', 'license_helper') );
register_activation_hook( ABSPATH.'wp-content\plugins\wpcargo-branch-addons\wpcargo-branch.php' , array('WPCargo_Branch_Manager_Checker_Eai2NsfyvL6pbusj', 'wpcargo_sp_function') );