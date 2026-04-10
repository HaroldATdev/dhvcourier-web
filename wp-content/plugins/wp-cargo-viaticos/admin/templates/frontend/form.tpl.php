<?php if ( ! defined( 'ABSPATH' ) ) exit;
$es_edicion = ! is_null( $viatico );
$val = [
	'transportista_id' => $es_edicion ? $viatico->transportista_id : ( $prev['transportista_id'] ?? 0 ),
	'ruta'             => $es_edicion ? $viatico->ruta              : ( $prev['ruta']             ?? '' ),
	'monto_asignado'   => $es_edicion ? $viatico->monto_asignado    : ( $prev['monto_asignado']   ?? '' ),
	'fecha_asignacion' => $es_edicion ? $viatico->fecha_asignacion  : ( $prev['fecha_asignacion'] ?? date( 'Y-m-d' ) ),
	'notas'            => $es_edicion ? ( $viatico->notas ?? '' )   : ( $prev['notas']            ?? '' ),
];
?>

<div class="row mb-3 align-items-center">
	<div class="col">
		<h5 class="mb-0"><?php echo $es_edicion ? 'Editar Viático' : 'Nuevo Viático'; ?></h5>
	</div>
	<div class="col-auto">
		<a href="<?php echo esc_url( $page_url ); ?>" class="btn btn-outline-secondary btn-sm">
			<i class="fa fa-arrow-left mr-1"></i> Volver
		</a>
	</div>
</div>

<?php if ( ! empty( $error ) ) : ?>
	<div class="alert alert-danger alert-dismissible fade show" role="alert">
		<strong>Error:</strong> <?php echo esc_html( $error ); ?>
		<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
	</div>
<?php endif; ?>
<?php if ( ( sanitize_key( $_GET['msg'] ?? '' ) ) === 'ampliado' ) : ?>
	<div class="alert alert-success alert-dismissible fade show">Viático ampliado correctamente.
		<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
<?php endif; ?>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<?php wp_nonce_field( 'wpcv_fe_nonce' ); ?>
	<input type="hidden" name="action" value="wpcv_guardar_fe">
	<input type="hidden" name="id"     value="<?php echo $es_edicion ? (int) $viatico->id : 0; ?>">

	<div style="background:#fff;border:1px solid #dee2e6;border-radius:6px;margin-bottom:20px;overflow:hidden">
		<div style="padding:16px">
			<div class="form-group row">
				<label for="f-trans" class="col-sm-3 col-form-label col-form-label-sm font-weight-bold">
					Transportista <span class="text-danger">*</span>
				</label>
				<div class="col-sm-9">
					<?php if ( empty( $transportistas ) ) : ?>
						<p class="text-muted form-control-plaintext form-control-sm">
							Sin transportistas activos. Activa el plugin WP Cargo Carrier.
						</p>
					<?php else : ?>
						<select id="f-trans" name="transportista_id"
						        class="form-control form-control-sm browser-default"
						        style="max-width:320px" required>
							<option value="">— Seleccionar —</option>
							<?php foreach ( $transportistas as $t ) : ?>
								<option value="<?php echo (int) $t->id; ?>"
									<?php selected( $val['transportista_id'], $t->id ); ?>>
									<?php echo esc_html( $t->nombre . ' (' . $t->brevete . ')' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					<?php endif; ?>
				</div>
			</div>
			<div class="form-group row">
				<label for="f-ruta" class="col-sm-3 col-form-label col-form-label-sm font-weight-bold">
					Ruta <span class="text-danger">*</span>
				</label>
				<div class="col-sm-9">
					<input type="text" id="f-ruta" name="ruta" class="form-control form-control-sm"
						required placeholder="Ej: Lima – Trujillo"
						value="<?php echo esc_attr( $val['ruta'] ); ?>">
				</div>
			</div>
			<div class="form-group row">
				<label for="f-monto" class="col-sm-3 col-form-label col-form-label-sm font-weight-bold">
					Monto Asignado (S/) <span class="text-danger">*</span>
				</label>
				<div class="col-sm-9">
					<input type="number" id="f-monto" name="monto_asignado"
						class="form-control form-control-sm"
						required min="0.01" step="0.01" style="max-width:160px"
						value="<?php echo esc_attr( $val['monto_asignado'] ); ?>">
				</div>
			</div>
			<div class="form-group row">
				<label for="f-fecha" class="col-sm-3 col-form-label col-form-label-sm font-weight-bold">
					Fecha <span class="text-danger">*</span>
				</label>
				<div class="col-sm-9">
					<input type="date" id="f-fecha" name="fecha_asignacion"
						class="form-control form-control-sm"
						required style="max-width:180px"
						value="<?php echo esc_attr( $val['fecha_asignacion'] ); ?>">
				</div>
			</div>
			<div class="form-group row mb-0">
				<label for="f-notas" class="col-sm-3 col-form-label col-form-label-sm">Notas</label>
				<div class="col-sm-9">
					<textarea id="f-notas" name="notas" class="form-control form-control-sm"
						rows="2"><?php echo esc_textarea( $val['notas'] ); ?></textarea>
				</div>
			</div>
		</div>
	</div>

	<button type="submit" class="btn btn-primary btn-sm">
		<i class="fa fa-save mr-1"></i>
		<?php echo $es_edicion ? 'Actualizar' : 'Crear Viático'; ?>
	</button>
	<a href="<?php echo esc_url( $page_url ); ?>" class="btn btn-outline-secondary btn-sm ml-2">Cancelar</a>
</form>

<?php if ( $es_edicion && $viatico->estado === 'activo' ) : ?>
<div style="background:#fff;border:2px solid #ffc107;border-radius:6px;margin-top:20px;overflow:hidden">
	<div style="background:#f8f9fa;border-bottom:1px solid #dee2e6;padding:10px 16px">
		<strong><i class="fa fa-plus-circle mr-1 text-success"></i>Ampliar Viático</strong>
		<span class="float-right text-muted small">
			Asignado actual: <?php echo esc_html( wpcv_monto( (float) $viatico->monto_asignado ) ); ?>
		</span>
	</div>
	<div style="padding:16px">
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'wpcv_fe_ampliar_nonce' ); ?>
		<input type="hidden" name="action" value="wpcv_ampliar_fe">
		<input type="hidden" name="id"     value="<?php echo (int) $viatico->id; ?>">
		<div class="form-group row mb-0">
			<label class="col-sm-3 col-form-label col-form-label-sm font-weight-bold">
				Monto adicional (S/) <span class="text-danger">*</span>
			</label>
			<div class="col-sm-9 d-flex align-items-center">
				<input type="number" name="adicional" class="form-control form-control-sm mr-2"
					required min="0.01" step="0.01" style="max-width:150px">
				<button type="submit" class="btn btn-success btn-sm">
					<i class="fa fa-plus mr-1"></i> Ampliar
				</button>
			</div>
		</div>
	</form>
	</div>
</div>
<?php endif; ?>
