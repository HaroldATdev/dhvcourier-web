<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
<h1>Historial por Transportista</h1>
<hr class="wp-header-end">

<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="margin:16px 0;display:flex;gap:12px;align-items:center;flex-wrap:wrap">
	<input type="hidden" name="page" value="wpcv-historial">
	<select name="transportista_id" class="postform">
		<option value="">Todos los transportistas</option>
		<?php foreach ( $transportistas as $t ) : ?>
			<option value="<?php echo (int) $t->id; ?>" <?php selected( $transportista_id, $t->id ); ?>>
				<?php echo esc_html( $t->nombre . ' (' . $t->brevete . ')' ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<select name="estado" class="postform">
		<option value="">Todos los estados</option>
		<option value="activo"  <?php selected( $estado, 'activo' ); ?>>Activos</option>
		<option value="cerrado" <?php selected( $estado, 'cerrado' ); ?>>Cerrados</option>
	</select>
	<label>Desde: <input type="date" name="desde" value="<?php echo esc_attr( $desde ); ?>"></label>
	<label>Hasta: <input type="date" name="hasta" value="<?php echo esc_attr( $hasta ); ?>"></label>
	<button type="submit" class="button">Filtrar</button>
	<?php if ( $transportista_id || $estado || $desde || $hasta ) : ?>
		<a href="<?php echo esc_url( add_query_arg( 'page', 'wpcv-historial', admin_url( 'admin.php' ) ) ); ?>" class="button">Limpiar</a>
	<?php endif; ?>
</form>

<?php if ( ! empty( $historial ) ) :
	$total_asignado = array_sum( array_column( (array) $historial, 'monto_asignado' ) );
	$total_usado    = array_sum( array_column( (array) $historial, 'monto_usado' ) );
	$diferencia     = $total_asignado - $total_usado;
?>
<table style="margin-bottom:16px;background:#f0f0f1;padding:12px 16px;border-radius:4px">
	<tr>
		<td style="padding-right:32px"><strong>Registros:</strong> <?php echo count($historial); ?></td>
		<td style="padding-right:32px"><strong>Total asignado:</strong> S/ <?php echo esc_html( number_format( $total_asignado, 2 ) ); ?></td>
		<td style="padding-right:32px"><strong>Total ejecutado:</strong> S/ <?php echo esc_html( number_format( $total_usado, 2 ) ); ?></td>
		<td><strong>Diferencia:</strong> <span style="color:<?php echo $diferencia >= 0 ? '#00a32a' : '#d63638'; ?>">S/ <?php echo esc_html( number_format( $diferencia, 2 ) ); ?></span></td>
	</tr>
</table>
<?php endif; ?>

<table class="wp-list-table widefat fixed striped">
	<thead>
		<tr>
			<th style="width:100px">Fecha</th>
			<th>Transportista</th>
			<th>Ruta</th>
			<th style="width:110px">Asignado</th>
			<th style="width:110px">Ejecutado</th>
			<th style="width:100px">Diferencia</th>
			<th style="width:80px">Estado</th>
		</tr>
	</thead>
	<tbody>
	<?php if ( empty( $historial ) ) : ?>
		<tr><td colspan="7" style="text-align:center;padding:20px;color:#666">Sin registros.</td></tr>
	<?php else : ?>
		<?php foreach ( $historial as $h ) :
			$diff = (float)$h->monto_asignado - (float)$h->monto_usado;
		?>
		<tr>
			<td><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $h->fecha_asignacion ) ) ); ?></td>
			<td><strong><?php echo esc_html( $h->transportista_nombre ?? '—' ); ?></strong></td>
			<td><?php echo esc_html( $h->ruta ); ?></td>
			<td>S/ <?php echo esc_html( number_format( (float)$h->monto_asignado, 2 ) ); ?></td>
			<td>S/ <?php echo esc_html( number_format( (float)$h->monto_usado, 2 ) ); ?></td>
			<td style="color:<?php echo $diff >= 0 ? '#00a32a' : '#d63638'; ?>;font-weight:600">
				S/ <?php echo esc_html( number_format( $diff, 2 ) ); ?>
			</td>
			<td><?php echo $h->estado === 'cerrado'
				? '<span class="dashicons dashicons-lock" style="color:#666"></span> Cerrado'
				: '<span class="dashicons dashicons-unlock" style="color:#00a32a"></span> Activo'; ?></td>
		</tr>
		<?php endforeach; ?>
	<?php endif; ?>
	</tbody>
</table>
</div>
