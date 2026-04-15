<?php
	$signature_fields 		= wpcpod_signature_field_list();
	$get_sid 				= $shipment_id;
	$get_pod_img 			= get_post_meta($get_sid, 'wpcargo-pod-image', true);
	$pod_signature 			= get_post_meta($get_sid, 'wpcargo-pod-signature', true);
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
		<?php do_action( 'wpcpod_after_status_container', $get_sid ); ?>
		<div class="pod-submit container">	
			<div class="status-btn pt-sm-4">
				<input type="submit" class="delivered-btn btn btn-success" name="submit" value="<?php esc_html_e('Update', 'wpcargo-pod' ); ?>">
			</div>
		</div>
    </div>
</form>
<?php do_action( 'wpcpod_after_sign_popup_form' ); ?>
<script>
	jQuery(document).ready(function ($) {
		const shipmentID 	= $( '[name="__pod_id"]' ).val();
		const AJAXHANDLER 	= '<?php echo admin_url( 'admin-ajax.php' ); ?>';
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
		$( '#wpcargo-pod-img-btn' ).click(function(e) {
			e.preventDefault();		
			var insertImage 	= wp.media.controller.Library.extend({
				defaults :  _.defaults({					
				}, wp.media.controller.Library.prototype.defaults )
			});
			var media_upload = wp.media({
				title: "<?php esc_html_e('Upload Images', 'wpcargo-pod' ); ?>",
				multiple: true, 
				button : { text : "<?php esc_html_e('Upload Images', 'wpcargo-pod' ); ?>" },
			}).open().on( 'select', function() {
				attachment = media_upload.state().get( 'selection' ).toJSON();
				var ids = [];
				for (i = 0; i < attachment.length; i++) {
					if(attachment[i]['subtype'] == 'png' || attachment[i]['subtype'] == 'jpeg' || attachment[i]['subtype'] == 'jpg' || attachment[i]['subtype'] == 'gif' || attachment[i]['subtype'] == 'gif' || attachment[i]['subtype'] == 'svg'){
						ids[i] = attachment[i]['id'];
					}
				}
				if( ids.length === 0 ){ return; } 
				var data = {
					'action': 'wpcpod_save_attachment',
					'attachments': ids,
					'shipmentID': shipmentID
				};
				$.post(AJAXHANDLER, data , function(response){
					$("#wpcargo-pod-images").html( response );
				});	
			});
		});	
	});
</script>