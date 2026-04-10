<?php if ( ! defined('ABSPATH') ) exit; ?>
<div class="wrap wpcte-admin-wrap">

<div class="wpcte-page-header">
    <div class="wpcte-page-header-left">
        <h1><span class="dashicons dashicons-calculator"></span> Cotizador DHV</h1>
        <p class="wpcte-subtitle">Simula precios de envío para cualquier modalidad y tipo de envío.</p>
    </div>
    <div class="wpcte-page-header-right">
        <a href="<?php echo admin_url('admin.php?page=wpcte-tarifario'); ?>" class="button button-secondary">
            📋 Gestionar Tarifario
        </a>
    </div>
</div>

<div style="display:flex;gap:1.5rem;flex-wrap:wrap;align-items:flex-start">

<!-- Panel Cotizador -->
<div style="flex:1;min-width:340px;max-width:600px">
<div style="background:#fff;border:1.5px solid #d0e8f5;border-radius:14px;padding:1.5rem;box-shadow:0 2px 12px rgba(0,120,200,.07)">
<h3 style="color:#0077b6;margin:0 0 1rem"><i class="fa fa-calculator"></i> Simulador de Cotización</h3>

<!-- Tipo de envío filter -->
<div style="margin-bottom:1rem">
<label style="font-size:.82rem;font-weight:600;color:#555;display:block;margin-bottom:3px">Tipo de Envío (Filtrar modalidades)</label>
<select id="cot-tipo-envio" style="width:100%;padding:6px;border:1px solid #ccc;border-radius:8px">
    <option value="">-- Todos --</option>
    <option value="puerta_puerta">Puerta a Puerta</option>
    <option value="agencia">Agencia</option>
    <option value="almacen">Almacén</option>
</select>
</div>

<?php
$tarifario = wpcte_tarifario();
echo wpcte_render_cotizador_html( $tarifario, '', 'crear' );
?>
</div>
</div>

<!-- Panel Info Tarifario Rápido -->
<div style="flex:1;min-width:300px">
<div style="background:#fff;border:1.5px solid #d0e8f5;border-radius:14px;padding:1.5rem;box-shadow:0 2px 12px rgba(0,120,200,.07)">
<h3 style="color:#0077b6;margin:0 0 1rem">📋 Resumen Tarifario</h3>

<?php $t = wpcte_tarifario(); ?>

<div style="margin-bottom:1rem">
<strong style="color:#0077b6">🏙️ Dentro de Lima — Vehículos</strong>
<table style="width:100%;margin-top:.5rem;font-size:.82rem;border-collapse:collapse">
<tr style="background:#f0f7ff"><th style="padding:4px 8px;text-align:left">Vehículo</th><th style="padding:4px 8px">Base (S/)</th></tr>
<?php foreach ( $t['lima_lima']['vehiculos'] as $k => $v ): ?>
<tr><td style="padding:3px 8px;border-bottom:1px solid #edf4ff"><?php echo esc_html($v['label']); ?></td><td style="padding:3px 8px;border-bottom:1px solid #edf4ff;text-align:right">S/ <?php echo number_format($v['precio_base'],2); ?></td></tr>
<?php endforeach; ?>
</table>
</div>

<div style="margin-bottom:1rem">
<strong style="color:#2a9d8f">📦 Carga General — Rutas</strong>
<?php foreach ( $t['carga_general']['rutas'] as $origen => $ruta ): ?>
<p style="font-size:.82rem;margin:.5rem 0 .25rem"><em>Desde <?php echo esc_html($origen); ?> — <?php echo count($ruta['destinos']); ?> destinos</em></p>
<?php endforeach; ?>
</div>

<div style="margin-bottom:1rem">
<strong style="color:#e76f51">✉️ Sobres</strong>
<table style="width:100%;margin-top:.5rem;font-size:.82rem;border-collapse:collapse">
<tr style="background:#f0f7ff"><th style="padding:4px 8px;text-align:left">Tipo</th><th>Agencia</th><th>Domicilio</th><th>Dev.</th></tr>
<?php foreach ( $t['sobres']['tarifas'] as $tipo => $s ): ?>
<tr><td style="padding:3px 8px;border-bottom:1px solid #edf4ff;font-size:.78rem"><?php echo esc_html($tipo); ?></td>
<td style="text-align:center;border-bottom:1px solid #edf4ff">S/ <?php echo $s['agencia']; ?></td>
<td style="text-align:center;border-bottom:1px solid #edf4ff">S/ <?php echo $s['domicilio']; ?></td>
<td style="text-align:center;border-bottom:1px solid #edf4ff">S/ <?php echo $s['devolucion']; ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<div>
<strong style="color:#6c5ce7">✈️ Aéreos — Destinos</strong>
<table style="width:100%;margin-top:.5rem;font-size:.82rem;border-collapse:collapse">
<tr style="background:#f0f7ff"><th style="padding:4px 8px;text-align:left">Destino</th><th>1kg (S/)</th><th>Lead</th></tr>
<?php foreach ( $t['aereo']['destinos'] as $dest => $d ): ?>
<tr><td style="padding:3px 8px;border-bottom:1px solid #edf4ff"><?php echo esc_html($dest); ?></td>
<td style="text-align:center;border-bottom:1px solid #edf4ff">S/ <?php echo $d['precio_1kg']; ?></td>
<td style="text-align:center;border-bottom:1px solid #edf4ff;font-size:.75rem"><?php echo esc_html($d['lead']); ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<p style="margin-top:1rem;font-size:.8rem;color:#888">
<a href="<?php echo admin_url('admin.php?page=wpcte-tarifario'); ?>" class="button button-small">⚙️ Gestionar Tarifario</a>
</p>
</div>
</div>
</div>
</div>

<style>
.wpcte-admin-wrap{max-width:1200px;margin-top:1.5rem;}
/* Cabecera */
.wpcte-page-header{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap;}
.wpcte-page-header h1{margin:0 0 .25rem;font-size:1.4rem;display:flex;align-items:center;gap:.5rem;}
.wpcte-page-header h1 .dashicons{color:#0077b6;font-size:1.6rem;width:1.6rem;height:1.6rem;}
.wpcte-page-header-right{display:flex;gap:.5rem;align-items:center;padding-top:.25rem;}
.wpcte-subtitle{color:#666;margin:0;}
#wpcte-btn-continuar{display:none!important;}
#wpcte-cotizador{box-shadow:none;border:none;padding:0;}
</style>

<script>
(function($){
var MOD_RULES=<?php echo wp_json_encode( wpcte_modalidades_por_tipo() ); ?>;

$('#cot-tipo-envio').on('change',function(){
    var tipo=$(this).val();
    var modSel=document.getElementById('wpcte-mod');
    if(!modSel)return;
    Array.from(modSel.options).forEach(function(op){
        if(!op.value){op.style.display='';return;}
        op.style.display=(!tipo||MOD_RULES[tipo]&&MOD_RULES[tipo].indexOf(op.value)>-1)?'':'none';
    });
    modSel.value='';$(modSel).trigger('change');
});
})(jQuery);
</script>
