<form method="post" action="" enctype="multipart/form-data" class="update-shipment">
	<?php wp_nonce_field( 'wpcinvoice_edit_action', 'wpcinvoice_form_fields' ); ?>
	<input type="hidden" name="shipment_id" value="<?php echo $shipment->ID; ?>">
	<div class="row">
		<div class="col-md-12 mb-3">
            <section class="row">        
                <?php if( has_action( 'before_wpcinvoice_shipment_form_fields' ) ): ?>
                    <?php do_action( 'before_wpcinvoice_shipment_form_fields', $shipment->ID ); ?>
                <?php endif; ?>
                <?php
                    $counter = 1;
                    $row_class = '';
                    $wpcinvoice_get_shipment_sections = wpcinvoice_get_shipment_sections();
                    if( !empty( $wpcinvoice_get_shipment_sections ) ){
                        foreach ( $wpcinvoice_get_shipment_sections as $section => $section_header ) {
                            if( empty( $section ) ){
                                continue;
                            }
                            $section_class = 'col-md-6';
                            $column = 12;
                            if( ( $section == 'shipper_info' || $section == 'receiver_info' ) && $counter <= 2 && count($wpcinvoice_get_shipment_sections ) > 1 ){
                                $column = 6;
                                $section_class = '';
                            }
                            if( $section != 'shipper_info' && $section != 'receiver_info' ){
                                $row_class = 'row';
                            }
                            $column = apply_filters( 'wpcfe_shipment_form_column', $column, $section ); 
                            ?>
                            <div id="<?php echo $section; ?>" class="col-md-<?php echo $column; ?> mb-4">
                                <div class="card">
                                    <section class="card-header">
                                        <?php echo $section_header; ?>
                                    </section>
                                    <section class="card-body <?php echo $row_class; ?>">
                                        <?php if( has_action( 'before_wpcfe_'.$section.'_form_fields' ) ): ?>
                                            <?php do_action( 'before_wpcfe_'.$section.'_form_fields', $shipment->ID ); ?>
                                        <?php endif; ?>
                                        <?php $section_fields = $WPCCF_Fields->get_custom_fields( $section ); ?>
                                        <?php $WPCCF_Fields->convert_to_form_fields( $section_fields, $shipment->ID, $section_class ); ?>
                                        <?php if( has_action( 'after_wpcfe_'.$section.'_form_fields' ) ): ?>
                                            <?php do_action( 'after_wpcfe_'.$section.'_form_fields', $shipment->ID ); ?>
                                        <?php endif; ?>
                                    </section>
                                </div>
                            </div>
                            <?php
                            $counter++;
                        }
                    }
                ?>
                <?php if( has_action( 'after_wpcinvoice_shipment_form_fields' ) ): ?>
                    <?php do_action( 'after_wpcinvoice_shipment_form_fields', $shipment->ID ); ?>
                <?php endif; ?>
                <div class="clearfix"></div>
			</section>
		</div>
		<div class="col-md-12 mb-3">
            <section class="row"> 
                <?php if( has_action( 'before_wpcinvoice_shipment_form_submit' ) ): ?>
                    <div class="after-shipments-info col-md-12 mb-4">
                        <?php do_action( 'before_wpcinvoice_shipment_form_submit', $shipment->ID ); ?>
                    </div>
                <?php endif; ?>
                <div class="col-md-3 text-right">
                    <button type="submit" class="btn btn-small btn-info btn-fill btn-wd btn-block"><?php esc_html_e('Update Shipment', 'wpcargo-invoice'); ?></button>
                </div>
                <div class="col-md-2 text-right">
                    <button type="button" class="btn btn-small btn-default btn-fill btn-wd btn-block wpcinvoice-print" data-id="<?php echo $shipment->ID; ?>"> <i class="fa fa-print"></i> <?php esc_html_e('Print Invoice', 'wpcargo-invoice'); ?></button>
                </div>
            </section>
		</div>
	</div>
</form>