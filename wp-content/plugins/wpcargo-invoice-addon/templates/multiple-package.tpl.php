<?php
	$packages = maybe_unserialize( get_post_meta( $shipment->ID, 'wpc-multiple-package', true ) ); 
	$shipment_type = wpcfe_get_shipment_type( $shipment->ID );
	$wpcargo_package_fields = wpcargo_package_fields();
	$section_header_label = apply_filters( 'wpcinvoice_multipack_header_label', esc_html__('Packages','wpcargo-invoice') );
	$shipment_types_without_packages = array( 'Delivery', 'Shipment Consolidation' );
	$repeater_off = "";
	// shipping rate integration start
	if( $shipment_type == 'Shipping Rate' ) {
		// remove "Shipping Cost" and "Cost" columns from packages
		unset($wpcargo_package_fields['unit-price']);
		unset($wpcargo_package_fields['unit-amount']);
		$repeater_off = "-off";
	}
	//** Insurenace Manager integration */
	$insurance_enabled = get_option('wpcsr_insurance');
	// shipping rate integration end

	// vehicle rate integration start
	if( $shipment_type == 'Delivery' ) {
		$section_header_label = wpcinvoice_multipack_header_delivery_data_label();
	}
	// vehicle rate integration end

	// vehicle rate integration start
	if( $shipment_type == 'Shipment Consolidation' ) {
		$section_header_label = wpcinvoice_multipack_header_shcon_label();
	}
	// vehicle rate integration end
?>
<div id="wpcinvoice_package" class="col-md-12 mb-4">
	<div class="card">
		<section class="card-header">
			<?php echo $section_header_label; ?>
		</section>
		<section class="card-body">
			<div id="wpcinvoice-multipack-table-wrapper" class="table-responsive">
			    
			    <?php do_action( 'wpcinvoice_before_package_table_row', $shipment ); ?>
			    
				<table id="wpcfe-packages-repeater<?php echo $repeater_off?>" class="table table-hover table-sm">
					<?php if( !in_array( $shipment_type, $shipment_types_without_packages ) ): ?>
						<thead>
							<tr class="text-center">
								<?php foreach ( $wpcargo_package_fields as $key => $value): ?>
									<?php 
										$colspan = ( ( $key == 'wpc-pm-description' && $shipment_type == 'Shipping Rate' ) ? 3 : '' );
										if( in_array( $key, wpcargo_package_dim_meta() ) && !wpcargo_package_settings()->dim_unit_enable ){
											continue;
										}
									?>
									<th colspan="<?php echo $colspan; ?>"><strong><?php echo $value['label']; ?></strong></th>
								<?php endforeach; ?>
								<th>&nbsp;</th>
							</tr>
						</thead>

						<tbody data-repeater-list="wpc-multiple-package">
							<?php
							if(!empty($packages) && is_array($packages)) {
								foreach($packages as $package) {
									?>
									<tr data-repeater-item>
										<?php foreach ( $wpcargo_package_fields as $key => $field_value): 
											$value = array_key_exists( $key, $package ) ? $package[$key] : '' ;
											$colspan = ( ( $key == 'wpc-pm-description' && $shipment_type == 'Shipping Rate' ) ? 3 : '' );
											$class = $field_value['field'] == 'select' ? 'form-control browser-default custom-select ' : 'form-control ' ; 
											$class .= $key;
											if( $key == 'unit-amount' || ( $shipment_type == 'Shipping Rate' && $key == 'wpc-pm-qty' ) ){
												$class .= ' readonly';
											}
											if( $insurance_enabled && ( $shipment_type == 'Shipping Rate' && $key == 'wpc-pm-value' ) ){
												$class .= ' readonly';
											}
											if( in_array( $key, wpcargo_package_dim_meta() ) && !wpcargo_package_settings()->dim_unit_enable ){
												continue;
											}
											?>
											<td colspan="<?php echo $colspan; ?>"><?php echo wpcargo_field_generator( $field_value, $key, $value, $class ); ?></td>
										<?php endforeach; ?>
										<td>
											<label for="del-pack" class="text-danger" style="font-size: 22px;" >
												<i class="fa fa-trash"></i>
											</label>
											<input data-repeater-delete type="button" id="del-pack" class="wpc-delete d-none" />
										</td>
									</tr>
									<?php
								}
							}else{
								?>
									<tr data-repeater-item>
										<?php foreach ( wpcargo_package_fields() as $key => $field_value): $class = $field_value['field'] == 'select' ? 'form-control browser-default custom-select' : 'form-control' ; ?>
											<?php 
											$class = $field_value['field'] == 'select' ? 'form-control browser-default custom-select ' : 'form-control ' ; 
											$class .= $key;
											if( $key == 'unit-amount' ){
												$class .= ' readonly';
											}
											if( in_array( $key, wpcargo_package_dim_meta() ) && !wpcargo_package_settings()->dim_unit_enable ){
												continue;
											}
											?>
											<td><?php echo wpcargo_field_generator( $field_value, $key, '', $class ); ?></td>
										<?php endforeach; ?>
										<td>
											<label for="del-pack" class="text-danger" style="font-size: 22px;" >
												<i class="fa fa-trash"></i>
											</label>
											<input data-repeater-delete type="button" id="del-pack" class="wpc-delete d-none" /></td>
									</tr>
								<?php
							}
							?>
						</tbody>
					<?php endif; ?>
					<tfoot>
					<?php if( !in_array( $shipment_type, $shipment_types_without_packages ) ): ?>
						<tr class="wpc-computation">
							<td colspan="<?php echo wpcfe_mpack_dim_enable() ? 11 : 7 ; ?>" class="text-left">
								<label for="add-pack" class="text-info" style="font-size: 18px;" >
									<i class="fa fa-plus"></i> <?php echo apply_filters( 'wpcinvoice_add_package_btn_label', __( 'Add', 'wpcargo-invoice' ) ); ?>
								</label>
								<input data-repeater-create type="button" id="add-pack" class="wpc-add d-none" />
							</td>
						</tr>
					<?php endif; ?>
					<?php do_action( 'wpcinvoice_after_package_table_row', $shipment ); ?>
					</tfoot>
				</table>
				<?php do_action('wpcargo_after_package_totals', $shipment );  ?>
			</div>
		</section>
	</div>
</div>