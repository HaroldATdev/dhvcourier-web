<?php
if ( ! defined( 'ABSPATH' ) ) { die; }
function wpcbranch_current_user_role(){
    $current_user   = wp_get_current_user();
    $user_roles     = $current_user->roles;
    return $user_roles;
}

function wpcbranch_get_branch_roles() {
	return array( 'wpcargo_branch_manager', 'wpcargo_employee', 'cargo_agent', 'wpcargo_client', 'wpcargo_driver' );
}

function wpcbranch_roles_in_branch_roles( $roles ) {
    return ( !empty( array_intersect( $roles, wpcbranch_get_branch_roles() ) ) );
}

function wpcdm_display_address_format( $id ){
	global $wpdb;
	$output 	= '';
	$table_name = $wpdb->prefix . WPC_BRANCHES_TABLE;
	$sql 		= "SELECT `address1`, `address2`, `city`, `postcode`, `country`, `state` FROM ".$table_name." WHERE id=".$id;
	$results 	= $wpdb->get_row( $sql );
	if( $results ){	
		ob_start();
		?>
		  <div itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">
		  	<span itemprop="streetAddress"><?php echo $results->address1.' '.$results->address2; ?></span>
		    <span itemprop="addressLocality"><?php echo $results->city; ?></span>,
		    <span itemprop="addressRegion"><?php echo $results->state.' '.$results->postcode; ?></span>, <span itemprop="addressCountry"><?php echo $results->country; ?></span>
 		 </div>
		<?php
		$output = ob_get_clean();
	}
	return $output; 
}
function wpcdm_get_user_displayname( $userID ){
	$user_info = get_userdata( $userID  );
	$displayname = '';
	if( !empty( $user_info->last_name ) && !empty( $user_info->first_name  ) ){
		$displayname = $user_info->last_name .  ", " . $user_info->first_name;
	}else{
		$displayname = $user_info->display_name;
	}
	return $displayname;
}
function wpcbm_base_color(){
	$options 		= get_option('wpcargo_option_settings');
	$baseColor 		= '#00A924';
	if( $options ){
		if( array_key_exists('wpcargo_base_color', $options) ){
			$baseColor = ( $options['wpcargo_base_color'] ) ? $options['wpcargo_base_color'] : $baseColor ;
		}
	}
	return $baseColor;
}
function wpcdm_assign_branch_role(){
	$assign_branch_role = get_option('wpcdm_assign_branch_role');
	if( empty( $assign_branch_role ) ){
		$assign_branch_role = array();
	}
	return $assign_branch_role;
}

function wpcargo_get_branch_managers(){
	global $wpcargo;
	$branch_managers_list = array();
	$args = array(
		'role__in'	=> array( 'wpcargo_branch_manager' ),
		'orderby'   => 'display_name',
		'order'     => 'ASC'
	);	
	$branch_managers = get_users( apply_filters( 'wpcargo_branch_manager_args', $args ) );
	if( !empty( $branch_managers ) ){
		foreach ( $branch_managers  as $branch_manager ) {
			$branch_managers_list[$branch_manager->ID] = $wpcargo->user_fullname( $branch_manager->ID );
		}
	}
	return $branch_managers_list;
}
function wpcbranch_get_user_list( $user_role ){
	global $wpcargo;
	$user_list = array();
	$args = array(
		'role__in'	=> array( $user_role ),
		'orderby'   => 'display_name',
		'order'     => 'ASC'
	);	
	$users = get_users( apply_filters( "wpcbranch_get_user_list_{$user_role}_args", $args ) );
	if( !empty( $users ) ){
		foreach ( $users  as $user ) {
			$user_list[$user->ID] = $wpcargo->user_fullname( $user->ID );
		}
	}
	return $user_list;
}

function wpcfe_assign_branch_manager( ){
    return apply_filters( 'wpcfe_assign_branch_manager', array('wpcargo_employee', 'administrator') );
}
function can_wpcfe_assign_branch_manager( ){
	$user_roles     = wpcbranch_current_user_role();
	$result         = false;
	if( array_intersect( wpcfe_assign_branch_manager(), $user_roles ) ){
		$result = true;
	}
	return apply_filters( 'can_wpcfe_assign_branch_manager',  $result );
}



function get_current_user_branch($column=''){
    global $wpdb;
    $table_prefix = $wpdb->prefix;
    
    $user = wp_get_current_user();
   	$current_user_id 	=  $user->ID;
    	
    	if ( isset( $user->roles ) && is_array( $user->roles ) ) {
    	    
        if ( in_array( 'administrator', $user->roles )  || empty($current_user_id) ) {
            
           return;
        }
        
        
    	if ( in_array( 'wpcargo_employee', $user->roles ) ) {
            
           $field= 'branch_employee';
        }   
    	    
    	if ( in_array( 'wpcargo_branch_manager', $user->roles ) ) {
            
           $field= 'branch_manager';
        } 
         if ( in_array( 'wpcargo_client', $user->roles ) ) {
            
           $field= 'branch_client';
        }   
          if ( in_array( 'wpcargo_driver', $user->roles ) ) {
            
           $field= 'branch_driver';
        }  
        if ( in_array( 'cargo_agent', $user->roles ) ) {
            
           $field= 'branch_agent';
        }  
    	}   
            
    	if (empty($current_user_id)   )
    	return;
    	
    //	SELECT * FROM `wpaq_wpcargo_branch` WHERE `branch_employee` LIKE '%\"12\"%'
    	if(empty($column))
    	$column='name';
    
		
		$query = "SELECT `%1s` FROM `%1swpcargo_branch` WHERE `%1s` LIKE '%\"%1s\"%'";
		$column_value =  $wpdb->get_var( $wpdb->prepare( $query,$column,$table_prefix,$field, $current_user_id ));
		
		return 	$column_value;
		
}


/*
 * Helper Functions For Affixes <END>
 */

 function wpcbranch_get_branch_by_role_id( $role, $id ) {

    $all_branches = wpcbm_get_all_branch( -1 );
	$role_users = array();

	if ( !empty( $all_branches ) ) {

        foreach ( $all_branches as $branch ) {

			switch( $role ) {
				
                case 'wpcargo_branch_manager': $role_users = (array)unserialize( $branch->branch_manager );
				                               break;
				case 'wpcargo_employee':       $role_users = (array)unserialize( $branch->branch_employee );
				                               break;
	            case 'cargo_agent':            $role_users = (array)unserialize( $branch->branch_agent );
				                               break;
				case 'wpcargo_client':         $role_users = (array)unserialize( $branch->branch_client );
				                               break;
				case 'wpcargo_driver':         $role_users = (array)unserialize( $branch->branch_driver );
				                               break;

			}
            
			if ( in_array( $id, $role_users ) ) {
				return $branch;
			}

		}

	}

	return false;

}



function wpcbranch_get_current_branch() {
	return get_option( 'current_branch' );
}

function wpcbranch_get_current_branch_callback() {

    echo( wpcbranch_get_current_branch() );
	wp_die();

}






/*
 * Language translation for the encrypted file
 */
function wpcdm_activate_license_message(){
	return sprintf( 
		'%s <a href="%s" title="%s">%s</a>',
		esc_html__('Please activate your license key for Branch Manager Add on', 'wpcargo-branches'),
		admin_url().'admin.php?page=wptaskforce-helper',
		esc_html__('WPCargo license page', 'wpcargo-branches'),
		esc_html__('here', 'wpcargo-branches')
	);
}
function wpcdm_page_permission_message(){
	return esc_html__( "Sorry you don't have enough premission to access this page. Please contact administrator.", 'wpcargo-branches' );
}
function wpcdm_license_helper_plugin_dependent_message(){
	return sprintf( 
		'%s <a href="http://wpcargo.com/" target="_blank">%s</a> %s',
		esc_html__('This plugin requires', 'wpcargo-branches'),
		esc_html__('WPTaskForce License Helper', 'wpcargo-branches'),
		esc_html__('plugin to be active!', 'wpcargo-branches')
	);
}
function wpcdm_wpcargo_plugin_dependent_message(){
	return sprintf( 
		'%s <a href="https://wordpress.org/plugins/wpcargo/" target="_blank">%s</a> %s',
		esc_html__('This plugin requires', 'wpcargo-branches'),
		esc_html__('WPCargo', 'wpcargo-branches'),
		esc_html__('plugin to be active!', 'wpcargo-branches')
	);
}
function wpcdm_cheating_message(){
	return esc_html__( 'Cheating, uh?', 'wpcargo-branches' );
}
function wpcdm_branch_manager_label(){
	return esc_html__('Colaborador de Sucursal', 'wpcargo-branches' );
}
function wpcdm_shipment_branch_label(){
	return esc_html__( 'Shipment Branch', 'wpcargo-branches' );
}
function wpcdm_branch_history_label(){
	return esc_html__( 'Branch History', 'wpcargo-branches' );
}
function wpcdm_branch_transfer_label(){
	return esc_html__('Branch Transfer', 'wpcargo-branches');
}
function wpcdm_branch_manager_settings_label(){
	return esc_html__( 'Branch Manager Settings', 'wpcargo-branches' );
}
function wpcdm_assign_branch_label(){
	return esc_html__( 'Assigned Branch', 'wpcargo-branches' );
}
function wpcdm_unable_assign_branch_label(){
	return esc_html__( 'Unable to Assign Branch', 'wpcargo-branches' );
}
function wpcdm_manage_branches_label(){
	return esc_html__( 'Manage Branches', 'wpcargo-branches' );
}
function wpcdm_manage_branch_label(){
	return esc_html__( 'Manage Branch', 'wpcargo-branches' );
}
function wpcdm_add_new_branch_label(){
	return esc_html__( 'Add New Branch', 'wpcargo-branches' );
}
function wpcdm_transfer_shipment_label(){
	return esc_html__( 'Transfer Shipment', 'wpcargo-branches' );
}
function wpcdm_select_branch_label(){
	return esc_html__( 'Select Branch', 'wpcargo-branches' );
}
function wpcdm_select_branch_option_label(){
	return esc_html__( '--Select Branch--', 'wpcargo-branches' );
}
function wpcdm_updated_by_label(){
	return esc_html__('Updated By:', 'wpcargo-branches');
}
function wpcdm_from_label(){
	return esc_html__('From:', 'wpcargo-branches');
}
function wpcdm_to_label(){
	return esc_html__('To:', 'wpcargo-branches');
}
function wpcdm_on_label(){
	return esc_html__('on', 'wpcargo-branches');
}
function wpcdm_branch_code_label(){
	return esc_html__('Branch Code', 'wpcargo-branches');
}
function wpcdm_phone_label(){
	return esc_html__('Phone', 'wpcargo-branches');
}
function wpcdm_can_send_email_branch_manager(){
	$gen_settings = get_option( 'wpcargo_option_settings' );
	$email_branch_manager = !array_key_exists('wpcargo_email_branch_manager', $gen_settings ) ? true : false;
	return $email_branch_manager;
}

function wpcdm_can_assign_branch( $userid = false ){
	if( !$userid ){
 		$current_user 	= wp_get_current_user();
 		$user_roles 	= $current_user->roles;
	}else{
		$user_info 		= get_userdata( $userid );
		$user_roles 	= $user_info->roles;
	}
	$result = array_intersect ( $user_roles , wpcdm_assign_branch_role() );
	return $result;
}

# field generator
function wpcbm_custom_field_generator($key, $val, $value = '', $custom_class = '', $custom_id = ''){
	$element = "";
	$field_type = $val['field'];
	$field_data_label = "data-el_label='{$val['label']}'";
	$field_name = "name='{$key}'";
	$field_mul = "";
	if($val['is_multiple']){
		$field_name = "name='{$key}[]'";
		$field_mul = 'multiple';
	}
	$field_id = "id='{$key}'";
	if($custom_id){
		$field_id = "id='{$custom_id}-{$key}'";
	}
	$field_class = "";
	if($custom_class){
		$field_class = "class='{$custom_class}'";
	}
	$field_value = "";
	if($value){
		$field_value = "value='{$value}'";
	}
	$field_req = $val['required'] ? 'required' : '';
	$field_rdo = $val['readonly'] ? 'readonly' : '';
	switch ($field_type) {
		case 'select':
			$element .= "<select {$field_id} {$field_name} {$field_class} {$field_mul} {$field_req} {$field_data_label}>";
			$element .= "<option value=''>-- Select --</option>";
			if(!empty($val['options'])){
				foreach($val['options'] as $_k => $_v){
					$selected_opt = "";
					if($value){
						if(is_array($value)){
							$selected_opt = in_array($_v, $value) ? 'selected' : '';
						} else {
							$selected_opt = ($value && ($value == $_k)) ? 'selected' : '';
						}
					}
					$element .= "<option value='{$_k}' {$selected_opt}>{$_v}</option>";
				}
			}
			$element .= "</select>";
			break;
		case 'textarea':
			$element .= "<textarea {$field_id} {$field_name} {$field_class} {$field_req} {$field_rdo} {$field_data_label} cols='30' rows='10'>{$field_value}</textarea>";
			break;
		default:
			$element .= "<input type='{$field_type}' {$field_id} {$field_name} {$field_class} {$field_data_label} {$field_value} {$field_req} {$field_rdo} />";
			break;
	}
	return $element;
}

# add && update branch dynamic fields
function wpcbm_add_update_branch_fields(){
	$fields = array(
		'name' => array(
			'field' => 'text',
			'label' => __('Nombre', WPC_BRANCHES_TEXTDOMAIN),
			'required' => true,
			'readonly' => false,
			'is_multiple' => false,
			'options' => array()
		),
		'code' => array(
			'field' => 'text',
			'label' => __('Código', WPC_BRANCHES_TEXTDOMAIN),
			'required' => true,
			'readonly' => false,
			'is_multiple' => false,
			'options' => array()
		),
		'phone' => array(
			'field' => 'text',
			'label' => __('Teléfono', WPC_BRANCHES_TEXTDOMAIN),
			'required' => false,
			'readonly' => false,
			'is_multiple' => false,
			'options' => array()
		),
		'address1' => array(
			'field' => 'text',
			'label' => __('Dirección', WPC_BRANCHES_TEXTDOMAIN),
			'required' => false,
			'readonly' => false,
			'is_multiple' => false,
			'options' => array()
		),
		'city' => array(
			'field' => 'text',
			'label' => __('Ciudad', WPC_BRANCHES_TEXTDOMAIN),
			'required' => false,
			'readonly' => false,
			'is_multiple' => false,
			'options' => array()
		),
		'branch_manager' => array(
			'field' => 'select',
			'label' => __('Colaborador de Sucursal', WPC_BRANCHES_TEXTDOMAIN),
			'required' => false,
			'readonly' => false,
			'is_multiple' => true,
			'options' => wpcargo_get_branch_managers()
		),
	);
	return apply_filters('wpcbm_add_update_branch_fields', $fields);
}

# select2 keys
function wpcbm_custom_select_keys(){
	$keys = array(
		'branch_manager',
		'branch_employee',
		'branch_agent',
		'branch_client',
		'branch_driver',
	);
	return apply_filters('wpcbm_custom_select_keys', $keys);
}

# get all woocommerce currencies
function wpcbm_wc_currencies(){
	$currencies = array();
	if(class_exists('WooCommerce')){
		foreach(get_woocommerce_currencies() as $code => $name){
			$symbol = get_woocommerce_currency_symbol($code);
			$currencies[$symbol] = "{$name} ( {$symbol} )";
		}
	}
	return $currencies;
}