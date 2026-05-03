jQuery(document).ready(function($) {

    let currentStep = 1;
    let emissionMode = 'registrado';
    let selectedUser = null;
    let selectedEnvios = [];
    let enviosData = {};

    // Cache de resultados de busqueda ocasional (id -> data)
    let ocasionalesCache = {};

    function showStep(step) {
        $('.wpcfact-wizard-step').removeClass('active');
        $('#step-' + step).addClass('active');

        $('.wpcfact-step-indicator').removeClass('active');
        for (let i = 1; i <= step; i++) {
            $('.wpcfact-step-indicator[data-step="' + i + '"]').addClass('active');
        }
        currentStep = step;
    }

    function resetStep2Selection() {
        selectedEnvios = [];
        enviosData = {};
        $('#table-envios-pendientes tbody').html('');
        $('#check-all-envios').removeClass('wpcfact-all-selected');
        calcularTotales();
    }

    function updateContinueButton() {
        if (emissionMode === 'registrado') {
            $('#btn-next-2').prop('disabled', !selectedUser);
            return;
        }

        const selectedCount = $('.wpcfact-ocasional-row.wpcfact-row-selected').length;
        $('#btn-next-2').prop('disabled', selectedCount === 0);
    }

    function renderOcasionalResults(rows) {
        if (!rows || rows.length === 0) {
            $('#wpcfact-resultados-ocasional').html('<p style="color:#b91c1c;">No se encontraron envios ocasionales.</p>');
            updateContinueButton();
            return;
        }

        let html = '<ul style="list-style:none; padding:0; margin:0;">';
        rows.forEach(function(envio) {
            ocasionalesCache[envio.id] = envio;
            html += `<li class="wpcfact-ocasional-row" data-id="${envio.id}" style="padding:10px; border:1px solid #cbd5e1; margin-bottom:6px; background:#fff; cursor:pointer; border-radius:6px;">
                <div style="display:flex; justify-content:space-between; gap:10px; align-items:center;">
                    <div>
                        <strong>${envio.title}</strong>
                        <div style="font-size:12px; color:#475569;">Remitente: ${envio.shipper_name || '-'} | Doc: ${envio.shipper_doc || '-'}</div>
                        <div style="font-size:12px; color:#64748b;">${envio.ruta}</div>
                    </div>
                    <div style="text-align:right; min-width:130px;">
                        <strong style="display:block;">S/. ${parseFloat(envio.monto).toFixed(2)}</strong>
                        <small style="color:#64748b;">${envio.date}</small>
                    </div>
                </div>
            </li>`;
        });
        html += '</ul>';
        $('#wpcfact-resultados-ocasional').html(html);
    }

    function renderEnviosTable(envios) {
        let html = '';
        enviosData = {};

        envios.forEach(function(envio) {
            enviosData[envio.id] = parseFloat(envio.monto) || 0;
            const shipperName = envio.shipper_name || '';
            const shipperDoc = envio.shipper_doc || '';
            const shipperAddress = envio.shipper_address || '';

            html += `<tr class="wpcfact-envio-row" data-id="${envio.id}" data-monto="${envio.monto}" data-shipper-name="${$('<div>').text(shipperName).html()}" data-shipper-doc="${$('<div>').text(shipperDoc).html()}" data-shipper-address="${$('<div>').text(shipperAddress).html()}" style="cursor:pointer;">
                <td>
                    <span class="wpcfact-row-check" style="
                        display:inline-flex; align-items:center; justify-content:center;
                        width:24px; height:24px; border-radius:50%;
                        border:2px solid #cbd5e1; background:#fff;
                        color:#fff; font-size:14px; font-weight:bold;
                        transition:all 0.2s;
                    ">&#10003;</span>
                </td>
                <td><strong>${envio.title}</strong></td>
                <td>${envio.ruta}</td>
                <td>${envio.date}</td>
                <td style="text-align:right;"><strong>S/. ${parseFloat(envio.monto).toFixed(2)}</strong></td>
            </tr>`;
        });

        $('#table-envios-pendientes tbody').html(html);
    }

    function normalizar(v) {
        return (v || '').toString().trim().replace(/\s+/g, ' ').toLowerCase();
    }

    function obtenerDatosRemitenteSeleccion() {
        const rows = $('.wpcfact-envio-row.wpcfact-row-selected');
        if (!rows.length) {
            return { consistente: true, nombre: '', doc: '', direccion: '' };
        }

        let base = null;
        let consistente = true;

        rows.each(function() {
            const row = $(this);
            const actual = {
                nombre: (row.data('shipper-name') || '').toString().trim(),
                doc: (row.data('shipper-doc') || '').toString().trim(),
                direccion: (row.data('shipper-address') || '').toString().trim()
            };

            if (!base) {
                base = actual;
                return;
            }

            const mismatchNombre = normalizar(base.nombre) !== normalizar(actual.nombre);
            const mismatchDoc = normalizar(base.doc) !== normalizar(actual.doc);
            const mismatchDir = normalizar(base.direccion) !== normalizar(actual.direccion);

            if (mismatchNombre || mismatchDoc || mismatchDir) {
                consistente = false;
                return false;
            }
        });

        return {
            consistente: consistente,
            nombre: consistente && base ? base.nombre : '',
            doc: consistente && base ? base.doc : '',
            direccion: consistente && base ? base.direccion : ''
        };
    }

    function calcularTotales() {
        selectedEnvios = [];
        let total = 0;

        $('.wpcfact-envio-row.wpcfact-row-selected').each(function() {
            const id = parseInt($(this).data('id'), 10);
            if (!Number.isNaN(id)) {
                selectedEnvios.push(id);
                total += enviosData[id] || 0;
            }
        });

        const base = total / 1.18;
        const igv = total - base;

        $('#summary-base').text('S/. ' + base.toFixed(2));
        $('#summary-igv').text('S/. ' + igv.toFixed(2));
        $('#summary-total').text('S/. ' + total.toFixed(2));

        $('#btn-next-3').prop('disabled', selectedEnvios.length === 0);
    }

    function detectarTipoDoc() {
        const doc = $('#wpcfact-receptor-doc').val().replace(/\s/g, '');
        const sel = $('#wpcfact-tipo-doc').val();

        if (doc.length === 11) {
            $('#wpcfact-tipo-detectado').text('RUC detectado').css('color', 'green');
            if (sel === '03') $('#wpcfact-tipo-doc').val('01');
        } else if (doc.length === 8) {
            $('#wpcfact-tipo-detectado').text('DNI detectado').css('color', 'green');
            if (sel === '01') $('#wpcfact-tipo-doc').val('03');
        } else {
            $('#wpcfact-tipo-detectado').text('Documento invalido o vacio').css('color', 'red');
        }
    }

    function cargarEnviosClienteRegistrado() {
        $('#table-envios-pendientes tbody').html('<tr><td colspan="5">Cargando envios...</td></tr>');

        $.post(wpcfact_ajax.url, {
            action: 'wpcfact_obtener_envios',
            nonce: wpcfact_ajax.nonce,
            user_id: selectedUser.id
        }, function(res) {
            if (res.success && res.data.length > 0) {
                renderEnviosTable(res.data);
            } else {
                $('#table-envios-pendientes tbody').html('<tr><td colspan="5" style="text-align:center; padding:20px; color:#64748b;">No hay envios pendientes facturables para este cliente.</td></tr>');
            }
            calcularTotales();
        });
    }

    function cargarEnviosOcasionalesSeleccionados() {
        const selectedRows = $('.wpcfact-ocasional-row.wpcfact-row-selected');
        const envios = [];

        selectedRows.each(function() {
            const id = parseInt($(this).data('id'), 10);
            if (!Number.isNaN(id) && ocasionalesCache[id]) {
                envios.push(ocasionalesCache[id]);
            }
        });

        if (!envios.length) {
            $('#table-envios-pendientes tbody').html('<tr><td colspan="5" style="text-align:center; padding:20px; color:#64748b;">No seleccionaste envios ocasionales.</td></tr>');
            calcularTotales();
            return;
        }

        renderEnviosTable(envios);

        // Marcar todos los seleccionados inicialmente
        $('.wpcfact-envio-row').each(function() {
            const row = $(this);
            row.addClass('wpcfact-row-selected').css('background', '#eff6ff');
            row.find('.wpcfact-row-check').css({ 'background': '#3b82f6', 'border-color': '#3b82f6', 'color': '#fff' });
        });

        calcularTotales();
    }

    // Navegacion
    $('#btn-prev-1').click(function() { showStep(1); });
    $('#btn-prev-2').click(function() { showStep(2); });

    // Cambio de modo
    $('#wpcfact-modo-emision').on('change', function() {
        emissionMode = $(this).val();

        selectedUser = null;
        resetStep2Selection();
        $('#wpcfact-resultados-cliente').empty();
        $('#wpcfact-resultados-ocasional').empty();

        if (emissionMode === 'registrado') {
            $('#wpcfact-box-cliente-registrado, #wpcfact-resultados-cliente').show();
            $('#wpcfact-box-envio-ocasional, #wpcfact-resultados-ocasional').hide();
        } else {
            $('#wpcfact-box-cliente-registrado, #wpcfact-resultados-cliente').hide();
            $('#wpcfact-box-envio-ocasional, #wpcfact-resultados-ocasional').show();
        }

        updateContinueButton();
    });

    // Buscar cliente registrado
    $('#btn-buscar-cliente').click(function() {
        if (emissionMode !== 'registrado') return;

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
                res.data.forEach(function(user) {
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
            updateContinueButton();
        });
    });

    // Seleccionar cliente
    $(document).on('click', '.wpcfact-user-row', function() {
        $('.wpcfact-user-row').removeClass('selected');
        $(this).addClass('selected');

        selectedUser = {
            id: $(this).data('id'),
            name: $(this).data('name'),
            doc: $(this).data('doc'),
            dir: $(this).data('dir')
        };

        updateContinueButton();
    });

    // Buscar envios ocasionales
    $('#btn-buscar-ocasional').click(function() {
        if (emissionMode !== 'ocasional') return;

        const query = $('#wpcfact-buscar-ocasional').val();
        $(this).text('Buscando...').prop('disabled', true);

        $.post(wpcfact_ajax.url, {
            action: 'wpcfact_buscar_envios_ocasionales',
            nonce: wpcfact_ajax.nonce,
            q: query
        }, function(res) {
            $('#btn-buscar-ocasional').text('Buscar').prop('disabled', false);
            if (res.success) {
                renderOcasionalResults(res.data || []);
            } else {
                $('#wpcfact-resultados-ocasional').html('<p style="color:#b91c1c;">' + (res.data || 'Error al buscar envios ocasionales.') + '</p>');
            }
            updateContinueButton();
        }).fail(function() {
            $('#btn-buscar-ocasional').text('Buscar').prop('disabled', false);
            $('#wpcfact-resultados-ocasional').html('<p style="color:#b91c1c;">Error de conexion al buscar envios.</p>');
            updateContinueButton();
        });
    });

    // Seleccionar envios ocasionales en paso 1
    $(document).on('click', '.wpcfact-ocasional-row', function() {
        $(this).toggleClass('wpcfact-row-selected');
        if ($(this).hasClass('wpcfact-row-selected')) {
            $(this).css('background', '#eff6ff');
            $(this).css('border-color', '#3b82f6');
        } else {
            $(this).css('background', '#fff');
            $(this).css('border-color', '#cbd5e1');
        }
        updateContinueButton();
    });

    // Continuar al paso 2
    $('#btn-next-2').click(function() {
        resetStep2Selection();

        if (emissionMode === 'registrado') {
            if (!selectedUser) return;
            cargarEnviosClienteRegistrado();
        } else {
            selectedUser = { id: 0, name: '', doc: '', dir: '' };
            cargarEnviosOcasionalesSeleccionados();
        }

        showStep(2);
    });

    // Paso 2: seleccionar filas
    $(document).on('click', '.wpcfact-envio-row', function() {
        $(this).toggleClass('wpcfact-row-selected');
        const check = $(this).find('.wpcfact-row-check');
        if ($(this).hasClass('wpcfact-row-selected')) {
            check.css({ 'background': '#3b82f6', 'border-color': '#3b82f6', 'color': '#fff' });
            $(this).css('background', '#eff6ff');
        } else {
            check.css({ 'background': '#fff', 'border-color': '#cbd5e1', 'color': '#fff' });
            $(this).css('background', '');
        }
        calcularTotales();
    });

    // Seleccionar todos en paso 2
    $(document).on('click', '#check-all-envios', function() {
        const selectAll = $(this).hasClass('wpcfact-all-selected') ? false : true;
        $(this).toggleClass('wpcfact-all-selected');

        $('.wpcfact-envio-row').each(function() {
            const check = $(this).find('.wpcfact-row-check');
            if (selectAll) {
                $(this).addClass('wpcfact-row-selected').css('background', '#eff6ff');
                check.css({ 'background': '#3b82f6', 'border-color': '#3b82f6', 'color': '#fff' });
            } else {
                $(this).removeClass('wpcfact-row-selected').css('background', '');
                check.css({ 'background': '#fff', 'border-color': '#cbd5e1', 'color': '#fff' });
            }
        });
        calcularTotales();
    });

    // Ir al paso 3
    $('#btn-next-3').click(function() {
        if (emissionMode === 'registrado') {
            $('#wpcfact-receptor-doc').val((selectedUser && selectedUser.doc) ? selectedUser.doc : '');
            $('#wpcfact-receptor-nombre').val((selectedUser && selectedUser.name) ? selectedUser.name : '');
            $('#wpcfact-receptor-direccion').val((selectedUser && selectedUser.dir) ? selectedUser.dir : '');
        } else {
            const remitente = obtenerDatosRemitenteSeleccion();

            if (remitente.consistente) {
                $('#wpcfact-receptor-doc').val(remitente.doc || '');
                $('#wpcfact-receptor-nombre').val(remitente.nombre || '');
                $('#wpcfact-receptor-direccion').val(remitente.direccion || '');
            } else {
                $('#wpcfact-receptor-doc').val('');
                $('#wpcfact-receptor-nombre').val('');
                $('#wpcfact-receptor-direccion').val('');
                Swal.fire({
                    icon: 'warning',
                    title: 'Remitentes diferentes',
                    text: 'Los envios seleccionados tienen datos de remitente distintos. Completa los datos de facturacion manualmente.'
                });
            }
        }

        detectarTipoDoc();
        showStep(3);
    });

    $('#wpcfact-receptor-doc').on('input', detectarTipoDoc);

    // Emitir
    $('#btn-emitir').click(function() {
        const data = {
            action: 'wpcfact_emitir_comprobante',
            nonce: wpcfact_ajax.nonce,
            modo: emissionMode,
            user_id: emissionMode === 'registrado' && selectedUser ? selectedUser.id : 0,
            envios: selectedEnvios,
            tipo: $('#wpcfact-tipo-doc').val(),
            doc_num: $('#wpcfact-receptor-doc').val(),
            nombre: $('#wpcfact-receptor-nombre').val(),
            direccion: $('#wpcfact-receptor-direccion').val(),
            forma_pago: $('#wpcfact-forma-pago').val(),
            guia_peso: $('#wpcfact-guia-peso').val(),
            guia_motivo: $('#wpcfact-guia-motivo').val(),
            guia_modalidad: $('#wpcfact-guia-modalidad').val()
        };

        showStep(4);

        $.post(wpcfact_ajax.url, data, function(res) {
            $('#wpcfact-resultado-box .spinner').removeClass('is-active');

            if (res.success) {
                $('#wpcfact-resultado-box').html(`
                    <div class="wpcfact-result-success">
                        <i class="dashicons dashicons-yes-alt"></i>
                        <h3>!Comprobante Emitido Exitosamente!</h3>
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
            $('#wpcfact-resultado-box').html('<h3 style="color:red;">Error de conexion. Intente nuevamente.</h3>');

            if (typeof wpcfact_ajax.url_emitir !== 'undefined') {
                $('#box-acciones-finales a').eq(0).attr('href', wpcfact_ajax.url_emitir);
                $('#box-acciones-finales a').eq(1).attr('href', wpcfact_ajax.url_listado);
            }
            $('#box-acciones-finales').show();
        });
    });

    // Estado inicial
    $('#wpcfact-modo-emision').trigger('change');
});
