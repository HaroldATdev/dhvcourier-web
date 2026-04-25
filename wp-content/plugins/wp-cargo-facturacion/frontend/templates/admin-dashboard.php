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
		<a href="<?php echo admin_url('admin.php?page=wpcfact-emitir'); ?>" target="_blank" class="wpcargo-btn wpcargo-btn-primary" style="background:#2271b1; color:#fff; padding:10px 15px; text-decoration:none; border-radius:3px;">+ Emitir Nuevo Comprobante (Admin)</a>
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
							<?php if ( $comp->estado === 'ACEPTADO' ) : ?>
								<a href="<?php echo esc_url( WPC_Facturacion_APISunat::get_pdf_url( $comp->document_id, $comp->file_name, 'A4' ) ); ?>" target="_blank" class="wpcargo-btn wpcargo-btn-sm" style="background:#444; color:#fff; padding:5px 10px; font-size:12px; text-decoration:none; border-radius:3px;">PDF</a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
