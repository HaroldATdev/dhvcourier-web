<?php
if (! defined('ABSPATH')) {
	exit;
}
function wpcargo_pod_dashboard_callback($shipment_id)
{
	$assigned_driver 	= get_post_meta($shipment_id, 'wpcargo_driver', true);
	$signature 			= get_post_meta($shipment_id, 'wpcargo-pod-signature', true);
	$images 			= get_post_meta($shipment_id, 'wpcargo-pod-image', true);
	require_once(wpcpod_include_template('assigned-driver.tpl'));
}
// Add script in the Dashboard script
function wpcargo_pod_dashboard_registered_styles($styles)
{
	$styles[] = 'wpcargo-pod-dashboard-style';
	return $styles;
}
// Add script in the Dashboard script
function wpcargo_pod_dashboard_registered_scripts($script)
{
	$script[] = 'wpcargo-pod-signature-scripts';
	$script[] = 'wpcargo-pod-scripts';
	return $script;
}
add_filter('wpcfe_registered_styles', 'wpcargo_pod_dashboard_registered_styles', 10, 1);
add_filter('wpcfe_registered_scripts', 'wpcargo_pod_dashboard_registered_scripts', 10, 1);

function wpcargo_pod_can_sign_shipment()
{
	$user = wp_get_current_user();
	if ( ! $user || empty( $user->roles ) ) {
		return false;
	}

	$allowed_roles = array( 'wpcargo_driver', 'administrator', 'wpcargo_admin' );
	return ! empty( array_intersect( $allowed_roles, $user->roles ) );
}

// Add Shipment table header "Sign"
function wpcargo_pod_dashboard_table_header_action()
{
	if ( ! wpcargo_pod_can_sign_shipment() ) return;
	echo '<th class="wpcpod-sign_data text-center hide-me">' . apply_filters('pod_table_header_sign_label', __('Sign', 'wpcargo-pod')) . '</th>';
}
function wpcargo_pod_dashboard_table_table_action($shipment_id)
{
	if ( ! wpcargo_pod_can_sign_shipment() ) return;
	$signature = get_post_meta($shipment_id, 'wpcargo-pod-signature', true);
	$current_status = get_post_meta($shipment_id, 'wpcargo_status', true);
	$normalized_status = strtolower( remove_accents( trim( (string) $current_status ) ) );
	$is_en_ruta = ( $normalized_status === 'en ruta' );
	$btn_label = apply_filters('pod_table_header_sign_label', __('Sign', 'wpcargo-pod'));
	$btn_color = 'btn-outline-info';
	$btn_disabled = '';
	$btn_disabled_class = '';
	$btn_title = '';
	if ($signature) {
		$btn_label = apply_filters('pod_table_header_signed_label', __('Signed', 'wpcargo-pod'));
		$btn_color = 'btn-outline-blue-grey';
	}
	if ( ! $is_en_ruta ) {
		$btn_disabled = ' disabled="disabled"';
		$btn_disabled_class = ' disabled';
		$btn_title = ' title="' . esc_attr__( 'Disponible solo cuando el envío está En ruta', 'wpcargo-pod' ) . '"';
	}
	echo '<td class="text-center"><button type="button" class="wpcpod-sign_data show-signaturepad btn ' . $btn_color . ' btn-rounded btn-small py-1 px-4 hide-me' . $btn_disabled_class . '" data-toggle="modal" data-target="#wpc_pod_signature-modal" data-id="' . $shipment_id . '"' . $btn_disabled . $btn_title . '>' . $btn_label . '</button></td>';
}
// Registrar hooks para inyectar cabecera y acción en la tabla de envíos
add_action('wpcfe_shipment_table_header_action', 'wpcargo_pod_dashboard_table_header_action', 25);
add_action('wpcfe_shipment_table_data_action', 'wpcargo_pod_dashboard_table_table_action', 25);
// Registrar modal para que se imprima después de la tabla de envíos
add_action('wpcfe_after_shipment_data', 'wpcargo_pod_after_admin_page_load_action', 10);
function wpcargo_pod_after_admin_page_load_action()
{
?>
	<!-- Modal -->
	<div class="modal fade top" id="wpc_pod_signature-modal" tabindex="-1" role="dialog" aria-labelledby="podModalPreview" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-frame modal-top" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="podModalPreview"><?php echo apply_filters('pod_modal_title', __('Proof of Delivery', 'wpcargo-pod')) ?></h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body my-4">
					<?php _e('Loading...', 'wpcargo-pod'); ?>
				</div>
			</div>
		</div>
	</div>
	<!-- Modal -->
<?php
	do_action('wpcargo_pod_after_sign_modal');
}
function wpcargo_pod_show_shignaturepad()
{
	global $wpcargo;
	$shipment_id 				= $_POST['sid'];
	$options  					= get_option('wpcargo_pod_option_settings');
	$shipper_selected_option 	= array();
	$receiver_selected_option 	= array();
	$wpcargo_get_status 		= get_post_meta($shipment_id, 'wpcargo_status', true);
	if (!empty($options) && array_key_exists('shipper_fields', $options)) {
		$shipper_selected_option = $options['shipper_fields'];
	}
	if (!empty($options) && array_key_exists('receiver_fields', $options)) {
		$receiver_selected_option = $options['receiver_fields'];
	}
	if (!current_user_can('upload_files')) {
		$user = get_role('wpcargo_driver');
		$user->add_cap('upload_files');
	}
	ob_start();
	require_once(wpcpod_include_template('wpc-pod-sign.tpl'));
	$output = ob_get_clean();
	echo $output;
	wp_die();
}

function wpcpod_get_form_values_by_name( $form_data, $target_name ) {
	$values = array();
	if ( ! is_array( $form_data ) ) {
		return $values;
	}
	foreach ( $form_data as $item ) {
		if ( ! is_array( $item ) || ! isset( $item['name'] ) ) {
			continue;
		}
		if ( $item['name'] !== $target_name ) {
			continue;
		}
		$values[] = isset( $item['value'] ) ? $item['value'] : '';
	}
	return $values;
}

function wpcpod_extract_payment_rows_from_form( $form_data ) {
	$methods = wpcpod_get_form_values_by_name( $form_data, 'pod_payment_method[]' );
	if ( empty( $methods ) ) {
		$methods = wpcpod_get_form_values_by_name( $form_data, 'pod_payment_method' );
	}
	$amounts = wpcpod_get_form_values_by_name( $form_data, 'pod_payment_amount[]' );
	if ( empty( $amounts ) ) {
		$amounts = wpcpod_get_form_values_by_name( $form_data, 'pod_payment_amount' );
	}
	$images = wpcpod_get_form_values_by_name( $form_data, 'pod_payment_image[]' );
	if ( empty( $images ) ) {
		$images = wpcpod_get_form_values_by_name( $form_data, 'pod_payment_image' );
	}

	$count = max( count( $methods ), count( $amounts ), count( $images ) );
	$rows = array();
	for ( $i = 0; $i < $count; $i++ ) {
		$method_id = isset( $methods[ $i ] ) ? (int) $methods[ $i ] : 0;
		$amount_raw = isset( $amounts[ $i ] ) ? (string) $amounts[ $i ] : '';
		$image_id = isset( $images[ $i ] ) ? (int) $images[ $i ] : 0;
		if ( ! $method_id && $amount_raw === '' && ! $image_id ) {
			continue;
		}
		$amount_raw = preg_replace('/[^0-9,\.\-]/', '', $amount_raw);
		$amount = (float) str_replace(',', '.', $amount_raw);
		$rows[] = array(
			'method_id' => $method_id,
			'amount'    => $amount,
			'image_id'  => $image_id,
		);
	}
	return $rows;
}

function wpcpod_validate_and_normalize_payment_rows( $shipment_id, $rows ) {
	global $wpdb;
	if ( ! is_array( $rows ) || empty( $rows ) ) {
		return new WP_Error( 'wpcpod_payment_required', __( 'Debe agregar al menos un metodo de pago.', 'wpcargo-pod' ) );
	}

	$table = $wpdb->prefix . 'wcfin_metodos_pago';
	$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	if ( $table_exists !== $table ) {
		return new WP_Error( 'wpcpod_payment_methods_unavailable', __( 'No se encontraron metodos de pago en el plugin de finanzas.', 'wpcargo-pod' ) );
	}

	$method_ids = array_values( array_unique( array_map( 'intval', wp_list_pluck( $rows, 'method_id' ) ) ) );
	$method_ids = array_filter( $method_ids );
	if ( empty( $method_ids ) ) {
		return new WP_Error( 'wpcpod_payment_method_missing', __( 'Debe seleccionar metodos de pago validos.', 'wpcargo-pod' ) );
	}

	$placeholders = implode( ',', array_fill( 0, count( $method_ids ), '%d' ) );
	$query = $wpdb->prepare( "SELECT id, nombre, slug FROM {$table} WHERE activo=1 AND id IN ({$placeholders})", $method_ids );
	$methods = $wpdb->get_results( $query, ARRAY_A );
	$methods_map = array();
	foreach ( (array) $methods as $method ) {
		$methods_map[ (int) $method['id'] ] = $method;
	}

	$seen = array();
	$total_assigned = 0.0;
	$normalized = array();
	foreach ( $rows as $row ) {
		$method_id = isset( $row['method_id'] ) ? (int) $row['method_id'] : 0;
		$amount = isset( $row['amount'] ) ? (float) $row['amount'] : 0.0;
		$image_id = isset( $row['image_id'] ) ? (int) $row['image_id'] : 0;

		if ( ! $method_id || ! isset( $methods_map[ $method_id ] ) ) {
			return new WP_Error( 'wpcpod_payment_invalid_method', __( 'Hay metodos de pago no validos.', 'wpcargo-pod' ) );
		}
		if ( isset( $seen[ $method_id ] ) ) {
			return new WP_Error( 'wpcpod_payment_duplicated_method', __( 'No se permite repetir metodos de pago.', 'wpcargo-pod' ) );
		}
		$seen[ $method_id ] = true;

		if ( $amount <= 0 ) {
			return new WP_Error( 'wpcpod_payment_invalid_amount', __( 'Todos los metodos deben tener un monto mayor a 0.', 'wpcargo-pod' ) );
		}

		$slug = (string) $methods_map[ $method_id ]['slug'];
		if ( $slug !== 'motorizado_efectivo' && $image_id <= 0 ) {
			return new WP_Error( 'wpcpod_payment_image_required', __( 'Debe subir comprobante en todos los metodos que lo requieren.', 'wpcargo-pod' ) );
		}

		$total_assigned += $amount;
		$normalized[] = array(
			'method_id'   => $method_id,
			'method_name' => (string) $methods_map[ $method_id ]['nombre'],
			'slug'        => $slug,
			'amount'      => (float) number_format( $amount, 2, '.', '' ),
			'image_id'    => $image_id,
			'image_url'   => $image_id ? (string) wp_get_attachment_url( $image_id ) : '',
		);
	}

	$total_raw = get_post_meta( (int) $shipment_id, 'monto', true );
	$total_raw = preg_replace('/[^0-9,\.\-]/', '', (string) $total_raw);
	$total_required = (float) str_replace(',', '.', $total_raw);

	if ( abs( $total_assigned - $total_required ) > 0.01 ) {
		return new WP_Error( 'wpcpod_payment_total_mismatch', __( 'La suma de metodos de pago debe ser igual al monto total a cobrar.', 'wpcargo-pod' ) );
	}

	return $normalized;
}

function wpcpod_get_finanzas_default_condicion_id() {
	global $wpdb;
	$table = $wpdb->prefix . 'wcfin_condiciones';
	$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	if ( $table_exists !== $table ) {
		return 0;
	}

	$condicion_id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$table} WHERE slug=%s AND activo=1 ORDER BY id ASC LIMIT 1",
			'contraentrega'
		)
	);
	if ( $condicion_id > 0 ) {
		return $condicion_id;
	}

	return (int) $wpdb->get_var( "SELECT id FROM {$table} WHERE activo=1 ORDER BY orden ASC, id ASC LIMIT 1" );
}

function wpcpod_sync_payments_to_finanzas( $shipment_id, $payment_rows ) {
	global $wpdb;

	$shipment_id = (int) $shipment_id;
	if ( ! $shipment_id || ! is_array( $payment_rows ) || empty( $payment_rows ) ) {
		return true;
	}

	$table_trans = $wpdb->prefix . 'wcfin_transacciones';
	$table_mov   = $wpdb->prefix . 'wcfin_movimientos';
	$table_regla = $wpdb->prefix . 'wcfin_reglas';

	$required_tables = array( $table_trans, $table_mov, $table_regla );
	foreach ( $required_tables as $table_name ) {
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
		if ( $table_exists !== $table_name ) {
			return true;
		}
	}

	$condicion_id = wpcpod_get_finanzas_default_condicion_id();
	if ( $condicion_id <= 0 ) {
		return new WP_Error( 'wpcpod_finanzas_condicion_missing', __( 'No se pudo determinar la condicion de pago en Finanzas.', 'wpcargo-pod' ) );
	}

	$pod_trans_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT id FROM {$table_trans} WHERE shipment_id=%d AND notas LIKE %s",
			$shipment_id,
			'[POD]%'
		)
	);
	if ( ! empty( $pod_trans_ids ) ) {
		$pod_trans_ids = array_map( 'intval', $pod_trans_ids );
		$id_list = implode( ',', $pod_trans_ids );
		$wpdb->query( "DELETE FROM {$table_mov} WHERE transaccion_id IN ({$id_list})" );
		$wpdb->query( "DELETE FROM {$table_trans} WHERE id IN ({$id_list})" );
	}

	foreach ( $payment_rows as $row ) {
		$method_id = isset( $row['method_id'] ) ? (int) $row['method_id'] : 0;
		$amount = isset( $row['amount'] ) ? (float) $row['amount'] : 0.0;
		if ( $method_id <= 0 || $amount <= 0 ) {
			continue;
		}

		$amount = (float) number_format( $amount, 2, '.', '' );
		$variables = array(
			'monto_total'         => $amount,
			'monto_servicio'      => 0.0,
			'monto_producto'      => $amount,
			'monto_contraentrega' => $amount,
			'origen'              => 'pod',
		);

		$method_name = isset( $row['method_name'] ) ? sanitize_text_field( (string) $row['method_name'] ) : '';
		$image_url = isset( $row['image_url'] ) ? esc_url_raw( (string) $row['image_url'] ) : '';
		$notas = '[POD] ' . ( $method_name ? $method_name : __( 'Metodo', 'wpcargo-pod' ) );
		if ( ! empty( $image_url ) ) {
			$notas .= ' | ' . $image_url;
		}

		$inserted = $wpdb->insert(
			$table_trans,
			array(
				'shipment_id'    => $shipment_id,
				'metodo_id'      => $method_id,
				'condicion_id'   => $condicion_id,
				'monto_servicio' => 0,
				'monto_total'    => $amount,
				'variables_json' => wp_json_encode( $variables ),
				'estado'         => 'confirmado',
				'notas'          => $notas,
				'creado_por'     => get_current_user_id(),
				'fecha_creacion' => current_time( 'mysql' ),
				'fecha_confirm'  => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%f', '%f', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error( 'wpcpod_finanzas_insert_trans_failed', __( 'No se pudo guardar la transaccion POD en Finanzas.', 'wpcargo-pod' ) . ' ' . $wpdb->last_error );
		}

		$trans_id = (int) $wpdb->insert_id;
		$reglas = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_regla}
				 WHERE metodo_id=%d AND (condicion_id=%d OR condicion_id IS NULL)
				 ORDER BY condicion_id DESC",
				$method_id,
				$condicion_id
			)
		);

		$used_accounts = array();
		$movement_count = 0;
		foreach ( (array) $reglas as $regla ) {
			$cuenta = isset( $regla->cuenta_afectada ) ? (string) $regla->cuenta_afectada : '';
			if ( empty( $cuenta ) || in_array( $cuenta, $used_accounts, true ) ) {
				continue;
			}
			$used_accounts[] = $cuenta;

			$base = isset( $regla->base_calculo ) ? (string) $regla->base_calculo : 'monto_total';
			$mov_amount = isset( $variables[ $base ] ) ? (float) $variables[ $base ] : 0.0;
			if ( $mov_amount <= 0 ) {
				continue;
			}

			$description_tpl = isset( $regla->descripcion_tpl ) ? (string) $regla->descripcion_tpl : '';
			$description = $description_tpl ? sprintf( $description_tpl, number_format( $mov_amount, 2 ) ) : '[POD] Movimiento automático';
			$mov_inserted = $wpdb->insert(
				$table_mov,
				array(
					'transaccion_id' => $trans_id,
					'shipment_id'    => $shipment_id,
					'cuenta'         => $cuenta,
					'monto'          => $mov_amount,
					'signo'          => isset( $regla->signo ) ? (int) $regla->signo : 1,
					'descripcion'    => $description,
					'tipo'           => 'transaccion',
					'fecha'          => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%s', '%f', '%d', '%s', '%s', '%s' )
			);

			if ( false === $mov_inserted ) {
				return new WP_Error( 'wpcpod_finanzas_insert_mov_failed', __( 'No se pudo guardar movimientos POD en Finanzas.', 'wpcargo-pod' ) . ' ' . $wpdb->last_error );
			}
			$movement_count++;
		}

		if ( $movement_count === 0 ) {
			$wpdb->insert(
				$table_mov,
				array(
					'transaccion_id' => $trans_id,
					'shipment_id'    => $shipment_id,
					'cuenta'         => 'balance_motorizado',
					'monto'          => $amount,
					'signo'          => 1,
					'descripcion'    => '[POD] Movimiento automático por ausencia de reglas',
					'tipo'           => 'transaccion',
					'fecha'          => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%s', '%f', '%d', '%s', '%s', '%s' )
			);
		}
	}

	return true;
}

function wpcpod_backfill_existing_pod_payments_to_finanzas() {
	$backfill_flag = get_option( 'wpcpod_finanzas_backfill_done_v1', '' );
	if ( ! empty( $backfill_flag ) ) {
		return;
	}

	global $wpdb;
	$table_trans = $wpdb->prefix . 'wcfin_transacciones';
	$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_trans ) );
	if ( $table_exists !== $table_trans ) {
		return;
	}

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key=%s",
			'wpcargo-pod-payments'
		)
	);

	if ( empty( $rows ) ) {
		update_option( 'wpcpod_finanzas_backfill_done_v1', current_time( 'mysql' ), false );
		return;
	}

	$ok = 0;
	$failed = 0;
	foreach ( $rows as $row ) {
		$shipment_id = isset( $row->post_id ) ? (int) $row->post_id : 0;
		if ( $shipment_id <= 0 ) {
			continue;
		}

		$payment_rows = maybe_unserialize( $row->meta_value );
		if ( ! is_array( $payment_rows ) || empty( $payment_rows ) ) {
			continue;
		}

		$sync = wpcpod_sync_payments_to_finanzas( $shipment_id, $payment_rows );
		if ( is_wp_error( $sync ) ) {
			$failed++;
			continue;
		}
		$ok++;
	}

	update_option( 'wpcpod_finanzas_backfill_done_v1', current_time( 'mysql' ) . '|ok:' . $ok . '|failed:' . $failed, false );
}
add_action( 'init', 'wpcpod_backfill_existing_pod_payments_to_finanzas', 25 );

function wpcargo_pod_signed_load_action()
{
	global $wpcargo;
	if (!wp_verify_nonce($_POST['nonce'], 'wpcpod_signature-nonce')) {
		wp_send_json(array(
			'status' => 'error',
			'message' => __('Something went wrong while processing your request, please reload the page and try again', 'wpcargo-pod')
		));
		wp_die();
	}

	$history_metakeys 		= array_keys(wpcpod_signature_field_list());
	$current_user 			= wp_get_current_user();
	$form_data 				= $_POST['formData'];
	$shipment_id 			= wpcpod_find_metakey($form_data, '__pod_id');
	$shipment_id 			= $shipment_id ? (int)$shipment_id['value'] : 0;
	$payment_rows 			= wpcpod_extract_payment_rows_from_form( $form_data );
	$payment_rows 			= wpcpod_validate_and_normalize_payment_rows( $shipment_id, $payment_rows );
	if ( is_wp_error( $payment_rows ) ) {
		wp_send_json(array(
			'status' => 'error',
			'message' => $payment_rows->get_error_message()
		));
		wp_die();
	}
	$signature_id 			= wpcpod_find_metakey($form_data, '__pod_signature');
	$signature_id 			= $signature_id ? (int)$signature_id['value'] : false;
	if ( ! $signature_id ) {
		wp_send_json(array(
			'status' => 'error',
			'message' => __('Debe existir una firma generada para actualizar el POD.', 'wpcargo-pod')
		));
		wp_die();
	}
	$saved_images = get_post_meta( $shipment_id, 'wpcargo-pod-image', true );
	$saved_images = array_filter( array_map( 'trim', explode( ',', (string) $saved_images ) ) );
	if ( empty( $saved_images ) ) {
		wp_send_json(array(
			'status' => 'error',
			'message' => __('Debe agregar al menos una imagen POD antes de actualizar.', 'wpcargo-pod')
		));
		wp_die();
	}
	$shipment_status 		= 'Entregado';

	// Save Shipment History
	$history 				= maybe_unserialize(get_post_meta($shipment_id, 'wpcargo_shipments_update', true));
	$history				= $history && is_array($history) ? $history : array();
	$pod_history 			= array(
		'date'          => current_time($wpcargo->date_format),
		'time' 			=> current_time($wpcargo->time_format),
		'updated-by' 	=> $current_user->ID,
		'updated-name' 	=> $wpcargo->user_fullname($current_user->ID),
	);

	if (!empty($history_metakeys)) {
		foreach ($history_metakeys as $sign_key) {
			$meta_data = wpcpod_find_metakey($form_data, $sign_key);
			if( $sign_key === 'status' ){
				$pod_history[$sign_key] = 'Entregado';
				continue;
			}
			$pod_history[$sign_key] = $meta_data ? $meta_data['value'] : '';
		}
	}
	$pod_history		= apply_filters('wpcargo_pod_current_history', $pod_history);
	$history[] 			= $pod_history;
	update_post_meta($shipment_id,	'wpcargo_shipments_update',	$history);
	// Save Signature
	if ($signature_id) {
		update_post_meta($shipment_id,	'wpcargo-pod-signature', $signature_id);
	}
	update_post_meta( $shipment_id, 'wpcargo-pod-payments', $payment_rows );
	$finanzas_sync = wpcpod_sync_payments_to_finanzas( $shipment_id, $payment_rows );
	if ( is_wp_error( $finanzas_sync ) ) {
		wp_send_json(array(
			'status' => 'error',
			'message' => $finanzas_sync->get_error_message()
		));
		wp_die();
	}
	// Save Shipment Status
	update_post_meta($shipment_id,	'wpcargo_status', $shipment_status);

	do_action('wpcargo_extra_pod_saving', $shipment_id, $form_data);

	// Save Custom Field data
	foreach ($form_data as $data) {
		$data_value = is_array($data['value']) ? $data_value : sanitize_text_field($data['value']);
		$data_info 	= wpcpod_custom_fields_data($data['name']);
		$data_info 	= apply_filters('wpcpod_custom_fields_data_results', $data_info, $data);
		if (!$data_info) {
			continue;
		}
		if ($data_info->field_type == 'file') {
			$file_data = get_post_meta($shipment_id, $data_info->field_key, true);
			$file_data = explode(",", $file_data);
			$file_data = array_filter(array_map('trim', $file_data));
			$file_data[] = $data['value'];
			$file_str = implode(',', $file_data);
			update_post_meta($shipment_id, $data_info->field_key, $file_str);
			continue;
		}
		if ($data_info->field_type == 'textarea') {
			$data_value = sanitize_textarea_field($data['value']);
		}
		update_post_meta($shipment_id, $data_info->field_key, $data_value);
	}
	wpcargo_send_email_notificatio($shipment_id, $shipment_status);
	do_action('wpcargo_extra_send_email_notification', $shipment_id, $shipment_status);
	do_action('wpc_add_sms_notification', $shipment_id);
	wp_send_json(array(
		'status' => 'success',
		'message' => sprintf(__('Shipment %s successfully udpated!', 'wpcargo-pod'), get_the_title($shipment_id))
	));
	wp_die();
}

// Save Image
function wpcpod_delete_image()
{
	$shipmentID 		= $_REQUEST['shipmentID'];
	$attchID 			= $_REQUEST['attchID'];
	$saved_images       = get_post_meta($shipmentID, 'wpcargo-pod-image', true);
	$arr_images     	= !empty($saved_images) ? explode(',', $saved_images) : array();
	if (($key = array_search($attchID, $arr_images)) !== false) {
		unset($arr_images[$key]);
	}
	$return  = update_post_meta($shipmentID, 'wpcargo-pod-image', implode(',', $arr_images));
	$message = $return ? sprintf(__('Attachment successfully removed in %s', 'wpcargo-pod'), get_the_title($shipmentID)) : sprintf(__('Attachment failed removed in %s', 'wpcargo-pod'), get_the_title($shipmentID));
	wp_send_json(array(
		'status' 	=> $return,
		'message' 	=> $message,
		'shipmentID' => $shipmentID,
		'attchID'	=> $attchID,
		'$arr_images' => $$arr_images
	));
	die();
}
add_action('wp_ajax_wpcpod_delete_image', 'wpcpod_delete_image');
add_action('wp_ajax_nopriv_wpcpod_delete_image', 'wpcpod_delete_image');

function wpcpod_save_attachment()
{
	$shipmentID 		= $_REQUEST['shipmentID'];
	$attachments 		= $_REQUEST['attachments'];
	$saved_images       = get_post_meta($shipmentID, 'wpcargo-pod-image', true);
	$explode_images     = !empty($saved_images) ? explode(',', $saved_images) : array();
	$set_attachments 	= array_unique(array_merge($attachments, array_filter($explode_images)));

	update_post_meta($shipmentID, 'wpcargo-pod-image', implode(',', $set_attachments));
	if (isset($attachments)) {
		echo '<p class="header-pod-result">' . __('Your current captured images', 'wpcargo-pod') . ':</p>';
		foreach ($set_attachments as $attachment) {
			echo '<div class="gallery-thumb" data-id="' . $attachment . '"><div class="single-img">';
			echo wp_get_attachment_image($attachment, 'wpcargo-pod-images');
			echo '</div><span class="delete-attachment" title="Remove">x</span></div>';
		}
	}
	die();
}
add_action('wp_ajax_wpcpod_save_attachment', 'wpcpod_save_attachment');
add_action('wp_ajax_nopriv_wpcpod_save_attachment', 'wpcpod_save_attachment');

function wpcpod_upload_image() {
	if ( ! empty($_REQUEST['nonce']) && ! wp_verify_nonce($_REQUEST['nonce'], 'wpcpod_signature-nonce') ) {
		wp_send_json(array(
			'status' => 'error',
			'message' => __('Token inválido. Recargue la página e intente nuevamente.', 'wpcargo-pod')
		));
		wp_die();
	}

	if ( empty($_FILES['pod_file']) || ! isset($_FILES['pod_file']['tmp_name']) ) {
		wp_send_json(array(
			'status' => 'error',
			'message' => __('No se recibió ningún archivo.', 'wpcargo-pod')
		));
		wp_die();
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$attachment_id = media_handle_upload('pod_file', 0);
	if ( is_wp_error($attachment_id) ) {
		wp_send_json(array(
			'status' => 'error',
			'message' => $attachment_id->get_error_message()
		));
		wp_die();
	}

	wp_send_json(array(
		'status' => 'success',
		'attachment_id' => (int) $attachment_id,
		'url' => (string) wp_get_attachment_url($attachment_id)
	));
	wp_die();
}
add_action('wp_ajax_wpcpod_upload_image', 'wpcpod_upload_image');
add_action('wp_ajax_nopriv_wpcpod_upload_image', 'wpcpod_upload_image');
// AJAX handlers for signature pad (called from pod-scripts.js)
add_action('wp_ajax_show_signaturepad', 'wpcargo_pod_show_shignaturepad');
add_action('wp_ajax_nopriv_show_signaturepad', 'wpcargo_pod_show_shignaturepad');
add_action('wp_ajax_pod_signed', 'wpcargo_pod_signed_load_action');
add_action('wp_ajax_nopriv_pod_signed', 'wpcargo_pod_signed_load_action');

function wpcargo_pod_assign_driver_save($shipment_id, $data)
{
	if (isset($data['wpcargo_driver']) && (int)$data['wpcargo_driver'] && can_wpcfe_assign_driver()) {
		$old_driver = get_post_meta($shipment_id, 'wpcargo_driver', true);
		update_post_meta($shipment_id, 'wpcargo_driver', (int)$data['wpcargo_driver']);
		// check if the driver is changed Send email notification
		if ($old_driver != (int)$data['wpcargo_driver'] && wpcargo_pod_can_send_email_driver()) {
			wpcargo_assign_shipment_email($shipment_id, (int)$data['wpcargo_driver'], __('Driver', 'wpcargo-pod'));
		}
	} elseif (isset($data['wpcargo_driver']) && !(int)$data['wpcargo_driver']) {
		update_post_meta($shipment_id, 'wpcargo_driver', '');
	}
}
function wpcargo_pod_assign_driver_dropdown($shipment_id)
{
	$assigned_driver = get_post_meta($shipment_id, 'wpcargo_driver', true);
	if (!can_wpcfe_assign_driver()) {
		return false;
	}
?>
	<div class="form-group">
		<div class="select-no-margin">
			<label><?php esc_html_e('Driver', 'wpcargo-pod'); ?></label>
			<select id="wpcargo_driver" name="wpcargo_driver" class="form-control browser-default mdb-select">
				<option value=""><?php echo apply_filters('pod_assign_vehicle_label', __('-- Select Driver --', 'wpcargo-pod')); ?></option>
				<?php foreach (wpcargo_pod_get_drivers() as $driverID => $driver_name): ?>
					<option value="<?php echo $driverID; ?>" <?php selected(get_post_meta($shipment_id, 'wpcargo_driver', true), $driverID); ?>><?php echo $driver_name; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
<?php
}
function wpcargo_pod_wpcfe_bulk_update_form_fields()
{
?>
	<div class="form-group">
		<div class="select-no-margin">
			<label><?php _e('Driver', 'wpcargo-pod'); ?></label>
			<select id="wpcargo_driver" name="wpcargo_driver" class="form-control browser-default mdb-select">
				<option value=""><?php echo apply_filters('pod_assign_vehicle_label', __('-- Select Driver --', 'wpcargo-pod')); ?></option>
				<?php foreach (wpcargo_pod_get_drivers() as $driverID => $driver_name): ?>
					<option value="<?php echo $driverID; ?>"><?php echo $driver_name; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
<?php
}
function wpcargo_pod_can_send_email_driver()
{
	$gen_settings = get_option('wpcargo_option_settings');
	$email_driver = !array_key_exists('wpcargo_email_driver', $gen_settings) ? true : false;
	return $email_driver;
}
function wpcargo_pod_assign_email_options($options)
{
?>
	<tr>
		<th><?php esc_html_e('Disable Email for Driver?', 'wpcargo-pod'); ?></th>
		<td>
			<input type="checkbox" name="wpcargo_option_settings[wpcargo_email_driver]" <?php echo (!empty($options['wpcargo_email_driver']) && $options['wpcargo_email_driver'] != NULL) ? 'checked' : ''; ?> />
		</td>
	</tr>
<?php
}
// Sidebar Menu
add_action('wp_loaded', 'wpcargo_pod_create_pages');
function wpcargo_pod_create_pages()
{
	wpcargo_pod_create_report_page();
	wpcpod_route_page();
	wpcpod_pickup_route_page();
	wpcpod_set_driver_access();
}
function wpcargo_pod_generate_page($post_title, $post_name, $post_content)
{
	$page_args    = array(
		'comment_status' => 'closed',
		'ping_status' 	=> 'closed',
		'post_author' 	=> 1,
		'post_date' 	=> date('Y-m-d H:i:s'),
		'post_content' 	=> $post_content,
		'post_name' 	=> $post_name,
		'post_status' 	=> 'publish',
		'post_title' 	=> $post_title,
		'post_type' 	=> 'page',
	);
	$page_id = wp_insert_post($page_args, false);
	update_post_meta($page_id, '_wp_page_template', 'dashboard.php');
	return $page_id;
}
function is_wpcpod_page_exist($shortcode)
{

	global $wpdb;
	$sql = "SELECT `ID` FROM `{$wpdb->prefix}posts` WHERE `post_status` LIKE 'publish' AND `post_type` LIKE 'page' AND `post_content` LIKE %s LIMIT 1";

	return $wpdb->get_var($wpdb->prepare($sql, '%' . $shortcode . '%'));
}
function wpcargo_pod_create_report_page()
{
	$wpcpod_page_report 	= get_option('wpcpod_page_report');

	if (!$wpcpod_page_report) {
		$page_id = is_wpcpod_page_exist('[wpcpod_report]');
		update_option('wpcpod_page_report', $page_id, '', 'yes');

		if ($page_id) {
			return $page_id;
		}

		$post_title 	= __('Driver Report', 'wpcargo-pod');
		$post_name 		= 'wpcpod-report-order';
		$post_content 	= '[wpcpod_report]';
		$page_id = wpcargo_pod_generate_page($post_title, $post_name, $post_content);
		update_option('wpcpod_page_report', $page_id, 'yes');
	}

	return $wpcpod_page_report;
}

function wpcpod_route_page()
{
	$wpcpod_route_page = get_option('wpcpod_route_page');

	if (!$wpcpod_route_page) {
		$page_id = is_wpcpod_page_exist('[wpcpod_route]');
		update_option('wpcpod_route_page', $page_id, 'yes');

		if ($page_id) {
			return $page_id;
		}

		$post_title 	= __('Driver Route Planner', 'wpcargo-pod');
		$post_name 		= 'wpcpo-route';
		$post_content 	= '[wpcpod_route]';
		$page_id = wpcargo_pod_generate_page($post_title, $post_name, $post_content);
		update_option('wpcpod_route_page', $page_id, 'yes');
	}

	return $wpcpod_route_page;
}

function wpcpod_pickup_route_page()
{
	$wpcpod_pickup_route_page = get_option('wpcpod_pickup_route_page');

	if (!$wpcpod_pickup_route_page) {
		$page_id = is_wpcpod_page_exist('[wpcpod_pickup_route]');
		update_option('wpcpod_pickup_route_page', $page_id, 'yes');

		if ($page_id) {
			return $page_id;
		}

		$post_title 	= __('Pickup Driver Route Planner', 'wpcargo-pod');
		$post_name 		= 'wpcpo-pickup-route';
		$post_content 	= '[wpcpod_pickup_route]';
		$page_id = wpcargo_pod_generate_page($post_title, $post_name, $post_content);
		update_option('wpcpod_pickup_route_page', $page_id, 'yes');
	}

	return $wpcpod_pickup_route_page;
}

function wpcpod_set_driver_access()
{
	$dashboard_role = get_option('wpcfe_access_dashboard_role');
	$dashboard_role = !empty($dashboard_role) && is_array($dashboard_role) ? $dashboard_role : array();
	if (!in_array('wpcargo_driver', $dashboard_role)) {
		$dashboard_role[] = 'wpcargo_driver';
		update_option('wpcfe_access_dashboard_role', $dashboard_role);
	}
}
function wpcargo_pod_sidebar_menu($menu_array)
{
	if (!function_exists('wpcfe_admin_page')) {
		return false;
	}
	$current_user = wp_get_current_user();
	$current_roles = is_a($current_user, 'WP_User') ? (array) $current_user->roles : array();
	$is_driver_sidebar_user = in_array('wpcargo_diver', $current_roles, true) || in_array('wpcargo_driver', $current_roles, true);
	$delivery_label = $is_driver_sidebar_user ? __('Lista de Entregas', 'wpcargo-pod') : __('Delivery Route', 'wpcargo-pod');
	$pickup_label = $is_driver_sidebar_user ? __('Lista de Recojos', 'wpcargo-pod') : __('Pickup Route', 'wpcargo-pod');

	if (wpcpod_route_allowed_user()) {
		$wpcpod_route_class = 'wpcpod-route';
		$wpcpod_pickup_route_class = 'wpcpod-pickup-route';
		if (wpcpod_route_page() == get_the_ID()) {
			$wpcpod_route_class .= ' active';
		}
		if (wpcpod_pickup_route_page() == get_the_ID()) {
			$wpcpod_pickup_route_class .= ' active';
		}
		$menu_array[$wpcpod_route_class] = array(
			'label' => apply_filters('wpcpod_delivery_driver_route_sidemenu_label', $delivery_label),
			'permalink' => get_the_permalink(wpcpod_route_page()),
			'icon' => 'fa-map-o'
		);
		$menu_array[$wpcpod_pickup_route_class] = array(
			'label' => apply_filters('wpcpod_pickup_driver_route_sidemenu_label', $pickup_label),
			'permalink' => get_the_permalink(wpcpod_pickup_route_page()),
			'icon' => 'fa-map-o'
		);
	}

	if (can_export_wpcpod_report()) {
		$wpcpod_report_class = 'wpcpod-menu';
		if (wpcargo_pod_create_report_page() == get_the_ID()) {
			$wpcpod_report_class .= ' active';
		}
		$menu_array[$wpcpod_report_class] = array(
			'label' => __('Driver Report', 'wpcargo-pod'),
			'permalink' => get_the_permalink(wpcargo_pod_create_report_page()),
			'icon' => 'fa-cloud-download'
		);
	}

	return $menu_array;
}
add_filter('wpcfe_after_sidebar_menus', 'wpcargo_pod_sidebar_menu', 10, 1);
function wpcpod_dashboard_route_script_callback()
{
	if (empty(get_option('shmap_api')) || !wpcpod_route_allowed_user()) {
		return false;
	}
	include_once(wpcpod_include_template('route-planner-script'));
}
add_action('wpcpod_after_route_planner', 'wpcpod_dashboard_route_script_callback');
function wpcpod_pickup_dashboard_route_script_callback()
{
	if (empty(get_option('shmap_api')) || !wpcpod_route_allowed_user()) {
		return false;
	}
	include_once(wpcpod_include_template('pickup-route-planner-script'));
}
add_action('wpcpod_pickup_after_route_planner', 'wpcpod_pickup_dashboard_route_script_callback');
// Driver Report page restriction
function driver_report_page_restriction()
{
	global $post;
	if (!$post) {
		return false;
	}
	if (wpcargo_pod_create_report_page() == $post->ID && !can_export_wpcpod_report()) {
		$dashboard = get_the_permalink(wpcfe_admin_page());
		wp_redirect($dashboard);
		die;
	}
}
add_action('template_redirect', 'driver_report_page_restriction');
function wpcpod_after_sign_popup_form_callback()
{
	$shmap_api          		= get_option('shmap_api');
	$shmap_country_restrict     = get_option('shmap_country_restrict');
	if (empty($shmap_api)) {
		return;
	}
?>
	<script>
		/*
		 ** Google map Script Auto Complete location
		 */
		function wpcpodGetPlaceDynamic() {
			var defaultBounds = new google.maps.LatLngBounds(
				new google.maps.LatLng(-33.8902, 151.1759),
				new google.maps.LatLng(-33.8474, 151.2631)
			);
			var input = document.getElementsByClassName('wpcargo-pod-location');
			var options = {
				bounds: defaultBounds,
				types: ['geocode'],
				<?php if (!empty($shmap_country_restrict)): ?>
					componentRestrictions: {
						country: "<?php echo $shmap_country_restrict; ?>"
					}
				<?php endif; ?>
			};
			for (i = 0; i < input.length; i++) {
				autocomplete = new google.maps.places.Autocomplete(input[i], options);
			}
			<?php do_action('wpcpod_after_get_dynamic_place'); ?>
		}
	</script>
<?php
	echo wpcargo_map_script('wpcpodGetPlaceDynamic');
}
add_action('wpcpod_after_sign_popup_form', 'wpcpod_after_sign_popup_form_callback', 10);

function allow_drivers_to_upload_files()
{

	$role = get_role('wpcargo_driver');
	if ($role && !$role->has_cap('upload_files')) {
		$role->add_cap('upload_files');
	}
	if ($role && !$role->has_cap('edit_posts')) {
		$role->add_cap('edit_posts');
	}
}

add_action('init', 'allow_drivers_to_upload_files');



// function remove_admin_bar()
// {
//     if (!current_user_can('edit_posts') || get_role('wpcargo_driver') || get_role('wpcargo_client')) {
//         return true;
//     }
//     return false;
// }

//add_filter('show_admin_bar', 'remove_admin_bar', PHP_INT_MAX);

add_action('init', 'blockusers_init');
function blockusers_init()
{
	if (is_admin() && !current_user_can('administrator') && !(defined('DOING_AJAX') && DOING_AJAX)) {
		wp_redirect(home_url());
		exit;
	}
}
