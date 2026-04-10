<?php if ( ! defined( 'ABSPATH' ) ) exit;
// Calcular alertas para banner global
$vencidos = array_filter( $vehiculos, fn($v) => WPCVH_Vehiculo::estado_alerta($v) === 'vencido' );
$proximos = array_filter( $vehiculos, fn($v) => WPCVH_Vehiculo::estado_alerta($v) === 'proximo' );
?>

<?php if ( 'guardado' === $mensaje ) : ?>
	<div class="alert alert-success alert-dismissible fade show">Vehículo registrado correctamente.
		<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
<?php elseif ( 'actualizado' === $mensaje ) : ?>
	<div class="alert alert-success alert-dismissible fade show">Vehículo actualizado correctamente.
		<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
<?php elseif ( 'estado_actualizado' === $mensaje ) : ?>
	<div class="alert alert-success alert-dismissible fade show">Estado actualizado.
		<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
<?php endif; ?>

<?php if ( ! empty( $vencidos ) ) : ?>
<div class="alert alert-danger py-2 mb-2">
	<i class="fa fa-exclamation-triangle mr-1"></i>
	<strong><?php echo count($vencidos); ?></strong> vehículo(s) con mantenimiento <strong>VENCIDO</strong>.
</div>
<?php endif; ?>
<?php if ( ! empty( $proximos ) ) : ?>
<div class="alert alert-warning py-2 mb-3">
	<i class="fa fa-clock-o mr-1"></i>
	<strong><?php echo count($proximos); ?></strong> vehículo(s) con mantenimiento <strong>próximo</strong>.
</div>
<?php endif; ?>

<div class="row mb-3 border-bottom pb-3 align-items-center">
	<div class="col">
		<form method="get" action="<?php echo esc_url( $page_url ); ?>" class="form-inline">
			<select name="estado" class="form-control form-control-sm browser-default mr-2">
				<option value="">Todos los estados</option>
				<option value="activo"   <?php selected( $estado, 'activo' ); ?>>Activos</option>
				<option value="inactivo" <?php selected( $estado, 'inactivo' ); ?>>Inactivos</option>
			</select>
			<input type="text" name="buscar" class="form-control form-control-sm mr-2"
				placeholder="Placa, marca o modelo…"
				value="<?php echo esc_attr( $buscar ); ?>">
			<button type="submit" class="btn btn-primary btn-sm mr-2">Filtrar</button>
			<?php if ( $estado || $buscar ) : ?>
				<a href="<?php echo esc_url( $page_url ); ?>" class="btn btn-outline-secondary btn-sm">Limpiar</a>
			<?php endif; ?>
		</form>
	</div>
	<div class="col-auto d-flex gap-2">
		<a href="<?php echo esc_url( wpcvh_frontend_url( [ 'wpcvh' => 'historial' ] ) ); ?>"
		   class="btn btn-outline-secondary btn-sm mr-2">
			<i class="fa fa-history mr-1"></i> Historial
		</a>
		<a href="<?php echo esc_url( wpcvh_frontend_url( [ 'wpcvh' => 'add' ] ) ); ?>"
		   class="btn btn-primary btn-sm">
			<i class="fa fa-plus mr-1"></i> Nuevo Vehículo
		</a>
	</div>
</div>

<div class="table-responsive">
	<table class="table table-hover table-sm">
		<thead class="thead-light">
			<tr>
				<th>Placa</th>
				<th>Tipo / Marca</th>
				<th>KM Actual</th>
				<th>Mantenimiento</th>
				<th>Estado</th>
				<th style="width:70px">Acciones</th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $vehiculos ) ) : ?>
			<tr><td colspan="6" class="text-center text-muted py-4">No se encontraron vehículos.</td></tr>
		<?php else : ?>
			<?php foreach ( $vehiculos as $v ) :
				$alerta      = WPCVH_Vehiculo::estado_alerta( $v );
				$km_falta    = WPCVH_Vehiculo::km_para_mant( $v );
				$km_desde    = (int)$v->km_actual - (int)$v->km_ultimo_mant;
				$pct         = $v->km_limite_mant > 0 ? min( 100, round( $km_desde / $v->km_limite_mant * 100 ) ) : 0;
				$url_editar  = wpcvh_frontend_url( [ 'wpcvh' => 'edit', 'id' => (int) $v->id ] );
				$nuevo_est   = $v->estado === 'activo' ? 'inactivo' : 'activo';
				$row_class   = $alerta === 'vencido' ? 'wpcvh-alerta-vencido' : ( $alerta === 'proximo' ? 'wpcvh-alerta-proximo' : '' );
			?>
			<tr class="<?php echo esc_attr( $row_class ); ?>">
				<td><strong style="font-size:1.05em"><?php echo esc_html( $v->placa ); ?></strong></td>
				<td>
					<?php echo esc_html( $v->tipo ); ?>
					<?php if ( $v->marca ) echo '<br><small class="text-muted">' . esc_html( $v->marca . ( $v->modelo ? ' ' . $v->modelo : '' ) ) . '</small>'; ?>
				</td>
				<td>
					<strong><?php echo esc_html( wpcvh_km( (int) $v->km_actual ) ); ?></strong>
					<br><small class="text-muted">Inicial: <?php echo esc_html( wpcvh_km( (int) $v->km_inicial ) ); ?></small>
				</td>
				<td style="min-width:140px">
					<?php if ( $alerta === 'vencido' ) : ?>
						<span class="badge badge-danger"><i class="fa fa-exclamation-triangle mr-1"></i>VENCIDO</span>
					<?php elseif ( $alerta === 'proximo' ) : ?>
						<span class="badge badge-warning"><i class="fa fa-clock-o mr-1"></i>PRÓXIMO</span>
						<br><small class="text-muted">Faltan <?php echo esc_html( wpcvh_km( $km_falta ) ); ?></small>
					<?php else : ?>
						<span class="text-success small"><i class="fa fa-check mr-1"></i>Al día</span>
						<br><small class="text-muted">Faltan <?php echo esc_html( wpcvh_km( $km_falta ) ); ?></small>
					<?php endif; ?>
					<div class="wpcvh-km-bar mt-1">
						<div class="wpcvh-km-bar-inner <?php echo esc_attr( $alerta ); ?>"
						     style="width:<?php echo esc_attr( $pct ); ?>%"></div>
					</div>
				</td>
				<td>
					<?php echo $v->estado === 'activo'
						? '<span class="badge badge-success">Activo</span>'
						: '<span class="badge badge-secondary">Inactivo</span>'; ?>
				</td>
				<td>
					<a href="<?php echo esc_url( $url_editar ); ?>" title="Editar" class="text-primary mr-2">
						<i class="fa fa-pencil"></i>
					</a>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
						<?php wp_nonce_field( 'wpcvh_fe_estado_nonce' ); ?>
						<input type="hidden" name="action" value="wpcvh_fe_estado">
						<input type="hidden" name="id"     value="<?php echo (int) $v->id; ?>">
						<input type="hidden" name="estado" value="<?php echo esc_attr( $nuevo_est ); ?>">
						<button type="submit" class="btn btn-link p-0 <?php echo $v->estado === 'activo' ? 'text-warning' : 'text-success'; ?>"
						        onclick="return confirm('¿Confirmar cambio de estado?')"
						        title="<?php echo $v->estado === 'activo' ? 'Desactivar' : 'Activar'; ?>">
							<i class="fa <?php echo $v->estado === 'activo' ? 'fa-ban' : 'fa-check'; ?>"></i>
						</button>
					</form>
				</td>
			</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>
