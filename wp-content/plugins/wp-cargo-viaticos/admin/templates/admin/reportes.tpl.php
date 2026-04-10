<?php if ( ! defined( 'ABSPATH' ) ) exit;
$total_asignado = empty($datos) ? 0 : array_sum( array_column( (array)$datos, 'monto_asignado' ) );
$total_usado    = empty($datos) ? 0 : array_sum( array_column( (array)$datos, 'monto_usado' ) );
$total_diff     = $total_asignado - $total_usado;

$por_trans = [];
foreach ( (array)$datos as $r ) {
    $n = $r->transportista_nombre ?? 'Sin nombre';
    if (!isset($por_trans[$n])) $por_trans[$n] = ['asignado'=>0,'usado'=>0];
    $por_trans[$n]['asignado'] += (float)$r->monto_asignado;
    $por_trans[$n]['usado']    += (float)$r->monto_usado;
}
$chart_labels    = array_values(array_keys($por_trans));
$chart_asignado  = array_values(array_map(fn($v)=>round($v['asignado'],2),$por_trans));
$chart_ejecutado = array_values(array_map(fn($v)=>round($v['usado'],2),$por_trans));
$chart_diff      = array_values(array_map(fn($v)=>round($v['asignado']-$v['usado'],2),$por_trans));
?>
<div class="wrap">
<h1>Reportes de Viáticos</h1>
<hr class="wp-header-end">

<!-- Filtros -->
<form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>"
      style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;background:#f6f7f7;padding:14px 16px;border:1px solid #ddd;border-radius:4px;margin:16px 0">
    <input type="hidden" name="page" value="wpcv-reportes">

    <label style="font-size:11px;font-weight:700;display:flex;flex-direction:column;gap:5px;color:#1d2327">Transportista
        <select name="transportista_id" class="postform" style="min-width:160px">
            <option value="">Todos</option>
            <?php foreach ($transportistas as $t): ?>
                <option value="<?php echo (int)$t->id; ?>" <?php selected($transportista_id,$t->id); ?>>
                    <?php echo esc_html($t->nombre); ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label style="font-size:11px;font-weight:700;display:flex;flex-direction:column;gap:5px;color:#1d2327">Ruta
        <input type="text" name="ruta" class="regular-text" style="height:30px;max-width:160px"
               placeholder="Filtrar ruta…" value="<?php echo esc_attr($ruta); ?>">
    </label>

    <label style="font-size:11px;font-weight:700;display:flex;flex-direction:column;gap:5px;color:#1d2327">Estado
        <select name="estado" class="postform">
            <option value="">Todos</option>
            <option value="activo"  <?php selected($estado,'activo'); ?>>Activos</option>
            <option value="cerrado" <?php selected($estado,'cerrado'); ?>>Cerrados</option>
        </select>
    </label>

    <label style="font-size:11px;font-weight:700;display:flex;flex-direction:column;gap:5px;color:#1d2327">Período
        <select name="periodo" id="wpcv-admin-periodo" class="postform">
            <option value="">Personalizado</option>
            <option value="diario"  <?php selected($periodo,'diario'); ?>>Hoy</option>
            <option value="semanal" <?php selected($periodo,'semanal'); ?>>Esta semana</option>
            <option value="mensual" <?php selected($periodo,'mensual'); ?>>Este mes</option>
        </select>
    </label>

    <div id="wpcv-admin-rango" style="display:<?php echo $periodo?'none':'flex'; ?>;gap:8px;align-items:flex-end">
        <label style="font-size:11px;font-weight:700;display:flex;flex-direction:column;gap:5px;color:#1d2327">Desde
            <input type="date" name="desde" value="<?php echo esc_attr($desde); ?>" style="height:30px">
        </label>
        <label style="font-size:11px;font-weight:700;display:flex;flex-direction:column;gap:5px;color:#1d2327">Hasta
            <input type="date" name="hasta" value="<?php echo esc_attr($hasta); ?>" style="height:30px">
        </label>
    </div>
    <?php if ($periodo): ?>
        <input type="hidden" name="desde" value="">
        <input type="hidden" name="hasta" value="">
    <?php endif; ?>

    <div style="display:flex;gap:8px;align-items:flex-end">
        <button type="submit" class="button button-primary">Generar reporte</button>
        <?php if ($transportista_id||$ruta||$estado||$periodo||$desde||$hasta): ?>
            <a href="<?php echo esc_url(add_query_arg('page','wpcv-reportes',admin_url('admin.php'))); ?>"
               class="button">Limpiar</a>
        <?php endif; ?>
    </div>
</form>

<script>
document.getElementById('wpcv-admin-periodo').addEventListener('change',function(){
    document.getElementById('wpcv-admin-rango').style.display=this.value?'none':'flex';
});
</script>

<!-- KPI -->
<div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap">
    <?php foreach ([
        ['label'=>'Registros',      'valor'=>count($datos),                       'color'=>'#2271b1'],
        ['label'=>'Total Asignado', 'valor'=>'S/ '.number_format($total_asignado,2),'color'=>'#1d2327'],
        ['label'=>'Total Ejecutado','valor'=>'S/ '.number_format($total_usado,2),   'color'=>'#e65100'],
        ['label'=>'Diferencia',     'valor'=>'S/ '.number_format($total_diff,2),    'color'=>$total_diff>=0?'#00a32a':'#d63638'],
    ] as $kpi): ?>
    <div style="background:#fff;border:1px solid #ddd;border-radius:4px;padding:12px 20px;min-width:140px;text-align:center">
        <div style="font-size:10px;color:#666;text-transform:uppercase;font-weight:700;letter-spacing:.5px"><?php echo esc_html($kpi['label']); ?></div>
        <div style="font-size:1.4rem;font-weight:700;color:<?php echo esc_attr($kpi['color']); ?>;margin-top:4px">
            <?php echo esc_html($kpi['valor']); ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (!empty($datos)): ?>
<!-- Gráfico — siempre abierto -->
<div style="background:#fff;border:1px solid #ddd;border-radius:4px;margin-bottom:20px">
    <div style="padding:10px 16px;border-bottom:1px solid #ddd;background:#f6f7f7;display:flex;justify-content:space-between;align-items:center">
        <strong style="font-size:13px">Asignado vs Ejecutado por Transportista</strong>
        <button type="button" class="button" id="wpcv-toggle-chart">
            &#9685; Ver distribución (doughnut)
        </button>
    </div>
    <div style="padding:16px">
        <div id="wpcv-bar-wrap">
            <canvas id="wpcvBar" height="<?php echo count($chart_labels)>6?100:70; ?>"></canvas>
        </div>
        <div id="wpcv-pie-wrap" style="display:none;max-width:440px;margin:0 auto">
            <canvas id="wpcvPie"></canvas>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
(function(){
  var labels    = <?php echo wp_json_encode($chart_labels); ?>;
  var asignado  = <?php echo wp_json_encode($chart_asignado); ?>;
  var ejecutado = <?php echo wp_json_encode($chart_ejecutado); ?>;
  var diffs     = <?php echo wp_json_encode($chart_diff); ?>;
  var palette   = ['#2271b1','#e65100','#00a32a','#f0b849','#8c5af5','#17a2b8','#fd7e14','#e83e8c'];

  new Chart(document.getElementById('wpcvBar').getContext('2d'),{
    type:'bar',
    data:{
      labels:labels,
      datasets:[
        {label:'Asignado (S/)',  data:asignado,  backgroundColor:'rgba(34,113,177,.85)', borderColor:'#2271b1',borderWidth:1,borderRadius:3},
        {label:'Ejecutado (S/)', data:ejecutado, backgroundColor:'rgba(230,81,0,.85)',   borderColor:'#e65100',borderWidth:1,borderRadius:3},
        {label:'Diferencia (S/)',data:diffs,
          backgroundColor:diffs.map(function(d){return d>=0?'rgba(0,163,42,.8)':'rgba(214,54,56,.8)';}),
          borderColor:diffs.map(function(d){return d>=0?'#00a32a':'#d63638';}),borderWidth:1,borderRadius:3}
      ]
    },
    options:{responsive:true,
      plugins:{legend:{position:'top'},tooltip:{callbacks:{label:function(c){return c.dataset.label+': S/ '+c.parsed.y.toFixed(2);}}}},
      scales:{y:{beginAtZero:true,ticks:{callback:function(v){return 'S/ '+v.toLocaleString();}}}}}
  });

  new Chart(document.getElementById('wpcvPie').getContext('2d'),{
    type:'doughnut',
    data:{labels:labels,datasets:[{data:ejecutado,
      backgroundColor:labels.map(function(_,i){return palette[i%palette.length];}),borderWidth:2}]},
    options:{responsive:true,plugins:{legend:{position:'right'},
      tooltip:{callbacks:{label:function(c){
        var t=c.dataset.data.reduce(function(a,b){return a+b;},0);
        var p=t>0?(c.parsed/t*100).toFixed(1):0;
        return c.label+': S/ '+c.parsed.toFixed(2)+' ('+p+'%)';
      }}}}}
  });

  document.getElementById('wpcv-toggle-chart').addEventListener('click',function(){
    var bw=document.getElementById('wpcv-bar-wrap'),pw=document.getElementById('wpcv-pie-wrap');
    var sb=bw.style.display!=='none';
    bw.style.display=sb?'none':''; pw.style.display=sb?'':'none';
    this.innerHTML=sb?'&#9635; Ver comparativo':'&#9685; Ver distribución (doughnut)';
  });
})();
</script>
<?php endif; ?>

<!-- Tabla — siempre abierta -->
<div style="background:#fff;border:1px solid #ddd;border-radius:4px;margin-bottom:20px">
    <div style="padding:10px 16px;border-bottom:1px solid #ddd;background:#f6f7f7;display:flex;justify-content:space-between;align-items:center">
        <strong style="font-size:13px">Detalle de registros</strong>
        <?php if (!empty($datos)): ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('wpcv_export_csv'); ?>
            <input type="hidden" name="action"           value="wpcv_export_csv">
            <input type="hidden" name="transportista_id" value="<?php echo (int)$transportista_id; ?>">
            <input type="hidden" name="ruta"             value="<?php echo esc_attr($ruta); ?>">
            <input type="hidden" name="estado"           value="<?php echo esc_attr($estado); ?>">
            <input type="hidden" name="periodo"          value="<?php echo esc_attr($periodo); ?>">
            <input type="hidden" name="desde"            value="<?php echo esc_attr($desde); ?>">
            <input type="hidden" name="hasta"            value="<?php echo esc_attr($hasta); ?>">
            <button type="submit" class="button button-secondary">⬇ Exportar CSV</button>
        </form>
        <?php endif; ?>
    </div>
    <div style="overflow-x:auto">
    <table class="wp-list-table widefat fixed striped" style="border:none">
        <thead><tr>
            <th style="width:90px">Fecha</th>
            <th>Transportista</th>
            <th>Ruta</th>
            <th style="width:120px">Asignado</th>
            <th style="width:120px">Ejecutado</th>
            <th style="width:110px">Diferencia</th>
            <th style="width:80px">Estado</th>
        </tr></thead>
        <tbody>
        <?php if (empty($datos)): ?>
            <tr><td colspan="7" style="text-align:center;padding:24px;color:#888">
                Sin datos. Aplica filtros y presiona <em>Generar reporte</em>.
            </td></tr>
        <?php else: foreach ($datos as $r):
            $diff=(float)$r->monto_asignado-(float)$r->monto_usado; ?>
            <tr>
                <td><?php echo esc_html(date_i18n('d/m/Y',strtotime($r->fecha_asignacion))); ?></td>
                <td><strong><?php echo esc_html($r->transportista_nombre??'—'); ?></strong></td>
                <td><?php echo esc_html($r->ruta); ?></td>
                <td>S/ <?php echo esc_html(number_format((float)$r->monto_asignado,2)); ?></td>
                <td>S/ <?php echo esc_html(number_format((float)$r->monto_usado,2)); ?></td>
                <td style="color:<?php echo $diff>=0?'#00a32a':'#d63638'; ?>;font-weight:700">
                    S/ <?php echo esc_html(number_format($diff,2)); ?>
                </td>
                <td><?php echo $r->estado==='cerrado'
                    ? '<span style="color:#666;font-size:12px">⛔ Cerrado</span>'
                    : '<span style="color:#00a32a;font-size:12px">✔ Activo</span>'; ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>
</div>
