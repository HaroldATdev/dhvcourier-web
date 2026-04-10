<?php if ( ! defined( 'ABSPATH' ) ) exit;
$total_asignado = empty($datos) ? 0 : array_sum( array_column( (array) $datos, 'monto_asignado' ) );
$total_usado    = empty($datos) ? 0 : array_sum( array_column( (array) $datos, 'monto_usado' ) );
$total_diff     = $total_asignado - $total_usado;

// Agrupar por transportista para gráficos
$por_trans = [];
foreach ( (array) $datos as $r ) {
	$n = $r->transportista_nombre ?? 'Sin nombre';
	if ( ! isset( $por_trans[$n] ) ) $por_trans[$n] = ['asignado'=>0,'usado'=>0];
	$por_trans[$n]['asignado'] += (float)$r->monto_asignado;
	$por_trans[$n]['usado']    += (float)$r->monto_usado;
}
$chart_labels    = array_values( array_keys( $por_trans ) );
$chart_asignado  = array_values( array_map( fn($v) => round($v['asignado'],2), $por_trans ) );
$chart_ejecutado = array_values( array_map( fn($v) => round($v['usado'],2),    $por_trans ) );
$chart_diff      = array_values( array_map( fn($v) => round($v['asignado']-$v['usado'],2), $por_trans ) );
?>

<div class="d-flex justify-content-between align-items-center mb-3">
	<h5 class="mb-0"><i class="fa fa-bar-chart mr-2 text-primary"></i>Reportes de Viáticos</h5>
	<a href="<?php echo esc_url( $page_url ); ?>" class="btn btn-outline-secondary btn-sm">
		<i class="fa fa-arrow-left mr-1"></i> Volver
	</a>
</div>

<!-- ── Filtros ──────────────────────────────────────────────── -->
<div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:6px;padding:16px 18px;margin-bottom:20px">
<form method="get" action="<?php echo esc_url( $page_url ); ?>" class="row align-items-end" style="row-gap:10px">
	<input type="hidden" name="wpcv" value="reportes">

	<div class="col-auto">
		<label class="d-block mb-1" style="font-size:11px;font-weight:600;color:#666;text-transform:uppercase">Transportista</label>
		<select name="transportista_id" class="form-control form-control-sm browser-default" style="min-width:170px">
			<option value="">Todos</option>
			<?php foreach ( $transportistas as $t ) : ?>
				<option value="<?php echo (int)$t->id; ?>" <?php selected($transportista_id,$t->id); ?>>
					<?php echo esc_html($t->nombre); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>

	<div class="col-auto">
		<label class="d-block mb-1" style="font-size:11px;font-weight:600;color:#666;text-transform:uppercase">Ruta</label>
		<input type="text" name="ruta" class="form-control form-control-sm"
		       placeholder="Filtrar…" style="min-width:140px" value="<?php echo esc_attr($ruta); ?>">
	</div>

	<div class="col-auto">
		<label class="d-block mb-1" style="font-size:11px;font-weight:600;color:#666;text-transform:uppercase">Estado</label>
		<select name="estado" class="form-control form-control-sm browser-default">
			<option value="">Todos</option>
			<option value="activo"  <?php selected($estado,'activo'); ?>>Activos</option>
			<option value="cerrado" <?php selected($estado,'cerrado'); ?>>Cerrados</option>
		</select>
	</div>

	<div class="col-auto">
		<label class="d-block mb-1" style="font-size:11px;font-weight:600;color:#666;text-transform:uppercase">Período</label>
		<select name="periodo" id="wpcv-periodo-sel" class="form-control form-control-sm browser-default">
			<option value="">Personalizado</option>
			<option value="diario"  <?php selected($periodo,'diario'); ?>>Hoy</option>
			<option value="semanal" <?php selected($periodo,'semanal'); ?>>Esta semana</option>
			<option value="mensual" <?php selected($periodo,'mensual'); ?>>Este mes</option>
		</select>
	</div>

	<div id="wpcv-rango" class="<?php echo $periodo ? 'd-none' : ''; ?>" style="display:<?php echo $periodo ? 'none!important' : ''; ?>">
		<div class="d-flex align-items-end" style="gap:8px">
			<div>
				<label class="d-block mb-1" style="font-size:11px;font-weight:600;color:#666;text-transform:uppercase">Desde</label>
				<input type="date" name="desde" class="form-control form-control-sm" value="<?php echo esc_attr($desde); ?>">
			</div>
			<div>
				<label class="d-block mb-1" style="font-size:11px;font-weight:600;color:#666;text-transform:uppercase">Hasta</label>
				<input type="date" name="hasta" class="form-control form-control-sm" value="<?php echo esc_attr($hasta); ?>">
			</div>
		</div>
	</div>
	<?php if ($periodo): ?>
		<input type="hidden" name="desde" value="">
		<input type="hidden" name="hasta" value="">
	<?php endif; ?>

	<div class="col-auto d-flex align-items-end" style="gap:8px">
		<button type="submit" class="btn btn-primary btn-sm px-3">
			<i class="fa fa-bar-chart mr-1"></i> Generar
		</button>
		<?php if ($transportista_id||$ruta||$estado||$periodo||$desde||$hasta): ?>
			<a href="<?php echo esc_url( wpcv_frontend_url(['wpcv'=>'reportes']) ); ?>"
			   class="btn btn-outline-secondary btn-sm">Limpiar</a>
		<?php endif; ?>
	</div>
</form>
</div>

<script>
document.getElementById('wpcv-periodo-sel').addEventListener('change', function(){
	var rango = document.getElementById('wpcv-rango');
	if (this.value) {
		rango.style.display = 'none';
	} else {
		rango.style.display = '';
		rango.classList.remove('d-none');
	}
});
</script>

<!-- ── KPI ──────────────────────────────────────────────────── -->
<div class="row mb-4" style="row-gap:12px">
	<?php foreach ( [
		[ 'label'=>'Registros',       'valor'=>count($datos),                   'icon'=>'fa-list',         'color'=>'#6c757d' ],
		[ 'label'=>'Total Asignado',  'valor'=>wpcv_monto($total_asignado),      'icon'=>'fa-money',        'color'=>'#2271b1' ],
		[ 'label'=>'Total Ejecutado', 'valor'=>wpcv_monto($total_usado),         'icon'=>'fa-shopping-cart','color'=>'#e65100' ],
		[ 'label'=>'Diferencia',      'valor'=>wpcv_monto($total_diff),          'icon'=>'fa-balance-scale','color'=>$total_diff>=0?'#28a745':'#dc3545' ],
	] as $kpi ) : ?>
	<div class="col-md-3 col-6">
		<div style="background:#fff;border:1px solid #dee2e6;border-radius:6px;padding:14px 12px;text-align:center">
			<i class="fa <?php echo esc_attr($kpi['icon']); ?> fa-lg mb-2 d-block"
			   style="color:<?php echo esc_attr($kpi['color']); ?>"></i>
			<div style="font-size:10px;color:#888;text-transform:uppercase;font-weight:700;letter-spacing:.5px">
				<?php echo esc_html($kpi['label']); ?>
			</div>
			<div style="font-size:1.3rem;font-weight:700;color:<?php echo esc_attr($kpi['color']); ?>;margin-top:4px">
				<?php echo esc_html($kpi['valor']); ?>
			</div>
		</div>
	</div>
	<?php endforeach; ?>
</div>

<?php if ( ! empty($datos) ) : ?>
<!-- ── Gráfico — siempre visible, no colapsable ─────────────── -->
<div style="background:#fff;border:1px solid #dee2e6;border-radius:6px;margin-bottom:20px;overflow:hidden">
	<div style="padding:12px 16px;border-bottom:1px solid #dee2e6;display:flex;justify-content:space-between;align-items:center;background:#f8f9fa">
		<strong style="font-size:.9rem"><i class="fa fa-bar-chart mr-1 text-primary"></i> Asignado vs Ejecutado por Transportista</strong>
		<button type="button" class="btn btn-outline-secondary btn-sm" id="wpcv-toggle-chart">
			<i class="fa fa-pie-chart mr-1"></i> Ver distribución
		</button>
	</div>
	<div style="padding:16px">
		<div id="wpcv-chart-bar-wrap">
			<canvas id="wpcvChartBar" height="<?php echo count($chart_labels) > 5 ? 120 : 80; ?>"></canvas>
		</div>
		<div id="wpcv-chart-pie-wrap" style="display:none;max-width:420px;margin:0 auto">
			<canvas id="wpcvChartPie"></canvas>
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
  var palette   = ['#2271b1','#e65100','#28a745','#ffc107','#6f42c1','#17a2b8','#fd7e14','#e83e8c'];

  new Chart(document.getElementById('wpcvChartBar').getContext('2d'), {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [
        { label:'Asignado (S/)',  data:asignado,  backgroundColor:'rgba(34,113,177,.82)', borderColor:'#2271b1', borderWidth:1, borderRadius:4 },
        { label:'Ejecutado (S/)', data:ejecutado, backgroundColor:'rgba(230,81,0,.82)',   borderColor:'#e65100', borderWidth:1, borderRadius:4 },
        { label:'Diferencia (S/)',data:diffs,
          backgroundColor:diffs.map(function(d){return d>=0?'rgba(40,167,69,.82)':'rgba(220,53,69,.82)';}),
          borderColor:diffs.map(function(d){return d>=0?'#28a745':'#dc3545';}),
          borderWidth:1, borderRadius:4 }
      ]
    },
    options:{
      responsive:true,
      plugins:{
        legend:{position:'top'},
        tooltip:{callbacks:{label:function(c){return c.dataset.label+': S/ '+c.parsed.y.toFixed(2);}}}
      },
      scales:{y:{beginAtZero:true,ticks:{callback:function(v){return 'S/ '+v.toLocaleString();}}}}
    }
  });

  new Chart(document.getElementById('wpcvChartPie').getContext('2d'), {
    type:'doughnut',
    data:{
      labels:labels,
      datasets:[{
        data:ejecutado,
        backgroundColor:labels.map(function(_,i){return palette[i%palette.length];}),
        borderWidth:2
      }]
    },
    options:{
      responsive:true,
      plugins:{
        legend:{position:'right'},
        tooltip:{callbacks:{label:function(c){
          var t=c.dataset.data.reduce(function(a,b){return a+b;},0);
          var p=t>0?(c.parsed/t*100).toFixed(1):0;
          return c.label+': S/ '+c.parsed.toFixed(2)+' ('+p+'%)';
        }}}
      }
    }
  });

  document.getElementById('wpcv-toggle-chart').addEventListener('click',function(){
    var bw=document.getElementById('wpcv-chart-bar-wrap'),pw=document.getElementById('wpcv-chart-pie-wrap');
    var showBar=bw.style.display!=='none';
    bw.style.display=showBar?'none':'';
    pw.style.display=showBar?'':'none';
    this.innerHTML=showBar?'<i class="fa fa-bar-chart mr-1"></i> Ver comparativo':'<i class="fa fa-pie-chart mr-1"></i> Ver distribución';
  });
})();
</script>
<?php endif; ?>

<!-- ── Detalle — siempre visible, no colapsable ─────────────── -->
<div style="background:#fff;border:1px solid #dee2e6;border-radius:6px;margin-bottom:20px;overflow:hidden">
	<div style="padding:12px 16px;border-bottom:1px solid #dee2e6;display:flex;justify-content:space-between;align-items:center;background:#f8f9fa">
		<strong style="font-size:.9rem"><i class="fa fa-table mr-1 text-secondary"></i> Detalle de registros</strong>
		<?php if ( ! empty($datos) ) : ?>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
			<?php wp_nonce_field('wpcv_export_csv'); ?>
			<input type="hidden" name="action"           value="wpcv_export_csv">
			<input type="hidden" name="transportista_id" value="<?php echo (int)$transportista_id; ?>">
			<input type="hidden" name="ruta"             value="<?php echo esc_attr($ruta); ?>">
			<input type="hidden" name="estado"           value="<?php echo esc_attr($estado); ?>">
			<input type="hidden" name="periodo"          value="<?php echo esc_attr($periodo); ?>">
			<input type="hidden" name="desde"            value="<?php echo esc_attr($desde); ?>">
			<input type="hidden" name="hasta"            value="<?php echo esc_attr($hasta); ?>">
			<button type="submit" class="btn btn-outline-success btn-sm">
				<i class="fa fa-download mr-1"></i> Exportar CSV
			</button>
		</form>
		<?php endif; ?>
	</div>
	<div class="table-responsive">
	<table class="table table-hover table-sm mb-0">
		<thead class="thead-light">
			<tr>
				<th>Fecha</th><th>Transportista</th><th>Ruta</th>
				<th>Asignado</th><th>Ejecutado</th><th>Diferencia</th><th>Estado</th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty($datos) ) : ?>
			<tr><td colspan="7" class="text-center text-muted py-5">
				<i class="fa fa-inbox fa-2x d-block mb-2"></i>
				Sin datos. Aplica filtros y presiona Generar.
			</td></tr>
		<?php else : foreach ( $datos as $r ) :
			$diff = (float)$r->monto_asignado - (float)$r->monto_usado; ?>
			<tr>
				<td class="small"><?php echo esc_html(date_i18n('d/m/Y',strtotime($r->fecha_asignacion))); ?></td>
				<td><strong><?php echo esc_html($r->transportista_nombre ?? '—'); ?></strong></td>
				<td class="small"><?php echo esc_html($r->ruta); ?></td>
				<td><?php echo esc_html(wpcv_monto((float)$r->monto_asignado)); ?></td>
				<td><?php echo esc_html(wpcv_monto((float)$r->monto_usado)); ?></td>
				<td class="font-weight-bold <?php echo $diff>=0?'text-success':'text-danger'; ?>">
					<?php echo esc_html(wpcv_monto($diff)); ?>
				</td>
				<td><?php echo $r->estado==='cerrado'
					? '<span class="badge badge-secondary">Cerrado</span>'
					: '<span class="badge badge-success">Activo</span>'; ?></td>
			</tr>
		<?php endforeach; endif; ?>
		</tbody>
	</table>
	</div>
</div>
