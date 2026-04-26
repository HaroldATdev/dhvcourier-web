<div class="wpcargo-container wpcfact-cliente-dashboard">
	<h2>Mis Comprobantes de Pago</h2>
	<p>Aquí puedes descargar todas las facturas y boletas emitidas a tu nombre.</p>

	<table class="wpcargo-table table table-striped table-bordered" style="width:100%; background:#fff; margin-top:20px;">
		<thead style="background:#f1f1f1;">
			<tr>
				<th>Fecha</th>
				<th>Documento</th>
				<th>Tipo</th>
				<th>Total</th>
				<th>Descargar</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $comprobantes ) ) : ?>
				<tr><td colspan="5" class="text-center">No tienes comprobantes disponibles.</td></tr>
			<?php else : ?>
				<?php foreach ( $comprobantes as $comp ) : ?>
					<tr>
						<td><?php echo date( 'd/m/Y', strtotime( $comp->emitido_en ) ); ?></td>
						<td><strong><?php echo esc_html( $comp->serie . '-' . $comp->correlativo ); ?></strong></td>
						<td><?php echo $comp->tipo == '01' ? 'Factura' : 'Boleta'; ?></td>
						<td>S/. <?php echo number_format( $comp->total, 2 ); ?></td>
						<td>
							<a href="<?php echo esc_url( WPC_Facturacion_APISunat::get_pdf_url( $comp->document_id, $comp->file_name, 'A4' ) ); ?>" target="_blank" class="wpcargo-btn wpcargo-btn-sm" style="background:#2271b1; color:#fff; padding:5px 10px; font-size:12px; text-decoration:none; border-radius:3px;">
								<i class="fa fa-download"></i> PDF
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
