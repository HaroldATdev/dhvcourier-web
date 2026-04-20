<div class="postbox">
	<div class="inside">
		<table id="transfer-shipment-branch">
			<tr>
				<th style="text-align: left;"><?php esc_html_e('Seleccionar Sucursal para Transferir', 'wpcargo-branches'); ?></th>
				<th>&nbsp;</th>
			</tr>
			<tr>
				<td>
					<select id="shipment-branch" name="shipment_branch"><?php
						?><option value=""><?php esc_html_e( '-- Seleccionar Sucursal --', 'wpcargo-branches' ); ?></option><?php
						if( !empty( $all_branch ) ){
							foreach ( $all_branch as $branch ) {
								?><option value="<?php echo $branch->id; ?>"><?php echo $branch->name; ?></option><?php
							}
						}
					?></select>
				</td>
				<td colspan="2"><input type="text" id="shipment-number" name="shipment_number" placeholder="<?php esc_html_e('Escanea el código de barras del envío o ingresa el número de seguimiento y presiona ENTER', 'wpcargo-branches' ); ?>" autocomplete="off"></td>
			</tr>
		</table>
		<h3><?php esc_html_e('Notas', 'wpcargo-branches' ); ?></h3>
		<ol>
			<li><?php esc_html_e('Si tienes conectado un lector de código de barras, escanea directamente y el Estado del Envío se actualizará automáticamente.', 'wpcargo-branches' ); ?></li>
			<li><?php esc_html_e('Si no tienes lector de código de barras, ingresa el número de seguimiento en el campo correspondiente y presiona Enter en tu teclado.', 'wpcargo-branches' ); ?></li>
		</ol>
	</div>
</div>