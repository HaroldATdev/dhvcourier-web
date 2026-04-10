<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-money-alt" style="font-size:1.8rem;vertical-align:middle;margin-right:6px;"></span>
		Viáticos
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcv-nuevo' ) ); ?>" class="page-title-action">
		+ Añadir nuevo
	</a>
	<hr class="wp-header-end">

	<?php if ( 'guardado' === $mensaje ) : ?>
		<div class="notice notice-success is-dismissible"><p>Viático guardado correctamente.</p></div>
	<?php elseif ( 'cerrado' === $mensaje ) : ?>
		<div class="notice notice-info is-dismissible"><p>Viático cerrado.</p></div>
	<?php endif; ?>

	<?php if ( ! WPCV_Viatico::carrier_activo() ) : ?>
		<div class="notice notice-warning">
			<p> El plugin <strong>WP Cargo Carrier</strong> no está activo. Actívalo para poder asignar viáticos a transportistas.</p>
		</div>
	<?php endif; ?>

	<!-- Filtros -->
	<form method="get" action="">
		<input type="hidden" name="page" value="wp-cargo-viaticos">
		<div style="display:flex;gap:8px;align-items:center;margin-bottom:12px;flex-wrap:wrap;">
			<?php if ( ! empty( $transportistas ) ) : ?>
			<select name="transportista_id" class="postform">
				<option value="">— Todos los transportistas —</option>
				<?php foreach ( $transportistas as $t ) : ?>
					<option value="<?php echo (int) $t->id; ?>" <?php selected( $transportista_id, $t->id ); ?>>
						<?php echo esc_html( $t->nombre . ' (' . $t->brevete . ')' ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php endif; ?>
			<select name="estado" class="postform">
				<option value="">— Todos los estados —</option>
				<option value="activo"  <?php selected( $estado, 'activo' ); ?>>Activos</option>
				<option value="cerrado" <?php selected( $estado, 'cerrado' ); ?>>Cerrados</option>
			</select>
			<?php submit_button( 'Filtrar', 'secondary', '', false ); ?>
			<?php if ( $estado || $transportista_id ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-cargo-viaticos' ) ); ?>" class="button">Limpiar</a>
			<?php endif; ?>
		</div>
	</form>

	<!-- Tabla -->
	<table class="wp-list-table widefat fixed striped posts">
		<thead>
			<tr>
				<th style="width:18%">Transportista</th>
				<th style="width:18%">Ruta</th>
				<th style="width:11%">Asignado</th>
				<th style="width:11%">Usado</th>
				<th style="width:11%">Saldo</th>
				<th style="width:10%">Fecha</th>
				<th style="width:8%">Estado</th>
				<th style="width:13%">Acciones</th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $viaticos ) ) : ?>
			<tr>
				<td colspan="8" style="text-align:center;padding:30px;color:#888;">
					No se encontraron viáticos.
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcv-nuevo' ) ); ?>">Crear el primero</a>
				</td>
			</tr>
		<?php else : ?>
			<?php foreach ( $viaticos as $v ) :
				$saldo = WPCV_Viatico::saldo( $v );
				$saldo_color = $saldo < 0 ? '#d63638' : '#00a32a';
			?>
			<tr>
				<td>
					<strong><?php echo esc_html( $v->transportista_nombre ?? ( 'ID #' . $v->transportista_id ) ); ?></strong>
				</td>
				<td><?php echo esc_html( $v->ruta ); ?></td>
				<td><?php echo esc_html( wpcv_monto( (float) $v->monto_asignado ) ); ?></td>
				<td><?php echo esc_html( wpcv_monto( (float) $v->monto_usado ) ); ?></td>
				<td style="color:<?php echo esc_attr( $saldo_color ); ?>;font-weight:600;">
					<?php echo esc_html( wpcv_monto( $saldo ) ); ?>
				</td>
				<td><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $v->fecha_asignacion ) ) ); ?></td>
				<td>
					<?php if ( 'activo' === $v->estado ) : ?>
						<span class="wpcv-status active"> Activo</span>
					<?php else : ?>
						<span class="wpcv-status closed"> Cerrado</span>
					<?php endif; ?>
				</td>
				<td>
					<?php if ( 'activo' === $v->estado ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcv-nuevo&id=' . (int) $v->id ) ); ?>"
						   class="button button-small">Editar</a>
						<?php $url_cerrar = wp_nonce_url( add_query_arg( [ 'action' => 'wpcv_cerrar', 'id' => (int) $v->id ], admin_url( 'admin-post.php' ) ), 'wpcv_cerrar_nonce' ); ?>
						<a href="<?php echo esc_url( $url_cerrar ); ?>"
						   class="button button-small"
						   onclick="return confirm('¿Cerrar este viático?')">Cerrar</a>
					<?php else : ?>
						<em style="color:#888">Cerrado</em>
					<?php endif; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
		<tfoot>
			<tr>
				<th>Transportista</th><th>Ruta</th><th>Asignado</th><th>Usado</th>
				<th>Saldo</th><th>Fecha</th><th>Estado</th><th>Acciones</th>
			</tr>
		</tfoot>
	</table>

	<p style="margin-top:12px;color:#777;font-size:12px;">
		Total: <strong><?php echo count( $viaticos ); ?></strong> viáticos
	</p>
</div>
