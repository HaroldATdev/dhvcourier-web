<div class="postbox">
	<div class="inside">
		<table id="transfer-shipment-branch">
			<tr>
				<th style="text-align: left;"><?php esc_html_e('Select Branch to Transfer'); ?></th>
				<th>&nbsp;</th>
			</tr>
			<tr>
				<td>
					<select id="shipment-branch" name="shipment_branch"><?php
						?><option value=""><?php esc_html_e( '--Select Branch--', 'wpcargo-branches' ); ?></option><?php
						if( !empty( $all_branch ) ){
							foreach ( $all_branch as $branch ) {
								?><option value="<?php echo $branch->id; ?>"><?php echo $branch->name; ?></option><?php
							}
						}
					?></select>
				</td>
				<td colspan="2"><input type="text" id="shipment-number" name="shipment_number" placeholder="<?php esc_html_e('Scan your shipment barcode to update or enter the tracking number and press ENTER', 'wpcargo-branches' ); ?>" autocomplete="off"></td>
			</tr>
		</table>
		<h3><?php esc_html_e('Notes', 'wpcargo-branches' ); ?></h3>
		<ol>
			<li><?php esc_html_e('If you have connected your barcode scanner please scan directly to the barcode and it will automatic update the Shipment Status', 'wpcargo-branches' ); ?></li>
			<li><?php esc_html_e('If you don\'t have a barcode scanner please input it to the tracking number field and press Enter on your keyboard', 'wpcargo-branches' ); ?></li>
		</ol>
	</div>
</div>