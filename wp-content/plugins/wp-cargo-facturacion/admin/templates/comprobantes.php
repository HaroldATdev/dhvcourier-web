<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table = WPC_Facturacion_Comprobante::get_table();

// Paginación y Filtros básicos
$paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
$limit = 20;
$offset = ( $paged - 1 ) * $limit;

$comprobantes = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}" );
$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
$total_pages = ceil( $total_items / $limit );
?>
<div class="wrap">
	<h1 class="wp-heading-inline">Comprobantes Emitidos</h1>
	<a href="admin.php?page=wpcfact-emitir" class="page-title-action">Emitir Nuevo</a>
	<hr class="wp-header-end">

	<table class="wp-list-table widefat fixed striped table-view-list">
		<thead>
			<tr>
				<th>ID</th>
				<th>Documento</th>
				<th>Cliente</th>
				<th>Fecha Emisión</th>
				<th>Total (S/.)</th>
				<th>Estado</th>
				<th>Acciones</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $comprobantes ) ) : ?>
				<tr><td colspan="7">No se encontraron comprobantes.</td></tr>
			<?php else : ?>
				<?php foreach ( $comprobantes as $comp ) : ?>
					<tr>
						<td><?php echo esc_html( $comp->id ); ?></td>
						<td><strong><?php echo esc_html( $comp->serie . '-' . $comp->correlativo ); ?></strong><br><small><?php echo esc_html( $comp->tipo == '01' ? 'Factura' : 'Boleta' ); ?></small></td>
						<td><?php echo esc_html( $comp->cliente_nombre ); ?><br><small><?php echo esc_html( $comp->cliente_doc_num ); ?></small></td>
						<td><?php echo esc_html( gmdate( 'd/m/Y H:i', strtotime( $comp->emitido_en ) ) ); ?></td>
						<td><?php echo number_format( $comp->total, 2 ); ?></td>
						<td>
							<?php if ( $comp->estado === 'ACEPTADO' ) : ?>
								<span style="color:green; font-weight:bold;">ACEPTADO</span>
							<?php elseif ( $comp->estado === 'PENDIENTE' ) : ?>
								<span style="color:orange; font-weight:bold;">PENDIENTE</span>
							<?php elseif ( $comp->estado === 'ANULADO' ) : ?>
								<span style="color:gray; font-weight:bold;">ANULADO</span>
							<?php else : ?>
								<span style="color:red; font-weight:bold;"><?php echo esc_html( $comp->estado ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( $comp->estado === 'ACEPTADO' ) : ?>
								<a href="<?php echo esc_url( WPC_Facturacion_APISunat::get_pdf_url( $comp->document_id, $comp->file_name, 'A4' ) ); ?>" target="_blank" class="button button-small">PDF (A4)</a>
								<a href="<?php echo esc_url( WPC_Facturacion_APISunat::get_pdf_url( $comp->document_id, $comp->file_name, 'ticket80mm' ) ); ?>" target="_blank" class="button button-small">Ticket</a>
								<button class="button button-small button-link-delete wpcfact-btn-anular" data-id="<?php echo esc_attr( $comp->id ); ?>">Anular</button>
							<?php endif; ?>
							<?php if ( $comp->estado === 'PENDIENTE' ) : ?>
								<button class="button button-small" disabled title="Esperando a SUNAT">Procesando...</button>
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
			<h3>Anular Comprobante</h3>
			<p>Por favor, ingrese el motivo de la anulación:</p>
			<input type="text" id="wpcfact-motivo-anulacion" class="regular-text" style="width:100%;" placeholder="Ej: Error en los datos del cliente">
			<input type="hidden" id="wpcfact-anular-id">
			<p class="submit">
				<button class="button" id="wpcfact-cancelar-anular">Cancelar</button>
				<button class="button button-primary" id="wpcfact-confirmar-anular">Confirmar Anulación</button>
			</p>
			<div id="wpcfact-anular-spinner" class="spinner" style="float:none;"></div>
			<div id="wpcfact-anular-error" style="color:red; margin-top:10px;"></div>
		</div>
	</div>

	<script>
	jQuery(document).ready(function($) {
		$('.wpcfact-btn-anular').click(function() {
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

			$('#wpcfact-anular-spinner').addClass('is-active');
			$(this).prop('disabled', true);

			$.post(ajaxurl, {
				action: 'wpcfact_anular_comprobante',
				nonce: '<?php echo wp_create_nonce("wpcfact_wizard_nonce"); ?>',
				comprobante_id: $('#wpcfact-anular-id').val(),
				motivo: motivo
			}, function(res) {
				if (res.success) {
					location.reload();
				} else {
					$('#wpcfact-anular-spinner').removeClass('is-active');
					$('#wpcfact-confirmar-anular').prop('disabled', false);
					$('#wpcfact-anular-error').text(res.data);
				}
			});
		});
	});
	</script>
</div>
