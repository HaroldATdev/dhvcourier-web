<?php
	global $wpcargo , $WPCCF_Fields;
	$user_role = wpcfe_current_user_role();
?>
<div id="wpcfe-misc-history" class="card mb-4">
	<section class="card-header">
		<?php echo apply_filters( 'wpcfe_history_header_label', __('History','wpcargo-frontend-manager') ); ?> <span class="float-right font-weight-bold text-uppercase"><?php echo $shipment->ID ? wpcfe_get_shipment_status( $shipment->ID ) : ''; ?></span>
	</section>
	<section class="card-body">
		<div class="form-row">
			<?php foreach( wpcargo_history_fields() as $history_metakey => $history_value ): ?>
				<?php 
					if( $history_metakey == 'updated-name' ){
						continue;
					}
					$custom_classes = array( 'form-control' );
					$value 			= '';
					if( $history_metakey == 'date' ){
						$custom_classes[] = 'wpccf-datepicker';
						$value = current_time( $wpcargo->date_format );
					}elseif( $history_metakey == 'time' ){
						$custom_classes[] = 'wpccf-timepicker';
						$value = current_time( $wpcargo->time_format );
					}
					if( $history_value['field'] == 'select'){
						$custom_classes[] = 'browser-default';
					}
					if( in_array( $history_metakey, wpcfe_autocomplete_address_fields() ) ){
						$custom_classes[] = 'wpcfe_autocomplete_address';
					}
					$custom_classes = implode(" ", $custom_classes );
				?>
				<div class="form-group col-md-12">
					<label for="status-<?php echo $history_metakey; ?>"><?php echo $history_value['label'];?></label>
					<?php echo wpcargo_field_generator( $history_value, $history_metakey, $value, 'status_'.$history_metakey.' '.$custom_classes ); ?>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
</div>
	<script type="text/javascript">
	jQuery(function($){
		// Cuando el tipo de envío sea 'almacen' o 'agencia', poner el history status en 'En espera'
		function getUrlParam(name){
			try{ var params = new URLSearchParams(window.location.search); return params.get(name); }catch(e){
				var regex = new RegExp('[?&]'+name+'=([^&#]*)','i'); var m = regex.exec(window.location.search); return m ? decodeURIComponent(m[1]) : null;
			}
		}
		function setHistoryStatusIfNeeded(){
			var tipo = $('input[name="wpcte_tipo_envio"]').val() || $('input[name="tipo_envio"]').val() || $('select[name="tipo_envio"]').val() || '';
			if(!tipo) return;
			if(tipo === 'almacen' || tipo === 'agencia'){
				var $select = $('#wpcfe-misc-history').find('select[name="status"]');
				if($select.length){
					// intentar seleccionar la opción con texto o value 'En espera'
					var found = false;
					$select.find('option').each(function(){
						var v = $(this).val();
						var t = $(this).text().trim();
						if(v === 'En espera' || t === 'En espera'){
							$(this).prop('selected', true);
							found = true;
							return false;
						}
					});
					if(found){ $select.trigger('change'); }
				}
			} 
			// Si es envio=puerta_puerta el status sera 'Pendiente'
			if(tipo === 'puerta_puerta'){
				var $select = $('#wpcfe-misc-history').find('select[name="status"]');
				if($select.length){
					// intentar seleccionar la opción con texto o value 'Pendiente'
					var found = false;
					$select.find('option').each(function(){
						var v = $(this).val();
						var t = $(this).text().trim();
						if(v === 'Pendiente' || t === 'Pendiente'){
							$(this).prop('selected', true);
							found = true;
							return false;
						}
					});
					if(found){ $select.trigger('change'); }
				}
			}
			
		}

		// If URL contains tipo_envio, preselect the type field and set hidden input if needed
		var tipoFromUrl = (getUrlParam('tipo_envio') || '').toString();
		if(tipoFromUrl){
			var $typeSelect = $('select[name="tipo_envio"]');
			if($typeSelect.length){
				var found=false, normTipo=tipoFromUrl.toLowerCase();
				$typeSelect.find('option').each(function(){
					var v = (''+$(this).val()).toLowerCase();
					var t = (''+$(this).text()).toLowerCase();
					if(v === normTipo || t.indexOf(normTipo) !== -1){
						$(this).prop('selected', true);
						found=true; return false;
					}
				});
				if(found){ $typeSelect.trigger('change'); }
			}
			// ensure hidden input used by tipo-envio plugin exists and matches
			var $hiddenTipo = $('input[name="wpcte_tipo_envio"]');
			if(!$hiddenTipo.length){
				$('form.add-shipment').append('<input type="hidden" name="wpcte_tipo_envio" value="'+tipoFromUrl+'" />');
			} else { $hiddenTipo.val(tipoFromUrl); }
		}

		// Ejecutar al cargar y cuando cambie el campo tipo de envío
		setHistoryStatusIfNeeded();
		$(document).on('change', 'input[name="wpcte_tipo_envio"], input[name="tipo_envio"], select[name="tipo_envio"]', function(){ setHistoryStatusIfNeeded(); });
	});
	</script>