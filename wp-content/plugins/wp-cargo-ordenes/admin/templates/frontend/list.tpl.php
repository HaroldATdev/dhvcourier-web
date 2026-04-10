<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php if ( 'guardado' === $mensaje ) : ?>
	<div class="alert alert-success alert-dismissible fade show">
		Orden creada correctamente. <strong>Código generado automáticamente.</strong>
		<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
	</div>
<?php elseif ( 'actualizado' === $mensaje ) : ?>
	<div class="alert alert-success alert-dismissible fade show">
		Orden actualizada correctamente.
		<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
	</div>
<?php elseif ( 'estado_actualizado' === $mensaje ) : ?>
	<div class="alert alert-success alert-dismissible fade show">
		Estado de la orden actualizado.
		<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
	</div>
<?php endif; ?>

<div class="row mb-3 border-bottom pb-3 align-items-center">
	<div class="col">
		<form method="get" action="<?php echo esc_url( $page_url ); ?>" class="form-inline">
			<input type="text" name="buscar" class="form-control form-control-sm mr-2"
				placeholder="Código, cliente, origen…"
				value="<?php echo esc_attr( $buscar ); ?>">
			<select name="estado" class="form-control form-control-sm browser-default mr-2">
				<option value="">Todos los estados</option>
				<?php foreach ( WPCO_Orden::$estados as $e ) : ?>
					<option value="<?php echo esc_attr( $e ); ?>" <?php selected( $estado, $e ); ?>><?php echo esc_html( $e ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="btn btn-primary btn-sm mr-2">Filtrar</button>
			<?php if ( $estado || $buscar ) : ?>
				<a href="<?php echo esc_url( $page_url ); ?>" class="btn btn-outline-secondary btn-sm">Limpiar</a>
			<?php endif; ?>
		</form>
	</div>
	<div class="col-auto">
		<a href="<?php echo esc_url( wpco_frontend_url( [ 'wpco' => 'add' ] ) ); ?>" class="btn btn-primary btn-sm">
			<i class="fa fa-plus mr-1"></i> Nueva Orden
		</a>
	</div>
</div>

<div class="table-responsive">
	<table class="table table-hover table-sm">
		<thead class="thead-light">
			<tr>
				<th>Código</th>
				<th>Cliente</th>
				<th>Origen</th>
				<th>Destino</th>
				<th>Peso (kg)</th>
				<th>Cant.</th>
				<th>Costo</th>
				<th>Transportista</th>
				<th>Estado</th>
				<th>Fecha</th>
				<th style="width:50px">Acc.</th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $ordenes ) ) : ?>
			<tr><td colspan="11" class="text-center text-muted py-4">No se encontraron órdenes.</td></tr>
		<?php else : ?>
			<?php foreach ( $ordenes as $o ) : ?>
			<tr>
				<td><code><?php echo esc_html( $o->codigo ); ?></code></td>
				<td><?php echo esc_html( $o->cliente ); ?></td>
				<td><?php echo esc_html( $o->origen ); ?></td>
				<td><?php echo esc_html( $o->destino ); ?></td>
				<td><?php echo esc_html( number_format( (float) $o->peso, 3 ) ); ?></td>
				<td><?php echo (int) $o->cantidad; ?></td>
				<td>S/ <?php echo esc_html( number_format( (float) $o->costo, 2 ) ); ?></td>
				<td><?php echo esc_html( $o->transportista_nombre ?? '—' ); ?></td>
				<td>
					<?php
					$badge = match ( $o->estado ) {
						'Registrado'   => 'badge-primary',
						'En transito'  => 'badge-warning',
						'Entregado'    => 'badge-success',
						'Cancelado'    => 'badge-danger',
						default        => 'badge-secondary',
					};
					?>
					<span class="badge <?php echo $badge; ?>"><?php echo esc_html( $o->estado ); ?></span>
				</td>
				<td class="small text-muted"><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $o->fecha_creacion ) ) ); ?></td>
				<td>
					<a href="<?php echo esc_url( wpco_frontend_url( [ 'wpco' => 'edit', 'id' => (int) $o->id ] ) ); ?>"
					   title="Editar" class="text-primary">
						<i class="fa fa-pencil"></i>
					</a>
				</td>
			</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>
