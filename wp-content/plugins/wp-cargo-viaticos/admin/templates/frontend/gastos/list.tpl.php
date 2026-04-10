<?php if ( ! defined( 'ABSPATH' ) ) exit;
$es_driver = $es_driver ?? false;
?>

<div class="row mb-3 align-items-center">
	<div class="col">
		<h5 class="mb-0">
			<a href="<?php echo esc_url( $page_url ); ?>" class="text-secondary mr-2" style="font-size:.9rem">
				<i class="fa fa-arrow-left"></i>
			</a>
			Gastos del Viático
		</h5>
		<small class="text-muted">
			<?php echo esc_html( $viatico->ruta ); ?> &mdash; <?php echo esc_html( $transportista_nombre ); ?>
		</small>
	</div>
	<?php if ( 'activo' === $viatico->estado ) : ?>
	<div class="col-auto">
		<a href="<?php echo esc_url( wpcv_frontend_url( [ 'wpcv' => 'gasto_add', 'viatico_id' => $viatico->id ] ) ); ?>"
		   class="btn btn-primary btn-sm">
			<i class="fa fa-plus mr-1"></i> Registrar Gasto
		</a>
	</div>
	<?php endif; ?>
</div>

<?php if ( 'guardado' === $msg_gasto ) : ?>
	<div class="alert alert-success alert-dismissible fade show">
		Gasto registrado correctamente.
		<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
	</div>
<?php elseif ( 'eliminado' === $msg_gasto ) : ?>
	<div class="alert alert-info alert-dismissible fade show">
		Gasto eliminado.
		<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
	</div>
<?php elseif ( 'cerrado' === $msg_gasto ) : ?>
	<div class="alert alert-success alert-dismissible fade show">
		Viático cerrado correctamente.
		<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
	</div>
<?php endif; ?>

<!-- Resumen del viático -->
<?php $saldo = WPCV_Viatico::saldo( $viatico ); ?>
<div class="row mb-3">
	<div class="col-md-3"><div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:6px;text-align:center"><div style="padding:8px 14px">
		<div class="small text-muted">Asignado</div>
		<div class="font-weight-bold"><?php echo esc_html( wpcv_monto( (float) $viatico->monto_asignado ) ); ?></div>
	</div></div></div>
	<div class="col-md-3"><div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:6px;text-align:center"><div style="padding:8px 14px">
		<div class="small text-muted">Gastado</div>
		<div class="font-weight-bold"><?php echo esc_html( wpcv_monto( (float) $viatico->monto_usado ) ); ?></div>
	</div></div></div>
	<div class="col-md-3"><div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:6px;text-align:center"><div style="padding:8px 14px">
		<div class="small text-muted">Saldo</div>
		<div class="font-weight-bold <?php echo $saldo < 0 ? 'text-danger' : 'text-success'; ?>">
			<?php echo esc_html( wpcv_monto( $saldo ) ); ?>
		</div>
	</div></div></div>
	<div class="col-md-3"><div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:6px;text-align:center"><div style="padding:8px 14px">
		<div class="small text-muted">Estado</div>
		<div><?php echo 'activo' === $viatico->estado
			? '<span class="badge badge-success">Activo</span>'
			: '<span class="badge badge-secondary">Cerrado</span>'; ?></div>
	</div></div></div>
</div>

<!-- Tabla de gastos -->
<?php if ( empty( $gastos ) ) : ?>
	<div class="text-center text-muted py-4">
		<i class="fa fa-inbox fa-2x mb-2 d-block"></i>
		No hay gastos registrados.
	</div>
<?php else : ?>
<div class="table-responsive">
	<table class="table table-hover table-sm">
		<thead class="thead-light">
			<tr>
				<th>Tipo</th><th>Descripción</th><th>Monto</th><th>Sustento</th><th>Fecha</th>
				<?php if ( 'activo' === $viatico->estado ) : ?><th style="width:40px"></th><?php endif; ?>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $gastos as $g ) : ?>
			<tr>
				<td><span class="badge badge-light border"><?php echo esc_html( $g->tipo ); ?></span></td>
				<td class="text-muted small"><?php echo esc_html( $g->descripcion ?: '—' ); ?></td>
				<td class="font-weight-bold"><?php echo esc_html( wpcv_monto( (float) $g->monto ) ); ?></td>
				<td>
					<?php if ( $g->sustento_url ) : ?>
						<a href="<?php echo esc_url( $g->sustento_url ); ?>" target="_blank" class="text-primary">
							<i class="fa <?php echo $g->sustento_tipo === 'pdf' ? 'fa-file-pdf-o' : 'fa-image'; ?>"></i>
						</a>
					<?php else : ?><span class="text-muted">—</span><?php endif; ?>
				</td>
				<td class="small text-muted"><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $g->fecha_gasto ) ) ); ?></td>
				<?php if ( 'activo' === $viatico->estado && ! $es_driver ) : ?>
				<td>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
						<?php wp_nonce_field( 'wpcv_fe_del_gasto' ); ?>
						<input type="hidden" name="action"     value="wpcv_gasto_eliminar_fe">
						<input type="hidden" name="gasto_id"   value="<?php echo (int) $g->id; ?>">
						<input type="hidden" name="viatico_id" value="<?php echo (int) $viatico->id; ?>">
						<button type="submit" class="btn btn-link text-danger p-0"
						        onclick="return confirm('¿Eliminar este gasto?')" title="Eliminar">
							<i class="fa fa-trash"></i>
						</button>
					</form>
				</td>
				<?php endif; ?>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
<?php endif; ?>

<!-- Cierre / Resumen de cierre (Historia 1.4) -->
<?php if ( 'activo' === $viatico->estado && ! $es_driver ) : ?>
<div class="border-top mt-4 pt-3">
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
	      onsubmit="return confirm('¿Cerrar este viático? No podrás agregar más gastos.')">
		<?php wp_nonce_field( 'wpcv_fe_cerrar_nonce' ); ?>
		<input type="hidden" name="action" value="wpcv_cerrar_fe">
		<input type="hidden" name="id"     value="<?php echo (int) $viatico->id; ?>">
		<button type="submit" class="btn btn-warning btn-sm">
			<i class="fa fa-lock mr-1"></i> Cerrar Viático
		</button>
	</form>
</div>
<?php else : ?>
<div class="border-top mt-4 pt-3">
	<div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:6px">
		<div style="padding:8px 14px">
			<strong>Resumen de cierre</strong>
			<div class="row mt-2 text-center">
				<div class="col-4">
					<div class="small text-muted">Asignado</div>
					<div><?php echo esc_html( wpcv_monto( (float) $viatico->monto_asignado ) ); ?></div>
				</div>
				<div class="col-4">
					<div class="small text-muted">Gastado</div>
					<div><?php echo esc_html( wpcv_monto( (float) $viatico->monto_usado ) ); ?></div>
				</div>
				<div class="col-4">
					<div class="small text-muted">Diferencia</div>
					<div class="font-weight-bold <?php echo $saldo < 0 ? 'text-danger' : 'text-success'; ?>">
						<?php echo esc_html( wpcv_monto( $saldo ) ); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php endif; ?>
