<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
?>
<div id="signature-content-pod container">
	<div class="box-group row">
		<div class="pod-signature-wrap col-md-6 mb-4">
			<div id="signature-pad" class="pod-signature">
				<div class="wpc-signature-wrap">
					<div class="wpc-signature">
						<div class="m-signature-pad">
							<div class="description">
								<h5><?php esc_html_e('Sign below', 'wpcargo-pod' ); ?></h5>
								<p><i><?php printf( __( 'Please click the <b>%s</b> button after you signed.', 'wpcargo-pod' ), 'SAVE' );?></i></p>
							</div>
							<div class="m-signature-pad--body">
								<canvas id="pod-canvas"></canvas>
							</div>
						</div>
						<div class="wpc-signature-actions">
							<button type="button" id="pod-save" class="btn btn-success save" data-action="save" value="save">
							<?php esc_html_e('Save', 'wpcargo-pod' ); ?>
							</button>
							<button type="button" id="pod-clear" class="btn btn-danger clear" data-action="clear" value="clear">
							<?php esc_html_e('Clear', 'wpcargo-pod' ); ?>
							</button>
						</div>
						<!-- signature-pad --> 
					</div>
					<!-- wpc-signature --> 					
				</div>
				<!-- .wpc-signature-wrap --> 
			</div>
		</div>
		<div class="pod-generated-signature col-md-6 mb-4">
			<div class="description">
				<h5><?php esc_html_e('Generated signature', 'wpcargo-pod' ); ?></h5>
				<div id="wpcargo-signature-img">	
					<?php
						if(!empty($pod_signature)){
							?><p class="header-pod-result"><?php esc_html_e('Your current signature:', 'wpcargo-pod' ); ?></p><?php
							echo '<img src="'.wp_get_attachment_url( $pod_signature ).'" alt="signature" class="signature-generated-img" />';
						}
					?>
				</div><!-- #wpcargo-signature-img -->
			</div>
		</div>
	</div>
</div>
<script>
	jQuery(document).ready(function($) {
		var get_shipment_id = '<?php echo isset($get_sid) ? $get_sid : ''; ?>';									
		var wrapper = document.getElementById("signature-pad"),
		clearButton = wrapper.querySelector("#pod-clear"),
		saveButton = wrapper.querySelector("#pod-save"),
		canvas = wrapper.querySelector("#pod-canvas"),
		signaturePad;
		// Adjust canvas coordinate space taking into account pixel ratio,
		// to make it look crisp on mobile devices.
		// This also causes canvas to be cleared.
		function resizeCanvas() {
			// When zoomed out to less than 100%, for some very strange reason,
			// some browsers report devicePixelRatio as less than 1
			// and only part of the canvas is cleared then.
			var ratio =  Math.max(window.devicePixelRatio || 1, 1);
			canvas.width = canvas.offsetWidth * ratio;
			canvas.height = canvas.offsetHeight * ratio;
			canvas.getContext("2d").scale(ratio, ratio);
		}
		window.onresize = resizeCanvas;
		resizeCanvas();
		signaturePad = new SignaturePad(canvas);
		clearButton.addEventListener("click", function (event) {
			signaturePad.clear();
		});
		saveButton.addEventListener("click", function (event) {
			if (signaturePad.isEmpty()) {
				alert("<?php esc_html_e('Please provide signature first.', 'wpcargo-pod' ); ?>");
			} else {
				var get_img_canvas 		= signaturePad.toDataURL();
				var get_wpcargo_id 		= get_shipment_id;
				var get_sign_field_id	= '__pod_signature';
				var get_img_wrap 		= 'wpcargo-signature-img';
				var get_load_url 		= '<?php echo WPCARGO_POD_URL.'assets/img/spin.gif'; ?>';
				$.ajax({
				type:"POST",
				data:{
					action:'wpc_results_pod_data',
					wpcargo_img_data: get_img_canvas,
					wpcargo_id: get_wpcargo_id,
					wpcargo_img_delete: $('#' + get_sign_field_id).val(),
					wpcargo_signature_key: 'wpcargo-pod-signature'
				},
				dataType: 'JSON',
				url : '<?php echo admin_url('admin-ajax.php'); ?>',
				beforeSend:function(){
					// response
					$(".wpc-signature-status").html('');
					$('body').append( '<div class="wpcargo-loading">Loading...</div>');
				},
				success:function(response){
					//response		
					$('body .wpcargo-loading').remove();
					$('#'+get_sign_field_id).val(response.wpcargo_attach_id);
					$('#'+get_img_wrap).html(response.wpcargo_attach_img);	
					$('.wpc-signature-status').html(response.signature_status);											
				},
				});
			}
		});
	});
</script> 