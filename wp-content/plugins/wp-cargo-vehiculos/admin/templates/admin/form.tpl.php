<?php if ( ! defined( 'ABSPATH' ) ) exit;
$es_edicion = ! is_null( $vehiculo );
$alerta     = $es_edicion ? WPCVH_Vehiculo::estado_alerta( $vehiculo ) : 'ok';
$km_falta   = $es_edicion ? WPCVH_Vehiculo::km_para_mant( $vehiculo ) : 0;
?>
<div class="wrap">
<h1><?php echo $es_edicion ? 'Editar Vehículo: ' . esc_html( $vehiculo->placa ) : 'Nuevo Vehículo'; ?></h1>

<?php if ( $error ) : ?>
	<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
<?php endif; ?>
<?php if ( $mensaje === 'km_actualizado' ) : ?>
	<div class="notice notice-success is-dismissible"><p>Kilometraje actualizado correctamente.</p></div>
<?php elseif ( $mensaje === 'mant_registrado' ) : ?>
	<div class="notice notice-success is-dismissible"><p>Mantenimiento registrado correctamente.</p></div>
<?php elseif ( $mensaje === 'mant_eliminado' ) : ?>
	<div class="notice notice-info is-dismissible"><p>Mantenimiento eliminado.</p></div>
<?php endif; ?>

<?php if ( $es_edicion && $alerta !== 'ok' ) : ?>
<div class="notice notice-<?php echo $alerta === 'vencido' ? 'error' : 'warning'; ?>">
	<p><strong><?php echo $alerta === 'vencido' ? '⚠ Mantenimiento VENCIDO.' : '⏰ Mantenimiento PRÓXIMO.' ?></strong>
	<?php if ( $alerta === 'proximo' ) echo 'Faltan ' . esc_html( wpcvh_km( $km_falta ) ) . ' para el siguiente mantenimiento.'; ?>
	</p>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:16px">

<!-- ── DATOS DEL VEHÍCULO ─────────────────────────────── -->
<div>
<div class="postbox">
<div class="postbox-header"><h2 class="hndle">Datos del vehículo</h2></div>
<div class="inside">
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<?php wp_nonce_field( 'wpcvh_nonce' ); ?>
	<input type="hidden" name="action" value="wpcvh_guardar">
	<input type="hidden" name="id"     value="<?php echo $es_edicion ? (int) $vehiculo->id : 0; ?>">
	<table class="form-table">
		<tr>
			<th>Placa <span style="color:red">*</span></th>
			<td>
				<input type="text" name="placa" class="regular-text" required
					style="text-transform:uppercase;max-width:150px"
					value="<?php echo $es_edicion ? esc_attr( $vehiculo->placa ) : ''; ?>"
					oninput="this.value=this.value.toUpperCase()">
			</td>
		</tr>
		<tr>
			<th>Tipo <span style="color:red">*</span></th>
			<td>
				<select name="tipo" required>
					<option value="">— Seleccionar —</option>
					<?php foreach ( WPCVH_Vehiculo::$tipos as $tipo ) : ?>
						<option value="<?php echo esc_attr( $tipo ); ?>"
							<?php selected( $es_edicion ? $vehiculo->tipo : '', $tipo ); ?>>
							<?php echo esc_html( $tipo ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th>Marca</th>
			<td><input type="text" name="marca" class="regular-text"
				value="<?php echo $es_edicion ? esc_attr( $vehiculo->marca ?? '' ) : ''; ?>"></td>
		</tr>
		<tr>
			<th>Modelo</th>
			<td><input type="text" name="modelo" class="regular-text"
				value="<?php echo $es_edicion ? esc_attr( $vehiculo->modelo ?? '' ) : ''; ?>"></td>
		</tr>
		<tr>
			<th>Año</th>
			<td><input type="number" name="anio" min="1980" max="<?php echo date('Y')+1; ?>"
				style="width:90px"
				value="<?php echo $es_edicion ? esc_attr( $vehiculo->anio ?? '' ) : ''; ?>"></td>
		</tr>
		<?php if ( ! $es_edicion ) : ?>
		<tr>
			<th>KM Inicial <span style="color:red">*</span></th>
			<td>
				<input type="number" name="km_inicial" min="0" step="1" required style="width:130px"
					value="0">
				<p class="description">Kilometraje actual del vehículo al momento de registrarlo.</p>
			</td>
		</tr>
		<?php endif; ?>
		<tr>
			<th>Límite KM Mantenimiento <span style="color:red">*</span></th>
			<td>
				<input type="number" name="km_limite_mant" min="100" step="100" required style="width:130px"
					value="<?php echo $es_edicion ? esc_attr( $vehiculo->km_limite_mant ) : '5000'; ?>">
				<p class="description">Cada cuántos km se debe hacer mantenimiento. Ej: 5000</p>
			</td>
		</tr>
		<tr>
			<th>Notas</th>
			<td><textarea name="notas" rows="3" class="large-text"><?php echo $es_edicion ? esc_textarea( $vehiculo->notas ?? '' ) : ''; ?></textarea></td>
		</tr>
	</table>
	<?php submit_button( $es_edicion ? 'Actualizar Vehículo' : 'Registrar Vehículo' ); ?>
</form>
</div>
</div>

<?php if ( $es_edicion ) : ?>
<!-- ── ACTUALIZAR KM ────────────────────────────────────── -->
<div class="postbox" style="margin-top:16px">
<div class="postbox-header"><h2 class="hndle">Actualizar Kilometraje</h2></div>
<div class="inside">
<p><strong>KM actual:</strong> <?php echo esc_html( wpcvh_km( (int) $vehiculo->km_actual ) ); ?></p>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<?php wp_nonce_field( 'wpcvh_km_nonce' ); ?>
	<input type="hidden" name="action" value="wpcvh_actualizar_km">
	<input type="hidden" name="id"     value="<?php echo (int) $vehiculo->id; ?>">
	<table class="form-table">
		<tr>
			<th>Nuevo KM</th>
			<td><input type="number" name="km_nuevo" min="<?php echo (int) $vehiculo->km_actual; ?>" step="1"
				required style="width:130px"></td>
		</tr>
		<tr>
			<th>Descripción</th>
			<td><input type="text" name="descripcion" class="regular-text" placeholder="Ej: Ruta Lima–Chiclayo"></td>
		</tr>
	</table>
	<?php submit_button( 'Actualizar KM', 'secondary' ); ?>
</form>
</div>
</div>
<?php endif; ?>
</div>

<!-- ── COLUMNA DERECHA ────────────────────────────────── -->
<div>
<?php if ( $es_edicion ) : ?>

<!-- REGISTRAR MANTENIMIENTO -->
<div class="postbox">
<div class="postbox-header"><h2 class="hndle">Registrar Mantenimiento</h2></div>
<div class="inside">
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<?php wp_nonce_field( 'wpcvh_mant_nonce' ); ?>
	<input type="hidden" name="action"      value="wpcvh_mant_guardar">
	<input type="hidden" name="vehiculo_id" value="<?php echo (int) $vehiculo->id; ?>">
	<table class="form-table">
		<tr>
			<th>Tipo <span style="color:red">*</span></th>
			<td>
				<select name="tipo_mant" required>
					<option value="">— Seleccionar —</option>
					<?php foreach ( WPCVH_Mantenimiento::$tipos as $t ) : ?>
						<option value="<?php echo esc_attr( $t ); ?>"><?php echo esc_html( $t ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th>KM al momento <span style="color:red">*</span></th>
			<td><input type="number" name="km_al_momento" min="0" step="1" required style="width:130px"
				value="<?php echo (int) $vehiculo->km_actual; ?>"></td>
		</tr>
		<tr>
			<th>Fecha <span style="color:red">*</span></th>
			<td><input type="date" name="realizado_en" required value="<?php echo date('Y-m-d'); ?>"></td>
		</tr>
		<tr>
			<th>Costo (S/)</th>
			<td><input type="number" name="costo" min="0" step="0.01" style="width:120px" value="0"></td>
		</tr>
		<tr>
			<th>Descripción</th>
			<td><textarea name="descripcion" rows="2" class="large-text"></textarea></td>
		</tr>
	</table>
	<?php submit_button( 'Registrar', 'primary' ); ?>
</form>
</div>
</div>

<!-- HISTORIAL DE MANTENIMIENTOS -->
<?php if ( ! empty( $mants ) ) : ?>
<div class="postbox" style="margin-top:16px">
<div class="postbox-header"><h2 class="hndle">Historial de Mantenimientos</h2></div>
<div class="inside" style="padding:0">
<table class="wp-list-table widefat striped" style="border:none">
	<thead><tr><th>Fecha</th><th>Tipo</th><th>KM</th><th>Costo</th><th style="width:40px"></th></tr></thead>
	<tbody>
	<?php foreach ( $mants as $m ) : ?>
	<tr>
		<td><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $m->realizado_en ) ) ); ?></td>
		<td><?php echo esc_html( $m->tipo_mant ); ?></td>
		<td><?php echo esc_html( wpcvh_km( (int) $m->km_al_momento ) ); ?></td>
		<td>S/ <?php echo esc_html( number_format( (float) $m->costo, 2 ) ); ?></td>
		<td>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
				<?php wp_nonce_field( 'wpcvh_mant_del_nonce' ); ?>
				<input type="hidden" name="action"      value="wpcvh_mant_eliminar">
				<input type="hidden" name="mant_id"     value="<?php echo (int) $m->id; ?>">
				<input type="hidden" name="vehiculo_id" value="<?php echo (int) $vehiculo->id; ?>">
				<button type="submit" class="button button-small button-link-delete"
				        onclick="return confirm('¿Eliminar?')">✕</button>
			</form>
		</td>
	</tr>
	<?php endforeach; ?>
	</tbody>
</table>
</div>
</div>
<?php endif; ?>

<!-- LOG DE KM -->
<?php if ( ! empty( $km_log ) ) : ?>
<div class="postbox" style="margin-top:16px">
<div class="postbox-header"><h2 class="hndle">Registro de Kilometraje</h2></div>
<div class="inside" style="padding:0">
<table class="wp-list-table widefat striped" style="border:none">
	<thead><tr><th>Fecha</th><th>KM Anterior</th><th>KM Nuevo</th><th>Descripción</th></tr></thead>
	<tbody>
	<?php foreach ( $km_log as $log ) : ?>
	<tr>
		<td><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $log->fecha ) ) ); ?></td>
		<td><?php echo esc_html( wpcvh_km( (int) $log->km_anterior ) ); ?></td>
		<td><?php echo esc_html( wpcvh_km( (int) $log->km_nuevo ) ); ?></td>
		<td class="text-muted"><?php echo esc_html( $log->descripcion ?: '—' ); ?></td>
	</tr>
	<?php endforeach; ?>
	</tbody>
</table>
</div>
</div>
<?php endif; ?>

<?php endif; // es_edicion ?>
</div><!-- col derecha -->
</div><!-- grid -->
</div><!-- wrap -->
