<?php if ( ! defined( 'ABSPATH' ) ) exit;
$es_driver = $es_driver ?? false;
?>

<div class="row mb-3 align-items-center">
	<div class="col">
		<h5 class="mb-0">
			<?php echo $es_driver ? 'Mi Historial de Viáticos' : 'Historial por Transportista'; ?>
		</h5>
	</div>
	<div class="col-auto">
		<a href="<?php echo esc_url( $page_url ); ?>" class="btn btn-outline-secondary btn-sm">
			<i class="fa fa-arrow-left mr-1"></i> Volver
		</a>
	</div>
</div>

<!-- Filtros -->
<form method="get" action="<?php echo esc_url( $page_url ); ?>" class="form-inline mb-3 flex-wrap">
	<input type="hidden" name="wpcv" value="historial">

	<?php if ( ! $es_driver && ! empty( $transportistas ) ) : ?>
	<select name="transportista_id" class="form-control form-control-sm browser-default mr-2 mb-2" style="max-width:250px">
		<option value="">Todos los transportistas</option>
		<?php foreach ( $transportistas as $t ) : ?>
			<option value="<?php echo (int) $t->id; ?>" <?php selected( $transportista_id, $t->id ); ?>>
				<?php echo esc_html( $t->nombre . ' (' . $t->brevete . ')' ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php endif; ?>

	<select name="estado" class="form-control form-control-sm browser-default mr-2 mb-2" style="max-width:160px">
		<option value="">Todos los estados</option>
		<option value="activo"  <?php selected( $estado, 'activo' ); ?>>Activos</option>
		<option value="cerrado" <?php selected( $estado, 'cerrado' ); ?>>Cerrados</option>
	</select>
	<input type="date" name="desde" class="form-control form-control-sm mr-2 mb-2"
		value="<?php echo esc_attr( $desde ); ?>">
	<input type="date" name="hasta" class="form-control form-control-sm mr-2 mb-2"
		value="<?php echo esc_attr( $hasta ); ?>">
	<button type="submit" class="btn btn-primary btn-sm mr-2 mb-2">Filtrar</button>
	<?php if ( ( ! $es_driver && $transportista_id ) || $desde || $hasta || $estado ) : ?>
		<a href="<?php echo esc_url( wpcv_frontend_url( [ 'wpcv' => 'historial' ] ) ); ?>"
		   class="btn btn-outline-secondary btn-sm mb-2">Limpiar</a>
	<?php endif; ?>
</form>

<!-- Totales -->
<?php if ( ! empty( $historial ) ) :
	$total_asignado = array_sum( array_column( (array) $historial, 'monto_asignado' ) );
	$total_usado    = array_sum( array_column( (array) $historial, 'monto_usado' ) );
	$diferencia     = $total_asignado - $total_usado;
?>
<div class="row mb-3">
	<div class="col-md-3"><div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:6px;text-align:center"><div style="padding:8px 14px">
		<div class="small text-muted">Registros</div>
		<div class="h6 mb-0 font-weight-bold"><?php echo count( $historial ); ?></div>
	</div></div></div>
	<div class="col-md-3"><div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:6px;text-align:center"><div style="padding:8px 14px">
		<div class="small text-muted">Total Asignado</div>
		<div class="h6 mb-0 font-weight-bold"><?php echo esc_html( wpcv_monto( $total_asignado ) ); ?></div>
	</div></div></div>
	<div class="col-md-3"><div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:6px;text-align:center"><div style="padding:8px 14px">
		<div class="small text-muted">Total Ejecutado</div>
		<div class="h6 mb-0 font-weight-bold"><?php echo esc_html( wpcv_monto( $total_usado ) ); ?></div>
	</div></div></div>
	<div class="col-md-3"><div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:6px;text-align:center"><div style="padding:8px 14px">
		<div class="small text-muted">Diferencia</div>
		<div class="h6 mb-0 font-weight-bold <?php echo $diferencia >= 0 ? 'text-success' : 'text-danger'; ?>">
			<?php echo esc_html( wpcv_monto( $diferencia ) ); ?>
		</div>
	</div></div></div>
</div>
<?php endif; ?>

<!-- Tabla -->
<div class="table-responsive">
<table class="table table-hover table-sm">
	<thead class="thead-light">
		<tr>
			<th>Fecha</th>
			<?php if ( ! $es_driver ) : ?><th>Transportista</th><?php endif; ?>
			<th>Ruta</th>
			<th>Asignado</th>
			<th>Ejecutado</th>
			<th>Diferencia</th>
			<th>Estado</th>
			<th>Gastos</th>
		</tr>
	</thead>
	<tbody>
	<?php if ( empty( $historial ) ) : ?>
		<tr><td colspan="<?php echo $es_driver ? '7' : '8'; ?>" class="text-center text-muted py-4">
			Sin registros para los filtros seleccionados.
		</td></tr>
	<?php else : ?>
		<?php foreach ( $historial as $h ) :
			$diff = (float)$h->monto_asignado - (float)$h->monto_usado;
		?>
		<tr>
			<td class="small"><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $h->fecha_asignacion ) ) ); ?></td>
			<?php if ( ! $es_driver ) : ?>
			<td><strong><?php echo esc_html( $h->transportista_nombre ?? '—' ); ?></strong></td>
			<?php endif; ?>
			<td><?php echo esc_html( $h->ruta ); ?></td>
			<td><?php echo esc_html( wpcv_monto( (float)$h->monto_asignado ) ); ?></td>
			<td><?php echo esc_html( wpcv_monto( (float)$h->monto_usado ) ); ?></td>
			<td class="font-weight-bold <?php echo $diff >= 0 ? 'text-success' : 'text-danger'; ?>">
				<?php echo esc_html( wpcv_monto( $diff ) ); ?>
			</td>
			<td>
				<?php echo $h->estado === 'cerrado'
					? '<span class="badge badge-secondary"><i class="fa fa-lock mr-1"></i>Cerrado</span>'
					: '<span class="badge badge-success">Activo</span>'; ?>
			</td>
			<td>
				<a href="<?php echo esc_url( wpcv_frontend_url( [ 'wpcv' => 'gastos', 'viatico_id' => (int)$h->id ] ) ); ?>"
				   class="text-info" title="Ver gastos">
					<i class="fa fa-list"></i>
				</a>
			</td>
		</tr>
		<?php endforeach; ?>
	<?php endif; ?>
	</tbody>
</table>
</div>
