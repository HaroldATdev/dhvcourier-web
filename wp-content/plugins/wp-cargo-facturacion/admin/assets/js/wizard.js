jQuery(document).ready(function($) {

	let currentStep = 1;
	let selectedUser = null;
	let selectedEnvios = [];
	let enviosData = {}; // Para guardar mapeo ID -> monto

	// Navegación de pasos
	function showStep(step) {
		$('.wpcfact-wizard-step').removeClass('active');
		$('#step-' + step).addClass('active');
		
		$('.wpcfact-step-indicator').removeClass('active');
		for (let i = 1; i <= step; i++) {
			$('.wpcfact-step-indicator[data-step="'+i+'"]').addClass('active');
		}
		currentStep = step;
	}

	$('#btn-prev-1').click(function() { showStep(1); });
	$('#btn-prev-2').click(function() { showStep(2); });

	// Paso 1: Buscar Cliente
	$('#btn-buscar-cliente').click(function() {
		const query = $('#wpcfact-buscar-cliente').val();
		if (!query) return;

		$(this).text('Buscando...').prop('disabled', true);
		
		$.post(wpcfact_ajax.url, {
			action: 'wpcfact_buscar_cliente',
			nonce: wpcfact_ajax.nonce,
			q: query
		}, function(res) {
			$('#btn-buscar-cliente').text('Buscar').prop('disabled', false);
			
			if (res.success && res.data.length > 0) {
				let html = '<ul style="list-style:none; padding:0;">';
				res.data.forEach(user => {
					html += `<li style="padding:10px; border:1px solid #ccc; margin-bottom:5px; background:#fff; cursor:pointer;" class="wpcfact-user-row" data-id="${user.id}" data-name="${user.razon_social}" data-doc="${user.doc_num || ''}" data-dir="${user.direccion || ''}">
						<strong>${user.razon_social}</strong> (${user.email}) <br>
						<small>Doc: ${user.doc_num || 'No registrado'}</small>
					</li>`;
				});
				html += '</ul>';
				$('#wpcfact-resultados-cliente').html(html);
			} else {
				$('#wpcfact-resultados-cliente').html('<p style="color:red;">No se encontraron clientes.</p>');
			}
		});
	});

	// Seleccionar Cliente
	$(document).on('click', '.wpcfact-user-row', function() {
		$('.wpcfact-user-row').removeClass('selected');
		$(this).addClass('selected');
		
		selectedUser = {
			id: $(this).data('id'),
			name: $(this).data('name'),
			doc: $(this).data('doc'),
			dir: $(this).data('dir')
		};

		$('#btn-next-2').prop('disabled', false);
	});

	// Ir al paso 2
	$('#btn-next-2').click(function() {
		if (!selectedUser) return;
		
		// Cargar envíos
		$('#table-envios-pendientes tbody').html('<tr><td colspan="5">Cargando envíos...</td></tr>');
		
		$.post(wpcfact_ajax.url, {
			action: 'wpcfact_obtener_envios',
			nonce: wpcfact_ajax.nonce,
			user_id: selectedUser.id
		}, function(res) {
			if (res.success && res.data.length > 0) {
				let html = '';
				enviosData = {};
				
				res.data.forEach(envio => {
					enviosData[envio.id] = parseFloat(envio.monto) || 0;
					html += `<tr>
						<td><input type="checkbox" class="wpcfact-check-envio wpcfact-check-custom" value="${envio.id}" style="cursor:pointer; pointer-events:auto;"></td>
						<td><strong>${envio.title}</strong></td>
						<td>${envio.ruta}</td>
						<td>${envio.date}</td>
						<td style="text-align:right;"><strong>S/. ${parseFloat(envio.monto).toFixed(2)}</strong></td>
					</tr>`;
				});
				$('#table-envios-pendientes tbody').html(html);
			} else {
				$('#table-envios-pendientes tbody').html('<tr><td colspan="5" style="text-align:center; padding:20px; color:#64748b;">No hay envíos pendientes facturables para este cliente.</td></tr>');
			}
			calcularTotales();
		});

		showStep(2);
	});

	// Paso 2: Seleccionar Envíos
	$(document).on('change', '.wpcfact-check-envio', function() {
		calcularTotales();
	});

	$(document).on('change', '#check-all-envios', function() {
		$('.wpcfact-check-envio').prop('checked', $(this).is(':checked'));
		calcularTotales();
	});

	function calcularTotales() {
		selectedEnvios = [];
		let total = 0;
		
		$('.wpcfact-check-envio:checked').each(function() {
			const id = $(this).val();
			selectedEnvios.push(id);
			total += enviosData[id];
		});

		// Base + IGV
		const base = total / 1.18;
		const igv = total - base;

		$('#summary-base').text('S/. ' + base.toFixed(2));
		$('#summary-igv').text('S/. ' + igv.toFixed(2));
		$('#summary-total').text('S/. ' + total.toFixed(2));

		if (selectedEnvios.length > 0) {
			$('#btn-next-3').prop('disabled', false);
		} else {
			$('#btn-next-3').prop('disabled', true);
		}
	}

	// Ir al paso 3
	$('#btn-next-3').click(function() {
		// Precargar datos
		$('#wpcfact-receptor-doc').val(selectedUser.doc);
		$('#wpcfact-receptor-nombre').val(selectedUser.name);
		$('#wpcfact-receptor-direccion').val(selectedUser.dir);

		detectarTipoDoc();
		showStep(3);
	});

	// Detectar tipo documento
	function detectarTipoDoc() {
		const doc = $('#wpcfact-receptor-doc').val().replace(/\s/g, '');
		const sel = $('#wpcfact-tipo-doc').val();
		
		if (doc.length === 11) {
			$('#wpcfact-tipo-detectado').text('RUC detectado').css('color', 'green');
			if (sel === '03') $('#wpcfact-tipo-doc').val('01'); // Auto switch a factura
		} else if (doc.length === 8) {
			$('#wpcfact-tipo-detectado').text('DNI detectado').css('color', 'green');
			if (sel === '01') $('#wpcfact-tipo-doc').val('03'); // Auto switch a boleta
		} else {
			$('#wpcfact-tipo-detectado').text('Documento inválido').css('color', 'red');
		}
	}

	$('#wpcfact-receptor-doc').on('input', detectarTipoDoc);

	// Emitir
	$('#btn-emitir').click(function() {
		const data = {
			action: 'wpcfact_emitir_comprobante',
			nonce: wpcfact_ajax.nonce,
			user_id: selectedUser.id,
			envios: selectedEnvios,
			tipo: $('#wpcfact-tipo-doc').val(),
			doc_num: $('#wpcfact-receptor-doc').val(),
			nombre: $('#wpcfact-receptor-nombre').val(),
			direccion: $('#wpcfact-receptor-direccion').val(),
			forma_pago: $('#wpcfact-forma-pago').val()
		};

		showStep(4);

		$.post(wpcfact_ajax.url, data, function(res) {
			$('#wpcfact-resultado-box .spinner').removeClass('is-active');
			
			if (res.success) {
				$('#wpcfact-resultado-box').html(`
					<div class="wpcfact-result-success">
						<i class="dashicons dashicons-yes-alt"></i>
						<h3>¡Comprobante Emitido Exitosamente!</h3>
						<p style="font-size:18px;">Documento: <strong>${res.data.serie}-${res.data.correlativo}</strong></p>
						<p style="color:#64748b; margin-top:10px;">El comprobante ha sido procesado por APISUNAT y su estado actual es <strong>${res.data.estado}</strong>.</p>
					</div>
				`);
			} else {
				$('#wpcfact-resultado-box').html(`
					<div class="wpcfact-result-success" style="color:#ef4444;">
						<i class="dashicons dashicons-dismiss" style="color:#ef4444;"></i>
						<h3 style="color:#ef4444;">Error al emitir</h3>
						<p>${res.data}</p>
					</div>
				`);
			}
			
			if (typeof wpcfact_ajax.url_emitir !== 'undefined') {
				$('#box-acciones-finales a').eq(0).attr('href', wpcfact_ajax.url_emitir);
				$('#box-acciones-finales a').eq(1).attr('href', wpcfact_ajax.url_listado);
			}
			$('#box-acciones-finales').show();
		}).fail(function() {
			$('#wpcfact-resultado-box .spinner').removeClass('is-active');
			$('#wpcfact-resultado-box').html(`<h3 style="color:red;">Error de conexión. Intente nuevamente.</h3>`);
			
			if (typeof wpcfact_ajax.url_emitir !== 'undefined') {
				$('#box-acciones-finales a').eq(0).attr('href', wpcfact_ajax.url_emitir);
				$('#box-acciones-finales a').eq(1).attr('href', wpcfact_ajax.url_listado);
			}
			$('#box-acciones-finales').show();
		});
	});

});
