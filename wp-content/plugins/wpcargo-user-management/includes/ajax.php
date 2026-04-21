<?php
// Save Users Group
function wpcumanage_save_user_group_callback(){
	global $wpdb;
	$save_type 				= sanitize_text_field( $_POST['save_type'] );
	$group_id 				= isset( $_POST['group_id'] ) ?sanitize_text_field( $_POST['group_id'] ) : 0;
	$label 					= sanitize_text_field( $_POST['label'] );
	$description 			= sanitize_text_field( $_POST['description'] );
	$wpcumanage_ug_users 	=  isset( $_POST['wpcumanage_ug_users'] ) ? maybe_serialize( $_POST['wpcumanage_ug_users'] ) : array();
	if( $save_type == 'update' ){
		$result = $wpdb->update(
			$wpdb->prefix.WPCU_MANAGEMENT_DB_USER_GROUP,
			array(
				'label' 	=> $label,
				'description' 	=> $description,
				'users' => $wpcumanage_ug_users,
			),
			array( 'user_group_id' => $group_id ),
			array(
				'%s',
				'%s',
				'%s',
			),
			array( '%d' )
		);
	}else{
		$result = $wpdb->insert(
			$wpdb->prefix.WPCU_MANAGEMENT_DB_USER_GROUP,
			array(
				'label' => $label,
				'description' => $description,
				'users' => $wpcumanage_ug_users,
			),
			array( '%s', '%s', '%s' )
		);
	}	
	
	if( $result ){
		$result = $wpdb->insert_id;
	}
	echo $result;
	wp_die();
}
add_action( 'wp_ajax_nopriv_wpcumanage_save_user_group', 'wpcumanage_save_user_group_callback' );
add_action( 'wp_ajax_wpcumanage_save_user_group', 'wpcumanage_save_user_group_callback' );

/***** WPCSR USER GROUP MODAL *****/
function wpcumanage_get_user_group_data_callback(){
    global $wpcargo;
	$id 		= $_REQUEST['id'];
	$user_group = wpcumanage_get_user_group_by_id( $id ) ?? '';
	$users = $user_group->users ? unserialize( $user_group->users ) : array();
	ob_start();
	?>
	<table class="form-table wpcumanage_add_table">
		<tr>
			<td><label><?php echo wpcumanage_group_name_label(); ?></label></td>
			<td><input type="text" name="wpcumanage_ug_label" id="wpcumanage_ug_label" class="wpcumanage_detail wpcumanage_ug_label" value="<?php echo $user_group->label; ?>" /></td>
		</tr>
		<tr>
			<td><label><?php echo wpcumanage_description_label(); ?></label></td>
			<td><textarea rows="6" name="wpcumanage_ug_desc" id="wpcumanage_ug_desc" class="wpcumanage_detail wpcumanage_ug_desc"><?php echo $user_group->description; ?></textarea></td>
		</tr>
		<tr>
			<td><label><?php echo wpcumanage_users_label(); ?></label></td>
			<td>
				<select name="wpcumanage_ug_users" id="wpcumanage_ug_users" class="wpcumanage_detail wpcumanage_ug_users" multiple>
					<?php 
					$args = array(
                                        'role__in'  => array('wpcargo_client'),
                                        'orderby'   => 'display_name',
                                        'order'     => 'ASC'
                                     );
 
                                    $users  = array();
                                     if( !empty( get_users( $args ) ) ){
                                         foreach ( get_users( $args ) as $user ) {
                                            $users[$user->ID] = $wpcargo->user_fullname( $user->ID );
                                         }
                                     }
    
									
									foreach( $users  as $user_id => $user_name ):?>
						<option value="<?php echo $user_id;?>" <?php echo ( in_array( $user_id, $users ) ) ? 'selected' : ''; ?>><?php echo $user_name; ?></option>
					<?php endforeach;?>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				<input type="hidden" id="wpcumanage_ug_id" value="<?php echo $id; ?>">
			</td>
			<td><input type="submit" id="wpcumanage_submit" class="wpcumanage_ug_btn button button-primary button-large wpcumanage_submit" value="<?php echo wpcumanage_update_group_label(); ?>" /></td>
		</tr>
	</table>
	<?php
	$output = ob_get_clean();
	echo $output;
	wp_die();
}
add_action( 'wp_ajax_wpcumanage_get_user_group_data', 'wpcumanage_get_user_group_data_callback' );
add_action( 'wp_ajax_nopriv_wpcumanage_get_user_group_data', 'wpcumanage_get_user_group_data_callback' );

/***** FRONTEND USER GROUP MODAL *****/
function wpcumanage_frontend_get_user_group_data_callback(){
	$group_id 	= $_REQUEST['group_id'];
	$user_group = wpcumanage_get_user_group_by_id( $group_id );
	$users = !empty( $user_group->users )? unserialize( $user_group->users ) : array();
	ob_start();
	?>
	<input type="hidden" id="wpcumanage_ug_id" value="<?php echo $group_id; ?>">
	<div class="col-md-12">
		<div class="form-group">
			<label for="wpcumanage_ug_label"><?php echo wpcumanage_group_name_label(); ?></label>
			<input id="wpcumanage_ug_label" type="text" class="form-control" name="wpcumanage_ug_label" value="<?php echo $user_group->label; ?>">
		</div>
	</div>
	<div class="col-md-12">
		<div class="form-group">
			<label for="wpcumanage_ug_label"><?php echo wpcumanage_description_label(); ?></label>
			<textarea rows="4" name="wpcumanage_ug_desc" id="wpcumanage_ug_desc" class="form-control wpcumanage_ug_desc"><?php echo $user_group->description; ?></textarea>
		</div>
	</div>
	<?php
	echo ob_get_clean();
	wp_die();
}
add_action( 'wp_ajax_frontend_get_user_group_data', 'wpcumanage_frontend_get_user_group_data_callback' );
add_action( 'wp_ajax_nopriv_frontend_get_user_group_data', 'wpcumanage_frontend_get_user_group_data_callback' );


/***** DELETE WPCSR DATA *****/ 
function wpcumanage_delete_user_group_callback(){
	global $wpdb;
	$id = $_REQUEST['id'];
	if( !empty( $id ) ){
		$wpdb->delete( $wpdb->prefix . WPCU_MANAGEMENT_DB_USER_GROUP, array( 'user_group_id' => $id ) );
	}
	die();
}
add_action( 'wp_ajax_wpcumanage_delete_user_group', 'wpcumanage_delete_user_group_callback' );
add_action( 'wp_ajax_nopriv_wpcumanage_delete_user_group', 'wpcumanage_delete_user_group_callback' );

/***** BULK WPCSR DATA *****/
function wpcumanage_ug_bulk_delete_callback(){
	global $wpdb;
	$ids 		= $_REQUEST['id'];
	$table_name = $wpdb->prefix .WPCU_MANAGEMENT_DB_USER_GROUP;
	$id_label = 'user_group_id';
	foreach ($ids as $array => $id) {
		$wpdb->delete( $table_name, array( $id_label => $id ) );
	}
    wp_die();
}
add_action( 'wp_ajax_wpcumanage_ug_bulk_delete', 'wpcumanage_ug_bulk_delete_callback' );
add_action( 'wp_ajax_nopriv_wpcumanage_ug_bulk_delete', 'wpcumanage_ug_bulk_delete_callback' );