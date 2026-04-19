<?php
	$signature_fields 		= wpcpod_signature_field_list();
	$get_sid 				= $shipment_id;
	$get_pod_img 			= get_post_meta($get_sid, 'wpcargo-pod-image', true);
	$pod_signature 			= get_post_meta($get_sid, 'wpcargo-pod-signature', true);
	$pod_payment_rows 		= get_post_meta($get_sid, 'wpcargo-pod-payments', true);
	$pod_payment_rows 		= is_array($pod_payment_rows) ? $pod_payment_rows : array();
	$monto_total_cobrar 	= get_post_meta($get_sid, 'monto', true);
	$monto_total_cobrar 	= (float) str_replace(',', '.', preg_replace('/[^0-9.,\-]/', '', (string) $monto_total_cobrar));
	$wcfin_methods 			= array();
	if ( class_exists('WCFIN_Metodo') ) {
		$wcfin_active = WCFIN_Metodo::obtener_activos();
		if ( is_array($wcfin_active) ) {
			foreach ( $wcfin_active as $method ) {
				$wcfin_methods[] = array(
					'id' => isset($method->id) ? (int) $method->id : 0,
					'nombre' => isset($method->nombre) ? (string) $method->nombre : '',
					'slug' => isset($method->slug) ? (string) $method->slug : ''
				);
			}
		}
	} else {
		global $wpdb;
		if ( isset($wpdb) ) {
			$methods_table = $wpdb->prefix . 'wcfin_metodos_pago';
			$table_exists = $wpdb->get_var( $wpdb->prepare('SHOW TABLES LIKE %s', $methods_table) );
			if ( $table_exists === $methods_table ) {
				$wcfin_methods = $wpdb->get_results("SELECT id, nombre, slug FROM {$methods_table} WHERE activo=1 ORDER BY orden, id", ARRAY_A);
			}
		}
	}
	$shipment_update 		= maybe_unserialize( get_post_meta( $get_sid, 'wpcargo_shipments_update', true ) );
	$shipment_update 		= $shipment_update && is_array( $shipment_update ) ? wpcargo_history_order( $shipment_update )[0] : array();
?>
<?php do_action( 'wpcpod_before_sign_popup_form' ); ?>
<form id="wpc_pod_signature-form" method="post" action="">
	<input type="hidden" id="__pod_id" name="__pod_id" value="<?php echo $get_sid;?>">
	<input type="hidden" id="__pod_signature" name="__pod_signature" value="<?php echo $pod_signature; ?>">	
	<div id="pod-pop-up">
		<?php do_action( 'wpcpod_before_popup_header' ); ?>
		<?php	
		if ( is_plugin_active( 'wpcargo-custom-field-addons/wpcargo-custom-field.php' ) ) {
			require_once(WPCARGO_POD_PATH.'templates/wpc-pod-sign-header-cf.tpl.php');
		}else{
			require_once(WPCARGO_POD_PATH.'templates/wpc-pod-sign-header.tpl.php');
		}
		?>
		<?php do_action( 'wpcpod_after_popup_header', $get_sid ); ?>
		<?php do_action( 'wpcpod_before_upload_container', $get_sid ); ?>
		<div class="wpcargo-upload container">
			<div class="wpcargo-add-signature">
				<?php require_once( WPCARGO_POD_PATH.'templates/wpc-pod-signature-form.tpl.php'); ?>
			</div>	
			<div id="images-section">
				<a href="#" id="wpcargo-pod-img-btn" class="wpcargo-btn wpcargo-btn-success"><?php esc_html_e( 'ADD IMAGES', 'wpcargo-pod' ); ?></a>	
				<input type="file" id="wpcargo-pod-img-input" accept="image/*" capture="environment" multiple style="display:none;">
				<div id="wpcargo-pod-images">			
					<p class="header-pod-result"><?php esc_html_e('Your current captured images:', 'wpcargo-pod' ); ?></p>
					<?php
					if(!empty($get_pod_img)) {
						$explode_pod_img = array_filter( explode(",", $get_pod_img) );
						if(is_array($explode_pod_img)) {
							foreach($explode_pod_img as $pod_img) {
								echo '<div class="gallery-thumb" data-id="'.$pod_img.'"><div class="single-img"><img width="250" src="'.wp_get_attachment_url( $pod_img ).'"/></div><span class="delete-attachment" title="Remove">x</span></div>';
							}
						}
					} else {
						?><img src="<?php echo WPCARGO_POD_URL. 'assets/img/no-image.jpg'; ?>"><?php
					}
					?>	
				</div>
			</div>
		</div>
		<?php do_action( 'wpcpod_after_upload_container', $get_sid ); ?>
		<?php do_action( 'wpcpod_before_status_container', $get_sid ); ?>
		<div class="pod-status container">	
			<div class="pod-details row">
				<?php foreach( $signature_fields as $metakey => $fieldinfo ): ?>
					<?php 
						$field_value = array_key_exists( $metakey, $shipment_update ) ? $shipment_update[$metakey] : '' ; 
						$class 		 = $fieldinfo['field'] != 'select' ? 'form-control' : 'form-control browser-default' ;
					?>
					<div class="col-md-6 mb-4">
						<p>
							<label><?php echo $fieldinfo['label']; ?> </label><br/>
							<?php echo wpcargo_field_generator( $fieldinfo, $metakey, $field_value, $class .' '.$metakey ); ?>
						</p>		
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<div class="pod-payments container mt-3">
			<div class="card">
				<div class="card-body">
					<div class="d-flex justify-content-between align-items-center flex-wrap">
						<h6 class="mb-2">Métodos de pago</h6>
						<button type="button" class="btn btn-sm btn-primary" id="pod-add-payment-method">Agregar metodos de pagos</button>
					</div>
					<p class="text-muted mb-2" id="pod-payment-help">Selecciona uno o más métodos hasta completar el monto total a cobrar.</p>
					<div id="pod-payment-cards"
						data-total="<?php echo esc_attr( number_format( $monto_total_cobrar, 2, '.', '' ) ); ?>"
						data-max="<?php echo esc_attr( count( $wcfin_methods ) ); ?>"></div>
					<div class="small mt-2">
						<div><strong>Total a cobrar:</strong> <span id="pod-total-required">S/ <?php echo esc_html( number_format( $monto_total_cobrar, 2 ) ); ?></span></div>
						<div><strong>Total asignado:</strong> <span id="pod-total-assigned">S/ 0.00</span></div>
					</div>
					<div id="pod-payment-errors" class="text-danger small mt-2"></div>
				</div>
			</div>
		</div>
		<?php do_action( 'wpcpod_after_status_container', $get_sid ); ?>
		<div class="pod-submit container">	
			<div class="status-btn pt-sm-4">
				<input type="submit" class="delivered-btn btn btn-success" name="submit" value="<?php esc_html_e('Update', 'wpcargo-pod' ); ?>" disabled>
			</div>
		</div>
    </div>
</form>
<?php do_action( 'wpcpod_after_sign_popup_form' ); ?>
<script>
	jQuery(document).ready(function ($) {
		const financeMethods = <?php echo wp_json_encode( $wcfin_methods ); ?>;
		const initialPaymentRows = <?php echo wp_json_encode( $pod_payment_rows ); ?>;
		const shipmentID 	= $( '[name="__pod_id"]' ).val();
		const AJAXHANDLER 	= '<?php echo admin_url( 'admin-ajax.php' ); ?>';
		const $paymentCards = $('#pod-payment-cards');
		const maxPaymentRows = Number($paymentCards.data('max') || 0);
		const requiredTotal = Number($paymentCards.data('total') || 0);
		const $submitBtn = $('#wpc_pod_signature-form .delivered-btn');

		function parseMoney(val){
			if(val === null || val === undefined){
				return 0;
			}
			const normalized = String(val).replace(',', '.').replace(/[^0-9.\-]/g, '');
			const parsed = parseFloat(normalized);
			return isNaN(parsed) ? 0 : parsed;
		}

		function formatMoney(val){
			return 'S/ ' + Number(val || 0).toFixed(2);
		}

		function selectedMethodIds(){
			const ids = [];
			$paymentCards.find('.pod-payment-method-select').each(function(){
				const v = parseInt($(this).val(), 10);
				if(v){
					ids.push(v);
				}
			});
			return ids;
		}

		function refreshSelectOptions(){
			const selected = selectedMethodIds();
			$paymentCards.find('.pod-payment-method-select').each(function(){
				const current = parseInt($(this).val(), 10) || 0;
				$(this).find('option[data-method-id]').each(function(){
					const optionId = parseInt($(this).attr('data-method-id'), 10);
					const shouldDisable = selected.indexOf(optionId) !== -1 && optionId !== current;
					$(this).prop('disabled', shouldDisable);
				});
			});
		}

		function paymentRowTemplate(row){
			let options = '<option value="">Seleccione método</option>';
			financeMethods.forEach(function(method){
				const selected = Number(row.method_id || 0) === Number(method.id) ? ' selected' : '';
				options += '<option data-method-id="'+ method.id +'" data-method-slug="'+ String(method.slug || '') +'" value="'+ method.id +'"'+ selected +'>'+ String(method.nombre || '') +'</option>';
			});

			const amountVal = row.amount ? String(row.amount) : '';
			const imageVal = row.image_id ? String(row.image_id) : '';
			const imageText = row.image_url ? 'Comprobante seleccionado' : 'Sin comprobante';
			const hasImage = row.image_id ? 'has-image' : '';

			return '' +
				'<div class="card mt-2 pod-payment-row">' +
					'<div class="card-body">' +
						'<div class="form-group mb-2">' +
							'<label class="mb-1">Método de pago</label>' +
							'<select class="form-control browser-default pod-payment-method-select" name="pod_payment_method[]">' + options + '</select>' +
						'</div>' +
						'<div class="pod-payment-extra d-none">' +
							'<div class="form-group mb-2">' +
								'<label class="mb-1">Monto</label>' +
								'<input type="number" step="0.01" min="0" class="form-control pod-payment-amount" name="pod_payment_amount[]" value="'+ amountVal +'" />' +
							'</div>' +
							'<div class="form-group mb-2 pod-payment-image-group">' +
								'<label class="mb-1">Comprobante</label><br>' +
								'<button type="button" class="btn btn-sm btn-outline-secondary pod-payment-upload">Subir imagen</button>' +
								'<input type="file" class="pod-payment-file-input" accept="image/*" capture="environment" style="display:none;" />' +
								'<input type="hidden" class="pod-payment-image-id" name="pod_payment_image[]" value="'+ imageVal +'" />' +
								'<span class="pod-payment-image-state ml-2 '+ hasImage +'">'+ imageText +'</span>' +
							'</div>' +
						'</div>' +
						'<button type="button" class="btn btn-sm btn-link text-danger p-0 pod-payment-remove">Quitar</button>' +
					'</div>' +
				'</div>';
		}

		function updateRowByMethod($row){
			const $selected = $row.find('.pod-payment-method-select option:selected');
			const methodId = parseInt($selected.val(), 10);
			if(!methodId){
				$row.find('.pod-payment-extra').addClass('d-none');
				$row.find('.pod-payment-amount').val('');
				$row.find('.pod-payment-image-id').val('');
				$row.find('.pod-payment-image-state').removeClass('has-image').text('Sin comprobante');
				$row.find('.pod-payment-image-group').removeClass('d-none');
				return;
			}

			const slug = String($selected.attr('data-method-slug') || '');
			$row.find('.pod-payment-extra').removeClass('d-none');
			if(slug === 'motorizado_efectivo'){
				$row.find('.pod-payment-image-group').addClass('d-none');
				$row.find('.pod-payment-image-id').val('');
				$row.find('.pod-payment-image-state').removeClass('has-image').text('Sin comprobante');
			}else{
				$row.find('.pod-payment-image-group').removeClass('d-none');
			}
		}

		function validatePodCompletion(){
			const errors = [];
			let totalAssigned = 0;
			const methodIds = [];
			const $rows = $paymentCards.find('.pod-payment-row');

			if($rows.length === 0){
				errors.push('Debe agregar al menos un método de pago.');
			}

			$rows.each(function(){
				const $row = $(this);
				const $selected = $row.find('.pod-payment-method-select option:selected');
				const methodId = parseInt($selected.val(), 10);
				const methodSlug = String($selected.attr('data-method-slug') || '');
				const amount = parseMoney($row.find('.pod-payment-amount').val());
				const imageId = parseInt($row.find('.pod-payment-image-id').val(), 10) || 0;

				if(!methodId){
					errors.push('Todos los métodos de pago deben estar seleccionados.');
					return;
				}
				if(methodIds.indexOf(methodId) !== -1){
					errors.push('No se puede repetir el mismo método de pago.');
				}
				methodIds.push(methodId);

				if(amount <= 0){
					errors.push('Todos los métodos deben tener un monto mayor a 0.');
				}

				if(methodSlug !== 'motorizado_efectivo' && !imageId){
					errors.push('Debe subir comprobante en todos los métodos que lo requieren.');
				}

				totalAssigned += amount;
			});

			const hasSignature = parseInt($('#__pod_signature').val(), 10) > 0;
			if(!hasSignature){
				errors.push('Debe existir una firma generada.');
			}

			const podImagesCount = $('#wpcargo-pod-images .gallery-thumb').length;
			if(podImagesCount < 1){
				errors.push('Debe agregar al menos una imagen POD.');
			}

			if(Math.abs(totalAssigned - requiredTotal) > 0.01){
				errors.push('La suma de métodos de pago debe ser igual al monto total a cobrar.');
			}

			$('#pod-total-assigned').text(formatMoney(totalAssigned));
			$('#pod-payment-errors').html(errors.length ? errors[0] : '');
			$submitBtn.prop('disabled', errors.length > 0);
		}

		function addPaymentRow(rowData){
			if(financeMethods.length === 0){
				$('#pod-payment-errors').html('No hay métodos de pago activos en el plugin de finanzas.');
				return;
			}
			if($paymentCards.find('.pod-payment-row').length >= maxPaymentRows){
				$('#pod-payment-errors').html('Ya alcanzó el máximo de métodos disponibles.');
				return;
			}

			const row = rowData || {};
			$paymentCards.append(paymentRowTemplate(row));
			const $newRow = $paymentCards.find('.pod-payment-row').last();
			updateRowByMethod($newRow);
			refreshSelectOptions();
			validatePodCompletion();
		}

		function uploadPodFile(file, onSuccess, onError){
			if(!file){
				if(typeof onError === 'function'){
					onError('Archivo inválido.');
				}
				return;
			}

			const formData = new FormData();
			formData.append('action', 'wpcpod_upload_image');
			formData.append('shipmentID', shipmentID);
			formData.append('pod_file', file);
			if(window.wpcargoPODAJAXHandler && window.wpcargoPODAJAXHandler.sign_nonce){
				formData.append('nonce', window.wpcargoPODAJAXHandler.sign_nonce);
			}

			$.ajax({
				type: 'POST',
				url: AJAXHANDLER,
				data: formData,
				processData: false,
				contentType: false,
				success: function(response){
					if(response && response.status === 'success' && response.attachment_id){
						if(typeof onSuccess === 'function'){
							onSuccess(response);
						}
						return;
					}
					if(typeof onError === 'function'){
						onError((response && response.message) ? response.message : 'No se pudo subir la imagen.');
					}
				},
				error: function(){
					if(typeof onError === 'function'){
						onError('Error de red al subir imagen.');
					}
				}
			});
		}

		$('#pod-add-payment-method').on('click', function(){
			addPaymentRow();
		});

		$paymentCards.on('change', '.pod-payment-method-select', function(){
			updateRowByMethod($(this).closest('.pod-payment-row'));
			refreshSelectOptions();
			validatePodCompletion();
		});

		$paymentCards.on('input change blur', '.pod-payment-amount', function(){
			validatePodCompletion();
		});

		$paymentCards.on('click', '.pod-payment-remove', function(){
			$(this).closest('.pod-payment-row').remove();
			refreshSelectOptions();
			validatePodCompletion();
		});

		$paymentCards.on('click', '.pod-payment-upload', function(e){
			e.preventDefault();
			const $row = $(this).closest('.pod-payment-row');
			$row.find('.pod-payment-file-input').trigger('click');
		});

		$paymentCards.on('change', '.pod-payment-file-input', function(){
			const $row = $(this).closest('.pod-payment-row');
			const file = this.files && this.files.length ? this.files[0] : null;
			if(!file){
				return;
			}

			$row.find('.pod-payment-image-state').removeClass('has-image').text('Subiendo...');
			uploadPodFile(file, function(response){
				$row.find('.pod-payment-image-id').val(String(response.attachment_id));
				$row.find('.pod-payment-image-state').addClass('has-image').text('Comprobante seleccionado');
				validatePodCompletion();
			}, function(message){
				$row.find('.pod-payment-image-id').val('');
				$row.find('.pod-payment-image-state').removeClass('has-image').text('Sin comprobante');
				alert(message);
				validatePodCompletion();
			});

			$(this).val('');
		});

		$(document).on('ajaxComplete', function(event, xhr, settings){
			if(!settings || !settings.data){
				return;
			}
			if(String(settings.data).indexOf('action=wpc_results_pod_data') !== -1 ||
				String(settings.data).indexOf('action=wpcpod_save_attachment') !== -1 ||
				String(settings.data).indexOf('action=wpcpod_delete_image') !== -1){
				validatePodCompletion();
			}
		});

		if(Array.isArray(initialPaymentRows) && initialPaymentRows.length){
			initialPaymentRows.forEach(function(row){
				addPaymentRow(row);
			});
		}else{
			validatePodCompletion();
		}

		$('#pod-pop-up').on('click', '.delete-attachment', function(){
			const parentElem = $(this).closest('.gallery-thumb');
			const attchID 	 = parentElem.attr('data-id');
			$.ajax({
				type: "POST",
				datatype: 'JSON',
				url: AJAXHANDLER,
				data:{
					action: 'wpcpod_delete_image',
					shipmentID : shipmentID,
					attchID: attchID
				},
				beforeSend:function(){
                    parentElem.addClass('d-none');
                },
				success:function(response){
					if(!response.status){
						parentElem.removeClass('d-none');
						alert( response.message );
						return;
					}
					parentElem.remove();
				}
			});
		});
		$('#wpcargo-pod-img-btn').on('click', function(e){
			e.preventDefault();
			$('#wpcargo-pod-img-input').trigger('click');
		});

		$('#wpcargo-pod-img-input').on('change', function(){
			const files = this.files ? Array.prototype.slice.call(this.files) : [];
			if(!files.length){
				return;
			}
			const uploadedIds = [];

			function uploadNext(index){
				if(index >= files.length){
					if(!uploadedIds.length){
						validatePodCompletion();
						return;
					}
					$.post(AJAXHANDLER, {
						action: 'wpcpod_save_attachment',
						attachments: uploadedIds,
						shipmentID: shipmentID
					}, function(response){
						$('#wpcargo-pod-images').html(response);
						validatePodCompletion();
					});
					return;
				}

				uploadPodFile(files[index], function(response){
					uploadedIds.push(response.attachment_id);
					uploadNext(index + 1);
				}, function(message){
					alert(message);
					uploadNext(index + 1);
				});
			}

			uploadNext(0);
			$(this).val('');
		});

		$('#wpc_pod_signature-form').on('submit', function(e){
			validatePodCompletion();
			if($submitBtn.is(':disabled')){
				e.preventDefault();
			}
		});
	});
</script>
<style>
	#pod-payment-cards .pod-payment-image-state.has-image {
		color: #28a745;
		font-weight: 600;
	}
</style>