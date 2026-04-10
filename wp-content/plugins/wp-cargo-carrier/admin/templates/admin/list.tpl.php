<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-id-alt" style="font-size:1.8rem;vertical-align:middle;margin-right:6px;"></span>
		Transportistas
	</h1>
	<a href="<?php echo esc_url( admin_url( 'users.php?role=wpcargo_driver' ) ); ?>" class="page-title-action">
		<span class="dashicons dashicons-admin-users" style="font-size:13px;line-height:2;vertical-align:middle"></span>
		Gestionar usuarios Driver
	</a>
	<hr class="wp-header-end">

	<?php if ( 'actualizado' === $mensaje ) : ?>
		<div class="notice notice-success is-dismissible"><p>Transportista actualizado correctamente.</p></div>
	<?php elseif ( 'estado_actualizado' === $mensaje ) : ?>
		<div class="notice notice-success is-dismissible"><p>Estado actualizado.</p></div>
	<?php endif; ?>

	<div class="notice notice-info" style="padding:10px 14px">
		<p>
			<span class="dashicons dashicons-info" style="color:#0072aF"></span>
			Los transportistas se crean automáticamente al registrar un usuario con rol <strong>WPCargo Driver</strong>.
			Para añadir un nuevo transportista, crea el usuario correspondiente.
		</p>
	</div>

	<form method="get" action="">
		<input type="hidden" name="page" value="wp-cargo-carrier">
		<div style="display:flex;gap:8px;align-items:center;margin-bottom:12px;flex-wrap:wrap;">
			<select name="estado" class="postform">
				<option value="">— Todos los estados —</option>
				<option value="activo"   <?php selected( $estado, 'activo' ); ?>>Activos</option>
				<option value="inactivo" <?php selected( $estado, 'inactivo' ); ?>>Inactivos</option>
			</select>
			<input type="search" name="buscar" placeholder="Nombre, DNI o brevete…"
				value="<?php echo esc_attr( $buscar ); ?>" class="regular-text">
			<?php submit_button( 'Filtrar', 'secondary', '', false ); ?>
			<?php if ( $estado || $buscar ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-cargo-carrier' ) ); ?>" class="button">Limpiar</a>
			<?php endif; ?>
		</div>
	</form>

	<table class="wp-list-table widefat fixed striped posts">
		<thead>
			<tr>
				<th style="width:12%">Nombres</th>
				<th style="width:14%">Apellidos</th>
				<th style="width:10%">DNI</th>
				<th style="width:12%">Brevete</th>
				<th style="width:12%">Teléfono</th>
				<th style="width:17%">Email</th>
				<th style="width:8%">Usuario WP</th>
				<th style="width:7%">Estado</th>
				<th style="width:8%">Registro</th>
			</tr>
		</thead>
		<tbody id="the-list">
		<?php if ( empty( $transportistas ) ) : ?>
			<tr>
				<td colspan="9" style="text-align:center;padding:30px;color:#888;">
					No se encontraron transportistas.
					<a href="<?php echo esc_url( admin_url( 'user-new.php' ) ); ?>">Crear usuario Driver</a>
				</td>
			</tr>
		<?php else : ?>
			<?php foreach ( $transportistas as $t ) :
				$nonce_url = wp_nonce_url(
					add_query_arg( [ 'action' => 'wpcc_cambiar_estado', 'id' => (int) $t->id, 'estado' => ( 'activo' === $t->estado ? 'inactivo' : 'activo' ) ], admin_url( 'admin-post.php' ) ),
					'wpcc_estado_nonce'
				);
				$user_wp = ! empty( $t->user_id ) ? get_userdata( (int) $t->user_id ) : null;
			?>
			<tr>
				<td>
					<strong>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcc-editar&id=' . (int) $t->id ) ); ?>">
							<?php echo esc_html( $t->nombres ?? '—' ); ?>
						</a>
					</strong>
					<div class="row-actions">
						<span class="edit">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcc-editar&id=' . (int) $t->id ) ); ?>">Editar</a>
						</span>
						|
						<span>
							<a href="<?php echo esc_url( $nonce_url ); ?>"
							   onclick="return confirm('¿Confirmas el cambio de estado?')"
							   style="color:<?php echo 'activo' === $t->estado ? '#d63638' : '#2271b1'; ?>">
								<?php echo 'activo' === $t->estado ? 'Desactivar' : 'Activar'; ?>
							</a>
						</span>
					</div>
				</td>
				<td><?php echo esc_html( $t->apellidos ?? '—' ); ?></td>
				<td><?php echo esc_html( $t->dni ); ?></td>
				<td><code><?php echo esc_html( $t->brevete ); ?></code></td>
				<td><?php echo esc_html( $t->telefono ?: '—' ); ?></td>
				<td><?php echo esc_html( $t->email ?: '—' ); ?></td>
				<td>
					<?php if ( $user_wp ) : ?>
						<a href="<?php echo esc_url( get_edit_user_link( $user_wp->ID ) ); ?>"
						   title="<?php echo esc_attr( $user_wp->display_name ); ?>"
						   style="color:#2271b1">
							<span class="dashicons dashicons-admin-users" style="font-size:16px;vertical-align:middle"></span>
						</a>
					<?php else : ?>
						<span style="color:#ccc">—</span>
					<?php endif; ?>
				</td>
				<td>
					<?php if ( 'activo' === $t->estado ) : ?>
						<span class="wpcc-status-dot active">Activo</span>
					<?php else : ?>
						<span class="wpcc-status-dot inactive">Inactivo</span>
					<?php endif; ?>
				</td>
				<td><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $t->fecha_creacion ) ) ); ?></td>
			</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
		<tfoot>
			<tr>
				<th>Nombres</th><th>Apellidos</th><th>DNI</th><th>Brevete</th>
				<th>Teléfono</th><th>Email</th><th>Usuario WP</th><th>Estado</th><th>Registro</th>
			</tr>
		</tfoot>
	</table>

	<p style="margin-top:12px;color:#777;font-size:12px;">
		Total: <strong><?php echo count( $transportistas ); ?></strong> transportistas
		<?php if ( $estado || $buscar ) : ?>(filtrado)<?php endif; ?>
	</p>
</div>
