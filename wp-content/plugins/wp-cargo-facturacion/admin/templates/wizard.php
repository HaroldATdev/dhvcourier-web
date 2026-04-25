<div class="wrap">
	<h1>Emitir Comprobante (Factura / Boleta / Guía)</h1>

	<div class="wpcfact-step-nav">
		<div class="wpcfact-step-indicator active" data-step="1">1. Seleccionar Cliente</div>
		<div class="wpcfact-step-indicator" data-step="2">2. Seleccionar Envíos</div>
		<div class="wpcfact-step-indicator" data-step="3">3. Confirmar Datos</div>
		<div class="wpcfact-step-indicator" data-step="4">4. Resultado</div>
	</div>

	<!-- PASO 1: CLIENTE -->
	<div id="step-1" class="wpcfact-wizard-step active">
		<h3>Seleccione el tipo de comprobante y el cliente</h3>
		<table class="form-table">
			<tr>
				<th>Tipo de Comprobante</th>
				<td>
					<select id="wpcfact-tipo-doc">
						<option value="01">Factura Electrónica (RUC)</option>
						<option value="03">Boleta de Venta Electrónica (DNI/CE)</option>
						<option value="09">Guía de Remisión Remitente</option>
					</select>
				</td>
			</tr>
			<tr>
				<th>Buscar Cliente</th>
				<td>
					<input type="text" id="wpcfact-buscar-cliente" class="regular-text" placeholder="Escriba nombre o documento..." autocomplete="off">
					<button type="button" id="btn-buscar-cliente" class="button">Buscar</button>
					<p class="description">Busca usuarios registrados en el sistema (rol: cliente).</p>
				</td>
			</tr>
		</table>
		
		<div id="wpcfact-resultados-cliente" style="margin-top: 15px;"></div>

		<p class="submit">
			<button type="button" class="button button-primary" id="btn-next-2" disabled>Siguiente Paso &raquo;</button>
		</p>
	</div>

	<!-- PASO 2: ENVÍOS -->
	<div id="step-2" class="wpcfact-wizard-step">
		<h3>Seleccione los envíos a facturar</h3>
		<p>Mostrando envíos pendientes (sin comprobante) para el cliente seleccionado.</p>
		
		<div class="wpcfact-shipment-list">
			<table id="table-envios-pendientes">
				<thead>
					<tr>
						<th><input type="checkbox" id="check-all-envios"></th>
						<th>Tracking</th>
						<th>Origen &rarr; Destino</th>
						<th>Fecha</th>
						<th>Monto (S/.)</th>
					</tr>
				</thead>
				<tbody>
					<!-- Generado por JS -->
				</tbody>
			</table>
		</div>

		<div class="wpcfact-summary-box">
			<p>Subtotal (Base): <span id="summary-base">S/. 0.00</span></p>
			<p>IGV (18%): <span id="summary-igv">S/. 0.00</span></p>
			<p><strong>Total a Pagar: <span id="summary-total">S/. 0.00</span></strong></p>
		</div>

		<p class="submit">
			<button type="button" class="button" id="btn-prev-1">&laquo; Atrás</button>
			<button type="button" class="button button-primary" id="btn-next-3" disabled>Siguiente Paso &raquo;</button>
		</p>
	</div>

	<!-- PASO 3: CONFIRMACIÓN -->
	<div id="step-3" class="wpcfact-wizard-step">
		<h3>Verifique y complete los datos del comprobante</h3>
		
		<table class="form-table">
			<tr>
				<th>Documento Receptor</th>
				<td>
					<input type="text" id="wpcfact-receptor-doc" class="regular-text" placeholder="RUC o DNI">
					<span class="description" id="wpcfact-tipo-detectado">Detectando tipo...</span>
				</td>
			</tr>
			<tr>
				<th>Razón Social / Nombre</th>
				<td>
					<input type="text" id="wpcfact-receptor-nombre" class="regular-text" style="width: 100%;">
				</td>
			</tr>
			<tr>
				<th>Dirección</th>
				<td>
					<input type="text" id="wpcfact-receptor-direccion" class="regular-text" style="width: 100%;">
				</td>
			</tr>
			<tr>
				<th>Forma de Pago</th>
				<td>
					<select id="wpcfact-forma-pago">
						<option value="Contado">Contado</option>
						<option value="Credito">Crédito</option>
					</select>
				</td>
			</tr>
			<tr>
				<th>Descripción del Servicio (Líneas)</th>
				<td>
					<p class="description">Las descripciones se autogenerarán como: "Servicio de courier - Tracking: {Número}".</p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="button" class="button" id="btn-prev-2">&laquo; Atrás</button>
			<button type="button" class="button button-primary button-hero" id="btn-emitir">Emitir Comprobante</button>
		</p>
	</div>

	<!-- PASO 4: RESULTADO -->
	<div id="step-4" class="wpcfact-wizard-step">
		<div id="wpcfact-resultado-box">
			<h3>Procesando...</h3>
			<span class="spinner is-active" style="float:none;"></span>
		</div>
		<p class="submit" style="display:none;" id="box-acciones-finales">
			<a href="admin.php?page=wpcfact-emitir" class="button">Nuevo Comprobante</a>
			<a href="admin.php?page=wpcfact-comprobantes" class="button button-primary">Ir a Comprobantes</a>
		</p>
	</div>
</div>
