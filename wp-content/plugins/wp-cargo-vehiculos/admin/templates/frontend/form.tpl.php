<?php if ( ! defined( 'ABSPATH' ) ) exit;
$es_edicion = ! is_null( $vehiculo );
$alerta     = $es_edicion ? WPCVH_Vehiculo::estado_alerta( $vehiculo ) : 'ok';
$km_falta   = $es_edicion ? WPCVH_Vehiculo::km_para_mant( $vehiculo ) : 0;

/* ── estilos reutilizables ── */
$fld = 'width:100%;height:34px;border:1px solid #ced4da;border-radius:4px;padding:4px 10px;font-size:.875rem;box-sizing:border-box';
$fld_select = $fld . ';appearance:auto;-webkit-appearance:menulist;display:block !important;visibility:visible !important;opacity:1 !important';
$fld_sm = $fld . ';max-width:160px';
$lbl = 'display:block;font-weight:600;margin-bottom:5px;font-size:.875rem;white-space:nowrap';
$sec_wrap  = 'background:#fff;border:1px solid #dee2e6;border-radius:6px;margin-bottom:18px;overflow:hidden';
$sec_head  = 'background:#f8f9fa;border-bottom:1px solid #dee2e6;padding:10px 16px;font-weight:700;font-size:.9rem';
$sec_body  = 'padding:16px 18px';
$field_row = 'margin-bottom:14px';
?>

<!-- Cabecera -->
<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px">
	<div>
		<h5 style="margin:0 0 2px 0">
			<?php echo $es_edicion
				? 'Vehículo: <strong>' . esc_html( $vehiculo->placa ) . '</strong>'
				: 'Nuevo Vehículo'; ?>
		</h5>
	</div>
	<a href="<?php echo esc_url( $page_url ); ?>" class="btn btn-outline-secondary btn-sm">
		<i class="fa fa-arrow-left" style="margin-right:4px"></i> Volver
	</a>
</div>

<?php if ( ! empty( $error ) ) : ?>
<div style="background:#f8d7da;border:1px solid #f5c6cb;border-radius:6px;padding:10px 14px;margin-bottom:14px;color:#721c24">
	<strong>Error:</strong> <?php echo esc_html( $error ); ?>
</div>
<?php endif; ?>

<?php if ( $mensaje === 'km_actualizado' ) : ?>
<div style="background:#d4edda;border:1px solid #c3e6cb;border-radius:6px;padding:10px 14px;margin-bottom:14px;color:#155724">
	Kilometraje actualizado correctamente.
</div>
<?php elseif ( $mensaje === 'mant_registrado' ) : ?>
<div style="background:#d4edda;border:1px solid #c3e6cb;border-radius:6px;padding:10px 14px;margin-bottom:14px;color:#155724">
	Mantenimiento registrado correctamente.
</div>
<?php elseif ( $mensaje === 'mant_eliminado' ) : ?>
<div style="background:#d1ecf1;border:1px solid #bee5eb;border-radius:6px;padding:10px 14px;margin-bottom:14px;color:#0c5460">
	Mantenimiento eliminado.
</div>
<?php endif; ?>

<?php if ( $es_edicion && $alerta !== 'ok' ) : ?>
<div style="background:<?php echo $alerta==='vencido'?'#f8d7da':'#fff3cd'; ?>;border:1px solid <?php echo $alerta==='vencido'?'#f5c6cb':'#ffeeba'; ?>;border-radius:6px;padding:10px 14px;margin-bottom:14px;color:<?php echo $alerta==='vencido'?'#721c24':'#856404'; ?>">
	<strong>
		<?php echo $alerta === 'vencido'
			? '<i class="fa fa-exclamation-triangle" style="margin-right:6px"></i>Mantenimiento VENCIDO — requiere atención inmediata.'
			: '<i class="fa fa-clock-o" style="margin-right:6px"></i>Mantenimiento PRÓXIMO — faltan ' . esc_html( wpcvh_km( $km_falta ) ) . '.'; ?>
	</strong>
</div>
<?php endif; ?>

<!-- Layout de dos columnas en pantallas medianas -->
<div style="display:flex;flex-wrap:wrap;gap:18px;align-items:flex-start">

<!-- ══ COLUMNA IZQUIERDA ══════════════════════════════════════ -->
<div style="flex:1;min-width:260px">

<!-- Datos del vehículo — siempre visible -->
<div style="<?php echo $sec_wrap; ?>">
	<div style="<?php echo $sec_head; ?>"><i class="fa fa-truck" style="margin-right:6px"></i>Datos del vehículo</div>
	<div style="<?php echo $sec_body; ?>">
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'wpcvh_fe_nonce' ); ?>
		<input type="hidden" name="action" value="wpcvh_fe_guardar">
		<input type="hidden" name="id"     value="<?php echo $es_edicion ? (int)$vehiculo->id : 0; ?>">

		<div style="<?php echo $field_row; ?>">
			<div style="<?php echo $lbl; ?>">Placa <span style="color:#dc3545">*</span></div>
			<input type="text" name="placa" required
			       style="<?php echo $fld_sm; ?>;text-transform:uppercase"
			       oninput="this.value=this.value.toUpperCase()"
			       value="<?php echo $es_edicion ? esc_attr($vehiculo->placa) : ''; ?>">
		</div>

		<div style="<?php echo $field_row; ?>">
			<div style="<?php echo $lbl; ?>">Tipo <span style="color:#dc3545">*</span></div>
			<?php $tipo_val = $es_edicion ? $vehiculo->tipo : ''; ?>
			<!-- Select custom: inmune al CSS del tema -->
			<input type="hidden" name="tipo" id="wpcc_tipo_hidden" value="<?php echo esc_attr($tipo_val); ?>">
			<div id="wpcc_tipo_select" style="position:relative;display:block;width:200px;user-select:none;font-size:.875rem;z-index:100">
				<div id="wpcc_tipo_trigger" style="border:1px solid #ced4da;border-radius:4px;padding:5px 32px 5px 10px;background:#fff;cursor:pointer;min-height:34px;line-height:22px;position:relative;">
					<span id="wpcc_tipo_label"><?php echo $tipo_val ? esc_html($tipo_val) : '— Seleccionar —'; ?></span>
					<span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;">&#9660;</span>
				</div>
				<div id="wpcc_tipo_dropdown" style="display:none;position:absolute;top:100%;left:0;width:100%;background:#fff;border:1px solid #ced4da;border-top:none;border-radius:0 0 4px 4px;z-index:9999;box-shadow:0 4px 8px rgba(0,0,0,.1);">
					<?php foreach ( WPCVH_Vehiculo::$tipos as $tipo ) : ?>
					<div class="wpcc-opt" data-value="<?php echo esc_attr($tipo); ?>"
					     style="padding:7px 12px;cursor:pointer;<?php echo $tipo_val === $tipo ? 'background:#e8f0fe;font-weight:600;' : ''; ?>"
					     onmouseover="this.style.background='#f0f4ff'" onmouseout="this.style.background='<?php echo $tipo_val === $tipo ? '#e8f0fe' : '#fff'; ?>'">
						<?php echo esc_html($tipo); ?>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
			<script>
			(function(){
				var trigger  = document.getElementById('wpcc_tipo_trigger');
				var dropdown = document.getElementById('wpcc_tipo_dropdown');
				var hidden   = document.getElementById('wpcc_tipo_hidden');
				var label    = document.getElementById('wpcc_tipo_label');
				if (!trigger) return;
				trigger.addEventListener('click', function(e){
					e.stopPropagation();
					dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
				});
				document.querySelectorAll('.wpcc-opt').forEach(function(opt){
					opt.addEventListener('click', function(){
						hidden.value = this.dataset.value;
						label.textContent = this.dataset.value;
						trigger.style.borderColor = '#ced4da';
						dropdown.style.display = 'none';
					});
				});
				document.addEventListener('click', function(){ dropdown.style.display = 'none'; });
				// Validar al submit
				var form = trigger.closest('form');
				if (form) form.addEventListener('submit', function(e){
					if (!hidden.value) {
						e.preventDefault();
						trigger.style.borderColor = '#dc3545';
						trigger.scrollIntoView({behavior:'smooth',block:'center'});
					}
				});
			})();
			</script>
		</div>

		<div style="<?php echo $field_row; ?>">
			<div style="<?php echo $lbl; ?>">Marca</div>
			<input type="text" name="marca"
			       style="<?php echo $fld; ?>;max-width:200px"
			       value="<?php echo $es_edicion ? esc_attr($vehiculo->marca ?? '') : ''; ?>">
		</div>

		<div style="<?php echo $field_row; ?>">
			<div style="<?php echo $lbl; ?>">Modelo</div>
			<input type="text" name="modelo"
			       style="<?php echo $fld; ?>;max-width:200px"
			       value="<?php echo $es_edicion ? esc_attr($vehiculo->modelo ?? '') : ''; ?>">
		</div>

		<div style="<?php echo $field_row; ?>">
			<div style="<?php echo $lbl; ?>">Año</div>
			<input type="number" name="anio" min="1980" max="<?php echo date('Y')+1; ?>"
			       style="<?php echo $fld_sm; ?>"
			       value="<?php echo $es_edicion ? esc_attr($vehiculo->anio ?? '') : ''; ?>">
		</div>

		<?php if ( ! $es_edicion ) : ?>
		<div style="<?php echo $field_row; ?>">
			<div style="<?php echo $lbl; ?>">KM Inicial <span style="color:#dc3545">*</span></div>
			<input type="number" name="km_inicial" required min="0" step="1"
			       style="<?php echo $fld_sm; ?>" value="0">
			<div style="font-size:.8rem;color:#888;margin-top:3px">KM actual al registrar.</div>
		</div>
		<?php endif; ?>

		<div style="<?php echo $field_row; ?>">
			<div style="<?php echo $lbl; ?>">Límite KM Mantenimiento <span style="color:#dc3545">*</span></div>
			<input type="number" name="km_limite_mant" required min="100" step="100"
			       style="<?php echo $fld_sm; ?>"
			       value="<?php echo $es_edicion ? esc_attr($vehiculo->km_limite_mant) : '5000'; ?>">
			<div style="font-size:.8rem;color:#888;margin-top:3px">Ej: 5000 km</div>
		</div>

		<div style="<?php echo $field_row; ?>">
			<div style="<?php echo $lbl; ?>">Notas</div>
			<textarea name="notas" rows="2"
			          style="width:100%;border:1px solid #ced4da;border-radius:4px;padding:6px 10px;font-size:.875rem;resize:vertical"
			><?php echo $es_edicion ? esc_textarea($vehiculo->notas ?? '') : ''; ?></textarea>
		</div>

		<div style="margin-top:16px">
			<button type="submit" class="btn btn-primary btn-sm" style="margin-right:8px">
				<i class="fa fa-save" style="margin-right:4px"></i>
				<?php echo $es_edicion ? 'Actualizar' : 'Registrar Vehículo'; ?>
			</button>
			<a href="<?php echo esc_url($page_url); ?>" class="btn btn-outline-secondary btn-sm">Cancelar</a>
		</div>
	</form>
	</div>
</div>

<?php if ( $es_edicion ) : ?>
<!-- Actualizar KM — siempre visible -->
<div style="<?php echo $sec_wrap; ?>">
	<div style="<?php echo $sec_head; ?>">
		<i class="fa fa-tachometer" style="margin-right:6px"></i>Actualizar Kilometraje
		<span style="float:right;font-weight:400;font-size:.8rem;color:#666">
			KM actual: <?php echo esc_html( wpcvh_km( (int)$vehiculo->km_actual ) ); ?>
		</span>
	</div>
	<div style="<?php echo $sec_body; ?>">
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'wpcvh_fe_km_nonce' ); ?>
		<input type="hidden" name="action" value="wpcvh_fe_actualizar_km">
		<input type="hidden" name="id"     value="<?php echo (int)$vehiculo->id; ?>">

		<div style="<?php echo $field_row; ?>">
			<div style="<?php echo $lbl; ?>">Nuevo KM <span style="color:#dc3545">*</span></div>
			<input type="number" name="km_nuevo" required
			       min="<?php echo (int)$vehiculo->km_actual; ?>" step="1"
			       style="<?php echo $fld_sm; ?>">
		</div>
		<div style="<?php echo $field_row; ?>">
			<div style="<?php echo $lbl; ?>">Descripción</div>
			<input type="text" name="descripcion" placeholder="Ej: Ruta Lima–Chiclayo"
			       style="<?php echo $fld; ?>;max-width:300px">
		</div>
		<button type="submit" class="btn btn-secondary btn-sm">
			<i class="fa fa-tachometer" style="margin-right:4px"></i> Actualizar KM
		</button>
	</form>
	</div>
</div>
<?php endif; ?>

</div><!-- col izq -->

<?php if ( $es_edicion ) : ?>
<!-- ══ COLUMNA DERECHA ═════════════════════════════════════════ -->
<div style="flex:1;min-width:260px">

<!-- Registrar Mantenimiento — siempre visible -->
<div style="<?php echo $sec_wrap; ?>">
	<div style="<?php echo $sec_head; ?>"><i class="fa fa-wrench" style="margin-right:6px"></i>Registrar Mantenimiento</div>
	<div style="<?php echo $sec_body; ?>">
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'wpcvh_fe_mant_nonce' ); ?>
		<input type="hidden" name="action"      value="wpcvh_fe_mant_guardar">
		<input type="hidden" name="vehiculo_id" value="<?php echo (int)$vehiculo->id; ?>">

		<div style="<?php echo $field_row; ?>">
			<div style="<?php echo $lbl; ?>">Tipo <span style="color:#dc3545">*</span></div>
			<input type="hidden" name="tipo_mant" id="wpcc_tmant_hidden" value="">
			<div id="wpcc_tmant_select" style="position:relative;display:block;width:220px;user-select:none;font-size:.875rem;z-index:100">
				<div id="wpcc_tmant_trigger" style="border:1px solid #ced4da;border-radius:4px;padding:5px 32px 5px 10px;background:#fff;cursor:pointer;min-height:34px;line-height:22px;position:relative;">
					<span id="wpcc_tmant_label">— Seleccionar —</span>
					<span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;">&#9660;</span>
				</div>
				<div id="wpcc_tmant_dropdown" style="display:none;position:absolute;top:100%;left:0;width:100%;background:#fff;border:1px solid #ced4da;border-top:none;border-radius:0 0 4px 4px;z-index:9999;box-shadow:0 4px 8px rgba(0,0,0,.1);">
					<?php foreach ( WPCVH_Mantenimiento::$tipos as $t ) : ?>
					<div class="wpcc-mant-opt" data-value="<?php echo esc_attr($t); ?>"
					     style="padding:7px 12px;cursor:pointer;"
					     onmouseover="this.style.background='#f0f4ff'" onmouseout="this.style.background='#fff'">
						<?php echo esc_html($t); ?>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
			<script>
			(function(){
				var trigger  = document.getElementById('wpcc_tmant_trigger');
				var dropdown = document.getElementById('wpcc_tmant_dropdown');
				var hidden   = document.getElementById('wpcc_tmant_hidden');
				var label    = document.getElementById('wpcc_tmant_label');
				if (!trigger) return;
				trigger.addEventListener('click', function(e){
					e.stopPropagation();
					dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
				});
				document.querySelectorAll('.wpcc-mant-opt').forEach(function(opt){
					opt.addEventListener('click', function(){
						hidden.value = this.dataset.value;
						label.textContent = this.dataset.value;
						trigger.style.borderColor = '#ced4da';
						dropdown.style.display = 'none';
					});
				});
				document.addEventListener('click', function(){ dropdown.style.display = 'none'; });
			})();
			</script>
		</div>

		<div style="<?php echo $field_row; ?>">
			<div style="<?php echo $lbl; ?>">KM al momento <span style="color:#dc3545">*</span></div>
			<input type="number" name="km_al_momento" required min="0" step="1"
			       style="<?php echo $fld_sm; ?>"
			       value="<?php echo (int)$vehiculo->km_actual; ?>">
		</div>

		<div style="<?php echo $field_row; ?>">
			<div style="<?php echo $lbl; ?>">Fecha <span style="color:#dc3545">*</span></div>
			<input type="date" name="realizado_en" required
			       style="<?php echo $fld_sm; ?>"
			       value="<?php echo date_i18n('Y-m-d'); ?>">
		</div>

		<div style="<?php echo $field_row; ?>">
			<div style="<?php echo $lbl; ?>">Costo (S/)</div>
			<input type="number" name="costo" min="0" step="0.01"
			       style="<?php echo $fld_sm; ?>" value="0">
		</div>

		<div style="<?php echo $field_row; ?>">
			<div style="<?php echo $lbl; ?>">Descripción</div>
			<textarea name="descripcion" rows="2"
			          style="width:100%;border:1px solid #ced4da;border-radius:4px;padding:6px 10px;font-size:.875rem;resize:vertical"></textarea>
		</div>

		<button type="submit" class="btn btn-primary btn-sm">
			<i class="fa fa-wrench" style="margin-right:4px"></i> Registrar Mantenimiento
		</button>
	</form>
	</div>
</div>

<?php if ( ! empty( $mants ) ) : ?>
<!-- Historial Mantenimientos — siempre visible -->
<div style="<?php echo $sec_wrap; ?>">
	<div style="<?php echo $sec_head; ?>"><i class="fa fa-list-alt" style="margin-right:6px"></i>Historial de Mantenimientos</div>
	<div style="overflow-x:auto">
	<table style="width:100%;border-collapse:collapse;font-size:.85rem">
		<thead>
			<tr style="background:#f8f9fa;border-bottom:2px solid #dee2e6">
				<th style="padding:8px 12px;text-align:left">Fecha</th>
				<th style="padding:8px 12px;text-align:left">Tipo</th>
				<th style="padding:8px 12px;text-align:right">KM</th>
				<th style="padding:8px 12px;text-align:right">Costo</th>
				<th style="padding:8px 4px;width:36px"></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $mants as $m ) : ?>
		<tr style="border-bottom:1px solid #f0f0f0">
			<td style="padding:7px 12px"><?php echo esc_html( date_i18n('d/m/Y', strtotime($m->realizado_en)) ); ?></td>
			<td style="padding:7px 12px"><?php echo esc_html( $m->tipo_mant ); ?></td>
			<td style="padding:7px 12px;text-align:right"><?php echo esc_html( wpcvh_km((int)$m->km_al_momento) ); ?></td>
			<td style="padding:7px 12px;text-align:right">S/ <?php echo esc_html( number_format((float)$m->costo,2) ); ?></td>
			<td style="padding:7px 4px">
				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
					<?php wp_nonce_field('wpcvh_fe_mant_del_nonce'); ?>
					<input type="hidden" name="action"      value="wpcvh_fe_mant_eliminar">
					<input type="hidden" name="mant_id"     value="<?php echo (int)$m->id; ?>">
					<input type="hidden" name="vehiculo_id" value="<?php echo (int)$vehiculo->id; ?>">
					<button type="submit" style="background:none;border:none;cursor:pointer;color:#dc3545;padding:2px"
					        onclick="return confirm('¿Eliminar este mantenimiento?')" title="Eliminar">
						<i class="fa fa-trash"></i>
					</button>
				</form>
			</td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	</div>
</div>
<?php endif; ?>

<?php if ( ! empty( $km_log ) ) : ?>
<!-- Log KM — siempre visible -->
<div style="<?php echo $sec_wrap; ?>">
	<div style="<?php echo $sec_head; ?>"><i class="fa fa-road" style="margin-right:6px"></i>Registro de Kilometraje</div>
	<div style="overflow-x:auto">
	<table style="width:100%;border-collapse:collapse;font-size:.85rem">
		<thead>
			<tr style="background:#f8f9fa;border-bottom:2px solid #dee2e6">
				<th style="padding:8px 12px;text-align:left">Fecha</th>
				<th style="padding:8px 12px;text-align:right">KM Ant.</th>
				<th style="padding:8px 12px;text-align:right">KM Nuevo</th>
				<th style="padding:8px 12px;text-align:left">Descripción</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $km_log as $log ) : ?>
		<tr style="border-bottom:1px solid #f0f0f0">
			<td style="padding:7px 12px"><?php echo esc_html( date_i18n('d/m/Y', strtotime($log->fecha)) ); ?></td>
			<td style="padding:7px 12px;text-align:right"><?php echo esc_html( wpcvh_km((int)$log->km_anterior) ); ?></td>
			<td style="padding:7px 12px;text-align:right;font-weight:600"><?php echo esc_html( wpcvh_km((int)$log->km_nuevo) ); ?></td>
			<td style="padding:7px 12px;color:#666"><?php echo esc_html( $log->descripcion ?: '—' ); ?></td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	</div>
</div>
<?php endif; ?>

</div><!-- col der -->
<?php endif; // es_edicion ?>

</div><!-- flex wrapper -->
