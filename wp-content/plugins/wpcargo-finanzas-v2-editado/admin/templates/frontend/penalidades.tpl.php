<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="d-flex align-items-center mb-3 border-bottom pb-3">
    <h5 class="mb-0 mr-auto"><i class="fa fa-exclamation-triangle mr-2 text-danger"></i>Penalidades</h5>
    <div>
        <a href="<?php echo esc_url($page_url); ?>" class="btn btn-outline-secondary btn-sm mr-1"><i class="fa fa-arrow-left mr-1"></i>Reportes</a>
        <a href="<?php echo esc_url(add_query_arg('wcfin_vista','condiciones',$page_url)); ?>" class="btn btn-outline-secondary btn-sm mr-1"><i class="fa fa-list-alt mr-1"></i>Condiciones</a>
        <a href="<?php echo esc_url(add_query_arg('wcfin_vista','metodos',$page_url)); ?>" class="btn btn-outline-secondary btn-sm mr-1"><i class="fa fa-credit-card mr-1"></i>Métodos</a>
        <?php if(!($edit_id||isset($_GET['editar']))): ?>
        <a href="<?php echo esc_url(add_query_arg(['wcfin_vista'=>'penalidades','editar'=>'nuevo'],$page_url)); ?>" class="btn btn-primary btn-sm"><i class="fa fa-plus mr-1"></i>Nueva penalidad</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($edit_id || isset($_GET['editar'])): ?>
<div style="background:#fff;border:1px solid #dee2e6;border-radius:6px;overflow:hidden">
    <div style="padding:12px 16px;border-bottom:1px solid #dee2e6;background:#f8f9fa">
        <strong><?php echo $penalidad ? 'Editar: '.esc_html($penalidad->nombre) : 'Nueva penalidad'; ?></strong>
    </div>
    <div style="padding:20px">
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('wcfin_penalidad_nonce'); ?>
        <input type="hidden" name="action"       value="wcfin_guardar_penalidad">
        <input type="hidden" name="penalidad_id" value="<?php echo intval($penalidad->id??0); ?>">

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="font-weight-bold">Nombre / Infracción <span class="text-danger">*</span></label>
                    <input name="nombre" type="text" class="form-control" value="<?php echo esc_attr($penalidad->nombre??''); ?>" placeholder="ej: Entrega tardía" required>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="font-weight-bold">Tipo de monto</label>
                    <select name="tipo_monto" class="form-control browser-default">
                        <option value="fijo"       <?php selected($penalidad->tipo_monto??'fijo','fijo'); ?>>Monto fijo (S/)</option>
                        <option value="porcentaje" <?php selected($penalidad->tipo_monto??'fijo','porcentaje'); ?>>Porcentaje (%)</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="font-weight-bold">Monto / % por defecto</label>
                    <input name="monto_default" type="number" step="0.01" min="0" class="form-control" value="<?php echo esc_attr($penalidad->monto_default??'0'); ?>">
                    <small class="text-muted">Ajustable al aplicar.</small>
                </div>
            </div>
            <div class="col-12">
                <div class="form-group">
                    <label class="font-weight-bold">Descripción / Detalle de la infracción</label>
                    <textarea name="descripcion" class="form-control" rows="2" placeholder="Describe la infracción o situación"><?php echo esc_textarea($penalidad->descripcion??''); ?></textarea>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="font-weight-bold">Aplica a</label>
                    <select name="aplica_a" class="form-control browser-default">
                        <?php foreach($actores as $v=>$l): ?>
                        <option value="<?php echo esc_attr($v); ?>" <?php selected($penalidad->aplica_a??'motorizado',$v); ?>><?php echo esc_html($l); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="font-weight-bold">Cuenta contable afectada</label>
                    <select name="cuenta_afectada" class="form-control browser-default">
                        <?php foreach($cuentas as $v=>$l): ?>
                        <option value="<?php echo esc_attr($v); ?>" <?php selected($penalidad->cuenta_afectada??'balance_motorizado',$v); ?>><?php echo esc_html($l); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="font-weight-bold">Efecto en el balance</label>
                    <select name="signo" class="form-control browser-default">
                        <option value="-1" <?php selected($penalidad->signo??-1,-1); ?>>− Descuento (resta del balance)</option>
                        <option value="1"  <?php selected($penalidad->signo??-1, 1); ?>>+ Cargo adicional (suma)</option>
                    </select>
                    <small class="text-muted">Las penalidades normalmente restan.</small>
                </div>
            </div>
        </div>

        <div class="d-flex" style="gap:8px">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save mr-1"></i>Guardar</button>
            <a href="<?php echo esc_url(add_query_arg('wcfin_vista','penalidades',$page_url)); ?>" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
    </div>
</div>

<?php else: ?>
<div style="background:#fff;border:1px solid #dee2e6;border-radius:6px;overflow:hidden">
    <div class="table-responsive">
    <table class="table table-hover table-sm mb-0">
        <thead class="thead-light">
            <tr><th>Nombre / Infracción</th><th>Tipo</th><th>Monto default</th><th>Aplica a</th><th>Cuenta</th><th>Efecto</th><th>Estado</th><th style="width:120px">Acciones</th></tr>
        </thead>
        <tbody>
        <?php if($lista): foreach($lista as $p): ?>
            <tr <?php echo !$p->activo?'class="text-muted"':''; ?>>
                <td>
                    <strong><?php echo esc_html($p->nombre); ?></strong>
                    <?php if($p->descripcion): ?><br><small class="text-muted"><?php echo esc_html(wp_trim_words($p->descripcion,10)); ?></small><?php endif; ?>
                </td>
                <td><?php echo $p->tipo_monto==='porcentaje'?'%':'Fijo'; ?></td>
                <td><?php echo $p->tipo_monto==='porcentaje'?esc_html($p->monto_default).'%':'S/ '.number_format($p->monto_default,2); ?></td>
                <td><?php echo esc_html($actores[$p->aplica_a]??$p->aplica_a); ?></td>
                <td><small><?php echo esc_html($cuentas[$p->cuenta_afectada]??$p->cuenta_afectada); ?></small></td>
                <td><?php echo $p->signo<0?'<span class="text-danger font-weight-bold">− Resta</span>':'<span class="text-success font-weight-bold">+ Suma</span>'; ?></td>
                <td>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline">
                        <?php wp_nonce_field('wcfin_penalidad_nonce'); ?>
                        <input type="hidden" name="action"       value="wcfin_toggle_penalidad">
                        <input type="hidden" name="penalidad_id" value="<?php echo intval($p->id); ?>">
                        <button type="submit" class="btn btn-sm <?php echo $p->activo?'btn-outline-secondary':'btn-outline-success'; ?>">
                            <?php echo $p->activo?'Desactivar':'Activar'; ?>
                        </button>
                    </form>
                </td>
                <td>
                    <a href="<?php echo esc_url(add_query_arg(['wcfin_vista'=>'penalidades','editar'=>$p->id],$page_url)); ?>" class="btn btn-outline-primary btn-sm mr-1"><i class="fa fa-pencil"></i></a>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline" onsubmit="return confirm('¿Eliminar esta penalidad?')">
                        <?php wp_nonce_field('wcfin_penalidad_nonce'); ?>
                        <input type="hidden" name="action"       value="wcfin_eliminar_penalidad">
                        <input type="hidden" name="penalidad_id" value="<?php echo intval($p->id); ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fa fa-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="8" class="text-center text-muted py-4"><i class="fa fa-inbox fa-2x d-block mb-2"></i>No hay penalidades configuradas.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>
