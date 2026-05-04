<div class="wpcargo-container wpcfact-dashboard">
	<h2>Panel de Facturación SUNAT</h2>

	<div style="display:flex; gap:15px; margin-bottom:20px;">
		<div style="flex:1; background:#fff; padding:15px; border-radius:5px; box-shadow:0 1px 3px rgba(0,0,0,0.1); text-align:center; border-top:3px solid #2271b1;">
			<h4 style="margin:0; color:#666;">Emitidos este mes</h4>
			<p style="font-size:24px; font-weight:bold; margin:10px 0 0 0;"><?php echo intval( $kpi_emitidos ); ?></p>
		</div>
		<div style="flex:1; background:#fff; padding:15px; border-radius:5px; box-shadow:0 1px 3px rgba(0,0,0,0.1); text-align:center; border-top:3px solid #46b450;">
			<h4 style="margin:0; color:#666;">Aceptados</h4>
			<p style="font-size:24px; font-weight:bold; margin:10px 0 0 0;"><?php echo intval( $kpi_aceptados ); ?></p>
		</div>
		<div style="flex:1; background:#fff; padding:15px; border-radius:5px; box-shadow:0 1px 3px rgba(0,0,0,0.1); text-align:center; border-top:3px solid #ffb900;">
			<h4 style="margin:0; color:#666;">Pendientes</h4>
			<p style="font-size:24px; font-weight:bold; margin:10px 0 0 0;"><?php echo intval( $kpi_pendientes ); ?></p>
		</div>
		<div style="flex:1; background:#fff; padding:15px; border-radius:5px; box-shadow:0 1px 3px rgba(0,0,0,0.1); text-align:center; border-top:3px solid #dc3232;">
			<h4 style="margin:0; color:#666;">Rechazados / Error</h4>
			<p style="font-size:24px; font-weight:bold; margin:10px 0 0 0;"><?php echo intval( $kpi_rechazados ); ?></p>
		</div>
	</div>

	<div style="margin-bottom:20px; text-align:right;">
		<!-- Enlazar al WP-Admin para crear -->
		<a href="<?php echo home_url('/emitir-comprobante/'); ?>" class="wpcargo-btn wpcargo-btn-primary" style="background:#2271b1; color:#fff; padding:10px 15px; text-decoration:none; border-radius:3px;">+ Emitir Nuevo Comprobante</a>
	</div>

	<table class="wpcargo-table table table-striped table-bordered" style="width:100%; background:#fff;">
		<thead style="background:#f1f1f1;">
			<tr>
				<th>Comprobante</th>
				<th>Cliente</th>
				<th>Fecha</th>
				<th>Total</th>
				<th>Estado</th>
				<th>Acciones</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $comprobantes ) ) : ?>
				<tr><td colspan="6" class="text-center">No hay comprobantes.</td></tr>
			<?php else : ?>
				<?php foreach ( $comprobantes as $comp ) : ?>
					<tr>
						<td>
							<strong><?php echo esc_html( $comp->serie . '-' . $comp->correlativo ); ?></strong><br>
							<small style="color:#888;"><?php echo $comp->tipo == '01' ? 'Factura' : 'Boleta'; ?></small>
						</td>
						<td>
							<?php echo esc_html( $comp->cliente_nombre ); ?><br>
							<small style="color:#888;"><?php echo esc_html( $comp->cliente_doc_num ); ?></small>
						</td>
						<td><?php echo date( 'd/m/Y', strtotime( $comp->emitido_en ) ); ?></td>
						<td>S/. <?php echo number_format( $comp->total, 2 ); ?></td>
						<td>
							<?php
							$badge_class = 'badge bg-secondary';
							if ( $comp->estado === 'ACEPTADO' ) $badge_class = 'badge bg-success';
							if ( $comp->estado === 'PENDIENTE' ) $badge_class = 'badge bg-warning text-dark';
							if ( $comp->estado === 'RECHAZADO' || $comp->estado === 'ERROR' ) $badge_class = 'badge bg-danger';
							?>
							<span class="<?php echo $badge_class; ?>"><?php echo esc_html( $comp->estado ); ?></span>
						</td>
						<td>
							<?php if ( ! empty( $comp->document_id ) && strpos( $comp->document_id, 'LOCAL-' ) !== 0 ) : ?>
								<a href="<?php echo esc_url( WPC_Facturacion_APISunat::get_pdf_url( $comp->document_id, $comp->file_name, 'A4' ) ); ?>" target="_blank" class="wpcargo-btn wpcargo-btn-sm" style="background:#444; color:#fff; padding:5px 10px; font-size:12px; text-decoration:none; border-radius:3px;">PDF</a>
							<?php endif; ?>
							<?php if ( $comp->estado === 'ACEPTADO' ) : ?>
								<button class="wpcargo-btn wpcargo-btn-sm wpcfact-btn-anular" data-id="<?php echo esc_attr( $comp->id ); ?>" style="background:#dc3232; color:#fff; padding:5px 10px; font-size:12px; border:none; border-radius:3px; cursor:pointer;">Anular</button>
								<button class="wpcargo-btn wpcargo-btn-sm wpcfact-btn-ncredito" data-id="<?php echo esc_attr( $comp->id ); ?>" style="background:#f59e0b; color:#fff; padding:5px 10px; font-size:12px; border:none; border-radius:3px; cursor:pointer;">N. Crédito</button>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<!-- Modal Anulación -->
	<div id="wpcfact-modal-anular" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:99999;">
		<div style="background:#fff; width:400px; margin: 100px auto; padding:20px; border-radius:4px; box-shadow:0 4px 8px rgba(0,0,0,0.2);">
			<h3 style="margin-top:0;">Anular Comprobante</h3>
			<p>Por favor, ingrese el motivo de la anulación:</p>
			<input type="text" id="wpcfact-motivo-anulacion" class="regular-text" style="width:100%; padding:8px; margin-bottom:10px;" placeholder="Ej: Error en los datos del cliente">
			<input type="hidden" id="wpcfact-anular-id">
			<p class="submit" style="text-align:right;">
				<button class="wpcargo-btn" id="wpcfact-cancelar-anular" style="background:#ccc; border:none; padding:8px 15px; border-radius:3px; cursor:pointer;">Cancelar</button>
				<button class="wpcargo-btn" id="wpcfact-confirmar-anular" style="background:#dc3232; color:#fff; border:none; padding:8px 15px; border-radius:3px; cursor:pointer;">Confirmar Anulación</button>
			</p>
			<div id="wpcfact-anular-error" style="color:red; margin-top:10px;"></div>
		</div>
	</div>

	<!-- Modal Nota Crédito -->
	<div id="wpcfact-modal-ncredito" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:99999;">
		<div style="background:#fff; width:400px; margin: 100px auto; padding:20px; border-radius:4px; box-shadow:0 4px 8px rgba(0,0,0,0.2);">
			<h3 style="margin-top:0;">Emitir Nota de Crédito</h3>
			<p>Tipo de Nota de Crédito:</p>
			<select id="wpcfact-ncredito-codigo" style="width:100%; margin-bottom: 10px; padding:8px;">
				<option value="01">Anulación de la operación</option>
				<option value="02">Anulación por error en el RUC</option>
				<option value="03">Corrección por error en la descripción</option>
				<option value="04">Descuento global</option>
				<option value="05">Descuento por ítem</option>
				<option value="06">Devolución total</option>
				<option value="07">Devolución por ítem</option>
				<option value="08">Bonificación</option>
				<option value="09">Disminución en el valor</option>
			</select>
			<p>Motivo/Sustento:</p>
			<input type="text" id="wpcfact-motivo-ncredito" style="width:100%; padding:8px; margin-bottom:10px;" placeholder="Ej: Anulación de la operación">
			<input type="hidden" id="wpcfact-ncredito-id">
			<p class="submit" style="text-align:right;">
				<button class="wpcargo-btn" id="wpcfact-cancelar-ncredito" style="background:#ccc; border:none; padding:8px 15px; border-radius:3px; cursor:pointer;">Cancelar</button>
				<button class="wpcargo-btn" id="wpcfact-confirmar-ncredito" style="background:#f59e0b; color:#fff; border:none; padding:8px 15px; border-radius:3px; cursor:pointer;">Emitir N. Crédito</button>
			</p>
			<div id="wpcfact-ncredito-error" style="color:red; margin-top:10px;"></div>
		</div>
	</div>

	<script>
	jQuery(document).ready(function($) {
		var custom_ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";

		$('.wpcfact-btn-anular').click(function(e) {
			e.preventDefault();
			$('#wpcfact-anular-id').val($(this).data('id'));
			$('#wpcfact-motivo-anulacion').val('');
			$('#wpcfact-anular-error').text('');
			$('#wpcfact-modal-anular').show();
		});

		$('#wpcfact-cancelar-anular').click(function() {
			$('#wpcfact-modal-anular').hide();
		});

		$('#wpcfact-confirmar-anular').click(function() {
			var motivo = $('#wpcfact-motivo-anulacion').val();
			if (!motivo) {
				$('#wpcfact-anular-error').text('Debe ingresar un motivo.');
				return;
			}

			$(this).prop('disabled', true).text('Procesando...');

			$.post(custom_ajaxurl, {
				action: 'wpcfact_anular_comprobante',
				nonce: '<?php echo wp_create_nonce("wpcfact_wizard_nonce"); ?>',
				comprobante_id: $('#wpcfact-anular-id').val(),
				motivo: motivo
			}, function(res) {
				if (res.success) {
					Swal.fire('Éxito', 'Comprobante anulado correctamente.', 'success').then(() => location.reload());
				} else {
					$('#wpcfact-confirmar-anular').prop('disabled', false).text('Confirmar Anulación');
					Swal.fire('Error', res.data, 'error');
					$('#wpcfact-anular-error').text(res.data);
				}
			});
		});

		// Nota de Credito Logic
		$('.wpcfact-btn-ncredito').click(function(e) {
			e.preventDefault();
			$('#wpcfact-ncredito-id').val($(this).data('id'));
			$('#wpcfact-motivo-ncredito').val('Anulación de la operación');
			$('#wpcfact-ncredito-codigo').val('01');
			$('#wpcfact-ncredito-error').text('');
			$('#wpcfact-modal-ncredito').show();
		});

		$('#wpcfact-cancelar-ncredito').click(function() {
			$('#wpcfact-modal-ncredito').hide();
		});

		$('#wpcfact-confirmar-ncredito').click(function() {
			var motivo = $('#wpcfact-motivo-ncredito').val();
			var codigo_motivo = $('#wpcfact-ncredito-codigo').val();
			if (!motivo) {
				$('#wpcfact-ncredito-error').text('Debe ingresar un motivo.');
				return;
			}

			$(this).prop('disabled', true).text('Procesando...');

			$.post(custom_ajaxurl, {
				action: 'wpcfact_emitir_nota_credito',
				nonce: '<?php echo wp_create_nonce("wpcfact_wizard_nonce"); ?>',
				comprobante_id: $('#wpcfact-ncredito-id').val(),
				motivo: motivo,
				codigo_motivo: codigo_motivo
			}, function(res) {
				if (res.success) {
					Swal.fire('Éxito', 'Nota de Crédito generada exitosamente.', 'success').then(() => location.reload());
				} else {
					$('#wpcfact-confirmar-ncredito').prop('disabled', false).text('Emitir N. Crédito');
					Swal.fire('Error', res.data, 'error');
					$('#wpcfact-ncredito-error').text(res.data);
				}
			});
		});
	});
	</script>
</div>
