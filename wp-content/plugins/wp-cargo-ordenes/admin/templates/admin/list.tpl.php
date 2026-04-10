<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-clipboard" style="font-size:1.8rem;vertical-align:middle;margin-right:6px;"></span>
		Órdenes de Servicio
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpco-nueva' ) ); ?>" class="page-title-action">+ Nueva Orden</a>
	<hr class="wp-header-end">

	<?php if ( 'guardado' === $mensaje ) : ?>
		<div class="notice notice-success is-dismissible"><p>Orden creada correctamente.</p></div>
	<?php elseif ( 'actualizado' === $mensaje ) : ?>
		<div class="notice notice-success is-dismissible"><p>Orden actualizada correctamente.</p></div>
	<?php elseif ( 'estado_actualizado' === $mensaje ) : ?>
		<div class="notice notice-success is-dismissible"><p>Estado actualizado.</p></div>
	<?php endif; ?>

	<form method="get" action="">
		<input type="hidden" name="page" value="wp-cargo-ordenes">
		<div style="display:flex;gap:8px;align-items:center;margin-bottom:12px;flex-wrap:wrap;">
			<input type="search" name="buscar" placeholder="Código, cliente, origen…"
				value="<?php echo esc_attr( $buscar ); ?>" class="regular-text">
			<select name="estado" class="postform">
				<option value="">— Todos los estados —</option>
				<?php foreach ( WPCO_Orden::$estados as $e ) : ?>
					<option value="<?php echo esc_attr( $e ); ?>" <?php selected( $estado, $e ); ?>><?php echo esc_html( $e ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php submit_button( 'Filtrar', 'secondary', '', false ); ?>
			<?php if ( $estado || $buscar ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-cargo-ordenes' ) ); ?>" class="button">Limpiar</a>
			<?php endif; ?>
		</div>
	</form>

	<table class="wp-list-table widefat fixed striped posts">
		<thead>
			<tr>
				<th style="width:12%">Código</th>
				<th style="width:15%">Cliente</th>
				<th style="width:12%">Origen</th>
				<th style="width:12%">Destino</th>
				<th style="width:7%">Peso</th>
				<th style="width:5%">Cant.</th>
				<th style="width:8%">Costo</th>
				<th style="width:14%">Transportista</th>
				<th style="width:9%">Estado</th>
				<th style="width:6%">Acc.</th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $ordenes ) ) : ?>
			<tr><td colspan="10" style="text-align:center;padding:30px;color:#888;">No se encontraron órdenes.</td></tr>
		<?php else : ?>
			<?php foreach ( $ordenes as $o ) : ?>
			<tr>
				<td><code><?php echo esc_html( $o->codigo ); ?></code></td>
				<td><?php echo esc_html( $o->cliente ); ?></td>
				<td><?php echo esc_html( $o->origen ); ?></td>
				<td><?php echo esc_html( $o->destino ); ?></td>
				<td><?php echo esc_html( number_format( (float) $o->peso, 3 ) ); ?> kg</td>
				<td><?php echo (int) $o->cantidad; ?></td>
				<td>S/ <?php echo esc_html( number_format( (float) $o->costo, 2 ) ); ?></td>
				<td><?php echo esc_html( $o->transportista_nombre ?? '—' ); ?></td>
				<td><?php echo esc_html( $o->estado ); ?></td>
				<td>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpco-nueva&id=' . (int) $o->id ) ); ?>"
					   class="button button-small" title="Editar">
						<span class="dashicons dashicons-edit" style="font-size:14px;line-height:1.8;"></span>
					</a>
				</td>
			</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
	<p style="margin-top:12px;color:#777;font-size:12px;">Total: <strong><?php echo count( $ordenes ); ?></strong> órdenes</p>
</div>
