<form method="post" action="" enctype="multipart/form-data" class="add-shipment">
	<?php wp_nonce_field( 'wpcfe_add_action', 'wpcfe_add_form_fields' ); ?>
	<div class="row">
		<div class="col-md-9 mb-3">
			<section class="row"> 
				<?php if( has_action( 'before_wpcfe_shipment_form_fields' ) ): ?>
					<?php do_action( 'before_wpcfe_shipment_form_fields', 0 ); ?>
				<?php
				endif;
				$counter = 1;
				$row_class = '';
				foreach ( wpcfe_get_shipment_sections() as $section => $section_header ) {		
					if( empty( $section ) ){
						continue;
					}
					$column = 12;
					// if( ( $section == 'shipper_info' || $section == 'receiver_info' ) && $counter <= 2 && count(wpcfe_get_shipment_sections() ) > 1 ){
					if( ( $section == 'shipper_info' || $section == 'receiver_info' ) && $counter <= 2 ){
						$column = 6;
					}
					$column = apply_filters( 'wpcfe_shipment_form_column', $column, $section ); 

					?>
					<div id="<?php echo $section; ?>" class="col-md-<?php echo $column; ?> mb-4">
						<div class="card">
							<section class="card-header">
								<?php echo $section_header; ?>
							</section>				
							<section class="card-body">
								<div class="row">
									<?php if( has_action( 'before_wpcfe_'.$section.'_form_fields' ) ): ?>
										<?php do_action( 'before_wpcfe_'.$section.'_form_fields', 0 ); ?>
									<?php endif; ?>
									<?php $section_fields = $WPCCF_Fields->get_custom_fields( $section ); ?>
									<?php $WPCCF_Fields->convert_to_form_fields( $section_fields ); ?>
									<?php if( has_action( 'after_wpcfe_'.$section.'_form_fields' ) ): ?>
										<?php do_action( 'after_wpcfe_'.$section.'_form_fields', 0 ); ?>
									<?php endif; ?>
								</div>
							</section>
						</div>
					</div>
					<?php
					$counter++;
				}
				if( has_action( 'after_wpcfe_shipment_form_fields' ) ): ?>
					<?php do_action( 'after_wpcfe_shipment_form_fields', 0 ); ?>
				<?php endif; ?>
			</section>
		</div>
		<div class="col-md-3 mb-3">
			<section class="row"> 
				<?php if( has_action( 'before_wpcfe_shipment_form_submit' ) ): ?>
					<div class="after-shipments-info col-md-12 mb-4">
						<?php do_action( 'before_wpcfe_shipment_form_submit' ); ?>
					</div>
				<?php endif; ?>
				<div class="col-md-12 mb-5 text-right">
					<button type="submit" class="btn btn-info btn-fill btn-wd btn-block"><?php esc_html_e('Add Shipment', 'wpcargo-frontend-manager'); ?></button>
				</div>
			</section>
		</div>
	</div>
	<div class="clearfix"></div>
</form>
<!-- Auto-set status to 'En espera' for certain shipment types -->
<script type="text/javascript">
jQuery(function($){
	function setEnEsperaIfTipo(tipo){
		if(!tipo) return;
		tipo = tipo.toString();
		if(tipo === 'almacen' || tipo === 'agencia'){
			var $status = $("#status, select#status, select[name='status'], #wpcargo_status, select[name='wpcargo_status']");
			if($status.length){
				$status.val('En espera').trigger('change');
			}
		}
	}

	var $hidden = $('input[name="wpcte_tipo_envio"], input#wpcte_tipo_envio, input[name="tipo_envio"]');
		if($hidden.length){
			setEnEsperaIfTipo($hidden.val());
		// listen for changes
		$(document).on('change', 'select[name="tipo_envio"], select#tipo_envio, input[name="wpcte_tipo_envio"], input#wpcte_tipo_envio', function(){
			setEnEsperaIfTipo($(this).val());
		});
		// observe value attribute changes if plugin updates hidden input programmatically
		var node = $hidden.get(0);
		if(node && window.MutationObserver){
			var obs = new MutationObserver(function(){
				setEnEsperaIfTipo($hidden.val());
			});
			obs.observe(node, { attributes: true, attributeFilter: ['value'] });
		}
	} else {
		// fallback: check visible selects on page load
		setEnEsperaIfTipo($('select[name="tipo_envio"]').val());
		$(document).on('change', 'select[name="tipo_envio"]', function(){ setEnEsperaIfTipo($(this).val()); });
	}

		// Ensure form submits include status if tipo_envio requires 'En espera'
		$('form.add-shipment').on('submit', function(){
			var tipo = '';
			if($hidden.length){ tipo = $hidden.val(); }
			if(!tipo){ tipo = $('select[name="tipo_envio"]').val() || ''; }
			if(tipo === 'almacen' || tipo === 'agencia'){
				// set/create hidden inputs so server receives status
				var $s = $(this).find('input[name="status"]');
				if(!$s.length){
					$(this).append('<input type="hidden" name="status" value="En espera" />');
				} else { $s.val('En espera'); }
				var $ws = $(this).find('input[name="wpcargo_status"]');
				if(!$ws.length){
					$(this).append('<input type="hidden" name="wpcargo_status" value="En espera" />');
				} else { $ws.val('En espera'); }
			}
		});
});
</script>

<?php do_action( 'before_wpcargo_shipment_history', 0); ?>