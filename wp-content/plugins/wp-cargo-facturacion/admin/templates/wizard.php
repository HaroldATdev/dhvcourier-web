<div class="wpcfact-wizard-container">
	
	<div class="wpcfact-step-nav">
		<div class="wpcfact-step-indicator active" data-step="1"><span class="step-num">1</span> Cliente</div>
		<div class="wpcfact-step-indicator" data-step="2"><span class="step-num">2</span> Envíos</div>
		<div class="wpcfact-step-indicator" data-step="3"><span class="step-num">3</span> Confirmar</div>
		<div class="wpcfact-step-indicator" data-step="4"><span class="step-num">4</span> Emisión</div>
	</div>

	<!-- Paso 1: Seleccionar Cliente -->
	<div class="wpcfact-wizard-step active" id="step-1">
		<h2>1. Iniciar Comprobante</h2>

		<div class="wpcfact-input-group">
			<label>Modo de Emision</label>
			<select id="wpcfact-modo-emision" class="wpcfact-select">
				<option value="registrado" selected>Cliente registrado</option>
				<option value="ocasional">Envio ocasional (sin cliente registrado)</option>
			</select>
			<small style="color:#64748b; margin-top:5px; display:block;">En modo ocasional podras buscar por tracking o nombre del remitente.</small>
		</div>
		
		<div class="wpcfact-input-group">
			<label>Tipo de Comprobante</label>
			<select id="wpcfact-tipo-doc" class="wpcfact-select">
				<option value="01">Factura Electrónica (RUC)</option>
				<option value="03">Boleta Electrónica (DNI)</option>
				<option value="00">Nota de Venta (Interna)</option>
				<option value="09">Guía de Remisión Remitente</option>
				<option value="31">Guía de Transportista</option>
			</select>
		</div>

		<div class="wpcfact-input-group" id="wpcfact-box-cliente-registrado">
			<label>Buscar Cliente (Remitente)</label>
			<div style="display:flex; gap:10px;">
				<input type="text" id="wpcfact-buscar-cliente" class="wpcfact-input" placeholder="Escriba nombre o documento..." style="flex:1;">
				<button type="button" id="btn-buscar-cliente" class="wpcfact-btn">
					<span class="dashicons dashicons-search" style="margin-top:4px;"></span> Buscar
				</button>
			</div>
			<small style="color:#64748b; margin-top:5px; display:block;">Busca usuarios registrados en el sistema con rol de cliente.</small>
		</div>

		<div id="wpcfact-resultados-cliente" style="margin-top: 15px; max-height: 300px; overflow-y: auto;"></div>

		<div class="wpcfact-input-group" id="wpcfact-box-envio-ocasional" style="display:none;">
			<label>Buscar Envios Ocasionales</label>
			<div style="display:flex; gap:10px;">
				<input type="text" id="wpcfact-buscar-ocasional" class="wpcfact-input" placeholder="Tracking o nombre del remitente..." style="flex:1;">
				<button type="button" id="btn-buscar-ocasional" class="wpcfact-btn">
					<span class="dashicons dashicons-search" style="margin-top:4px;"></span> Buscar
				</button>
			</div>
			<small style="color:#64748b; margin-top:5px; display:block;">Solo se mostraran envios sin cliente registrado asociado y aun no facturados.</small>
		</div>

		<div id="wpcfact-resultados-ocasional" style="margin-top: 15px; max-height: 320px; overflow-y: auto; display:none;"></div>

		<div class="wpcfact-actions" style="justify-content: flex-end;">
			<button type="button" id="btn-next-2" class="wpcfact-btn" disabled>Continuar a Envíos &rarr;</button>
		</div>
	</div>

	<!-- Paso 2: Seleccionar Envios -->
	<div class="wpcfact-wizard-step" id="step-2">
		<h2>2. Seleccione los envíos a facturar</h2>
		<p style="color:#475569; margin-bottom:20px;">Mostrando envíos pendientes (sin comprobante) para el cliente seleccionado.</p>
		
		<div class="wpcfact-table-container">
			<table class="wpcfact-shipment-list" id="table-envios-pendientes">
				<thead>
					<tr>
						<th style="width: 40px;"><input type="checkbox" id="check-all-envios" class="wpcfact-check-custom"></th>
						<th>Tracking</th>
						<th>Origen &rarr; Destino</th>
						<th>Fecha</th>
						<th style="text-align:right;">Monto</th>
					</tr>
				</thead>
				<tbody>
					<!-- Cargado por AJAX -->
				</tbody>
			</table>
		</div>

		<div class="wpcfact-summary-box">
			<div class="row">
				<span>Subtotal (Base Imponible):</span>
				<span id="summary-base">S/. 0.00</span>
			</div>
			<div class="row">
				<span>IGV (18%):</span>
				<span id="summary-igv">S/. 0.00</span>
			</div>
			<div class="row total">
				<span>Total a Pagar:</span>
				<span id="summary-total">S/. 0.00</span>
			</div>
		</div>

		<div class="wpcfact-actions">
			<button type="button" id="btn-prev-1" class="wpcfact-btn wpcfact-btn-outline">&larr; Volver</button>
			<button type="button" id="btn-next-3" class="wpcfact-btn" disabled>Confirmar Datos &rarr;</button>
		</div>
	</div>

	<!-- Paso 3: Confirmar Datos -->
	<div class="wpcfact-wizard-step" id="step-3">
		<h2>3. Verifique y complete los datos fiscales</h2>
		
		<div style="display:flex; gap:20px; flex-wrap:wrap;">
			<div class="wpcfact-input-group" style="flex:1; min-width:250px;">
				<label>Documento Receptor (RUC / DNI) <span style="color:red;">*</span></label>
				<input type="text" id="wpcfact-receptor-doc" class="wpcfact-input">
				<small id="wpcfact-tipo-detectado" style="display:block; margin-top:5px; font-weight:600;"></small>
			</div>

			<div class="wpcfact-input-group" style="flex:2; min-width:300px;">
				<label>Razón Social / Nombres <span style="color:red;">*</span></label>
				<input type="text" id="wpcfact-receptor-nombre" class="wpcfact-input">
			</div>
		</div>

		<div class="wpcfact-input-group">
			<label>Dirección Fiscal</label>
			<input type="text" id="wpcfact-receptor-direccion" class="wpcfact-input">
		</div>

		<div class="wpcfact-input-group" style="width:250px;">
			<label>Forma de Pago</label>
			<select id="wpcfact-forma-pago" class="wpcfact-select">
				<option value="Contado">Contado</option>
				<option value="Credito">Crédito</option>
			</select>
		</div>

		<div id="wpcfact-campos-guia" style="display:none; margin-top:20px; padding:15px; border:1px solid #cbd5e1; border-radius:6px; background:#f8fafc;">
			<h3 style="margin-top:0;">Datos Adicionales para Guía</h3>
			<div style="display:flex; gap:20px; flex-wrap:wrap;">
				<div class="wpcfact-input-group" style="flex:1;">
					<label>Peso Bruto Total (KGM)</label>
					<input type="number" step="0.01" id="wpcfact-guia-peso" class="wpcfact-input" value="1.00">
				</div>
				<div class="wpcfact-input-group" style="flex:1;">
					<label>Motivo de Traslado</label>
					<select id="wpcfact-guia-motivo" class="wpcfact-select">
						<option value="01">Venta</option>
						<option value="14">Venta sujeta a confirmación del comprador</option>
						<option value="02">Compra</option>
						<option value="04">Traslado entre establecimientos de la misma empresa</option>
						<option value="13">Otros</option>
					</select>
				</div>
			</div>
			<div style="display:flex; gap:20px; flex-wrap:wrap; margin-top:15px;">
				<div class="wpcfact-input-group" style="flex:1;">
					<label>Modalidad de Traslado</label>
					<select id="wpcfact-guia-modalidad" class="wpcfact-select">
						<option value="01">Transporte Público</option>
						<option value="02">Transporte Privado</option>
					</select>
				</div>
			</div>
			<small style="color:#64748b; margin-top:10px; display:block;">(El sistema usará la dirección fiscal como punto de partida y la dirección del cliente como punto de llegada por defecto).</small>
		</div>

		<div style="background:#fffbeb; border:1px solid #fef3c7; border-left:4px solid #f59e0b; padding:15px; border-radius:6px; margin-top:20px; color:#b45309;">
			<strong>Nota:</strong> Las líneas del comprobante/guía se autogenerarán indicando el servicio y el número de tracking de cada envío seleccionado.
		</div>

		<script>
			// Mostrar/Ocultar campos de Guía según tipo de documento
			jQuery('#wpcfact-tipo-doc').on('change', function() {
				var val = jQuery(this).val();
				if(val === '09' || val === '31') {
					jQuery('#wpcfact-campos-guia').show();
					jQuery('#wpcfact-forma-pago').parent().hide();
				} else {
					jQuery('#wpcfact-campos-guia').hide();
					jQuery('#wpcfact-forma-pago').parent().show();
				}
			});
		</script>

		<div class="wpcfact-actions">
			<button type="button" id="btn-prev-2" class="wpcfact-btn wpcfact-btn-outline">&larr; Volver a envíos</button>
			<button type="button" id="btn-emitir" class="wpcfact-btn" style="background: linear-gradient(to right, #10b981, #059669);">Emitir a la SUNAT &rarr;</button>
		</div>
	</div>

	<!-- Paso 4: Resultado -->
	<div class="wpcfact-wizard-step" id="step-4">
		<div id="wpcfact-resultado-box" style="text-align:center; padding: 40px 20px;">
			<h2>Procesando...</h2>
			<p>Enviando documento a la SUNAT, por favor no cierre esta ventana.</p>
			<div class="spinner is-active" style="float:none; width:auto; height:auto; background:none;">
				<svg width="40" height="40" viewBox="0 0 50 50" style="animation: spin 1s linear infinite;">
					<circle cx="25" cy="25" r="20" fill="none" stroke="#3b82f6" stroke-width="4" stroke-dasharray="90 150"></circle>
				</svg>
				<style>@keyframes spin { 100% { transform: rotate(360deg); } }</style>
			</div>
		</div>
		
		<div class="wpcfact-actions" style="display:none; justify-content:center; gap:20px;" id="box-acciones-finales">
			<a href="admin.php?page=wpcfact-emitir" class="wpcfact-btn wpcfact-btn-outline">Crear Nuevo</a>
			<a href="admin.php?page=wpcfact-comprobantes" class="wpcfact-btn">Ir a Mis Comprobantes</a>
		</div>
	</div>
</div>
