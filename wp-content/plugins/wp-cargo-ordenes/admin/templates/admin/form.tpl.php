<?php if ( ! defined( 'ABSPATH' ) ) exit;
$es_edicion = ! is_null( $orden );
?>
<div class="wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-<?php echo $es_edicion ? 'edit' : 'plus-alt'; ?>" style="font-size:1.8rem;vertical-align:middle;margin-right:6px;"></span>
		<?php echo $es_edicion ? 'Editar Orden' : 'Nueva Orden de Servicio'; ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-cargo-ordenes' ) ); ?>" class="page-title-action">&larr; Volver</a>
	<hr class="wp-header-end">

	<?php if ( ! empty( $error ) ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
	<?php endif; ?>

	<?php if ( $es_edicion ) : ?>
		<p><strong>Código:</strong> <code><?php echo esc_html( $orden->codigo ); ?></code></p>
	<?php else : ?>
		<p class="description">El código de orden se generará automáticamente al crear.</p>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'wpco_nonce' ); ?>
		<input type="hidden" name="action" value="wpco_guardar">
		<?php if ( $es_edicion ) : ?><input type="hidden" name="id" value="<?php echo (int) $orden->id; ?>"><?php endif; ?>

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="post-body-content">
					<div class="postbox">
						<div class="postbox-header"><h2>Datos del Envío</h2></div>
						<div class="inside">
							<table class="form-table" role="presentation">
								<tr>
									<th><label for="cliente">Cliente <span style="color:#d63638">*</span></label></th>
									<td><input type="text" id="cliente" name="cliente" class="large-text" required value="<?php echo $es_edicion ? esc_attr( $orden->cliente ) : ''; ?>"></td>
								</tr>
								<tr>
									<th><label for="origen">Origen <span style="color:#d63638">*</span></label></th>
									<td><input type="text" id="origen" name="origen" class="regular-text" required placeholder="Ciudad / Dirección de recojo" value="<?php echo $es_edicion ? esc_attr( $orden->origen ) : ''; ?>"></td>
								</tr>
								<tr>
									<th><label for="destino">Destino <span style="color:#d63638">*</span></label></th>
									<td><input type="text" id="destino" name="destino" class="regular-text" required placeholder="Ciudad / Dirección de entrega" value="<?php echo $es_edicion ? esc_attr( $orden->destino ) : ''; ?>"></td>
								</tr>
								<tr>
									<th><label for="peso">Peso (kg) <span style="color:#d63638">*</span></label></th>
									<td><input type="number" id="peso" name="peso" class="small-text" required min="0.001" step="0.001" value="<?php echo $es_edicion ? esc_attr( $orden->peso ) : ''; ?>"></td>
								</tr>
								<tr>
									<th><label for="cantidad">Cantidad <span style="color:#d63638">*</span></label></th>
									<td><input type="number" id="cantidad" name="cantidad" class="small-text" required min="1" step="1" value="<?php echo $es_edicion ? esc_attr( $orden->cantidad ) : '1'; ?>"></td>
								</tr>
								<tr>
									<th><label for="costo">Costo (S/) <span style="color:#d63638">*</span></label></th>
									<td><input type="number" id="costo" name="costo" class="small-text" required min="0.01" step="0.01" value="<?php echo $es_edicion ? esc_attr( $orden->costo ) : ''; ?>"></td>
								</tr>
								<tr>
									<th><label for="transportista_id">Transportista</label></th>
									<td>
										<select id="transportista_id" name="transportista_id">
											<option value="">— Sin asignar —</option>
											<?php foreach ( $transportistas as $t ) : ?>
												<option value="<?php echo (int) $t->id; ?>" <?php selected( $es_edicion ? $orden->transportista_id : '', $t->id ); ?>>
													<?php echo esc_html( $t->nombre . ' (' . $t->codigo . ')' ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</td>
								</tr>
								<tr>
									<th><label for="notas">Notas</label></th>
									<td><textarea id="notas" name="notas" class="large-text" rows="3"><?php echo $es_edicion ? esc_textarea( $orden->notas ?? '' ) : ''; ?></textarea></td>
								</tr>
							</table>
						</div>
					</div>
				</div>

				<div id="postbox-container-1" class="postbox-container">
					<div class="postbox">
						<div class="postbox-header"><h2>Publicar</h2></div>
						<div class="inside">
							<?php if ( $es_edicion ) : ?>
								<div style="margin-bottom:10px;">
									<label for="estado"><strong>Estado:</strong></label><br>
									<select id="estado" name="estado" style="width:100%">
										<?php foreach ( WPCO_Orden::$estados as $e ) : ?>
											<option value="<?php echo esc_attr( $e ); ?>" <?php selected( $orden->estado, $e ); ?>><?php echo esc_html( $e ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div style="margin-bottom:10px;font-size:12px;color:#777;">
									Registro: <?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $orden->fecha_creacion ) ) ); ?>
								</div>
							<?php else : ?>
								<input type="hidden" name="estado" value="Registrado">
								<p class="description">Estado inicial: <strong>Registrado</strong></p>
							<?php endif; ?>
							<div class="submitbox">
								<div id="publishing-action">
									<?php submit_button( $es_edicion ? 'Actualizar Orden' : 'Crear Orden', 'primary large', 'submit', false ); ?>
								</div>
								<div class="clear"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>
