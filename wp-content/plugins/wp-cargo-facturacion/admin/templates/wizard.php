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
				<option value="libre">Libre (ingresar líneas manualmente)</option>
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

		<div class="wpcfact-input-group" id="wpcfact-box-libre-info" style="display:none;">
			<div style="background:#f0fdf4; border:1px solid #bbf7d0; border-left:4px solid #22c55e; padding:15px; border-radius:6px;">
				<strong style="color:#166534;">Modo Libre</strong>
				<p style="color:#166534; margin:5px 0 0;">En el siguiente paso podrás ingresar las líneas del comprobante manualmente, igual que en SUNAT. No se vinculará ningún envío.</p>
			</div>
		</div>

		<div class="wpcfact-actions" style="justify-content: flex-end;">
			<button type="button" id="btn-next-2" class="wpcfact-btn" disabled>Continuar a Envíos &rarr;</button>
		</div>
	</div>

	<!-- Paso 2: Seleccionar Envios -->
	<div class="wpcfact-wizard-step" id="step-2">
		<h2>2. Seleccione los envíos a facturar</h2>
		<p style="color:#475569; margin-bottom:20px;">Mostrando envíos pendientes (sin comprobante) para el cliente seleccionado.</p>

		<!-- Modo libre: líneas manuales -->
		<div id="wpcfact-step2-libre" style="display:none;">
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
				<h3 style="margin:0;">Líneas del Comprobante</h3>
				<button type="button" id="btn-add-linea-libre" class="wpcfact-btn" style="padding:6px 16px;">+ Agregar línea</button>
			</div>
			<table class="wpcfact-shipment-list" id="table-lineas-libres" style="width:100%;">
				<thead>
					<tr>
						<th>Descripción</th>
						<th style="width:90px; text-align:center;">Cant.</th>
						<th style="width:160px; text-align:right;">Precio Unit. (c/IGV)</th>
						<th style="width:120px; text-align:right;">Total</th>
						<th style="width:40px;"></th>
					</tr>
				</thead>
				<tbody id="tbody-lineas-libres"></tbody>
			</table>
			<p id="libre-vacio-msg" style="text-align:center; color:#94a3b8; padding:30px 0;">Haz clic en &ldquo;+ Agregar línea&rdquo; para comenzar.</p>
		</div>

		<!-- Modo normal: tabla de envíos -->
		<div id="wpcfact-step2-shipments">
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

		</div><!-- /#wpcfact-step2-shipments -->

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

		<div id="wpcfact-campos-guia" style="display:none; margin-top:30px; padding:25px; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc;">
			<h3 style="margin-top:0; margin-bottom:25px; padding-bottom:15px; border-bottom:2px solid #e2e8f0; font-size:18px;">Datos Adicionales para Guía</h3>

			<!-- Campo común: peso -->
			<div style="display:flex; gap:20px; flex-wrap:wrap; margin-bottom:30px; padding-bottom:25px; border-bottom:1px solid #e2e8f0;">
				<div class="wpcfact-input-group" style="flex:1; min-width:140px;">
					<label>Peso Bruto Total (KGM)</label>
					<input type="number" step="0.01" id="wpcfact-guia-peso" class="wpcfact-input" value="1.00">
				</div>
		</div>

<!-- Solo tipo 09: remitente, motivo, modalidad -->
		<div class="wpcfact-campo-09" style="display:none; margin-top:30px; padding:20px; background:#fafbfc; border-radius:6px; border-left:4px solid #3b82f6;">
			<h4 style="margin:0 0 18px 0; color:#1e293b; font-size:16px;">Transportista</h4>
			<div style="display:flex; gap:20px; flex-wrap:wrap;">
				<div class="wpcfact-input-group" style="flex:1; min-width:140px;">
					</div>
				</div>
			</div>

			<!-- Solo tipo 09: conductor -->
			<div class="wpcfact-campo-09" style="display:none; margin-top:30px; padding:20px; background:#fafbfc; border-radius:6px; border-left:4px solid #3b82f6;">
				<h4 style="margin:0 0 18px 0; color:#1e293b; font-size:16px;">Conductor</h4>
				<div style="display:flex; gap:20px; flex-wrap:wrap;">
					<div class="wpcfact-input-group" style="flex:1; min-width:120px;">
						<label>DNI del Conductor</label>
						<input type="text" id="wpcfact-guia-09-conductor-dni" class="wpcfact-input" maxlength="8" placeholder="12345678">
					</div>
					<div class="wpcfact-input-group" style="flex:2; min-width:200px;">
						<label>Nombre Completo del Conductor</label>
						<input type="text" id="wpcfact-guia-09-conductor-nombre" class="wpcfact-input" placeholder="Nombres y apellidos">
					</div>
					<div class="wpcfact-input-group" style="flex:1; min-width:140px;">
						<label>Licencia de Conducir</label>
						<input type="text" id="wpcfact-guia-09-conductor-licencia" class="wpcfact-input" placeholder="A-I-123456789">
					</div>
					<div class="wpcfact-input-group" style="flex:1; min-width:120px;">
						<label>Placa del Vehículo</label>
						<input type="text" id="wpcfact-guia-09-vehiculo-placa" class="wpcfact-input" placeholder="ABC-123">
					</div>
				</div>
			</div>

			<!-- Solo tipo 09: motivo y modalidad -->
			<div class="wpcfact-campo-09" style="display:none; margin-top:30px; padding:20px; background:#fafbfc; border-radius:6px; border-left:4px solid #3b82f6;">
				<h4 style="margin:0 0 18px 0; color:#1e293b; font-size:16px;">Detalles del Traslado</h4>
				<div style="display:flex; gap:20px; flex-wrap:wrap;">
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
				<div class="wpcfact-input-group" style="flex:1;">
					<label>Modalidad de Traslado</label>
					<select id="wpcfact-guia-modalidad" class="wpcfact-select">
						<option value="01">Transporte Público</option>
						<option value="02">Transporte Privado</option>
					</select>
					</div>
				</div>
			</div>

<!-- Ubigeo solo para tipo 09 -->
		<div class="wpcfact-campo-09" style="display:none; margin-top:30px; padding:20px; background:#fafbfc; border-radius:6px; border-left:4px solid #3b82f6;">
			<h4 style="margin:0 0 18px 0; color:#1e293b; font-size:16px;">Ubicación de Partida y Llegada</h4>
			<div style="display:flex; gap:20px; flex-wrap:wrap;">
				<div class="wpcfact-input-group" style="flex:1; min-width:120px;">
					<label>Ubigeo Partida (6 dígitos)</label>
					<input type="text" id="wpcfact-guia-ubigeo-partida" class="wpcfact-input" maxlength="6" placeholder="150101" value="150101">
				</div>
				<div class="wpcfact-input-group" style="flex:1; min-width:120px;">
					<label>Ubigeo Llegada (6 dígitos)</label>
					<input type="text" id="wpcfact-guia-ubigeo-llegada" class="wpcfact-input" maxlength="6" placeholder="150131" value="150131">
				</div>
			</div>
		</div>

		<!-- Departamento/Provincia/Distrito solo para tipo 31 -->
		<div class="wpcfact-campo-31" style="display:none; margin-top:30px; padding:20px; background:#fafbfc; border-radius:6px; border-left:4px solid #8b5cf6;">
			<h4 style="margin:0 0 18px 0; color:#1e293b; font-size:16px;">Punto de Partida</h4>
			<div style="display:flex; gap:15px; flex-wrap:wrap;">
				<div class="wpcfact-input-group" style="flex:1; min-width:120px;">
					<label>Departamento</label>
					<input type="text" id="wpcfact-guia-31-partida-departamento" class="wpcfact-input" placeholder="Ej: LIMA">
				</div>
				<div class="wpcfact-input-group" style="flex:1; min-width:120px;">
					<label>Provincia</label>
					<input type="text" id="wpcfact-guia-31-partida-provincia" class="wpcfact-input" placeholder="Ej: LIMA">
				</div>
				<div class="wpcfact-input-group" style="flex:1; min-width:120px;">
					<label>Distrito</label>
					<input type="text" id="wpcfact-guia-31-partida-distrito" class="wpcfact-input" placeholder="Ej: LIMA">
				</div>
			</div>

			<h4 style="margin:30px 0 18px 0; color:#1e293b; font-size:16px;">Punto de Llegada</h4>
			<div style="display:flex; gap:15px; flex-wrap:wrap;">
				<div class="wpcfact-input-group" style="flex:1; min-width:120px;">
					<label>Departamento</label>
					<input type="text" id="wpcfact-guia-31-llegada-departamento" class="wpcfact-input" placeholder="Ej: AREQUIPA">
				</div>
				<div class="wpcfact-input-group" style="flex:1; min-width:120px;">
					<label>Provincia</label>
					<input type="text" id="wpcfact-guia-31-llegada-provincia" class="wpcfact-input" placeholder="Ej: AREQUIPA">
				</div>
				<div class="wpcfact-input-group" style="flex:1; min-width:120px;">
					<label>Distrito</label>
					<input type="text" id="wpcfact-guia-31-llegada-distrito" class="wpcfact-input" placeholder="Ej: AREQUIPA">
				</div>
				</div>
			</div>

			<!-- Solo tipo 31: remitente -->
			<div class="wpcfact-campo-31" style="display:none; margin-top:30px; padding:20px; background:#fafbfc; border-radius:6px; border-left:4px solid #8b5cf6;">
				<h4 style="margin:0 0 18px 0; color:#1e293b; font-size:16px;">Datos del Remitente <small style="font-weight:normal; color:#6b7280; font-size:14px;">(quien entrega la carga al transportista)</small></h4>
				<div style="display:flex; gap:20px; flex-wrap:wrap;">
					<div class="wpcfact-input-group" style="flex:1; min-width:140px;">
						<label>RUC / DNI del Remitente</label>
						<input type="text" id="wpcfact-guia-remitente-doc" class="wpcfact-input" maxlength="11" placeholder="RUC o DNI">
					</div>
					<div class="wpcfact-input-group" style="flex:2; min-width:220px;">
						<label>Nombre / Razón Social del Remitente</label>
						<input type="text" id="wpcfact-guia-remitente-nombre" class="wpcfact-input" placeholder="Razón social o nombre completo">
					</div>
				</div>
			</div>

			<!-- Solo tipo 31: conductor -->
			<div class="wpcfact-campo-31" style="display:none; margin-top:30px; padding:20px; background:#fafbfc; border-radius:6px; border-left:4px solid #8b5cf6;">
				<h4 style="margin:0 0 18px 0; color:#1e293b; font-size:16px;">Datos del Conductor y Vehículo</h4>
				<div style="display:flex; gap:20px; flex-wrap:wrap;">
					<div class="wpcfact-input-group" style="flex:1; min-width:120px;">
						<label>DNI del Conductor</label>
						<input type="text" id="wpcfact-guia-conductor-dni" class="wpcfact-input" maxlength="8" placeholder="12345678">
					</div>
					<div class="wpcfact-input-group" style="flex:2; min-width:200px;">
						<label>Nombre completo del Conductor</label>
						<input type="text" id="wpcfact-guia-conductor-nombre" class="wpcfact-input" placeholder="Nombre y apellidos">
					</div>
					<div class="wpcfact-input-group" style="flex:1; min-width:140px;">
						<label>Licencia de Conducir</label>
						<input type="text" id="wpcfact-guia-conductor-licencia" class="wpcfact-input" placeholder="A-I-123456789">
					</div>
					<div class="wpcfact-input-group" style="flex:1; min-width:120px;">
						<label>Placa del Vehículo</label>
						<input type="text" id="wpcfact-guia-vehiculo-placa" class="wpcfact-input" placeholder="ABC-123">
					</div>
				</div>
			</div>

		</div>

		<small style="color:#64748b; margin-top:25px; display:block; padding:15px; background:#f1f5f9; border-radius:6px; border-left:3px solid #94a3b8;">(El sistema usará la dirección del emisor como punto de partida y la dirección del destinatario como punto de llegada).</small>

		<div style="background:#fffbeb; border:1px solid #fef3c7; border-left:4px solid #f59e0b; padding:18px; border-radius:6px; margin-top:30px; color:#b45309; line-height:1.6;">
			<strong>ℹ️ Nota:</strong> Las líneas del comprobante/guía se autogenerarán indicando el servicio y el número de tracking de cada envío seleccionado.
		</div>
	</div>

	<script>
		// Mostrar/Ocultar campos de Guía según tipo de documento
		jQuery('#wpcfact-tipo-doc').on('change', function() {
			var val = jQuery(this).val();
			if (val === '09' || val === '31') {
				jQuery('#wpcfact-campos-guia').show();
				jQuery('#wpcfact-forma-pago').closest('.wpcfact-input-group').hide();
				jQuery('.wpcfact-campo-09').toggle(val === '09');
				jQuery('.wpcfact-campo-31').toggle(val === '31');
			} else {
				jQuery('#wpcfact-campos-guia').hide();
				jQuery('#wpcfact-forma-pago').closest('.wpcfact-input-group').show();
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
