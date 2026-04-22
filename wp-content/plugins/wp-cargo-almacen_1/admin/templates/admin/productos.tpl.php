<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap"><h1 class="wp-heading-inline">Productos</h1>
<a href="<?php echo esc_url(admin_url('admin.php?page=wpca-productos&action=add')); ?>" class="page-title-action">+ Nuevo</a>
<hr class="wp-header-end">
<?php if($msg):?><div class="notice notice-success is-dismissible"><p><?php echo esc_html($msg); ?></p></div><?php endif;?>
<table class="wp-list-table widefat striped">
<thead><tr><th>Código</th><th>Descripción</th><th>Marca</th><th>Unidad</th><th>Stock</th><th>Mín.</th><th>Acciones</th></tr></thead>
<tbody>
<?php if(empty($productos)): ?><tr><td colspan="7">No hay productos.</td></tr>
<?php else: foreach($productos as $p): ?>
<tr>
  <td><code><?php echo esc_html($p->codigo); ?></code></td>
  <td><?php echo esc_html($p->descripcion); ?></td>
  <td><?php echo esc_html($p->marca); ?></td>
  <td><?php echo esc_html($p->unidad); ?></td>
  <td><?php echo wpca_num($p->stock_actual); ?></td>
  <td><?php echo wpca_num($p->stock_minimo); ?></td>
  <td>
    <a href="<?php echo esc_url(admin_url('admin.php?page=wpca-productos&action=edit&id='.(int)$p->id)); ?>" class="button button-small">Editar</a>
    <?php $can_delete = (int) $p->stock_actual <= 0; ?>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;" onsubmit="return confirm('¿Borrar definitivamente este producto? Esta acción no se puede deshacer.');">
      <?php wp_nonce_field('wpca_admin_borrar_prod_nonce'); ?>
      <input type="hidden" name="action" value="wpca_admin_borrar_prod">
      <input type="hidden" name="id" value="<?php echo (int)$p->id; ?>">
      <button type="submit" class="button button-small" title="<?php echo $can_delete ? 'Borrar definitivamente' : 'Solo se puede borrar cuando el stock es 0'; ?>" <?php disabled( ! $can_delete ); ?>>Borrar</button>
    </form>
  </td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div>
