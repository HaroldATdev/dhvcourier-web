<div class="mb-4 border-bottom">
    <h4 class="heading"><?php echo wpcinvoice_export_heading_label(); ?></h4>
</div>
<form id="wpcinvoice-ie-export-form" method="POST" class="container-fluid" action="" >
    <div id="wpcie-fields-wrapper" class="row">
        <?php wp_nonce_field( 'wpcinvoice_ie_nonce_action', 'wpcinvoice_ie_nonce_field' ); ?>
        <div class="col-md-6">
            <?php do_action( 'wpcie_frontend_before_export_form_field' ); ?>
            <?php if( !empty($client_users) ): ?>
                <section class="form-group">
                    <label for="registered_shipper"><?php _e( 'Registered Shipper', 'wpcargo-invoice' ); ?></label>
                    <select name="registered_shipper" class="form-control browser-default custom-select _group-data" id="registered_shipper">
                        <option value=""><?php _e('-- Registered Shipper --', 'wpcargo-invoice' ); ?></option>
                        <?php foreach( $client_users as $user ): ?>
                            <option value="<?php  echo $user->ID; ?>" <?php selected( $registered_shipper, $user->ID ); ?> ><?php echo $wpcargo->user_fullname( $user->ID ); ?></option>
                        <?php endforeach; ?>      
                    </select>
                </section>
            <?php endif; ?>
            <?php if( !empty( wpcinvoice_status_list() ) ): ?>
                <section class="form-group">
                    <label for="invoice_status"><?php _e( 'Status', 'wpcargo-invoice' ); ?></label>
                    <select name="invoice_status" class="form-control browser-default custom-select _group-data" id="invoice_status">
                        <option value=""><?php _e('-- Status --', 'wpcargo-invoice' ); ?></option>
                        <?php foreach( wpcinvoice_status_list() as $status_key => $status_label ): ?>
                            <option value="<?php  echo $status_key; ?>" <?php selected( $invoice_status, $status_key ); ?> ><?php echo $status_label; ?></option>
                        <?php endforeach; ?>      
                    </select>
                </section>
            <?php endif; ?>
            <section class="form-row">
                <div class="col-md-6 mb-4">
                    <div class="md-form">
                        <input placeholder="<?php _e('YYYY-MM-DD', 'wpcargo-invoice'); ?>" type="text" id="startingDate" name="date-from" class="form-control wpccf-datepicker _group-data"/>
                        <label for="startingDate"><?php _e( 'Start', 'wpcargo-invoice' ); ?></label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                    <div class="md-form">
                        <input placeholder="<?php _e('YYYY-MM-DD', 'wpcargo-invoice'); ?>" type="text" id="endingDate" name="date-to" class="form-control wpccf-datepicker _group-data">
                        <label for="endingDate"><?php _e( 'End', 'wpcargo-invoice' ); ?></label>
                    </div>
                </div>
            </section>
            <?php do_action( 'wpcie_frontend_after_export_form_field' ); ?>
        </div>
        <div class="col-sm-12 mt-2">
	        <input type="submit" class="btn btn-primary btn-sm" name="export_invoice" value="<?php _e( 'Export Invoice', 'wpcargo-invoice' ); ?>" />
	    </div>
	</div> <!-- End row -->
</form>