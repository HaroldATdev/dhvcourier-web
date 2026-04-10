<?php
/**
 * DHV Courier — Tracking Result Template
 * Ruta: /wp-content/themes/tu-child-theme/wpcargo/result-form.tpl.php
 */

$shipment_number = isset( $shipment_number ) ? $shipment_number : '';
?>

<div id="wpcargo-result-wrapper" class="dhv-tracking-wrapper">
<div class="wpcargo-result wpcargo" id="wpcargo-result">
<?php
$shipment_id = wpcargo_trackform_shipment_number( $shipment_number );
if ( !empty( $shipment_id ) ) :

	/* ── Datos base ── */
	$shipment             = new stdClass;
	$shipment->ID         = (int) esc_html( $shipment_id );
	$shipment->post_title = esc_html( get_the_title( $shipment_id ) );

	$shipment_status = esc_html( get_post_meta( $shipment->ID, 'wpcargo_status', true ) );
	$class_status    = strtolower( str_replace( ' ', '_', $shipment_status ) );
	$status_lower    = strtolower( trim( $shipment_status ) );

	/* ── Variables personalizadas DHV ── */
	$tipo_envio            = strtolower( trim( get_post_meta( $shipment->ID, 'tipo_envio', true ) ) );
	$remitente             = esc_html( get_post_meta( $shipment->ID, 'remitente', true ) );
	$lugar_origen          = esc_html( get_post_meta( $shipment->ID, 'lugar_origen', true ) );
	$lugar_destino         = esc_html( get_post_meta( $shipment->ID, 'lugar_destino', true ) );
	$dni_remitente         = esc_html( get_post_meta( $shipment->ID, 'dni_remitente', true ) );
	$telefono_remitente    = esc_html( get_post_meta( $shipment->ID, 'telefono_remitente', true ) );
	$destinatario          = esc_html( get_post_meta( $shipment->ID, 'destinatario', true ) );
	$dni_destinatario      = esc_html( get_post_meta( $shipment->ID, 'dni_destinatario', true ) );
	$telefono_destinatario = esc_html( get_post_meta( $shipment->ID, 'telefono_destinatario', true ) );
	$direccion_destinatario= esc_html( get_post_meta( $shipment->ID, 'direccion_destinatario', true ) );
	$monto                 = esc_html( get_post_meta( $shipment->ID, 'monto', true ) );
	$peso                  = esc_html( get_post_meta( $shipment->ID, 'peso', true ) );
	$condicion_pago        = esc_html( get_post_meta( $shipment->ID, 'condicion_pago', true ) );
	$total_bultos          = esc_html( get_post_meta( $shipment->ID, 'total_bultos', true ) );

	$last_update  = get_the_modified_date( 'd/m/Y', $shipment->ID ) . ' ' . get_the_modified_time( 'H:i', $shipment->ID );
	$created_date = get_the_date( 'd/m/Y', $shipment->ID );

	/* ── Estados finales especiales ── */
	$estados_finales_especiales = [ 'anulado', 'devuelto', 'reprogramado' ];
	$es_final_especial          = in_array( $status_lower, $estados_finales_especiales );

	/* ── Definición de pasos por tipo de envío ── */
	if ( $tipo_envio === 'puerta_puerta' ) {
		$pasos_base = [
			[ 'key' => 'pendiente', 'label' => 'Pendiente', 'icon' => 'clock'  ],
			[ 'key' => 'recogido',  'label' => 'Recogido',  'icon' => 'pickup' ],
			[ 'key' => 'en ruta',   'label' => 'En Ruta',   'icon' => 'truck'  ],
			[ 'key' => 'entregado', 'label' => 'Entregado', 'icon' => 'check'  ],
		];
	} else {
		// agencia / almacen
		$pasos_base = [
			[ 'key' => 'en espera', 'label' => 'En Espera', 'icon' => 'clock'  ],
			[ 'key' => 'en ruta',   'label' => 'En Ruta',   'icon' => 'truck'  ],
			[ 'key' => 'entregado', 'label' => 'Entregado', 'icon' => 'check'  ],
		];
	}

	/* Si el estado es un final especial, reemplazar el último paso */
	$pasos = $pasos_base;
	if ( $es_final_especial ) {
		$icono_final = $status_lower === 'anulado' ? 'ban' : ( $status_lower === 'devuelto' ? 'return' : 'calendar' );
		$pasos[ count($pasos) - 1 ] = [
			'key'   => $status_lower,
			'label' => ucfirst( $shipment_status ),
			'icon'  => $icono_final,
		];
	}

	/* ── Paso activo ── */
	$current_step = 0;
	foreach ( $pasos as $i => $paso ) {
		if ( $paso['key'] === $status_lower ) {
			$current_step = $i;
			break;
		}
	}
	$total_pasos = count( $pasos );

	/* ── Color por estado ── */
	$colores = [
		'pendiente'    => '#94a3b8',
		'en espera'    => '#f59e0b',
		'recogido'     => '#3b82f6',
		'en ruta'      => '#00b9f1',
		'entregado'    => '#10b981',
		'anulado'      => '#ef4444',
		'devuelto'     => '#f97316',
		'reprogramado' => '#a855f7',
	];
	$status_color = isset( $colores[ $status_lower ] ) ? $colores[ $status_lower ] : '#00b9f1';

	/* ── Progreso en % ── */
	$pct_progress = $total_pasos > 1 ? round( ( $current_step / ( $total_pasos - 1 ) ) * 100 ) : 0;

	do_action( 'wpcargo_before_search_result' );
?>

<!-- ═══════════════════════════════════════
     CARD PRINCIPAL
═══════════════════════════════════════ -->
<div class="dhv-card <?php echo esc_attr( $class_status ); ?>" id="wpcargo-result-print">

	<!-- ── HEADER ── -->
	<div class="dhv-header" style="--sc:<?php echo $status_color; ?>;">
		<div class="dhv-header-bg"></div>
		<div class="dhv-logo-wrap">
			<img src="https://dhvcourier.com/wp-content/uploads/2026/04/6-1-2.webp"
			     alt="DHV Courier" class="dhv-logo"
			     onerror="this.style.display='none'">
		</div>
		<div class="dhv-status-badge" style="background:<?php echo $status_color; ?>;">
			<?php
			$svg_icons = [
				'pendiente'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
				'en espera'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
				'recogido'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>',
				'en ruta'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
				'entregado'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>',
				'anulado'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>',
				'devuelto'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4"/></svg>',
				'reprogramado' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
			];
			echo isset( $svg_icons[ $status_lower ] ) ? $svg_icons[ $status_lower ] : $svg_icons['pendiente'];
			?>
		</div>
		<h2 class="dhv-status-text"><?php echo strtoupper( $shipment_status ); ?></h2>
		<div class="dhv-tn-label">N° de seguimiento</div>
		<div class="dhv-tn-value"><?php echo esc_html( $shipment->post_title ); ?></div>
		<p class="dhv-update-date">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
			Última actualización: <?php echo $last_update; ?>
		</p>
	</div>

	<!-- ── BARRA DE PROGRESO ── -->
	<div class="dhv-progress-section">
		<div class="dhv-progress-track">
			<div class="dhv-progress-fill" style="width:<?php echo $pct_progress; ?>%; background:<?php echo $status_color; ?>;"></div>
		</div>
		<div class="dhv-steps dhv-steps--<?php echo $total_pasos; ?>">
			<?php foreach ( $pasos as $i => $paso ) :
				if ( $i < $current_step ) $sc = 'completed';
				elseif ( $i === $current_step ) $sc = 'active';
				else $sc = 'pending';
				$bubble_color = ( $sc !== 'pending' ) ? $status_color : '';
			?>
			<div class="dhv-step <?php echo $sc; ?>">
				<div class="dhv-step-bubble" <?php if($bubble_color) echo 'style="background:'.$bubble_color.';border-color:'.$bubble_color.';"'; ?>>
					<?php if ( $sc === 'completed' ) : ?>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" width="13" height="13"><polyline points="20 6 9 17 4 12"/></svg>
					<?php elseif ( $sc === 'active' ) : ?>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" width="13" height="13"><polyline points="20 6 9 17 4 12"/></svg>
					<?php else : ?>
						<span><?php echo $i + 1; ?></span>
					<?php endif; ?>
				</div>
				<span class="dhv-step-label" <?php if($sc!=='pending') echo 'style="color:'.$status_color.';"'; ?>><?php echo esc_html( $paso['label'] ); ?></span>
			</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- ── CUERPO ── -->
	<div class="dhv-body">

		<!-- Ruta origen → destino -->
		<?php if ( $lugar_origen || $lugar_destino ) : ?>
		<div class="dhv-route-card">
			<div class="dhv-route-point">
				<div class="dhv-route-dot dhv-route-dot--o"></div>
				<div>
					<span class="dhv-rlbl">Origen</span>
					<span class="dhv-rval"><?php echo $lugar_origen ?: '—'; ?></span>
				</div>
			</div>
			<div class="dhv-route-arrow">
				<svg viewBox="0 0 24 24" fill="none" stroke="#00b9f1" stroke-width="2" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
			</div>
			<div class="dhv-route-point">
				<div class="dhv-route-dot dhv-route-dot--d" style="border-color:<?php echo $status_color; ?>;background:<?php echo $status_color; ?>22;"></div>
				<div>
					<span class="dhv-rlbl">Destino</span>
					<span class="dhv-rval"><?php echo $lugar_destino ?: '—'; ?></span>
				</div>
			</div>
			<?php if ( $tipo_envio ) : ?>
			<div class="dhv-tipo-badge">
				<?php
				$tipo_labels = [
					'puerta_puerta' => '🚪 Puerta a Puerta',
					'agencia'       => '🏢 Agencia',
					'almacen'       => '📦 Almacén',
				];
				echo isset( $tipo_labels[$tipo_envio] ) ? $tipo_labels[$tipo_envio] : esc_html( $tipo_envio );
				?>
			</div>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<!-- Grid: Remitente + Destinatario -->
		<div class="dhv-personas-grid">

			<!-- Remitente -->
			<div class="dhv-persona-card">
				<div class="dhv-persona-header">
					<div class="dhv-persona-icon dhv-persona-icon--rem">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
					</div>
					<span class="dhv-persona-title">Remitente</span>
				</div>
				<ul class="dhv-persona-data">
					<?php if($remitente):?>           <li><span>Nombre</span><strong><?php echo $remitente;?></strong></li><?php endif;?>
					<?php if($lugar_origen):?>        <li><span>Origen</span><strong><?php echo $lugar_origen;?></strong></li><?php endif;?>
					<?php if($dni_remitente):?>       <li><span>DNI</span><strong><?php echo $dni_remitente;?></strong></li><?php endif;?>
					<?php if($telefono_remitente):?>  <li><span>Teléfono</span><strong><?php echo $telefono_remitente;?></strong></li><?php endif;?>
				</ul>
			</div>

			<!-- Destinatario -->
			<div class="dhv-persona-card">
				<div class="dhv-persona-header">
					<div class="dhv-persona-icon dhv-persona-icon--dest">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
					</div>
					<span class="dhv-persona-title">Destinatario</span>
				</div>
				<ul class="dhv-persona-data">
					<?php if($destinatario):?>             <li><span>Nombre</span><strong><?php echo $destinatario;?></strong></li><?php endif;?>
					<?php if($lugar_destino):?>            <li><span>Destino</span><strong><?php echo $lugar_destino;?></strong></li><?php endif;?>
					<?php if($dni_destinatario):?>         <li><span>DNI</span><strong><?php echo $dni_destinatario;?></strong></li><?php endif;?>
					<?php if($telefono_destinatario):?>    <li><span>Teléfono</span><strong><?php echo $telefono_destinatario;?></strong></li><?php endif;?>
					<?php if($direccion_destinatario):?>   <li><span>Dirección</span><strong><?php echo $direccion_destinatario;?></strong></li><?php endif;?>
				</ul>
			</div>

		</div>

		<!-- Info del envío -->
		<?php if ( $monto || $peso || $condicion_pago || $total_bultos || $created_date ) : ?>
		<div class="dhv-info-strip">
			<div class="dhv-info-strip-title">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
				Información del Envío
			</div>
			<div class="dhv-info-chips">
				<?php if($created_date):?><div class="dhv-chip"><span>Creación</span><strong><?php echo $created_date;?></strong></div><?php endif;?>
				<?php if($monto):?><div class="dhv-chip"><span>Monto</span><strong>S/ <?php echo $monto;?></strong></div><?php endif;?>
				<?php if($peso):?><div class="dhv-chip"><span>Peso</span><strong><?php echo $peso;?> kg</strong></div><?php endif;?>
				<?php if($condicion_pago):?><div class="dhv-chip"><span>Pago</span><strong><?php echo ucfirst($condicion_pago);?></strong></div><?php endif;?>
				<?php if($total_bultos):?><div class="dhv-chip"><span>Bultos</span><strong><?php echo $total_bultos;?></strong></div><?php endif;?>
			</div>
		</div>
		<?php endif; ?>

		<!-- ═══════════════════════════════════════════════════════
		     CORRECCIÓN 1: Se eliminan los do_action() nativos de
		     WPCargo que renderizaban la versión antigua del tracking
		     debajo del card personalizado. Solo se mantiene el botón
		     de impresión oficial.
		════════════════════════════════════════════════════════ -->
		<?php do_action( 'wpcargo_print_btn' ); ?>

	</div><!-- /.dhv-body -->

	<!-- ── FOOTER ── -->
	<div class="dhv-footer">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="13" height="13"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.68A2 2 0 012 .918h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
		¿Necesitas ayuda? Contáctanos en <strong>dhvcourier.com</strong>
	</div>

</div><!-- /.dhv-card -->

<?php else : ?>
<div class="dhv-no-result">
	<div class="dhv-no-result-icon">
		<svg viewBox="0 0 24 24" fill="none" stroke="#00b9f1" stroke-width="1.5" width="52" height="52"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
	</div>
	<h3><?php echo apply_filters( 'wpcargo_tn_no_result_text', esc_html__( 'No se encontraron resultados', 'wpcargo' ) ); ?></h3>
	<p>Verifica el número de seguimiento e intenta nuevamente.</p>
</div>
<?php endif; ?>

</div>
</div>

<!-- ══════════════════════════════════════════════════════
     ESTILOS CSS — puedes moverlos a tu style.css del child theme
══════════════════════════════════════════════════════ -->
<style id="dhv-tracking-styles">
/* ── Reset & variables ── */
#wpcargo-result-wrapper,
#wpcargo-result-wrapper * { box-sizing: border-box; }
#wpcargo-result-wrapper {
	--p:  #00b9f1;
	--pd: #0099cc;
	--g50:  #f8fafc;
	--g100: #f1f5f9;
	--g200: #e2e8f0;
	--g400: #94a3b8;
	--g600: #475569;
	--g800: #1e293b;
	--rad:  18px;
	--rads: 11px;
	--shadow: 0 6px 28px rgba(0,185,241,.12), 0 1px 4px rgba(0,0,0,.06);
	font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
	width: 100%;
	max-width: 660px;
	margin: 0 auto;
	padding: 16px 12px 40px;
}

/* ── Card ── */
.dhv-card {
	background: #fff;
	border-radius: var(--rad);
	box-shadow: var(--shadow);
	overflow: hidden;
	animation: dhvUp .45s cubic-bezier(.22,1,.36,1) both;
}
@keyframes dhvUp {
	from { opacity:0; transform:translateY(20px); }
	to   { opacity:1; transform:translateY(0); }
}

/* ── Header ── */
#wpcargo-result-wrapper .dhv-header {
	position: relative !important;
	background: #0099cc !important;
	background: linear-gradient(145deg, #00b9f1 0%, #0099cc 100%) !important;
	padding: 32px 20px 24px !important;
	text-align: center !important;
	color: #fff !important;
	overflow: hidden !important;
	border: none !important;
}
#wpcargo-result-wrapper .dhv-header *:not(img):not(svg):not(path):not(circle):not(polyline):not(line):not(rect):not(polygon) {
	color: #fff !important;
}
#wpcargo-result-wrapper .dhv-header-bg {
	position: absolute !important; inset: 0 !important; pointer-events: none !important;
	background:
		radial-gradient(circle at 10% 50%, rgba(255,255,255,.14) 0%, transparent 52%),
		radial-gradient(circle at 88% 18%, rgba(255,255,255,.09) 0%, transparent 42%) !important;
}
#wpcargo-result-wrapper .dhv-logo {
	max-height: 52px !important; max-width: 170px !important; object-fit: contain !important;
	filter: drop-shadow(0 2px 8px rgba(0,0,0,.2)) !important;
	margin-bottom: 14px !important; position: relative !important;
}
#wpcargo-result-wrapper .dhv-status-badge {
	width: 50px !important; height: 50px !important; border-radius: 50% !important;
	border: 3px solid rgba(255,255,255,.38) !important;
	display: flex !important; align-items: center !important; justify-content: center !important;
	margin: 0 auto 10px !important; position: relative !important;
	box-shadow: 0 4px 14px rgba(0,0,0,.14) !important;
}
#wpcargo-result-wrapper .dhv-status-badge svg { width: 22px !important; height: 22px !important; color: #fff !important; }
#wpcargo-result-wrapper .dhv-status-text {
	margin: 0 0 7px !important; font-size: 1.55rem !important; font-weight: 800 !important;
	letter-spacing: .06em !important; position: relative !important;
}
#wpcargo-result-wrapper .dhv-tn-label {
	display: block !important; font-size: .68rem !important; text-transform: uppercase !important;
	letter-spacing: .1em !important; opacity: .78 !important; position: relative !important;
}
#wpcargo-result-wrapper .dhv-tn-value {
	display: inline-block !important;
	background: rgba(255,255,255,.2) !important;
	font-size: 1.1rem !important; font-weight: 700 !important; letter-spacing: .06em !important;
	padding: 3px 16px !important; border-radius: 30px !important; margin-top: 4px !important; position: relative !important;
}
.dhv-update-date {
	margin: 9px 0 0; font-size: .72rem; opacity: .8; position: relative;
	display: flex; align-items: center; justify-content: center; gap: 4px;
}

/* ── Progreso ── */
.dhv-progress-section {
	background: var(--g50); padding: 20px 18px 16px;
	border-bottom: 1px solid var(--g200); position: relative;
}
.dhv-progress-track {
	position: absolute;
	top: 35px; left: calc(18px + 17px); right: calc(18px + 17px);
	height: 3px; background: var(--g200); border-radius: 2px;
}
.dhv-progress-fill { height: 100%; border-radius: 2px; transition: width .9s cubic-bezier(.4,0,.2,1); }
.dhv-steps { display: flex; justify-content: space-between; position: relative; }
.dhv-step  { display: flex; flex-direction: column; align-items: center; gap: 7px; flex: 1; }
.dhv-step-bubble {
	width: 34px; height: 34px; border-radius: 50%;
	display: flex; align-items: center; justify-content: center;
	font-weight: 700; font-size: .8rem;
	border: 2.5px solid var(--g200);
	background: #fff; color: var(--g400);
	transition: all .35s ease;
}
.dhv-step.completed .dhv-step-bubble,
.dhv-step.active .dhv-step-bubble { color: #fff; border: none; }
.dhv-step.active .dhv-step-bubble {
	transform: scale(1.16);
	box-shadow: 0 0 0 5px rgba(0,185,241,.2);
}
.dhv-step-label {
	font-size: .63rem; font-weight: 600; text-align: center;
	line-height: 1.2; color: var(--g400);
}
.dhv-steps--3 .dhv-step-label { font-size: .66rem; }
.dhv-steps--4 .dhv-step-label { font-size: .6rem; }

/* ── Body ── */
.dhv-body { padding: 16px 16px 6px; }

/* ── Ruta ── */
.dhv-route-card {
	background: linear-gradient(135deg, #e8f8ff 0%, #f4fcff 100%);
	border: 1px solid rgba(0,185,241,.18);
	border-radius: var(--rads); padding: 14px 16px; margin-bottom: 14px;
}
.dhv-route-point { display: flex; align-items: center; gap: 10px; }
.dhv-route-dot { width: 11px; height: 11px; border-radius: 50%; border: 2px solid; flex-shrink: 0; }
.dhv-route-dot--o { border-color: #f59e0b; background: #fef9ec; }
.dhv-route-dot--d { border-color: var(--p); background: #e0f7ff; }
.dhv-route-arrow { padding-left: 2px; opacity: .45; }
.dhv-rlbl { display: block; font-size: .63rem; text-transform: uppercase; letter-spacing: .08em; color: var(--g400); font-weight: 600; }
.dhv-rval { display: block; font-size: .95rem; font-weight: 700; color: var(--g800); }
.dhv-tipo-badge {
	display: inline-block; margin-top: 10px;
	background: rgba(0,185,241,.1); color: var(--pd);
	font-size: .72rem; font-weight: 700; padding: 3px 12px;
	border-radius: 20px; border: 1px solid rgba(0,185,241,.22);
}

/* ── Personas grid ── */
.dhv-personas-grid {
	display: grid; grid-template-columns: 1fr 1fr;
	gap: 10px; margin-bottom: 14px;
}
@media(max-width:500px){ .dhv-personas-grid { grid-template-columns: 1fr; } }
.dhv-persona-card {
	border: 1px solid var(--g200); border-radius: var(--rads);
	overflow: hidden;
}
.dhv-persona-header {
	display: flex; align-items: center; gap: 8px;
	padding: 9px 12px; border-bottom: 1px solid var(--g200);
	background: var(--g50);
}
.dhv-persona-icon {
	width: 28px; height: 28px; border-radius: 7px;
	display: flex; align-items: center; justify-content: center;
}
.dhv-persona-icon--rem  { background: #dbeafe; color: #2563eb; }
.dhv-persona-icon--dest { background: #ccfbf1; color: #0d9488; }
.dhv-persona-icon svg { display: block; }
.dhv-persona-title { font-size: .78rem; font-weight: 700; color: var(--g800); text-transform: uppercase; letter-spacing: .05em; }
.dhv-persona-data { list-style: none; margin: 0; padding: 8px 12px; display: flex; flex-direction: column; gap: 5px; }
.dhv-persona-data li { display: flex; flex-direction: column; gap: 0; }
.dhv-persona-data li span  { font-size: .62rem; text-transform: uppercase; letter-spacing: .07em; color: var(--g400); font-weight: 600; }
.dhv-persona-data li strong { font-size: .83rem; color: var(--g800); font-weight: 700; word-break: break-word; }

/* ── Info strip ── */
.dhv-info-strip {
	border: 1px solid var(--g200); border-radius: var(--rads);
	overflow: hidden; margin-bottom: 14px;
}
.dhv-info-strip-title {
	display: flex; align-items: center; gap: 7px;
	padding: 9px 14px; background: var(--g50);
	border-bottom: 1px solid var(--g200);
	font-size: .78rem; font-weight: 700; color: var(--g600);
	text-transform: uppercase; letter-spacing: .04em;
}
.dhv-info-chips {
	display: flex; flex-wrap: wrap; gap: 0;
	padding: 10px 12px;
}
.dhv-chip {
	display: flex; flex-direction: column; gap: 1px;
	padding: 6px 14px; border-right: 1px solid var(--g200);
}
.dhv-chip:last-child { border-right: none; }
.dhv-chip span   { font-size: .61rem; text-transform: uppercase; letter-spacing: .07em; color: var(--g400); font-weight: 600; }
.dhv-chip strong { font-size: .87rem; color: var(--g800); font-weight: 700; }

/* ── Footer ── */
.dhv-footer {
	display: flex; align-items: center; justify-content: center; gap: 6px;
	padding: 12px 16px; font-size: .73rem; color: var(--g400);
	border-top: 1px solid var(--g100); background: var(--g50);
}

/* ── Sin resultado ── */
.dhv-no-result {
	text-align: center; padding: 52px 24px;
	background: #fff; border-radius: var(--rad);
	box-shadow: var(--shadow);
}
.dhv-no-result h3 { color: var(--g800); font-size: 1.05rem; margin: 12px 0 6px; }
.dhv-no-result p  { color: var(--g400); font-size: .84rem; }

/* ── Responsive desktop ── */
@media(min-width: 600px) {
	#wpcargo-result-wrapper { padding: 28px 20px 56px; }
	.dhv-header { padding: 36px 28px 28px; }
	.dhv-body   { padding: 20px 20px 8px; }
	.dhv-status-text { font-size: 1.75rem; }
	.dhv-chip { padding: 6px 18px; }
}

/* ══════════════════════════════════════════════════════
   CORRECCIÓN 2: Fondo celeste en la etiqueta de impresión
   (print_color_adjust fuerza los fondos degradados al imprimir)
══════════════════════════════════════════════════════ */
@media print {
	#wpcargo-result-wrapper {
		padding: 0;
		max-width: 100%;
	}
	.dhv-card {
		box-shadow: none;
		border-radius: 0;
	}
	.dhv-header {
		-webkit-print-color-adjust: exact;
		print-color-adjust: exact;
		background: linear-gradient(145deg, #00b9f1 0%, #0099cc 100%) !important;
		color: #fff !important;
	}
	.dhv-header * {
		color: #fff !important;
	}
	.dhv-header-bg {
		-webkit-print-color-adjust: exact;
		print-color-adjust: exact;
	}
	.dhv-status-badge {
		-webkit-print-color-adjust: exact;
		print-color-adjust: exact;
		background: rgba(0, 153, 204, 0.85) !important;
		border-color: rgba(255,255,255,.38) !important;
	}
	.dhv-tn-value {
		-webkit-print-color-adjust: exact;
		print-color-adjust: exact;
		background: rgba(255,255,255,.2) !important;
	}
	.dhv-progress-section,
	.dhv-progress-fill,
	.dhv-step-bubble {
		-webkit-print-color-adjust: exact;
		print-color-adjust: exact;
	}
	.dhv-route-card {
		-webkit-print-color-adjust: exact;
		print-color-adjust: exact;
		background: linear-gradient(135deg, #e8f8ff 0%, #f4fcff 100%) !important;
	}
	@keyframes dhvUp { from { opacity:1; transform:none; } }
}
</style>
