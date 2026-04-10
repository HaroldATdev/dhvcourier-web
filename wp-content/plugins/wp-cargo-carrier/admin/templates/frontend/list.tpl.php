<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php if ( 'actualizado' === $mensaje ) : ?>
	<div style="background:#d4edda;border:1px solid #c3e6cb;border-radius:6px;padding:10px 14px;margin-bottom:14px;color:#155724">
		Transportista actualizado correctamente.
	</div>
<?php elseif ( 'estado_actualizado' === $mensaje ) : ?>
	<div style="background:#d4edda;border:1px solid #c3e6cb;border-radius:6px;padding:10px 14px;margin-bottom:14px;color:#155724">
		Estado actualizado correctamente.
	</div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;flex-wrap:wrap;gap:8px;border-bottom:1px solid #dee2e6;padding-bottom:12px">
	<form method="get" action="<?php echo esc_url( $page_url ); ?>" style="display:flex;gap:6px;flex-wrap:wrap">
		<select name="estado" class="browser-default form-control form-control-sm" style="max-width:160px">
			<option value="">Todos los estados</option>
			<option value="activo"   <?php selected( $estado, 'activo' ); ?>>Activos</option>
			<option value="inactivo" <?php selected( $estado, 'inactivo' ); ?>>Inactivos</option>
		</select>
		<input type="text" name="buscar" class="form-control form-control-sm"
			placeholder="Nombre, DNI o brevete…"
			style="max-width:200px"
			value="<?php echo esc_attr( $buscar ); ?>">
		<button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
		<?php if ( $estado || $buscar ) : ?>
			<a href="<?php echo esc_url( $page_url ); ?>" class="btn btn-outline-secondary btn-sm">Limpiar</a>
		<?php endif; ?>
	</form>
	<div style="font-size:.8rem;color:#888;font-style:italic;align-self:center">
		<i class="fa fa-info-circle"></i> Los transportistas se crean automáticamente con el rol WPDriver.
	</div>
</div>

<div style="overflow-x:auto">
	<table style="width:100%;border-collapse:collapse;font-size:.875rem">
		<thead>
			<tr style="background:#f8f9fa;border-bottom:2px solid #dee2e6">
				<th style="padding:10px 12px;text-align:left">Nombres</th>
				<th style="padding:10px 12px;text-align:left">Apellidos</th>
				<th style="padding:10px 12px;text-align:left">DNI</th>
				<th style="padding:10px 12px;text-align:left">Brevete</th>
				<th style="padding:10px 12px;text-align:left">Teléfono</th>
				<th style="padding:10px 12px;text-align:left">Estado</th>
				<th style="padding:10px 8px;width:40px"></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $transportistas ) ) : ?>
			<tr>
				<td colspan="7" style="text-align:center;padding:30px;color:#888">
					No se encontraron transportistas.
				</td>
			</tr>
		<?php else : ?>
			<?php foreach ( $transportistas as $t ) :
				$nuevo_estado = 'activo' === $t->estado ? 'inactivo' : 'activo';
				$url_editar   = wpcc_frontend_url( [ 'wpcc' => 'edit', 'id' => (int) $t->id ] );
			?>
			<tr style="border-bottom:1px solid #f0f0f0">
				<td style="padding:9px 12px">
					<strong><?php echo esc_html( $t->nombres ?? '—' ); ?></strong>
					<?php if ( ! empty( $t->user_id ) ) : ?>
						<i class="fa fa-user" style="margin-left:4px;color:#2271b1;font-size:.8rem" title="Usuario WPCargo vinculado"></i>
					<?php endif; ?>
				</td>
				<td style="padding:9px 12px"><?php echo esc_html( $t->apellidos ?? '—' ); ?></td>
				<td style="padding:9px 12px"><?php echo esc_html( $t->dni ); ?></td>
				<td style="padding:9px 12px"><code><?php echo esc_html( $t->brevete ); ?></code></td>
				<td style="padding:9px 12px"><?php echo esc_html( $t->telefono ?: '—' ); ?></td>
				<td style="padding:9px 12px">
					<?php if ( 'activo' === $t->estado ) : ?>
						<span style="background:#d4edda;color:#155724;padding:2px 8px;border-radius:4px;font-size:.8rem">Activo</span>
					<?php else : ?>
						<span style="background:#e2e3e5;color:#383d41;padding:2px 8px;border-radius:4px;font-size:.8rem">Inactivo</span>
					<?php endif; ?>
				</td>
				<td style="padding:9px 8px">
					<a href="<?php echo esc_url( $url_editar ); ?>" title="Editar" style="color:#2271b1">
						<i class="fa fa-pencil"></i>
					</a>
				</td>
			</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>
