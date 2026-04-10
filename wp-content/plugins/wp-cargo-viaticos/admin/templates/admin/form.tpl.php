<?php if ( ! defined( 'ABSPATH' ) ) exit;
$es_edicion = ! is_null( $viatico );
?>
<div class="wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-<?php echo $es_edicion ? 'edit' : 'plus-alt'; ?>" style="font-size:1.8rem;vertical-align:middle;margin-right:6px;"></span>
		<?php echo $es_edicion ? 'Editar Viático' : 'Nuevo Viático'; ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-cargo-viaticos' ) ); ?>" class="page-title-action">
		← Volver al listado
	</a>
	<hr class="wp-header-end">

	<?php if ( ! WPCV_Viatico::carrier_activo() ) : ?>
		<div class="notice notice-error">
			<p>El plugin <strong>WP Cargo Carrier</strong> no está activo. Actívalo primero.</p>
		</div>
	<?php elseif ( empty( $transportistas ) ) : ?>
		<div class="notice notice-warning">
			<p>No hay transportistas <strong>activos</strong>. 
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcc-nuevo' ) ); ?>">Crea uno aquí</a>.</p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $error ) ) : ?>
		<div class="notice notice-error"><p> <?php echo esc_html( $error ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'wpcv_nonce' ); ?>
		<input type="hidden" name="action" value="wpcv_guardar">
		<?php if ( $es_edicion ) : ?>
			<input type="hidden" name="id" value="<?php echo (int) $viatico->id; ?>">
		<?php endif; ?>

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">

				<!-- Columna principal -->
				<div id="post-body-content">

					<?php if ( $es_edicion ) :
						$saldo = WPCV_Viatico::saldo( $viatico );
					?>
					<div class="postbox">
						<div class="postbox-header"><h2>Resumen del Viático</h2></div>
						<div class="inside">
							<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;text-align:center;">
								<div style="background:#f9f9f9;padding:12px;border-radius:4px;">
									<div style="font-size:11px;color:#777;text-transform:uppercase;margin-bottom:4px;">Asignado</div>
									<div style="font-size:18px;font-weight:700;"><?php echo esc_html( wpcv_monto( (float) $viatico->monto_asignado ) ); ?></div>
								</div>
								<div style="background:#f9f9f9;padding:12px;border-radius:4px;">
									<div style="font-size:11px;color:#777;text-transform:uppercase;margin-bottom:4px;">Usado</div>
									<div style="font-size:18px;font-weight:700;"><?php echo esc_html( wpcv_monto( (float) $viatico->monto_usado ) ); ?></div>
								</div>
								<div style="background:#f9f9f9;padding:12px;border-radius:4px;">
									<div style="font-size:11px;color:#777;text-transform:uppercase;margin-bottom:4px;">Saldo</div>
									<div style="font-size:18px;font-weight:700;color:<?php echo $saldo < 0 ? '#d63638' : '#00a32a'; ?>">
										<?php echo esc_html( wpcv_monto( $saldo ) ); ?>
									</div>
								</div>
							</div>
						</div>
					</div>
					<?php endif; ?>

					<div class="postbox">
						<div class="postbox-header"><h2>Datos del Viático</h2></div>
						<div class="inside">
							<table class="form-table" role="presentation">
								<tr>
									<th><label for="transportista_id">Transportista <span style="color:#d63638">*</span></label></th>
									<td>
										<?php if ( empty( $transportistas ) ) : ?>
											<p style="color:#d63638">No hay transportistas disponibles.</p>
										<?php else : ?>
											<select id="transportista_id" name="transportista_id" required class="postform">
												<option value="">— Seleccionar —</option>
												<?php foreach ( $transportistas as $t ) : ?>
													<option value="<?php echo (int) $t->id; ?>"
														<?php selected( $es_edicion ? $viatico->transportista_id : 0, $t->id ); ?>>
														<?php echo esc_html( $t->nombre . ' (' . $t->brevete . ')' ); ?>
													</option>
												<?php endforeach; ?>
											</select>
										<?php endif; ?>
									</td>
								</tr>
								<tr>
									<th><label for="ruta">Ruta <span style="color:#d63638">*</span></label></th>
									<td>
										<input type="text" id="ruta" name="ruta" class="large-text" required
											placeholder="Ej: Lima - Chiclayo"
											value="<?php echo $es_edicion ? esc_attr( $viatico->ruta ) : ''; ?>">
									</td>
								</tr>
								<tr>
									<th><label for="monto_asignado">Monto Asignado (S/) <span style="color:#d63638">*</span></label></th>
									<td>
										<input type="number" id="monto_asignado" name="monto_asignado"
											class="small-text" required min="0.01" step="0.01"
											value="<?php echo $es_edicion ? esc_attr( number_format( (float) $viatico->monto_asignado, 2, '.', '' ) ) : ''; ?>">
										<p class="description">Monto en soles. Mayor a cero.</p>
									</td>
								</tr>
								<tr>
									<th><label for="fecha_asignacion">Fecha <span style="color:#d63638">*</span></label></th>
									<td>
										<input type="date" id="fecha_asignacion" name="fecha_asignacion" required
											value="<?php echo $es_edicion ? esc_attr( $viatico->fecha_asignacion ) : esc_attr( date( 'Y-m-d' ) ); ?>">
									</td>
								</tr>
								<tr>
									<th><label for="notas">Notas</label></th>
									<td>
										<textarea id="notas" name="notas" class="large-text" rows="3"
											><?php echo $es_edicion ? esc_textarea( $viatico->notas ?? '' ) : ''; ?></textarea>
									</td>
								</tr>
							</table>
						</div>
					</div>

					<?php
					// ── GASTOS ────────────────────────────────────────────────
					if ( $es_edicion ) :
						$mensaje_actual = sanitize_key( $_GET['mensaje'] ?? '' );
					?>
					<?php if ( $mensaje_actual === 'gasto_registrado' ) : ?>
						<div class="notice notice-success is-dismissible"><p>Gasto registrado correctamente.</p></div>
					<?php elseif ( $mensaje_actual === 'gasto_eliminado' ) : ?>
						<div class="notice notice-info is-dismissible"><p>Gasto eliminado.</p></div>
					<?php endif; ?>

					<!-- Listado de gastos existentes -->
					<?php if ( ! empty( $gastos ) ) : ?>
					<div class="postbox">
						<div class="postbox-header"><h2>Gastos Registrados</h2></div>
						<div class="inside" style="padding:0">
						<table class="wp-list-table widefat striped" style="border:none">
							<thead><tr>
								<th>Fecha</th>
								<th>Tipo</th>
								<th>Monto</th>
								<th>Descripción</th>
								<th>Sustento</th>
								<th style="width:60px"></th>
							</tr></thead>
							<tbody>
							<?php foreach ( $gastos as $g ) : ?>
							<tr>
								<td><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $g->fecha_gasto ) ) ); ?></td>
								<td><?php echo esc_html( $g->tipo ); ?></td>
								<td><strong>S/ <?php echo esc_html( number_format( (float) $g->monto, 2 ) ); ?></strong></td>
								<td><?php echo esc_html( $g->descripcion ?: '—' ); ?></td>
								<td>
									<?php if ( $g->sustento_url ) : ?>
										<a href="<?php echo esc_url( $g->sustento_url ); ?>" target="_blank"
										   class="button button-small">
											<span class="dashicons dashicons-<?php echo $g->sustento_tipo === 'pdf' ? 'media-document' : 'format-image'; ?>" style="font-size:14px;vertical-align:middle"></span>
											Ver
										</a>
									<?php else : ?>
										<span style="color:#aaa">—</span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( $viatico->estado === 'activo' ) : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<?php wp_nonce_field( 'wpcv_gasto_del_nonce' ); ?>
										<input type="hidden" name="action"     value="wpcv_admin_gasto_del">
										<input type="hidden" name="gasto_id"   value="<?php echo (int) $g->id; ?>">
										<input type="hidden" name="viatico_id" value="<?php echo (int) $viatico->id; ?>">
										<button type="submit"
										        class="button button-small"
										        style="color:#d63638;border-color:#d63638"
										        onclick="return confirm('¿Eliminar este gasto?')">
											<span class="dashicons dashicons-trash" style="font-size:14px;vertical-align:middle"></span>
										</button>
									</form>
									<?php endif; ?>
								</td>
							</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
						</div>
					</div>
					<?php endif; ?>

					<!-- Registrar nuevo gasto (solo si viático activo) -->
					<?php if ( $viatico->estado === 'activo' ) :
						$saldo = WPCV_Viatico::saldo( $viatico );
					?>
					<div class="postbox">
						<div class="postbox-header"><h2>Registrar Gasto</h2></div>
						<div class="inside">
							<p style="margin-bottom:12px">
								Saldo disponible:
								<strong style="color:<?php echo $saldo > 0 ? '#00a32a' : '#d63638'; ?>">
									<?php echo esc_html( wpcv_monto( $saldo ) ); ?>
								</strong>
							</p>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
							      enctype="multipart/form-data">
								<?php wp_nonce_field( 'wpcv_gasto_admin_nonce' ); ?>
								<input type="hidden" name="action"     value="wpcv_admin_gasto_guardar">
								<input type="hidden" name="viatico_id" value="<?php echo (int) $viatico->id; ?>">
								<table class="form-table" role="presentation">
									<tr>
										<th><label for="g_tipo">Tipo de gasto <span style="color:#d63638">*</span></label></th>
										<td>
											<select id="g_tipo" name="tipo" required class="postform">
												<option value="">— Seleccionar —</option>
												<?php foreach ( WPCV_Gasto::tipos() as $tipo ) : ?>
													<option value="<?php echo esc_attr( $tipo ); ?>"><?php echo esc_html( $tipo ); ?></option>
												<?php endforeach; ?>
											</select>
										</td>
									</tr>
									<tr>
										<th><label for="g_monto">Monto (S/) <span style="color:#d63638">*</span></label></th>
										<td>
											<input type="number" id="g_monto" name="monto" required
											       class="small-text" min="0.01" step="0.01"
											       max="<?php echo esc_attr( number_format( $saldo, 2, '.', '' ) ); ?>">
											<p class="description">Máximo: <?php echo esc_html( wpcv_monto( $saldo ) ); ?></p>
										</td>
									</tr>
									<tr>
										<th><label for="g_desc">Descripción</label></th>
										<td>
											<input type="text" id="g_desc" name="descripcion"
											       class="regular-text" placeholder="Ej: Grifo Repsol km 45">
										</td>
									</tr>
									<tr>
										<th><label for="g_sustento">Sustento</label></th>
										<td>
											<input type="file" id="g_sustento" name="sustento" accept="image/*,.pdf">
											<p class="description">Imagen o PDF, máximo 5 MB.</p>
										</td>
									</tr>
								</table>
								<?php submit_button( 'Registrar Gasto', 'secondary', 'submit_gasto', false ); ?>
							</form>
						</div>
					</div>
					<?php endif; // activo ?>
					<?php endif; // es_edicion ?>

				</div>

				<!-- Sidebar -->
				<div id="postbox-container-1" class="postbox-container">
					<div class="postbox">
						<div class="postbox-header"><h2>Publicar</h2></div>
						<div class="inside">
							<?php if ( $es_edicion ) : ?>
								<div style="margin-bottom:12px;">
									<strong>Estado:</strong>
									<?php if ( 'activo' === $viatico->estado ) : ?>
										<span style="color:#00a32a"> Activo</span>
									<?php else : ?>
										<span style="color:#888"> Cerrado</span>
									<?php endif; ?>
								</div>
								<div style="margin-bottom:12px;font-size:12px;color:#777;">
									Fecha registro: <?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $viatico->fecha_creacion ) ) ); ?>
								</div>
							<?php endif; ?>
							<?php submit_button( $es_edicion ? 'Actualizar Viático' : 'Crear Viático', 'primary large', 'submit', false ); ?>
						</div>
					</div>

					<?php if ( $es_edicion && 'activo' === $viatico->estado ) : ?>
					<div class="postbox">
						<div class="postbox-header"><h2>Acciones</h2></div>
						<div class="inside">
							<?php $url_cerrar = wp_nonce_url( add_query_arg( [ 'action' => 'wpcv_admin_cerrar', 'id' => (int) $viatico->id ], admin_url( 'admin-post.php' ) ), 'wpcv_cerrar_nonce' ); ?>
							<a href="<?php echo esc_url( $url_cerrar ); ?>"
							   class="button"
							   style="color:#d63638;border-color:#d63638"
							   onclick="return confirm('¿Cerrar este viático? No se puede reabrir.')">
								 Cerrar viático
							</a>
							<p class="description" style="margin-top:6px">Al cerrar no se puede editar.</p>
					</div>
					</div>
					<div class="postbox">
					<div class="postbox-header"><h2>Ampliar Viático</h2></div>
					<div class="inside">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'wpcv_ampliar_nonce' ); ?>
					<input type="hidden" name="action" value="wpcv_ampliar">
					<input type="hidden" name="id"     value="<?php echo (int) $viatico->id; ?>">
					<table class="form-table"><tr>
					<th>Monto adicional (S/)</th>
					<td><input type="number" name="adicional" min="0.01" step="0.01" required class="small-text"></td>
					</tr></table>
					<?php submit_button( 'Ampliar', 'secondary', 'submit', false ); ?>
					</form>
						</div>
					</div>
					<?php endif; ?>
				</div>

			</div>
		</div>
	</form>
</div>
