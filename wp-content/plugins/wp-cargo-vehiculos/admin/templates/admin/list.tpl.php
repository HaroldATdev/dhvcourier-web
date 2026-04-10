<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
<h1 class="wp-heading-inline">Vehículos</h1>
<a href="<?php echo esc_url( add_query_arg( [ 'page' => 'wpcvh-nuevo' ], admin_url( 'admin.php' ) ) ); ?>"
   class="page-title-action">+ Nuevo Vehículo</a>
<hr class="wp-header-end">

<?php if ( $mensaje === 'guardado' ) : ?><div class="notice notice-success is-dismissible"><p>Vehículo registrado correctamente.</p></div><?php endif; ?>
<?php if ( $mensaje === 'actualizado' ) : ?><div class="notice notice-success is-dismissible"><p>Vehículo actualizado correctamente.</p></div><?php endif; ?>
<?php if ( $mensaje === 'estado_actualizado' ) : ?><div class="notice notice-success is-dismissible"><p>Estado actualizado.</p></div><?php endif; ?>

<?php
// Contar alertas
$alertas = array_filter( $vehiculos, fn( $v ) => WPCVH_Vehiculo::estado_alerta( $v ) !== 'ok' );
if ( ! empty( $alertas ) ) :
	$vencidos = array_filter( $alertas, fn( $v ) => WPCVH_Vehiculo::estado_alerta( $v ) === 'vencido' );
?>
<div class="notice notice-<?php echo ! empty( $vencidos ) ? 'error' : 'warning'; ?>">
	<p>
		<strong><span class="dashicons dashicons-warning"></span> Alertas de Mantenimiento:</strong>
		<?php if ( ! empty( $vencidos ) ) : ?>
			<?php echo count( $vencidos ); ?> vehículo(s) con mantenimiento <strong>vencido</strong>.
		<?php endif; ?>
		<?php $proximos = array_filter( $alertas, fn( $v ) => WPCVH_Vehiculo::estado_alerta( $v ) === 'proximo' ); ?>
		<?php if ( ! empty( $proximos ) ) : ?>
			<?php echo count( $proximos ); ?> vehículo(s) con mantenimiento <strong>próximo</strong>.
		<?php endif; ?>
	</p>
</div>
<?php endif; ?>

<!-- Filtros -->
<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="margin:16px 0">
	<input type="hidden" name="page" value="wp-cargo-vehiculos">
	<select name="estado" class="postform">
		<option value="">Todos los estados</option>
		<option value="activo"   <?php selected( $estado, 'activo' ); ?>>Activos</option>
		<option value="inactivo" <?php selected( $estado, 'inactivo' ); ?>>Inactivos</option>
	</select>
	<input type="text" name="buscar" value="<?php echo esc_attr( $buscar ); ?>"
	       placeholder="Placa, marca o modelo…" class="regular-text">
	<button type="submit" class="button">Filtrar</button>
	<?php if ( $estado || $buscar ) : ?>
		<a href="<?php echo esc_url( add_query_arg( 'page', 'wp-cargo-vehiculos', admin_url( 'admin.php' ) ) ); ?>" class="button">Limpiar</a>
	<?php endif; ?>
</form>

<table class="wp-list-table widefat fixed striped">
	<thead>
		<tr>
			<th style="width:120px">Placa</th>
			<th>Tipo / Marca / Modelo</th>
			<th style="width:130px">KM Actual</th>
			<th style="width:180px">Alerta Mantenimiento</th>
			<th style="width:80px">Estado</th>
			<th style="width:130px">Acciones</th>
		</tr>
	</thead>
	<tbody>
	<?php if ( empty( $vehiculos ) ) : ?>
		<tr><td colspan="6" style="text-align:center;padding:20px;color:#666">No se encontraron vehículos.</td></tr>
	<?php else : ?>
		<?php foreach ( $vehiculos as $v ) :
			$alerta     = WPCVH_Vehiculo::estado_alerta( $v );
			$km_faltante = WPCVH_Vehiculo::km_para_mant( $v );
			$url_editar  = add_query_arg( [ 'page' => 'wpcvh-nuevo', 'id' => (int) $v->id ], admin_url( 'admin.php' ) );
			$nuevo_est   = $v->estado === 'activo' ? 'inactivo' : 'activo';
			$url_estado  = wp_nonce_url(
				add_query_arg( [ 'action' => 'wpcvh_estado', 'id' => (int) $v->id, 'estado' => $nuevo_est ], admin_url( 'admin-post.php' ) ),
				'wpcvh_estado_nonce'
			);
		?>
		<tr style="<?php echo $alerta === 'vencido' ? 'background:#fff5f5' : ( $alerta === 'proximo' ? 'background:#fffde7' : '' ); ?>">
			<td><strong style="font-size:1.05em"><?php echo esc_html( $v->placa ); ?></strong></td>
			<td>
				<span class="dashicons dashicons-car" style="color:#666;margin-right:4px"></span>
				<?php echo esc_html( implode( ' / ', array_filter( [ $v->tipo, $v->marca, $v->modelo ] ) ) ); ?>
				<?php if ( $v->anio ) echo '<span style="color:#999;font-size:.9em"> · ' . esc_html( $v->anio ) . '</span>'; ?>
			</td>
			<td>
				<strong><?php echo esc_html( wpcvh_km( (int) $v->km_actual ) ); ?></strong>
				<br><small style="color:#999">Inicial: <?php echo esc_html( wpcvh_km( (int) $v->km_inicial ) ); ?></small>
			</td>
			<td>
				<?php if ( $alerta === 'vencido' ) : ?>
					<span style="color:#d63638;font-weight:600">
						<span class="dashicons dashicons-warning"></span> VENCIDO
					</span>
					<br><small style="color:#d63638">Hace <?php echo esc_html( wpcvh_km( (int)$v->km_actual - (int)$v->km_ultimo_mant - (int)$v->km_limite_mant ) ); ?> de exceso</small>
				<?php elseif ( $alerta === 'proximo' ) : ?>
					<span style="color:#996800;font-weight:600">
						<span class="dashicons dashicons-clock"></span> PRÓXIMO
					</span>
					<br><small style="color:#996800">Faltan <?php echo esc_html( wpcvh_km( $km_faltante ) ); ?></small>
				<?php else : ?>
					<span style="color:#00a32a">✓ Al día</span>
					<br><small style="color:#999">Faltan <?php echo esc_html( wpcvh_km( $km_faltante ) ); ?></small>
				<?php endif; ?>
			</td>
			<td>
				<?php echo $v->estado === 'activo'
					? '<span class="dashicons dashicons-yes-alt" style="color:#00a32a"></span> Activo'
					: '<span class="dashicons dashicons-no" style="color:#999"></span> Inactivo'; ?>
			</td>
			<td>
				<a href="<?php echo esc_url( $url_editar ); ?>" class="button button-small">Editar</a>
				<a href="<?php echo esc_url( $url_estado ); ?>"
				   class="button button-small"
				   onclick="return confirm('¿Confirmar cambio de estado?')">
					<?php echo $v->estado === 'activo' ? 'Desactivar' : 'Activar'; ?>
				</a>
			</td>
		</tr>
		<?php endforeach; ?>
	<?php endif; ?>
	</tbody>
</table>
</div>
