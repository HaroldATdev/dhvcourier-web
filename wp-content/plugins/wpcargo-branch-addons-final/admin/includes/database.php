<?php
if ( ! defined( 'ABSPATH' ) ) { die; }
function wpcbranch_activation_callback(){
    add_role( 
        'wpcargo_branch_manager', 
        wpcdm_branch_manager_label(),
        array(
                'create_posts' => true,
                'delete_posts'	=> true ,
                'edit_posts'	=> true ,
                'edit_published_posts'	=> true ,
                'publish_posts'	=> true ,
                'read'	=> true
            ) 
    );
    wpcbranch_create_manage_branch_table();
    add_option('current_branch', '');
    wpc_braches_database_updates();
}
function wpcbranch_deactivation_callback(){

    remove_role( 'wpcargo_branch_manager' );
    delete_option( 'current_branch' );

}
function wpcbranch_create_manage_branch_table(){
    global $wpdb;
    $table_name = $wpdb->prefix . WPC_BRANCHES_TABLE;
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql             = "CREATE TABLE IF NOT EXISTS $table_name (
                        id int(100) NOT NULL auto_increment,
                        name VARCHAR(200) NOT NULL,
                        code VARCHAR(100) NOT NULL,
                        phone VARCHAR(100) NOT NULL,
                        address1 VARCHAR(255) NOT NULL,
                        address2 VARCHAR(255) NOT NULL,
                        city VARCHAR(100) NOT NULL,
                        postcode VARCHAR(50) NOT NULL,
                        country VARCHAR(100) NOT NULL,
                        state VARCHAR(100) NOT NULL,
                        UNIQUE KEY id (id)
                        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
function wpc_braches_database_updates(){
    // Add new table column
    // @ branch_manager
    wpc_branches_add_manager();
    // @ branch_client @ branch_agent @ branch_employee @ branch_driver
    wpc_branches_add_assigment();
    // @ branch_prefix @ branch_suffix
    wpc_branches_add_affix();
    // @ branch_currency
    if(class_exists('WooCommerce')){
        wpc_branches_add_currency();
    }
    
}
function wpc_branches_add_manager(){
    global $wpdb;
	$branches_table = $wpdb->prefix . WPC_BRANCHES_TABLE;
	if( version_compare( WPC_BRANCHES_DB_VERSION, '5.0.0' ) > 0  ){
		$old_table_columns = $wpdb->get_col( "DESC " . $branches_table, 0 );
		if( !in_array( 'branch_manager', $old_table_columns) ){
			$sql_alter = "ALTER TABLE `{$branches_table}` ADD `branch_manager` TEXT NOT NULL AFTER `state`";
			$wpdb->query($sql_alter);
		}	
	}
}
function wpc_branches_add_assigment(){
    global $wpdb;
	$branches_table = $wpdb->prefix . WPC_BRANCHES_TABLE;
	if( version_compare( WPC_BRANCHES_DB_VERSION, '5.0.1' ) > 0  ){
		$table_columns = $wpdb->get_col( "DESC " . $branches_table, 0 );
		if( !in_array( 'branch_client', $table_columns) ){
			$sql_alter = "ALTER TABLE `{$branches_table}` ADD `branch_client` TEXT NOT NULL AFTER `branch_manager`";
			$wpdb->query($sql_alter);
        }	
        if( !in_array( 'branch_agent', $table_columns) ){
			$sql_alter = "ALTER TABLE `{$branches_table}` ADD `branch_agent` TEXT NOT NULL AFTER `branch_client`";
			$wpdb->query($sql_alter);
        }
        if( !in_array( 'branch_employee', $table_columns) ){
			$sql_alter = "ALTER TABLE `{$branches_table}` ADD `branch_employee` TEXT NOT NULL AFTER `branch_agent`";
			$wpdb->query($sql_alter);
        }
        if( !in_array( 'branch_driver', $table_columns) ){
			$sql_alter = "ALTER TABLE `{$branches_table}` ADD `branch_driver` TEXT NOT NULL AFTER `branch_employee`";
			$wpdb->query($sql_alter);
		}
	}
}

function wpc_branches_add_affix(){
    global $wpdb;
	$branches_table = $wpdb->prefix . WPC_BRANCHES_TABLE;
	if( version_compare( WPC_BRANCHES_DB_VERSION, '5.0.1' ) > 0  ) {

		$table_columns = $wpdb->get_col( "DESC " . $branches_table, 0 );
		if( !in_array( 'branch_prefix', $table_columns) ){
			$sql_alter = "ALTER TABLE `{$branches_table}` ADD `branch_prefix` TEXT NOT NULL AFTER `branch_driver`";
			$wpdb->query($sql_alter);
        }	

        if( !in_array( 'branch_suffix', $table_columns) ){
			$sql_alter = "ALTER TABLE `{$branches_table}` ADD `branch_suffix` TEXT NOT NULL AFTER `branch_prefix`";
			$wpdb->query($sql_alter);
        }

	}
}
function wpc_branches_add_currency(){
    global $wpdb;
	$branches_table = $wpdb->prefix . WPC_BRANCHES_TABLE;
    $table_columns = $wpdb->get_col( "DESC " . $branches_table, 0 );
    if( !in_array( 'branch_currency', $table_columns) ){
        $sql_alter = "ALTER TABLE `{$branches_table}` ADD `branch_currency` TEXT NOT NULL AFTER `branch_suffix`";
        $wpdb->query($sql_alter);
    }
}

// Function Helpers
function wpcbm_get_all_branch( $limit = 12 ){
	global $wpdb;
	$table_name = $wpdb->prefix . WPC_BRANCHES_TABLE;
	if( $limit < 0 ){
		$sql = "SELECT * FROM ".$table_name;
	}else{
		$sql = "SELECT * FROM ".$table_name." LIMIT ".$limit;
	}
	$result = $wpdb->get_results( $sql );
	return $result;
}
function wpcdm_get_branch( $id ){
	global $wpdb;
	$get_id = !empty( $id ) ? $id : 0;
	$table_name = $wpdb->prefix . WPC_BRANCHES_TABLE;
	$sql 		= "SELECT * FROM ".$table_name." WHERE id=".$get_id;
	$results 	= $wpdb->get_row( $sql, ARRAY_A );
	return 	$results;
}
function wpcdm_get_branch_info( $id, $column_name = 'name' ){
	global $wpdb;
	$results = false;
	if( $id && is_numeric( $id ) ){
		$table_name = $wpdb->prefix . WPC_BRANCHES_TABLE;
		$sql 		= "SELECT `".$column_name."` FROM ".$table_name." WHERE id=".$id;
		$results 	= $wpdb->get_var( $sql);
	}
	return $results;
}
function wpcbranch_registered_users( $user_type, $user_id = false ){
    global $wpdb, $wpcargo;
    $user_id        = (int)$user_id ? (int)$user_id : get_current_user_id();
    $table          = $wpdb->prefix . WPC_BRANCHES_TABLE;
    $tblcolumn      = "branch_".$user_type;
    $table_columns  = $wpdb->get_col( "DESC {$table}", 0 ); 
    $users          = array();
    if( !in_array( $tblcolumn, $table_columns ) ){
        return $users;
    }
    $sql        = $wpdb->prepare( "SELECT `{$tblcolumn}` FROM {$table} WHERE `branch_manager` LIKE %s", '%:"'.$user_id.'";%');
    $sql        = apply_filters( 'wpcbranch_registered_users', $sql, $user_type, $user_id );
    $results    = $wpdb->get_col( $sql );
    if( empty( $results ) ){
        return $users;
    }
    foreach ($results as $values ) {
        $values =  maybe_unserialize( $values );
        if( !is_array( $values ) || empty( $values ) ){
            continue;
        }
        foreach ( $values as $_user_id ) {
            if( array_key_exists( $_user_id, $users ) ){
                continue;
            }
            $users[$_user_id] = $wpcargo->user_fullname( $_user_id );
        }
    }
    return $users;
}