<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
<h1>Historial de Mantenimientos</h1>
<hr class="wp-header-end">

<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="margin:16px 0;display:flex;gap:12px;align-items:center;flex-wrap:wrap">
	<input type="hidden" name="page" value="wpcvh-mant">
	<select name="vehiculo_id" class="postform">
		<option value="">Todos los vehículos</option>
		<?php foreach ( $vehiculos as $v ) : ?>
			<option value="<?php echo (int) $v->id; ?>" <?php selected( $vehiculo_id, $v->id ); ?>>
				<?php echo esc_html( $v->placa . ' – ' . $v->tipo ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<label>Desde: <input type="date" name="desde" value="<?php echo esc_attr( $desde ); ?>"></label>
	<label>Hasta: <input type="date" name="hasta" value="<?php echo esc_attr( $hasta ); ?>"></label>
	<button type="submit" class="button">Filtrar</button>
	<?php if ( $vehiculo_id || $desde || $hasta ) : ?>
		<a href="<?php echo esc_url( add_query_arg( 'page', 'wpcvh-mant', admin_url( 'admin.php' ) ) ); ?>" class="button">Limpiar</a>
	<?php endif; ?>
</form>

<?php if ( ! empty( $mants ) ) :
	$total_costo = array_sum( array_column( (array) $mants, 'costo' ) );
?>
<p style="margin-bottom:8px">
	<strong><?php echo count( $mants ); ?></strong> registro(s) encontrado(s).
	Costo total: <strong>S/ <?php echo esc_html( number_format( $total_costo, 2 ) ); ?></strong>
</p>
<?php endif; ?>

<table class="wp-list-table widefat fixed striped">
	<thead>
		<tr>
			<th style="width:100px">Fecha</th>
			<th style="width:120px">Vehículo</th>
			<th>Tipo de mantenimiento</th>
			<th style="width:110px">KM al momento</th>
			<th style="width:100px">Costo</th>
			<th>Descripción</th>
		</tr>
	</thead>
	<tbody>
	<?php if ( empty( $mants ) ) : ?>
		<tr><td colspan="6" style="text-align:center;padding:20px;color:#666">No hay mantenimientos registrados.</td></tr>
	<?php else : ?>
		<?php foreach ( $mants as $m ) : ?>
		<tr>
			<td><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $m->realizado_en ) ) ); ?></td>
			<td>
				<strong><?php echo esc_html( $m->placa ); ?></strong>
				<br><small style="color:#666"><?php echo esc_html( $m->tipo ); ?></small>
			</td>
			<td><?php echo esc_html( $m->tipo_mant ); ?></td>
			<td><?php echo esc_html( wpcvh_km( (int) $m->km_al_momento ) ); ?></td>
			<td>S/ <?php echo esc_html( number_format( (float) $m->costo, 2 ) ); ?></td>
			<td style="color:#666"><?php echo esc_html( $m->descripcion ?: '—' ); ?></td>
		</tr>
		<?php endforeach; ?>
	<?php endif; ?>
	</tbody>
</table>
</div>
