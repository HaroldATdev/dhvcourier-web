<?php if ( ! defined( 'ABSPATH' ) ) exit;
$es_driver = $es_driver ?? false;
?>
<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php if ( 'guardado' === $mensaje ) : ?>
	<div class="alert alert-success alert-dismissible fade show">
		Viático creado correctamente.
		<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
	</div>
<?php elseif ( 'actualizado' === $mensaje ) : ?>
	<div class="alert alert-success alert-dismissible fade show">
		Viático actualizado correctamente.
		<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
	</div>
<?php elseif ( 'cerrado' === $mensaje ) : ?>
	<div class="alert alert-info alert-dismissible fade show">
		Viático cerrado correctamente.
		<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
	</div>
<?php endif; ?>

<?php if ( ! $es_driver ) : ?>
<div class="row mb-3 border-bottom pb-3 align-items-center">
	<div class="col">
		<form method="get" action="<?php echo esc_url( $page_url ); ?>" class="form-inline">
			<?php if ( ! empty( $transportistas ) ) : ?>
			<select name="transportista_id" class="form-control form-control-sm browser-default mr-2">
				<option value="">Todos los transportistas</option>
				<?php foreach ( $transportistas as $t ) : ?>
					<option value="<?php echo (int) $t->id; ?>" <?php selected( $transportista_id, $t->id ); ?>>
						<?php echo esc_html( $t->nombre ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php endif; ?>
			<select name="estado" class="form-control form-control-sm browser-default mr-2">
				<option value="">Todos los estados</option>
				<option value="activo"  <?php selected( $estado, 'activo' ); ?>>Activos</option>
				<option value="cerrado" <?php selected( $estado, 'cerrado' ); ?>>Cerrados</option>
			</select>
			<button type="submit" class="btn btn-primary btn-sm mr-2">Filtrar</button>
			<?php if ( $estado || $transportista_id ) : ?>
				<a href="<?php echo esc_url( $page_url ); ?>" class="btn btn-outline-secondary btn-sm">Limpiar</a>
			<?php endif; ?>
		</form>
	</div>
	<div class="col-auto">
		<a href="<?php echo esc_url( wpcv_frontend_url( [ 'wpcv' => 'historial' ] ) ); ?>"
		   class="btn btn-outline-secondary btn-sm mr-1">
			<i class="fa fa-history mr-1"></i> Historial
		</a>
		<a href="<?php echo esc_url( wpcv_frontend_url( [ 'wpcv' => 'reportes' ] ) ); ?>"
		   class="btn btn-outline-secondary btn-sm mr-2">
			<i class="fa fa-bar-chart mr-1"></i> Reportes
		</a>
		<a href="<?php echo esc_url( wpcv_frontend_url( [ 'wpcv' => 'add' ] ) ); ?>" class="btn btn-primary btn-sm">
			<i class="fa fa-plus mr-1"></i> Nuevo Viático
		</a>
	</div>
</div>
<?php else : ?>
<div class="row mb-3 border-bottom pb-3 align-items-center">
	<div class="col">
		<h5 class="mb-0"><i class="fa fa-money mr-2 text-success"></i>Mis Viáticos</h5>
		<small class="text-muted">Viáticos asignados a tu cuenta</small>
	</div>
	<div class="col-auto">
		<a href="<?php echo esc_url( wpcv_frontend_url( [ 'wpcv' => 'historial' ] ) ); ?>"
		   class="btn btn-outline-secondary btn-sm">
			<i class="fa fa-history mr-1"></i> Mi Historial
		</a>
	</div>
</div>
<?php endif; ?>

<div class="table-responsive">
	<table class="table table-hover table-sm">
		<thead class="thead-light">
			<tr>
				<th>Transportista</th>
				<th>Ruta</th>
				<th>Asignado</th>
				<th>Gastado</th>
				<th>Saldo</th>
				<th>Fecha</th>
				<th>Estado</th>
				<th style="width:70px">Acciones</th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $viaticos ) ) : ?>
			<tr><td colspan="8" class="text-center text-muted py-4">No se encontraron viáticos.</td></tr>
		<?php else : ?>
			<?php foreach ( $viaticos as $v ) :
				$saldo      = WPCV_Viatico::saldo( $v );
				$url_gastos = wpcv_frontend_url( [ 'wpcv' => 'gastos', 'viatico_id' => (int) $v->id ] );
				$url_editar = wpcv_frontend_url( [ 'wpcv' => 'edit', 'id' => (int) $v->id ] );
			?>
			<tr>
				<td><strong><?php echo esc_html( $v->transportista_nombre ?? ( 'ID #' . $v->transportista_id ) ); ?></strong></td>
				<td><?php echo esc_html( $v->ruta ); ?></td>
				<td><?php echo esc_html( wpcv_monto( (float) $v->monto_asignado ) ); ?></td>
				<td><?php echo esc_html( wpcv_monto( (float) $v->monto_usado ) ); ?></td>
				<td class="<?php echo $saldo < 0 ? 'text-danger font-weight-bold' : 'text-success font-weight-bold'; ?>">
					<?php echo esc_html( wpcv_monto( $saldo ) ); ?>
				</td>
				<td><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $v->fecha_asignacion ) ) ); ?></td>
				<td>
					<?php if ( 'activo' === $v->estado ) : ?>
						<span class="badge badge-success">Activo</span>
					<?php else : ?>
						<span class="badge badge-secondary">Cerrado</span>
					<?php endif; ?>
				</td>
				<td>
					<a href="<?php echo esc_url( $url_gastos ); ?>" title="Ver gastos" class="text-info mr-2">
						<i class="fa fa-list"></i>
					</a>
					<?php if ( ! $es_driver && 'activo' === $v->estado ) : ?>
						<a href="<?php echo esc_url( $url_editar ); ?>" title="Editar" class="text-primary">
							<i class="fa fa-pencil"></i>
						</a>
					<?php endif; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>
