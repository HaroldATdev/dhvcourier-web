<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<style>
.wpca-select { display: block !important; width: 100%; height: calc(1.5em + .75rem + 2px); padding: .375rem 1.75rem .375rem .75rem; font-size: 1rem; line-height: 1.5; color: #495057; background-color: #fff; background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='4' height='5' viewBox='0 0 4 5'%3e%3cpath fill='%23343a40' d='M2 0L0 2h4zm0 5L0 3h4z'/%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right .75rem center; background-size: 8px 10px; border: 1px solid #ced4da; border-radius: .25rem; appearance: none; }
.wpca-select:focus { border-color: #80bdff; outline: 0; box-shadow: 0 0 0 .2rem rgba(0,123,255,.25); }
.wpca-select.form-control-sm { height: calc(1.5em + .5rem + 2px); padding: .25rem 1.5rem .25rem .5rem; font-size: .875rem; }
.wpca-thumb-btn { border: 0; background: transparent; padding: 0; cursor: zoom-in; line-height: 0; }
.wpca-image-modal { display: none; position: fixed; inset: 0; z-index: 99999; background: rgba(0,0,0,.75); align-items: center; justify-content: center; padding: 20px; }
.wpca-image-modal.open { display: flex; }
.wpca-image-modal__content { position: relative; max-width: 92vw; max-height: 92vh; }
.wpca-image-modal__img { display: block; max-width: 92vw; max-height: 92vh; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,.45); background: #fff; }
.wpca-image-modal__close { position: absolute; top: -12px; right: -12px; width: 30px; height: 30px; border: 0; border-radius: 50%; background: #fff; color: #111; font-size: 20px; line-height: 1; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,.3); }
</style>


<?php if ( $msg === 'creado' || $msg === 'actualizado' ) : ?>
    <div class="alert alert-success alert-dismissible fade show">
        Producto <?php echo $msg === 'creado' ? 'creado' : 'actualizado'; ?> correctamente.
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php elseif ( $msg === 'eliminado' ) : ?>
    <div class="alert alert-info alert-dismissible fade show">
        Producto desactivado.
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php elseif ( $msg === 'borrado' ) : ?>
    <div class="alert alert-success alert-dismissible fade show">
        Producto borrado correctamente.
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="fa fa-box mr-2"></i> Productos</h5>
    <div style="display:flex;gap:.5rem;align-items:center;">
        <?php if ( empty( $mostrar_inactivos ) ) : ?>
            <a href="<?php echo esc_url( add_query_arg( [ 'wpca' => 'productos', 'mostrar_inactivos' => '1' ], $page_url ) ); ?>" class="btn btn-outline-secondary btn-sm">Ver inactivos</a>
        <?php else : ?>
            <a href="<?php echo esc_url( add_query_arg( [ 'wpca' => 'productos' ], $page_url ) ); ?>" class="btn btn-outline-secondary btn-sm">Ver solo activos</a>
        <?php endif; ?>
        <a href="<?php echo esc_url( add_query_arg( 'wpca', 'nuevo-producto', $page_url ) ); ?>" class="btn btn-primary btn-sm">
        <i class="fa fa-plus mr-1"></i> Nuevo Producto
    </a>
    </div>
</div>

<form method="get" class="form-inline mb-3 flex-wrap" style="gap:.5rem;">
    <input type="hidden" name="wpca" value="productos">
    <?php $page_id = wpca_get_frontend_page_id(); echo '<input type="hidden" name="page_id" value="' . $page_id . '">'; ?>
    <input type="text" name="buscar" class="form-control form-control-sm" placeholder="Código o descripción..." value="<?php echo esc_attr( $buscar ); ?>" style="min-width:180px;">
    <?php if ( ! empty( $clientes ) ) : ?>
    <select name="marca" class="wpca-select form-control-sm">
        <option value="">— Todos los clientes —</option>
        <?php foreach ( $clientes as $cliente ) : ?>
            <option value="<?php echo esc_attr( $cliente->label ); ?>" <?php selected( $marca, $cliente->label ); ?>>
                <?php echo esc_html( $cliente->label ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php else : ?>
    <input type="text" name="marca" class="form-control form-control-sm" placeholder="Cliente / Marca..." value="<?php echo esc_attr( $marca ); ?>" style="min-width:160px;">
    <?php endif; ?>
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter mr-1"></i> Filtrar</button>
    <a href="<?php echo esc_url( add_query_arg( 'wpca', 'productos', $page_url ) ); ?>" class="btn btn-secondary btn-sm">Limpiar</a>
</form>

<div class="table-responsive">
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="wpca-bulk-form">
        <?php wp_nonce_field( 'wpca_bulk_prod_nonce' ); ?>
        <input type="hidden" name="action" value="wpca_bulk_action">
        <div class="mb-2 d-flex" style="gap:.5rem;">
            <button type="button" data-action="activar" class="wpca-bulk-btn btn btn-success btn-sm">Activar seleccionados</button>
            <button type="button" data-action="desactivar" class="wpca-bulk-btn btn btn-warning btn-sm">Desactivar seleccionados</button>
            <button type="button" data-action="borrar" class="wpca-bulk-btn btn btn-danger btn-sm" id="wpca-bulk-borrar">Borrar seleccionados</button>
        </div>
        <style>
            /* Forzar visibilidad de los checkboxes en caso de que el tema los oculte */
            #wpca-bulk-form input[type="checkbox"].wpca-row-check,
            #wpca-bulk-form input[type="checkbox"]#wpca-select-all {
                opacity: 1 !important;
                visibility: visible !important;
                height: 18px !important;
                width: 18px !important;
                position: relative !important;
                left: auto !important;
                top: auto !important;
                margin: 0 !important;
                display: inline-block !important;
            }
            /* Asegurar que el checkbox no quede fuera del flujo por estilos absolutos */
            #wpca-bulk-form .wpca-row-check { pointer-events: auto !important; }
        </style>
        <table class="table table-hover table-sm">
        <thead class="thead-light">
            <tr>
                <th style="width:40px;"><input type="checkbox" id="wpca-select-all"></th>
                <th style="width:50px;"></th><th>Código</th><th>Descripción</th><th>Cliente</th><th>Unidad</th>
                <th class="text-center">Stock</th><th class="text-center">Mín.</th>
                <th class="text-center">Estado</th><th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $productos ) ) : ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No hay productos.</td></tr>
            <?php else : ?>
                <?php foreach ( $productos as $p ) : ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="wpca-row-check" name="ids[]" value="<?php echo (int) $p->id; ?>">
                        </td>
                        <td>
                            <?php if ( ! empty( $p->imagen ) ) : ?>
                                <button type="button" class="wpca-thumb-btn wpca-open-image" data-src="<?php echo esc_url( $p->imagen ); ?>" data-alt="<?php echo esc_attr( $p->descripcion ); ?>" title="Ver imagen grande">
                                    <img src="<?php echo esc_url( $p->imagen ); ?>" alt="<?php echo esc_attr( $p->descripcion ); ?>" style="width:40px;height:40px;object-fit:contain;border-radius:4px;border:1px solid #dee2e6;">
                                </button>
                            <?php else : ?>
                                <span style="display:inline-block;width:40px;height:40px;background:#f8f9fa;border:1px solid #dee2e6;border-radius:4px;"></span>
                            <?php endif; ?>
                        </td>
                        <td><code><?php echo esc_html( $p->codigo ); ?></code></td>
                        <td><?php echo esc_html( $p->descripcion ); ?></td>
                        <td><?php echo esc_html( $p->marca ); ?></td>
                        <td><?php echo esc_html( $p->unidad ); ?></td>
                        <td class="text-center"><strong><?php echo wpca_num( $p->stock_actual ); ?></strong></td>
                        <td class="text-center"><?php echo wpca_num( $p->stock_minimo ); ?></td>
                        <td class="text-center"><?php echo wpca_stock_badge( $p ); ?></td>
                        <td class="text-right" style="white-space:nowrap;">
                            <a href="<?php echo esc_url( add_query_arg( [ 'wpca' => 'editar-producto', 'id' => $p->id ], $page_url ) ); ?>" class="btn btn-outline-secondary btn-sm" title="Editar">
                                <i class="fa fa-pencil"></i>
                            </a>
                            <?php if ( (int) $p->activo === 1 ) : ?>
                                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('¿Desactivar este producto?');" style="display:inline;">
                                    <?php wp_nonce_field( 'wpca_del_prod_nonce' ); ?>
                                    <input type="hidden" name="action" value="wpca_eliminar_prod">
                                    <input type="hidden" name="id" value="<?php echo (int) $p->id; ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Desactivar"><i class="fa fa-ban"></i></button>
                                </form>
                                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('¿Borrar definitivamente este producto? Esta acción no se puede deshacer.');" style="display:inline;">
                                    <?php wp_nonce_field( 'wpca_borrar_prod_nonce' ); ?>
                                    <input type="hidden" name="action" value="wpca_borrar_prod">
                                    <input type="hidden" name="id" value="<?php echo (int) $p->id; ?>">
                                    <?php $can_delete = (int) $p->stock_actual <= 0; ?>
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="<?php echo $can_delete ? 'Borrar definitivamente' : 'Solo se puede borrar cuando el stock es 0'; ?>" <?php disabled( ! $can_delete ); ?>><i class="fa fa-trash"></i></button>
                                </form>
                            <?php else : ?>
                                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('¿Activar este producto?');" style="display:inline;">
                                    <?php wp_nonce_field( 'wpca_activate_prod_nonce' ); ?>
                                    <input type="hidden" name="action" value="wpca_activar_prod">
                                    <input type="hidden" name="id" value="<?php echo (int) $p->id; ?>">
                                    <button type="submit" class="btn btn-outline-success btn-sm" title="Activar"><i class="fa fa-check"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </form>
</div>

<div id="wpca-image-modal" class="wpca-image-modal" aria-hidden="true">
    <div class="wpca-image-modal__content">
        <button type="button" id="wpca-image-modal-close" class="wpca-image-modal__close" aria-label="Cerrar">&times;</button>
        <img id="wpca-image-modal-img" class="wpca-image-modal__img" src="" alt="Imagen del producto">
    </div>
</div>

<script>
document.getElementById('wpca-select-all')?.addEventListener('change', function(e){
    var checked = e.target.checked;
    document.querySelectorAll('.wpca-row-check').forEach(function(cb){ cb.checked = checked; });
});

// Bulk action handler: collect selected ids and submit form programmatically
document.querySelectorAll('.wpca-bulk-btn').forEach(function(btn){
    btn.addEventListener('click', function(e){
        var action = btn.getAttribute('data-action');
        if ( action === 'borrar' ) {
            if ( ! confirm('¿Borrar definitivamente los seleccionados? Esta acción no se puede deshacer.') ) return;
        }
        var form = document.getElementById('wpca-bulk-form');
        if ( ! form ) return;
        // collect checked ids
        var ids = Array.from(document.querySelectorAll('.wpca-row-check:checked')).map(function(cb){ return cb.value; });
        if ( ids.length === 0 ) { alert('Selecciona al menos un producto.'); return; }
        // remove any previous hidden inputs named ids[]
        Array.from(form.querySelectorAll('input[name="ids[]"]')).forEach(function(n){ n.remove(); });
        // create hidden inputs
        ids.forEach(function(id){ var inp = document.createElement('input'); inp.type='hidden'; inp.name='ids[]'; inp.value = id; form.appendChild(inp); });
        // set or create hidden bulk_action input
        var bulkInput = form.querySelector('input[name="bulk_action"]');
        if ( bulkInput ) { bulkInput.value = action; } else { var h = document.createElement('input'); h.type='hidden'; h.name='bulk_action'; h.value=action; form.appendChild(h); }
        form.submit();
    });
});

(function(){
    var modal = document.getElementById('wpca-image-modal');
    var modalImg = document.getElementById('wpca-image-modal-img');
    var closeBtn = document.getElementById('wpca-image-modal-close');
    if (!modal || !modalImg || !closeBtn) return;

    function closeModal(){
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        modalImg.src = '';
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.wpca-open-image').forEach(function(btn){
        btn.addEventListener('click', function(){
            var src = btn.getAttribute('data-src') || '';
            var alt = btn.getAttribute('data-alt') || 'Imagen del producto';
            if (!src) return;
            modalImg.src = src;
            modalImg.alt = alt;
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        });
    });

    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function(e){
        if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape' && modal.classList.contains('open')) closeModal();
    });
})();
</script>
