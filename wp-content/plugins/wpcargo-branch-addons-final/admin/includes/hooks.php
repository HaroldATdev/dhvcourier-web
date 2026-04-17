<?php
if ( ! defined( 'ABSPATH' ) ) { die; }
add_filter( 'default_wpcargo_columns', 'wpcbm_assigned_branch_columns' );
function wpcbm_assigned_branch_columns( $columns ) {
	$shipment_branch = array( 'shipment_branch' => esc_html__( 'Shipment Branch', 'wpcargo-branches' ) );
	$position = count( $columns ) -1 ;
	$columns = array_slice($columns, 0, $position, true) + $shipment_branch + array_slice( $columns, $position, count($columns) - 1, true );
	return $columns;
}
add_action( 'manage_wpcargo_shipment_posts_custom_column', 'manage_wpcbm_assigned_branch_columns', 10, 2 );
function manage_wpcbm_assigned_branch_columns( $column, $post_id ) {
	if( $column == 'shipment_branch' ){
		echo wpcdm_get_branch_info( get_post_meta( $post_id, 'shipment_branch', true ) );
	}
}
add_filter( 'manage_edit-wpcargo_shipment_sortable_columns', 'wpcbm_assigned_branch_sortable_columns' );
function wpcbm_assigned_branch_sortable_columns( $columns ) {
	$columns['shipment_branch'] = 'shipment_branch';	
	return $columns;
}
/*
** Plugin Auto Update
*/
// Load the auto-update class
function wpcbm_get_plugin_remote_update(){
	require_once( WPC_BRANCHES_PATH. 'admin/classes/class-autoupdate.php');
	$plugin_remote_path = 'http://www.wpcargo.com/repository/wpcargo-branch-addons/'.WPC_BRANCHES_UPDATE_REMOTE.'.php';
	return new WPCargo_Branch_Manager_AutoUpdate ( WPC_BRANCHES_VERSION, $plugin_remote_path, WPC_BRANCHES_BASENAME );
}
function wpcbm_activate_au(){
	wpcbm_get_plugin_remote_update();
}
function wpcbm_plugin_update_message( $data, $response ) {
	$WPCBM_AutoUpdate 	= wpcbm_get_plugin_remote_update();
	$remote_info 		= $WPCBM_AutoUpdate->getRemote('info');
	if( !empty( $remote_info->update_message ) ){
		echo $remote_info->update_message;
	}
}
add_action( 'in_plugin_update_message-wpcargo-branch-addons/wpcargo-branch.php', 'wpcbm_plugin_update_message', 10, 2 );
add_action( 'admin_init', 'wpcbm_activate_au' );
/*
** Load Plugin text domain
*/
function wpcbranch_plugins_loaded_callback(){
	wpc_branch_manager_load_textdomain();
	add_filter( 'wpcfe_get_users_wpcargo_client_list', 'wpcbranch_client_options' );
	add_filter( 'wpcfe_get_users_cargo_agent_list', 'wpcbranch_agent_options' );
	add_filter( 'wpcargo_pod_get_drivers_lists', 'wpcbranch_driver_options' );
	//add_filter( 'wpcfe_is_user_shipment', 'wpcbranch_access_shipment_callback', 10, 2 );
}
add_action( 'plugins_loaded', 'wpcbranch_plugins_loaded_callback' );
function wpc_branch_manager_load_textdomain() {
	load_plugin_textdomain( 'wpcargo-branches', false, '/wpcargo-branch-addons/languages' );
}
// Frontend Manager Assignement options filter
function wpcbranch_client_options( $options ){
	$current_roles = wpcbranch_current_user_role();
	if( in_array( 'administrator', $current_roles ) ){
		return $options;
	}elseif( in_array( 'wpcargo_branch_manager', $current_roles ) && get_option('wpcbranch_restrict_all_clients')){
		return wpcbranch_registered_users('client');
	}
	return $options;
}
function wpcbranch_agent_options( $options ){
	$current_roles = wpcbranch_current_user_role();
	if( in_array( 'administrator', $current_roles ) ){
		return $options;
	}elseif( in_array( 'wpcargo_branch_manager', $current_roles ) && get_option('wpcbranch_restrict_all_agents')){
		return wpcbranch_registered_users('agent');
	}
	return $options;
}
function wpcbranch_employee_options( $options ){
	$current_roles = wpcbranch_current_user_role();
	if( in_array( 'administrator', $current_roles ) ){
		return $options;
	}elseif( in_array( 'wpcargo_branch_manager', $current_roles ) && get_option('wpcbranch_restrict_all_employees')){
		return wpcbranch_registered_users('employee');
	}
	return $options;
}
function wpcbranch_driver_options( $options ){
	$current_roles = wpcbranch_current_user_role();
	if( in_array( 'administrator', $current_roles ) ){
		return $options;
	}elseif( in_array( 'wpcargo_branch_manager', $current_roles ) && get_option('wpcbranch_restrict_all_drivers')){
		return wpcbranch_registered_users('driver');
	}
	return $options;
}
function wpcbranch_access_shipment_callback( $result, $shipment_id ){

	$user_is_admin = in_array( 'administrator', wpcbranch_current_user_role() );
	$user_role_is_in_branch_roles = !empty( array_intersect( wpcbranch_current_user_role(), wpcbranch_get_branch_roles() ) );
	$shipment_in_the_current_branch = get_post_meta( $shipment_id, 'shipment_branch', true ) == (int)wpcbranch_get_current_branch();

	return $user_is_admin || ( $result && $user_role_is_in_branch_roles && $shipment_in_the_current_branch );
	
}
/*
** TABLE FILTERS HOOK
*/
add_action('restrict_manage_posts', 'wpcbm_assigned_branch_filter');
function wpcbm_assigned_branch_filter(){
	global $typenow;
	$post_type = 'wpcargo_shipment'; // change to your post type
	if ($typenow == $post_type) {
		$all_branch = wpcbm_get_all_branch( -1 );
		if( !empty( $all_branch ) ){
			$shipment_branch = isset( $_GET['shipment_branch'] ) ? $_GET['shipment_branch'] : 0 ;
			?>
			<select id="wpc-user-branch" name="shipment_branch">
				<option value=""><?php esc_html_e( 'Select Branch', 'wpcargo-branches' ); ?></option>
				<?php
					foreach ( $all_branch as $branch ) {
						?><option value="<?php echo $branch->id; ?>" <?php selected( $shipment_branch, $branch->id ); ?>><?php echo $branch->name; ?></option><?php
					}
				?>
			</select>
			<?php
		}
	}
}
add_filter('wpcargo_shipment_query_filter', function( $metakey ){
	$metakey[] = 'shipment_branch';
	return $metakey;
});
add_action('wpc_after_shipment_designation', 'wpc_mb_assign_branch_manager');
function wpc_mb_assign_branch_manager( $shipment_id ){
	?>
	<div class="section-wrapper">
		<div class="label-section"><label><strong><label><?php esc_html_e('Colaborador de Sucursal', 'wpcargo-branches'); ?></label></strong></label></div>
		<div class="select-section">
			<select name="wpcargo_branch_manager" class="mdb-select mt-0 form-control browser-default" id="wpcargo_branch_manager" disabled>
			<option value=""><?php esc_html_e('-- Seleccionar Colaborador --','wpcargo-branches'); ?></option>
			<?php if( !empty( wpcargo_get_branch_managers() ) ): ?>
				<?php foreach( wpcargo_get_branch_managers() as $branch_managerID => $branch_manager_name ): ?>
					<option value="<?php echo $branch_managerID; ?>" <?php selected( get_post_meta( $shipment_id, 'wpcargo_branch_manager', TRUE ), $branch_managerID ); ?>><?php echo $branch_manager_name; ?></option>
				<?php endforeach; ?>	
			<?php  endif; ?>	                
			</select>
		</div>
	</div>
	<?php
}

add_filter('wpcfe_registered_scripts', 'wpcbm_frontend_scripts', 10, 1 );
function wpcbm_frontend_scripts( $scripts ){
	$scripts[] = 'wpcbm-frontend-scripts';
	return $scripts;
}

add_filter('wpcfe_registered_scripts', 'wpc_admin_scripts', 10, 1 );
function wpc_admin_scripts( $scripts ){
	$scripts[] = 'branch-manager-scripts';
	return $scripts;
}

add_action( 'wpcfe_after_designation_dropdown', 'assign_branch_manager_dropdown' );
function assign_branch_manager_dropdown( $shipment_id ){
	$branch 			= get_post_meta( $shipment_id, 'shipment_branch', true );
	$get_branch 		= wpcdm_get_branch( $branch );
	$branch_managers 	= !empty( $get_branch ) && array_key_exists('branch_manager', $get_branch ) ? unserialize( $get_branch['branch_manager'] ) : array();
	if( !can_wpcfe_assign_branch_manager() ){
		return false;
	}
	?>
	<div class="form-group">
		<div class="select-no-margin">
			<label><?php esc_html_e('Colaborador de Sucursal','wpcargo-branches'); ?></label>
			<?php if( !empty( $branch ) ): ?>
				<select name="wpcargo_branch_manager" class="mdb-select mt-0 form-control browser-default" id="wpcargo_branch_manager">
					<option value=""><?php esc_html_e('-- Seleccionar Colaborador --', 'wpcargo-branches'); ?></option>
					<?php if( !empty( wpcargo_get_branch_managers() ) ): ?>
						<?php foreach( wpcargo_get_branch_managers() as $branch_managerID => $branch_manager_name ): ?>
							<?php if( in_array( $branch_managerID, $branch_managers ) ): ?>
								<option value="<?php echo $branch_managerID; ?>" <?php selected( get_post_meta( $shipment_id, 'wpcargo_branch_manager', TRUE ), $branch_managerID ); ?>><?php echo $branch_manager_name; ?></option>
							<?php endif; ?>
						<?php endforeach; ?>	
					<?php endif; ?>	                
				</select>
			<?php else: ?>
				<select name="wpcargo_branch_manager" class="mdb-select mt-0 form-control browser-default" id="wpcargo_branch_manager" disabled>
					<option value=""><?php esc_html_e('-- Seleccionar Colaborador --', 'wpcargo-branches'); ?></option>	                
				</select>
				<i class="text-danger empty-branch-notice"><?php esc_html_e('Por favor seleccione una sucursal antes de asignar un colaborador.','wpcargo-branches'); ?></i>
			<?php endif; ?>
		</div>
	</div>
	<?php 
}
add_filter( 'wpcfe_assign_agent', 'wpc_branch_capabilities' );
add_filter( 'wpcfe_assign_client', 'wpc_branch_capabilities' );
add_filter( 'wpcfe_add_shipment_role', 'wpc_branch_capabilities' );
function wpc_branch_capabilities( $users ){
	$users[] = 'wpcargo_branch_manager';
	return $users;
}

add_action( 'after_wpcfe_save_shipment', 'wpcb_assign_current_bm_to_shipment', 10, 2 );
function wpcb_assign_current_bm_to_shipment( $shipment_id, $data ){
    $current_user = wp_get_current_user();
    $user_role = $current_user->roles;
    if( in_array( 'wpcargo_branch_manager', $user_role ) ){
        update_post_meta( $shipment_id, 'wpcargo_branch_manager', (int)$current_user->ID );
    }
}
add_action( 'after_wpcfe_save_shipment', 'wpcbm_assign_branch_manager_save', 10, 2 );
function wpcbm_assign_branch_manager_save( $shipment_id, $data ){
	if( isset( $data['wpcargo_branch_manager'] ) && (int)$data['wpcargo_branch_manager'] && can_wpcfe_assign_manager() ){
        $old_manager = get_post_meta( $shipment_id, 'wpcargo_branch_manager', true );
        update_post_meta( $shipment_id, 'wpcargo_branch_manager', (int)$data['wpcargo_branch_manager'] );
        // check if the manager is changed Send email notification
        if( $old_manager != (int)$data['wpcargo_branch_manager'] && wpcdm_can_send_email_branch_manager() ){
            wpcargo_assign_shipment_email( $shipment_id, (int)$data['wpcargo_branch_manager'], esc_html__('Colaborador de Sucursal', 'wpcargo-branches' ) );
        }
    }
    if( isset( $data['shipment_branch'] ) ){
        update_post_meta( $shipment_id, 'shipment_branch', (int)$data['shipment_branch'] );
    } else {
		update_post_meta( $shipment_id, 'shipment_branch', (int)wpcbranch_get_current_branch() );
	}
}
add_action( 'before_wpcfe_shipment_form_submit', 'wpcbm_assigned_branch', 10, 2 );
function wpcbm_assigned_branch( $shipment_id ){
	$shipment       = new stdClass();
	$all_branch		= wpcbm_get_all_branch( -1 );
	$shipment_branch = get_post_meta( $shipment_id, 'shipment_branch', true ) ? get_post_meta( $shipment_id, 'shipment_branch', true ) : '';
	if( !can_wpcfe_assign_branch_manager() ){
		return false;
	}
	?>
	<div class="card mb-4">
		<section class="card-header">
			<?php echo wpcdm_assign_branch_label(); ?>
		</section>
		<section class="card-body">
			<div class="form-row">
				<p>
					<?php if( !empty( $all_branch ) ): ?>
						<select id="wpc-user-branch" name="shipment_branch" class="mdb-select mt-0 form-control browser-default">
							<option value=""><?php echo wpcdm_select_branch_label(); ?></option>
							<?php foreach ( $all_branch as $branch ): ?>
								<option value="<?php echo $branch->id; ?>" <?php selected( $shipment_branch, $branch->id ); ?>><?php echo $branch->name; ?></option>
							<?php endforeach; ?>
						</select>
					<?php else: ?>
						<i><?php esc_html_e('No available branches.', 'wpcargo-branches' ).' * '; ?></i>
					<?php endif; ?>
				</p>
			</div>
		</section>
	</div>
	<?php remove_all_actions('wpcfe_before_assign_form_content');
}
add_action( 'wpcargo_after_assign_email', 'wpcbm_assign_email_options' );
function wpcbm_assign_email_options( $options ){
	?>
	<tr>
		<th><?php esc_html_e( 'Deshabilitar email para Colaborador de Sucursal?', 'wpcargo-branches' ) ; ?></th>
		<td>
			<input type="checkbox" name="wpcargo_option_settings[wpcargo_email_branch_manager]" <?php  echo ( !empty( $options['wpcargo_email_branch_manager'] ) && $options['wpcargo_email_branch_manager'] != NULL  ) ? 'checked' : '' ; ?> />
		</td>
	</tr>
	<?php
}



//add_action( 'wp_ajax_get_affixes', 'wpcbranch_get_affixes_callback' );
//add_action( 'wp_ajax_get_default_affixes', 'wpcbranch_get_default_affixes_callback' );
//add_action( 'wp_ajax_modify_affixes', 'wpcbm_modify_affixes_callback' );
//add_action( 'wp_ajax_set_current_branch', 'wpcbranch_set_current_branch_callback' );



// Replace  Prefix & Suffix when the Branch Addons Load 
add_action( 'plugin_loaded', function(){

if(!empty(get_option( 'wpcfe_nsequence_enable' )) && !empty(get_option( 'wpcbranch_dynamic_prefix_suffix' ))){
		add_filter( 'wpcargo_prefix_fe_extra' , 'wpcbm_modify_shipment_prefix' , 10,1 );
		add_filter( 'wpcargo_suffix_fe_extra' , 'wpcbm_modify_shipment_suffix' , 10 ,1);
	}else{
		add_filter( 'wpcargo_prefix_extra' , 'wpcbm_modify_shipment_prefix' , 10,1 );
		add_filter( 'wpcargo_suffix_extra' , 'wpcbm_modify_shipment_suffix' , 10 ,1);
	}



});

// Replace  Prefix
function wpcbm_modify_shipment_prefix( $prefix) {
if(empty(get_current_user_branch('name')))
return $prefix;

   $prefix_branch=get_current_user_branch('branch_prefix');
   
    return   $prefix_branch;
 
}

// Replace  Suffix
function wpcbm_modify_shipment_suffix($suffix) {
if(empty(get_current_user_branch('name')))
return $suffix;

   $suffix_branch= get_current_user_branch('branch_suffix') ;
   return $suffix_branch;


}

# add new field for currency
function wpcbm_add_update_branch_fields_currency($fields){
	$fields['branch_currency'] = array(
		'field' => 'select',
		'label' => __('Branch Currency', WPC_BRANCHES_TEXTDOMAIN),
		'required' => false,
		'readonly' => false,
		'is_multiple' => false,
		'options' => wpcbm_wc_currencies()
	);
	return $fields;
}

# add new field as select 2 element
function wpcbm_custom_select_keys_currency($keys){
	$keys[] = 'branch_currency';
	return $keys;
}

# add new table header for branch currency
function wpcbm_after_table_header_branch_currency(){
	?>
	<th rowspan="2"><?php esc_html_e( 'Branch Currency', WPC_BRANCHES_TEXTDOMAIN ); ?></th>
	<?php
}

# add new table data for branch currency
function wpcbm_after_table_data_branch_currency($branch){
	?>
	<td><?php echo $branch->branch_currency; ?></td>
	<?php
}

function wpcbm_plugins_loaded_cb(){
	if(class_exists('WooCommerce')){
		add_filter('wpcbm_add_update_branch_fields', 'wpcbm_add_update_branch_fields_currency', 10, 1);
		add_filter('wpcbm_custom_select_keys', 'wpcbm_custom_select_keys_currency', 10, 1);
		add_action('wpcbm_after_table_header', 'wpcbm_after_table_header_branch_currency', 10);
		add_action('wpcbm_after_table_data', 'wpcbm_after_table_data_branch_currency', 10, 1);
	}
}
add_action('plugins_loaded', 'wpcbm_plugins_loaded_cb');
/*
** WPCargo Frontend Dashboard - Administrar Sucursales
** Agrega ítem al sidebar del panel de WPCargo (solo administradores)
** Mismo patrón que wpcfe_after_sidebar_menus usado por otros addons
*/

/**
 * Obtiene o crea la página frontend de sucursales (con shortcode).
 */
function wpcbm_get_frontend_page_id(): int {
	$saved = (int) get_option( 'wpcbm_frontend_page_id' );
	if ( $saved && get_post_status( $saved ) === 'publish' ) {
		return $saved;
	}
	global $wpdb;
	$id = (int) $wpdb->get_var(
		"SELECT ID FROM {$wpdb->prefix}posts
		 WHERE post_content LIKE '%[wpcbm-sucursales]%'
		   AND post_status = 'publish'
		 LIMIT 1"
	);
	if ( ! $id ) {
		$id = (int) wp_insert_post( array(
			'post_title'   => __( 'Administrar Sucursales', 'wpcargo-branches' ),
			'post_content' => '[wpcbm-sucursales]',
			'post_status'  => 'publish',
			'post_type'    => 'page',
		) );
	}
	if ( $id ) {
		update_post_meta( $id, '_wp_page_template', 'dashboard.php' );
		update_post_meta( $id, 'wpcfe_menu_icon',   'fa fa-building mr-3' );
		update_option( 'wpcbm_frontend_page_id', $id, false );
	}
	return $id;
}

/**
 * URL de la página frontend de sucursales.
 */
function wpcbm_frontend_url(): string {
	return get_permalink( wpcbm_get_frontend_page_id() ) ?: home_url( '/administrar-sucursales/' );
}

/**
 * Registra el shortcode [wpcbm-sucursales] que renderiza el módulo.
 */
add_shortcode( 'wpcbm-sucursales', 'wpcbm_frontend_shortcode' );
function wpcbm_frontend_shortcode(): string {
	if ( ! current_user_can( 'manage_options' ) ) {
		return '<div class="alert alert-warning"><i class="fa fa-lock mr-2"></i>' .
		       esc_html__( 'Acceso restringido.', 'wpcargo-branches' ) . '</div>';
	}
	$all_branches = wpcbm_get_all_branch( -1 );
	global $wpcargo;
	ob_start();
	?>
	<div class="wpcbm-fe-wrap">

		<div class="d-flex align-items-center mb-3 border-bottom pb-3">
			<div class="mr-auto">
				<h5 class="mb-0">
					<i class="fa fa-building mr-2 text-primary"></i>
					<?php esc_html_e( 'Administrar Sucursales', 'wpcargo-branches' ); ?>
				</h5>
				<small class="text-muted">
					<?php esc_html_e( 'Lista de todas las sucursales registradas.', 'wpcargo-branches' ); ?>
				</small>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=manage_branch' ) ); ?>"
			   class="btn btn-primary btn-sm">
				<i class="fa fa-cog mr-1"></i>
				<?php esc_html_e( 'Gestionar en Admin', 'wpcargo-branches' ); ?>
			</a>
		</div>

		<?php if ( ! empty( $all_branches ) ) : ?>
		<div class="table-responsive">
			<table class="table table-sm table-bordered table-hover wpcbm-fe-table">
				<thead class="thead-light">
					<tr>
						<th><?php esc_html_e( 'Nombre', 'wpcargo-branches' ); ?></th>
						<th><?php esc_html_e( 'Código', 'wpcargo-branches' ); ?></th>
						<th><?php esc_html_e( 'Teléfono', 'wpcargo-branches' ); ?></th>
						<th><?php esc_html_e( 'Ciudad', 'wpcargo-branches' ); ?></th>
						<th><?php esc_html_e( 'Colaborador', 'wpcargo-branches' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $all_branches as $branch ) :
					$branch_manager      = array();
					$unserialize_branch  = @unserialize( $branch->branch_manager );
					if ( $unserialize_branch ) {
						foreach ( $unserialize_branch as $branch_data ) {
							$branch_manager[] = $wpcargo->user_fullname( $branch_data );
						}
					}
					$assigned_bm = ! empty( $branch_manager ) ? join( ', ', $branch_manager ) : '--';
				?>
					<tr>
						<td><?php echo esc_html( $branch->name ); ?></td>
						<td><?php echo esc_html( $branch->code ); ?></td>
						<td><?php echo esc_html( $branch->phone ); ?></td>
						<td><?php echo esc_html( $branch->city ); ?></td>
						<td><?php echo esc_html( $assigned_bm ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php else : ?>
			<p class="text-muted">
				<?php esc_html_e( 'No hay sucursales registradas.', 'wpcargo-branches' ); ?>
			</p>
		<?php endif; ?>

	</div>
	<?php
	return ob_get_clean();
}

/**
 * Agrega "Administrar Sucursales" al sidebar del panel frontend de WPCargo.
 * Solo visible para administradores (manage_options).
 */
add_filter( 'wpcfe_after_sidebar_menus', 'wpcbm_sidebar_menu_item', 30, 1 );
function wpcbm_sidebar_menu_item( array $menu ): array {
	if ( ! current_user_can( 'manage_options' ) ) {
		return $menu;
	}
	$menu['wpcbm-sucursales'] = array(
		'page-id'   => wpcbm_get_frontend_page_id(),
		'label'     => __( 'Administrar Sucursales', 'wpcargo-branches' ),
		'permalink' => wpcbm_frontend_url(),
		'icon'      => 'fa-building',
	);
	return $menu;
}

// WordPress admin dashboard widget (solo administradores)
add_action( 'wp_dashboard_setup', 'wpcbm_wp_dashboard_widget_register' );
function wpcbm_wp_dashboard_widget_register() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	wp_add_dashboard_widget(
		'wpcbm_branches_dashboard_widget',
		esc_html__( 'Administrar Sucursales', 'wpcargo-branches' ),
		'wpcbm_wp_dashboard_widget_display'
	);
}
function wpcbm_wp_dashboard_widget_display() {
	$all_branches = wpcbm_get_all_branch( -1 );
	global $wpcargo;
	?>
	<style>
		#wpcbm_branches_dashboard_widget .wpcbm-dash-table { width:100%; border-collapse:collapse; font-size:13px; }
		#wpcbm_branches_dashboard_widget .wpcbm-dash-table th { background:#f1f1f1; padding:6px 8px; text-align:left; border-bottom:1px solid #ddd; }
		#wpcbm_branches_dashboard_widget .wpcbm-dash-table td { padding:6px 8px; border-bottom:1px solid #f0f0f0; vertical-align:top; }
		#wpcbm_branches_dashboard_widget .wpcbm-dash-table tr:last-child td { border-bottom:none; }
		#wpcbm_branches_dashboard_widget .wpcbm-dash-link { display:inline-block; margin-top:10px; font-weight:600; }
	</style>
	<?php if ( ! empty( $all_branches ) ) : ?>
	<table class="wpcbm-dash-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Nombre', 'wpcargo-branches' ); ?></th>
				<th><?php esc_html_e( 'Código', 'wpcargo-branches' ); ?></th>
				<th><?php esc_html_e( 'Teléfono', 'wpcargo-branches' ); ?></th>
				<th><?php esc_html_e( 'Ciudad', 'wpcargo-branches' ); ?></th>
				<th><?php esc_html_e( 'Colaborador', 'wpcargo-branches' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $all_branches as $branch ) :
			$branch_manager     = array();
			$unserialize_branch = @unserialize( $branch->branch_manager );
			if ( $unserialize_branch ) {
				foreach ( $unserialize_branch as $branch_data ) {
					$branch_manager[] = $wpcargo->user_fullname( $branch_data );
				}
			}
			$assigned_bm = ! empty( $branch_manager ) ? join( ', ', $branch_manager ) : '--';
		?>
			<tr>
				<td><?php echo esc_html( $branch->name ); ?></td>
				<td><?php echo esc_html( $branch->code ); ?></td>
				<td><?php echo esc_html( $branch->phone ); ?></td>
				<td><?php echo esc_html( $branch->city ); ?></td>
				<td><?php echo esc_html( $assigned_bm ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php else : ?>
		<p><?php esc_html_e( 'No hay sucursales registradas.', 'wpcargo-branches' ); ?></p>
	<?php endif; ?>
	<a class="wpcbm-dash-link" href="<?php echo esc_url( admin_url( 'admin.php?page=manage_branch' ) ); ?>">
		<?php esc_html_e( 'Administrar Sucursales →', 'wpcargo-branches' ); ?>
	</a>
	<?php
}
