<div id="addBranchModal" class="modal" style="display: none;">
	<div class="modal-content">
		<div class="header">
			<h1>
				<?php esc_html_e('Add Branch', 'wpcargo-branches' ); ?>
				<span class="close">x</span>
			</h1>
		</div>
		<div class="content">
			<form id="add-branch">
				<table class="add-branch-table" width="100%">
					<tr>
						<td><label for="name"><?php esc_html_e('Nombre', 'wpcargo-branches'); ?></label>:</td>
						<td><input type="text" id="name" name="name" required /></td>
					</tr>
					<tr>
						<td><label for="code"><?php esc_html_e('Código', 'wpcargo-branches'); ?></label>:</td>
						<td><input type="text" id="code" name="code" required /></td>
					</tr>
					<tr>
						<td><label for="phone"><?php esc_html_e('Teléfono', 'wpcargo-branches'); ?></label>:</td>
						<td><input type="text" id="phone" name="phone" /></td>
					</tr>
					<tr>
						<td><label for="address1"><?php esc_html_e('Dirección', 'wpcargo-branches'); ?></label>:</td>
						<td><input type="text" id="address1" name="address1" /></td>
					</tr>
					<tr>
						<td><label for="city"><?php esc_html_e('Ciudad', 'wpcargo-branches'); ?></label>:</td>
						<td><input type="text" id="city" name="city" /></td>
					</tr>
					<tr>
						<td><label for="branch_manager"><?php esc_html_e('Colaborador de Sucursal', 'wpcargo-branches'); ?></label>:</td>
						<td>
							<select id="branch_manager" name="branch_manager[]" class="select-bm" data-el_label="Colaborador de Sucursal" multiple>
								<option value="">-- Seleccionar --</option>
								<?php foreach( wpcargo_get_branch_managers() as $mgr_id => $mgr_name ): ?>
									<option value="<?php echo $mgr_id; ?>"><?php echo $mgr_name; ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan="2"><input type="submit" class="button button-primary button-large" name="submit" value="<?php esc_html_e('Save Branch', 'wpcargo-branches' ); ?>"></td>
						<td></td>
					</tr>
				</table>
			</form>
		</div>
	</div>
</div>
