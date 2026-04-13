<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
<h1 class="wp-heading-inline">Penalidades</h1>
<?php if ( ! ($edit_id || isset($_GET['editar'])) ): ?>
<a href="<?php echo esc_url(wcfin_url('wcfin-penalidades',['editar'=>'nuevo'])); ?>" class="page-title-action">Añadir nueva</a>
<?php endif; ?>
<hr class="wp-header-end">

<?php if ($edit_id || isset($_GET['editar'])): ?>
<!-- ═══ FORMULARIO ══════════════════════════════════════════════════════ -->
<h2><?php echo $penalidad ? 'Editar penalidad' : 'Nueva penalidad'; ?></h2>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('wcfin_penalidad_nonce'); ?>
    <input type="hidden" name="action"       value="wcfin_guardar_penalidad">
    <input type="hidden" name="penalidad_id" value="<?php echo intval($penalidad->id ?? 0); ?>">

    <table class="form-table" role="presentation">
        <tr>
            <th><label for="nombre">Nombre / Infracción</label></th>
            <td><input id="nombre" name="nombre" type="text" class="regular-text" value="<?php echo esc_attr($penalidad->nombre??''); ?>" placeholder="ej: Entrega tardía" required></td>
        </tr>
        <tr>
            <th><label for="descripcion">Descripción</label></th>
            <td><textarea id="descripcion" name="descripcion" class="large-text" rows="3" placeholder="Describe la infracción o situación que genera esta penalidad"><?php echo esc_textarea($penalidad->descripcion??''); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="tipo_monto">Tipo de monto</label></th>
            <td>
                <select id="tipo_monto" name="tipo_monto">
                    <option value="fijo"       <?php selected($penalidad->tipo_monto??'fijo','fijo'); ?>>Monto fijo (S/)</option>
                    <option value="porcentaje" <?php selected($penalidad->tipo_monto??'fijo','porcentaje'); ?>>Porcentaje del envío (%)</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="monto_default">Monto / % por defecto</label></th>
            <td>
                <input id="monto_default" name="monto_default" type="number" step="0.01" min="0" class="small-text" value="<?php echo esc_attr($penalidad->monto_default??'0'); ?>">
                <p class="description">Valor sugerido. El operador puede ajustarlo al aplicarla a un envío específico.</p>
            </td>
        </tr>
        <tr>
            <th><label for="aplica_a">Aplica a</label></th>
            <td>
                <select id="aplica_a" name="aplica_a" class="regular-text">
                    <?php foreach($actores as $v=>$l): ?>
                    <option value="<?php echo esc_attr($v); ?>" <?php selected($penalidad->aplica_a??'motorizado',$v); ?>><?php echo esc_html($l); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="cuenta_afectada">Cuenta contable afectada</label></th>
            <td>
                <select id="cuenta_afectada" name="cuenta_afectada" class="regular-text">
                    <?php foreach($cuentas as $v=>$l): ?>
                    <option value="<?php echo esc_attr($v); ?>" <?php selected($penalidad->cuenta_afectada??'balance_motorizado',$v); ?>><?php echo esc_html($l); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="signo">Efecto en el balance</label></th>
            <td>
                <select id="signo" name="signo">
                    <option value="-1" <?php selected($penalidad->signo??-1,-1); ?>>− Descuento (resta del balance)</option>
                    <option value="1"  <?php selected($penalidad->signo??-1, 1); ?>>+ Cargo adicional (suma al balance)</option>
                </select>
                <p class="description">Las penalidades normalmente <strong>restan</strong> del balance del afectado.</p>
            </td>
        </tr>
    </table>

    <p class="submit">
        <button type="submit" class="button button-primary">Guardar</button>
        <a href="<?php echo esc_url(wcfin_url('wcfin-penalidades')); ?>" class="button">Cancelar</a>
    </p>
</form>

<?php else: ?>
<!-- ═══ LISTA ════════════════════════════════════════════════════════════ -->
<table class="wp-list-table widefat fixed striped">
    <thead><tr>
        <th>Nombre / Infracción</th><th>Tipo</th><th>Monto default</th>
        <th>Aplica a</th><th>Cuenta</th><th>Efecto</th><th>Estado</th><th>Acciones</th>
    </tr></thead>
    <tbody>
    <?php if($lista): foreach($lista as $p): ?>
        <tr <?php echo !$p->activo ? 'style="opacity:.55"' : ''; ?>>
            <td>
                <strong><?php echo esc_html($p->nombre); ?></strong>
                <?php if($p->descripcion): ?><br><small style="color:#666"><?php echo esc_html(wp_trim_words($p->descripcion,12)); ?></small><?php endif; ?>
            </td>
            <td><?php echo $p->tipo_monto==='porcentaje'?'Porcentaje':'Fijo'; ?></td>
            <td><?php echo $p->tipo_monto==='porcentaje' ? esc_html($p->monto_default).'%' : 'S/ '.number_format($p->monto_default,2); ?></td>
            <td><?php echo esc_html($actores[$p->aplica_a]??$p->aplica_a); ?></td>
            <td><?php echo esc_html($cuentas[$p->cuenta_afectada]??$p->cuenta_afectada); ?></td>
            <td><?php echo $p->signo<0 ? '<span style="color:#d63638">− Resta</span>' : '<span style="color:#00a32a">+ Suma</span>'; ?></td>
            <td>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline">
                    <?php wp_nonce_field('wcfin_penalidad_nonce'); ?>
                    <input type="hidden" name="action"       value="wcfin_toggle_penalidad">
                    <input type="hidden" name="penalidad_id" value="<?php echo intval($p->id); ?>">
                    <button type="submit" class="button button-small"><?php echo $p->activo?'Desactivar':'Activar'; ?></button>
                </form>
            </td>
            <td>
                <a href="<?php echo esc_url(wcfin_url('wcfin-penalidades',['editar'=>$p->id])); ?>" class="button button-small">Editar</a>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline" onsubmit="return confirm('¿Eliminar esta penalidad?')">
                    <?php wp_nonce_field('wcfin_penalidad_nonce'); ?>
                    <input type="hidden" name="action"       value="wcfin_eliminar_penalidad">
                    <input type="hidden" name="penalidad_id" value="<?php echo intval($p->id); ?>">
                    <button type="submit" class="button button-small button-link-delete">Eliminar</button>
                </form>
            </td>
        </tr>
    <?php endforeach; else: ?>
        <tr><td colspan="8" style="text-align:center;padding:20px;color:#888">No hay penalidades configuradas.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<?php endif; ?>
</div>
