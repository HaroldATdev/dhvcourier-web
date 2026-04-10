<?php if ( ! defined( 'ABSPATH' ) ) exit;
$tipos = WPCV_Tipos_Gasto::obtener();
?>
<div class="wrap">
<h1>Tipos de Gasto</h1>
<hr class="wp-header-end">

<?php if ( ( $ok ?? '' ) === 'agregado' ) : ?><div class="notice notice-success is-dismissible"><p>Tipo agregado correctamente.</p></div><?php endif; ?>
<?php if ( ( $ok ?? '' ) === 'eliminado' ) : ?><div class="notice notice-info is-dismissible"><p>Tipo eliminado.</p></div><?php endif; ?>
<?php if ( ! empty( $error ) ) : ?><div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div><?php endif; ?>

<div style="max-width:500px;margin-top:16px">
<div class="postbox">
<div class="postbox-header"><h2 class="hndle">Tipos configurados</h2></div>
<div class="inside" style="padding:0">
<table class="wp-list-table widefat striped" style="border:none">
	<thead><tr><th>Tipo de gasto</th><th style="width:80px">Acción</th></tr></thead>
	<tbody>
	<?php foreach ( $tipos as $tipo ) : ?>
	<tr>
		<td><?php echo esc_html( $tipo ); ?></td>
		<td>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
				<?php wp_nonce_field( 'wpcv_tipo_del_nonce' ); ?>
				<input type="hidden" name="action" value="wpcv_tipo_eliminar">
				<input type="hidden" name="tipo"   value="<?php echo esc_attr( $tipo ); ?>">
				<button type="submit" class="button button-small button-link-delete"
				        onclick="return confirm('¿Eliminar este tipo?')">Eliminar</button>
			</form>
		</td>
	</tr>
	<?php endforeach; ?>
	</tbody>
</table>
</div>
</div>

<div class="postbox" style="margin-top:16px">
<div class="postbox-header"><h2 class="hndle">Agregar nuevo tipo</h2></div>
<div class="inside">
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<?php wp_nonce_field( 'wpcv_tipo_nonce' ); ?>
	<input type="hidden" name="action" value="wpcv_tipo_agregar">
	<div style="display:flex;gap:8px;align-items:center">
		<input type="text" name="tipo" class="regular-text" placeholder="Ej: Estacionamiento" required>
		<button type="submit" class="button button-primary">Agregar</button>
	</div>
</form>
</div>
</div>

<div class="postbox" style="margin-top:16px">
<div class="postbox-header"><h2 class="hndle">Tipos por defecto</h2></div>
<div class="inside">
<p class="description">Por defecto: <?php echo esc_html( implode( ', ', WPCV_Tipos_Gasto::$defaults ) ); ?></p>
</div>
</div>
</div>
</div>
