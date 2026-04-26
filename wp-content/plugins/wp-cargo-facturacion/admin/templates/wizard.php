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
			<label>Tipo de Comprobante</label>
			<select id="wpcfact-tipo-doc" class="wpcfact-select">
				<option value="01">Factura Electrónica (RUC)</option>
				<option value="03">Boleta Electrónica (DNI)</option>
			</select>
		</div>

		<div class="wpcfact-input-group">
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

		<div style="background:#fffbeb; border:1px solid #fef3c7; border-left:4px solid #f59e0b; padding:15px; border-radius:6px; margin-top:20px; color:#b45309;">
			<strong>Nota:</strong> Las líneas del comprobante se autogenerarán indicando el servicio de courier y el número de tracking de cada envío seleccionado.
		</div>

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
