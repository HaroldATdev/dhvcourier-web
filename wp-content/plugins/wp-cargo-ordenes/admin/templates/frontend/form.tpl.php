<?php if ( ! defined( 'ABSPATH' ) ) exit;
$es_edicion = ! is_null( $orden );
$val = [
	'cliente'          => $es_edicion ? $orden->cliente          : ( $prev['cliente']          ?? '' ),
	'origen'           => $es_edicion ? $orden->origen           : ( $prev['origen']           ?? '' ),
	'destino'          => $es_edicion ? $orden->destino          : ( $prev['destino']          ?? '' ),
	'peso'             => $es_edicion ? $orden->peso             : ( $prev['peso']             ?? '' ),
	'cantidad'         => $es_edicion ? $orden->cantidad         : ( $prev['cantidad']         ?? 1  ),
	'costo'            => $es_edicion ? $orden->costo            : ( $prev['costo']            ?? '' ),
	'transportista_id' => $es_edicion ? $orden->transportista_id : ( $prev['transportista_id'] ?? '' ),
	'estado'           => $es_edicion ? $orden->estado           : 'Registrado',
	'notas'            => $es_edicion ? ( $orden->notas ?? '' )  : ( $prev['notas']            ?? '' ),
];
?>

<div class="row mb-3 align-items-center">
	<div class="col">
		<h5 class="mb-0"><?php echo $es_edicion ? 'Editar Orden' : 'Nueva Orden de Servicio'; ?></h5>
		<?php if ( $es_edicion ) : ?>
			<small class="text-muted">Código: <strong><?php echo esc_html( $orden->codigo ); ?></strong></small>
		<?php else : ?>
			<small class="text-muted">El código se generará automáticamente</small>
		<?php endif; ?>
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

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<?php wp_nonce_field( 'wpco_fe_nonce' ); ?>
	<input type="hidden" name="action" value="wpco_guardar_fe">
	<input type="hidden" name="id"     value="<?php echo $es_edicion ? (int) $orden->id : 0; ?>">
	<?php if ( ! $es_edicion ) : ?>
		<input type="hidden" name="estado" value="Registrado">
	<?php endif; ?>

	<div class="card shadow-sm mb-3">
		<div class="card-header bg-light py-2"><strong>Datos del envío</strong></div>
		<div class="card-body">
			<div class="form-group row">
				<label class="col-sm-3 col-form-label col-form-label-sm font-weight-bold">Cliente <span class="text-danger">*</span></label>
				<div class="col-sm-9">
					<input type="text" name="cliente" class="form-control form-control-sm" required
						value="<?php echo esc_attr( $val['cliente'] ); ?>">
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label col-form-label-sm font-weight-bold">Origen <span class="text-danger">*</span></label>
				<div class="col-sm-9">
					<input type="text" name="origen" class="form-control form-control-sm" required
						placeholder="Ciudad / Dirección de recojo"
						value="<?php echo esc_attr( $val['origen'] ); ?>">
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label col-form-label-sm font-weight-bold">Destino <span class="text-danger">*</span></label>
				<div class="col-sm-9">
					<input type="text" name="destino" class="form-control form-control-sm" required
						placeholder="Ciudad / Dirección de entrega"
						value="<?php echo esc_attr( $val['destino'] ); ?>">
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label col-form-label-sm font-weight-bold">Peso (kg) <span class="text-danger">*</span></label>
				<div class="col-sm-4">
					<input type="number" name="peso" class="form-control form-control-sm"
						required min="0.001" step="0.001"
						value="<?php echo esc_attr( $val['peso'] ); ?>">
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label col-form-label-sm font-weight-bold">Cantidad <span class="text-danger">*</span></label>
				<div class="col-sm-4">
					<input type="number" name="cantidad" class="form-control form-control-sm"
						required min="1" step="1"
						value="<?php echo esc_attr( $val['cantidad'] ); ?>">
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label col-form-label-sm font-weight-bold">Costo (S/) <span class="text-danger">*</span></label>
				<div class="col-sm-4">
					<input type="number" name="costo" class="form-control form-control-sm"
						required min="0.01" step="0.01"
						value="<?php echo esc_attr( $val['costo'] ); ?>">
				</div>
			</div>
		</div>
	</div>

	<div class="card shadow-sm mb-3">
		<div class="card-header bg-light py-2"><strong>Asignación y estado</strong></div>
		<div class="card-body">
			<div class="form-group row">
				<label class="col-sm-3 col-form-label col-form-label-sm">Transportista</label>
				<div class="col-sm-9">
					<?php if ( empty( $transportistas ) ) : ?>
						<p class="text-muted form-control-plaintext form-control-sm">Sin transportistas activos.</p>
					<?php else : ?>
						<select name="transportista_id" class="form-control form-control-sm browser-default" style="max-width:320px">
							<option value="">— Sin asignar —</option>
							<?php foreach ( $transportistas as $t ) : ?>
								<option value="<?php echo (int) $t->id; ?>"
									<?php selected( $val['transportista_id'], $t->id ); ?>>
									<?php echo esc_html( $t->nombre . ' (' . $t->codigo . ')' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( $es_edicion ) : ?>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label col-form-label-sm font-weight-bold">Estado</label>
				<div class="col-sm-9">
					<select name="estado" class="form-control form-control-sm browser-default" style="max-width:200px">
						<?php foreach ( WPCO_Orden::$estados as $e ) : ?>
							<option value="<?php echo esc_attr( $e ); ?>" <?php selected( $val['estado'], $e ); ?>>
								<?php echo esc_html( $e ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<?php endif; ?>
			<div class="form-group row mb-0">
				<label class="col-sm-3 col-form-label col-form-label-sm">Notas</label>
				<div class="col-sm-9">
					<textarea name="notas" class="form-control form-control-sm" rows="2"
						><?php echo esc_textarea( $val['notas'] ); ?></textarea>
				</div>
			</div>
		</div>
	</div>

	<button type="submit" class="btn btn-primary btn-sm">
		<i class="fa fa-save mr-1"></i>
		<?php echo $es_edicion ? 'Actualizar Orden' : 'Crear Orden'; ?>
	</button>
	<a href="<?php echo esc_url( $page_url ); ?>" class="btn btn-outline-secondary btn-sm ml-2">Cancelar</a>
</form>
