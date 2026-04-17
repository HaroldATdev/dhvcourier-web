<div class="modal fade top" id="invoiceUpdateModal" tabindex="-1" role="dialog" aria-labelledby="invoiceUpdateLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<form id="invoiceUpdate-form">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="invoiceUpdateLabel"><?php _e('Invoice Update', 'wpcargo-invoice'); ?></h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div class="invoice-list-wrapper mb-4 pb-4 border-bottom">
						<h6 class="font-weight-bold"><?php _e('Invoice List', 'wpcargo-invoice'); ?></h6>
						<ul class="invoice-list list-group d-flex flex-row flex-wrap"></ul>
					</div>
					<?php do_action( 'wpc_invoice_before_update_formfield' ); ?>
                    <div id="__wpcinvoice_status-wrapper" class="form-group">
                        <div class="select-no-margin">
                            <label><?php _e('Status','wpcargo-invoice'); ?></label>
                            <select name="__wpcinvoice_status" class="mdb-select mt-0 form-control browser-default" id="invoice_status" required>
                                <option value="">--<?php _e('Select Status','wpcargo-invoice'); ?>--</option>   
                                <?php foreach( wpcinvoice_status_list() as $key => $value ): ?>    
                                    <option value="<?php echo $key; ?>"><?php echo $value; ?></option> 
                                <?php endforeach; ?>                                                                              
                            </select>
                        </div>
                    </div>
					<div id="__wpcinvoice_remarks-wrapper" class="form-group">
						<label for="__wpcinvoice_remarks"><?php _e('Remarks','wpcargo-invoice'); ?></label>
						<textarea id="__wpcinvoice_remarks" class="md-textarea form-control" name="__wpcinvoice_remarks"></textarea>
			        </div>
                    <?php do_action( 'wpc_invoice_after_update_formfield' ); ?>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal"><?php _e('Close','wpcargo-invoice'); ?></button>
					<button type="submit" class="btn btn-sm btn-primary"><?php _e('Update','wpcargo-invoice'); ?></button>
				</div>
			</div>
		</form>
	</div>
</div>