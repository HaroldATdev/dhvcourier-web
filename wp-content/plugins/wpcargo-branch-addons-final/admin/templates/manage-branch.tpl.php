<div id="wpc-branch-wrapper">
	<table class="wpcargo-table branch-manager-list" style="border-collapse:collapse;">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Branch Name', 'wpcargo-branches' ); ?></th>
				<th><?php esc_html_e( 'Branch Code', 'wpcargo-branches' ); ?></th>
				<th><?php esc_html_e( 'Phone', 'wpcargo-branches' ); ?></th>
				<th><?php esc_html_e( 'Dirección', 'wpcargo-branches' ); ?></th>
				<th><?php esc_html_e( 'Ciudad', 'wpcargo-branches' ); ?></th>
				<th><?php esc_html_e( 'Colaborador de Sucursal', 'wpcargo-branches' ); ?></th>
				<?php do_action('wpcbm_after_table_header'); ?>
				<th><?php esc_html_e( 'Actions', 'wpcargo-branches' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
			if( !empty( $all_branches ) ){
				foreach ( $all_branches as $branch ) {
					$branch_manager = array();
					$unserialize_branch = unserialize( $branch->branch_manager );
					if( $unserialize_branch ){
						foreach( $unserialize_branch as $branch_data ){
							$branch_manager[] = $wpcargo->user_fullname( $branch_data );
						}
					}
					$assigned_bm = !empty( $branch_manager ) ? join('<br/>', $branch_manager ) : esc_html__( '--', 'wpcargo-branches' );
					?>
					<tr id="branch-<?php echo $branch->id; ?>" class="branches">
						<td><?php echo $branch->name ?></td>
						<td><?php echo $branch->code; ?></td>
						<td><?php echo $branch->phone; ?></td>
						<td><?php echo $branch->address1; ?></td>
						<td><?php echo $branch->city; ?></td>
						<td><?php echo $assigned_bm; ?></td>
						<?php do_action('wpcbm_after_table_data', $branch); ?>
						<td>
							<div class="action">
								<a href="#" class="edit" data-id="<?php echo $branch->id; ?>" ><span class="dashicons dashicons-edit"></span></a>
								<a href="#" class="delete" data-id="<?php echo $branch->id; ?>" ><span class="dashicons dashicons-trash"></span></a>
							</div>
						</td>
					</tr>
					<?php
				}
			} else {
				?>
				<tr>
					<td colspan="7" style="text-align: center; padding: 0.5rem;"><?php echo apply_filters('wpcbm_empty_branches_data_msg', __('No branches yet.', WPC_BRANCHES_TEXTDOMAIN)); ?></td>
				</tr>
				<?php
			}
		?>
		</tbody>
	</table>
</div>
