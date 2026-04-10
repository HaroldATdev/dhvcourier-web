<?php if ( ! defined( 'ABSPATH' ) ) exit;
$total_costo = empty( $mants ) ? 0 : array_sum( array_column( (array) $mants, 'costo' ) );
?>

<div class="row mb-3 align-items-center">
	<div class="col">
		<h5 class="mb-0">Historial de Mantenimientos</h5>
	</div>
	<div class="col-auto">
		<a href="<?php echo esc_url( $page_url ); ?>" class="btn btn-outline-secondary btn-sm">
			<i class="fa fa-arrow-left mr-1"></i> Volver
		</a>
	</div>
</div>

<!-- Filtros -->
<form method="get" action="<?php echo esc_url( $page_url ); ?>" class="form-inline mb-3 flex-wrap">
	<input type="hidden" name="wpcvh" value="historial">
	<select name="vehiculo_id" class="form-control form-control-sm browser-default mr-2 mb-2">
		<option value="">Todos los vehículos</option>
		<?php foreach ( $vehiculos as $v ) : ?>
			<option value="<?php echo (int) $v->id; ?>" <?php selected( $vehiculo_id, $v->id ); ?>>
				<?php echo esc_html( $v->placa . ' – ' . $v->tipo ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<input type="date" name="desde" class="form-control form-control-sm mr-2 mb-2"
		value="<?php echo esc_attr( $desde ); ?>" title="Desde">
	<input type="date" name="hasta" class="form-control form-control-sm mr-2 mb-2"
		value="<?php echo esc_attr( $hasta ); ?>" title="Hasta">
	<button type="submit" class="btn btn-primary btn-sm mr-2 mb-2">Filtrar</button>
	<?php if ( $vehiculo_id || $desde || $hasta ) : ?>
		<a href="<?php echo esc_url( wpcvh_frontend_url( [ 'wpcvh' => 'historial' ] ) ); ?>"
		   class="btn btn-outline-secondary btn-sm mb-2">Limpiar</a>
	<?php endif; ?>
</form>

<!-- Indicadores resumen -->
<?php if ( ! empty( $mants ) ) : ?>
<div class="row mb-3">
	<div class="col-md-4">
		<div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:6px;text-align:center">
			<div style="padding:8px 14px">
				<div class="small text-muted">Registros</div>
				<div class="h5 mb-0 font-weight-bold"><?php echo count( $mants ); ?></div>
			</div>
		</div>
	</div>
	<div class="col-md-4">
		<div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:6px;text-align:center">
			<div style="padding:8px 14px">
				<div class="small text-muted">Costo Total</div>
				<div class="h5 mb-0 font-weight-bold">S/ <?php echo esc_html( number_format( $total_costo, 2 ) ); ?></div>
			</div>
		</div>
	</div>
	<div class="col-md-4">
		<div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:6px;text-align:center">
			<div style="padding:8px 14px">
				<div class="small text-muted">Promedio por servicio</div>
				<div class="h5 mb-0 font-weight-bold">S/ <?php echo esc_html( number_format( $total_costo / count( $mants ), 2 ) ); ?></div>
			</div>
		</div>
	</div>
</div>
<?php endif; ?>

<!-- Tabla -->
<div class="table-responsive">
<table class="table table-hover table-sm">
	<thead class="thead-light">
		<tr>
			<th>Fecha</th>
			<th>Vehículo</th>
			<th>Tipo de mantenimiento</th>
			<th>KM al momento</th>
			<th>Costo</th>
			<th>Descripción</th>
		</tr>
	</thead>
	<tbody>
	<?php if ( empty( $mants ) ) : ?>
		<tr><td colspan="6" class="text-center text-muted py-4">No hay mantenimientos registrados.</td></tr>
	<?php else : ?>
		<?php foreach ( $mants as $m ) : ?>
		<tr>
			<td class="small"><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $m->realizado_en ) ) ); ?></td>
			<td>
				<strong><?php echo esc_html( $m->placa ); ?></strong>
				<br><small class="text-muted"><?php echo esc_html( $m->tipo ); ?></small>
			</td>
			<td><?php echo esc_html( $m->tipo_mant ); ?></td>
			<td><?php echo esc_html( wpcvh_km( (int) $m->km_al_momento ) ); ?></td>
			<td class="font-weight-bold">S/ <?php echo esc_html( number_format( (float) $m->costo, 2 ) ); ?></td>
			<td class="text-muted small"><?php echo esc_html( $m->descripcion ?: '—' ); ?></td>
		</tr>
		<?php endforeach; ?>
	<?php endif; ?>
	</tbody>
</table>
</div>
