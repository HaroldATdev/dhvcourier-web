<?php 
	$packages = maybe_unserialize( get_post_meta( $shipment->ID, 'wpc-multiple-package', true ) ); 
	$shipment_type = get_post_meta( get_post_meta( $shipment->ID, '__shipment_type', true ) );
?>
<div id="package_id" class="col-md-12 mb-4">
	<div class="card">
		<section class="card-header">
			<?php echo apply_filters( 'wpcfe_multipack_header_label', esc_html__('Packages','wpcargo-frontend-manager') ); ?>
		</section>
		<section class="card-body">
			<?php do_action('wpcfe_before_update_package_details', $shipment ); ?>
			<div id="wpcfe-multipack-table-wrapper" class="table-responsive">
				<table id="wpcfe-packages-repeater" class="table table-hover table-sm">
					<thead>
						<tr class="text-center">
							<?php foreach ( wpcargo_package_fields() as $key => $value): ?>
								<?php 
									if( in_array( $key, wpcargo_package_dim_meta() ) && !wpcargo_package_settings()->dim_unit_enable ){
										continue;
									}
								?>
								<th><strong><?php echo $value['label']; ?></strong></th>
							<?php endforeach; ?>
							<th>&nbsp;</th>
						</tr>
					</thead>
					<tbody data-repeater-list="wpc-multiple-package">
					<?php
					if(!empty($packages) && is_array($packages)) {
						foreach($packages as $package) { ?>
							<tr data-repeater-item>
								<?php foreach ( wpcargo_package_fields() as $key => $field_value): 
									$value = array_key_exists( $key, $package ) ? $package[$key] : '' ;
									$class = $field_value['field'] == 'select' ? 'form-control browser-default custom-select' : 'form-control' ; ?>
									<?php 
									if( in_array( $key, wpcargo_package_dim_meta() ) && !wpcargo_package_settings()->dim_unit_enable ){
										continue;
									}
									?>
									<td><?php echo wpcfe_field_generator( $field_value, $key, $value, $class ); ?></td>
								<?php endforeach; ?>
									<td>								<label for="del-pack" class="text-danger pck-delete" >
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
									if( in_array( $key, wpcargo_package_dim_meta() ) && !wpcargo_package_settings()->dim_unit_enable ){
										continue;
									}
									?>
									<td><?php echo wpcfe_field_generator( $field_value, $key, '', $class ); ?></td>
								<?php endforeach; ?>
								<td>								<label for="del-pack" class="text-danger pck-delete" >
										<i class="fa fa-trash"></i>
									</label>
									<input data-repeater-delete type="button" id="del-pack" class="wpc-delete d-none" />
								</td>
							</tr>
						<?php
					}
					?>
					
					</tbody>
					<tfoot>
					<tr class="wpc-computation">
						<td colspan="<?php echo wpcfe_mpack_dim_enable() ? 11 : 7 ; ?>" class="text-left">
							<label for="add-pack" class="text-info pck-add" >
								<i class="fa fa-plus"></i> <?php echo apply_filters( 'wpcinvoice_add_package_btn_label', __( 'Add', 'wpcargo-invoice' ) ); ?>
							</label>
							<input data-repeater-create type="button" id="add-pack" class="wpc-add d-none" />
						</td>
					</tr>
					<?php do_action( 'wpcargo_after_package_table_row', $shipment ); ?>
					
					</tfoot>
				</table>
				<?php do_action('wpcfe_after_update_package_details', $shipment ); ?>
				<?php do_action('wpcargo_after_package_totals', $shipment ); ?>
			</div>
		</section>
	</div>
</div>