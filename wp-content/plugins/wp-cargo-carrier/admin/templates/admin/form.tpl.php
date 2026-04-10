<?php if ( ! defined( 'ABSPATH' ) ) exit;
$user_wp = ! empty( $transportista->user_id ) ? get_userdata( (int) $transportista->user_id ) : null;
?>
<div class="wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-edit" style="font-size:1.8rem;vertical-align:middle;margin-right:6px;"></span>
		Editar Transportista
	</h1>
	<?php if ( $user_wp ) : ?>
		<a href="<?php echo esc_url( get_edit_user_link( $user_wp->ID ) ); ?>" class="page-title-action">
			<span class="dashicons dashicons-admin-users" style="font-size:14px;line-height:1.8;vertical-align:middle"></span>
			Editar usuario WP
		</a>
	<?php endif; ?>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-cargo-carrier' ) ); ?>" class="page-title-action">
		&larr; Volver al listado
	</a>
	<hr class="wp-header-end">

	<?php if ( ! empty( $error ) ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
	<?php endif; ?>

	<?php if ( $user_wp ) : ?>
	<div class="notice notice-info" style="padding:10px 14px">
		<p>
			<span class="dashicons dashicons-info" style="color:#0072aF"></span>
			<strong>Sincronización activa:</strong> Los cambios aquí se reflejarán automáticamente en el perfil del usuario
			<strong><?php echo esc_html( $user_wp->display_name ); ?></strong>.
		</p>
	</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'wpcc_nonce' ); ?>
		<input type="hidden" name="action" value="wpcc_guardar">
		<input type="hidden" name="id" value="<?php echo (int) $transportista->id; ?>">

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">

				<div id="post-body-content">
					<div class="postbox">
						<div class="postbox-header"><h2>Datos del Transportista</h2></div>
						<div class="inside">
							<table class="form-table" role="presentation">
								<tr>
									<th><label for="nombres">Nombres <span style="color:#d63638">*</span></label></th>
									<td>
										<input type="text" id="nombres" name="nombres" class="regular-text" required
											value="<?php echo esc_attr( $transportista->nombres ?? '' ); ?>">
										<p class="description">Primer nombre y segundo nombre.</p>
									</td>
								</tr>
								<tr>
									<th><label for="apellidos">Apellidos <span style="color:#d63638">*</span></label></th>
									<td>
										<input type="text" id="apellidos" name="apellidos" class="regular-text" required
											value="<?php echo esc_attr( $transportista->apellidos ?? '' ); ?>">
										<p class="description">Apellido paterno y materno.</p>
									</td>
								</tr>
								<tr>
									<th><label for="dni">DNI <span style="color:#d63638">*</span></label></th>
									<td>
										<input type="text" id="dni" name="dni" class="small-text" required
											maxlength="8" pattern="[0-9]{8}" inputmode="numeric"
											oninput="this.value=this.value.replace(/\D/g,'')"
											value="<?php echo esc_attr( $transportista->dni ); ?>">
										<p class="description">8 dígitos numéricos. Debe ser único.</p>
									</td>
								</tr>
								<tr>
									<th><label for="brevete">Brevete <span style="color:#d63638">*</span></label></th>
									<td>
										<input type="text" id="brevete" name="brevete" class="regular-text" required
											maxlength="12"
											oninput="this.value=this.value.toUpperCase()"
											placeholder="Ej: A-2345"
											value="<?php echo esc_attr( $transportista->brevete ); ?>">
										<p class="description">Brevete de conducir peruano. Formato: A-2345 o Q3-12345.</p>
									</td>
								</tr>
								<tr>
									<th><label for="telefono">Teléfono</label></th>
									<td>
										<input type="tel" id="telefono" name="telefono" class="small-text"
											maxlength="9" pattern="[0-9]{9}" inputmode="numeric"
											oninput="this.value=this.value.replace(/\D/g,'')"
											value="<?php echo esc_attr( $transportista->telefono ?? '' ); ?>">
										<p class="description">9 dígitos numéricos.</p>
									</td>
								</tr>
								<tr>
									<th><label for="email">Email</label></th>
									<td>
										<input type="email" id="email" name="email" class="regular-text"
											value="<?php echo esc_attr( $transportista->email ?? '' ); ?>">
									</td>
								</tr>
							</table>
						</div>
					</div>

					<?php if ( $user_wp ) : ?>
					<div class="postbox">
						<div class="postbox-header"><h2>Usuario WPCargo Driver vinculado</h2></div>
						<div class="inside">
							<table class="form-table" role="presentation">
								<tr>
									<th>Usuario</th>
									<td>
										<strong><?php echo esc_html( $user_wp->display_name ); ?></strong>
										&nbsp;&mdash;&nbsp;<?php echo esc_html( $user_wp->user_email ); ?>
										<br>
										<a href="<?php echo esc_url( get_edit_user_link( $user_wp->ID ) ); ?>" class="button button-small" style="margin-top:6px">
											<span class="dashicons dashicons-admin-users" style="font-size:14px;line-height:1.8"></span>
											Editar perfil de usuario
										</a>
									</td>
								</tr>
								<tr>
									<th>Rol</th>
									<td><span style="color:#2271b1">WPCargo Driver</span></td>
								</tr>
							</table>
							<p class="description" style="margin-top:0">
								Al guardar, los datos se sincronizarán automáticamente con el perfil de este usuario.
							</p>
						</div>
					</div>
					<?php endif; ?>
				</div>

				<div id="postbox-container-1" class="postbox-container">
					<div class="postbox">
						<div class="postbox-header"><h2>Acciones</h2></div>
						<div class="inside">
							<div style="margin-bottom:12px;">
								<strong>Estado:</strong>
								<?php if ( 'activo' === $transportista->estado ) : ?>
									<span style="color:#00a32a">Activo</span>
								<?php else : ?>
									<span style="color:#888">Inactivo</span>
								<?php endif; ?>
							</div>
							<div style="margin-bottom:12px;font-size:12px;color:#777;">
								Registro: <?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $transportista->fecha_creacion ) ) ); ?>
							</div>
							<div class="submitbox">
								<div id="publishing-action">
									<?php submit_button( 'Actualizar Transportista', 'primary large', 'submit', false ); ?>
								</div>
								<div class="clear"></div>
							</div>
						</div>
					</div>

					<div class="postbox">
						<div class="postbox-header"><h2>Cambiar estado</h2></div>
						<div class="inside">
							<?php
							$nuevo = 'activo' === $transportista->estado ? 'inactivo' : 'activo';
							$url   = wp_nonce_url(
								add_query_arg( [ 'action' => 'wpcc_cambiar_estado', 'id' => (int) $transportista->id, 'estado' => $nuevo ], admin_url( 'admin-post.php' ) ),
								'wpcc_estado_nonce'
							);
							$label = 'activo' === $transportista->estado ? 'Desactivar' : 'Activar';
							$color = 'activo' === $transportista->estado ? '#d63638' : '#00a32a';
							?>
							<a href="<?php echo esc_url( $url ); ?>"
							   class="button"
							   style="color:<?php echo $color; ?>;border-color:<?php echo $color; ?>"
							   onclick="return confirm('¿Confirmas el cambio de estado?')">
								<?php echo esc_html( $label ); ?>
							</a>
						</div>
					</div>
				</div>

			</div>
		</div>
	</form>
</div>
