jQuery(document).ready(function($) {

    let currentStep = 1;
    let emissionMode = 'registrado';
    let selectedUser = null;
    let selectedEnvios = [];
    let enviosData = {};

    // Cache de resultados de busqueda ocasional (id -> data)
    let ocasionalesCache = {};
    let libresLineas = [];

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
        if (emissionMode === 'libre') {
            $('#btn-next-2').prop('disabled', false);
            return;
        }
        const selectedCount = $('.wpcfact-ocasional-row.wpcfact-row-selected').length;
        $('#btn-next-2').prop('disabled', selectedCount === 0);
    }

    // ---- Modo Libre: gestión de líneas manuales ----

    function renderLineasLibres() {
        const tbody = $('#tbody-lineas-libres');
        tbody.empty();
        if (!libresLineas.length) {
            $('#libre-vacio-msg').show();
            $('#table-lineas-libres').hide();
            calcularTotalesLibre();
            return;
        }
        $('#libre-vacio-msg').hide();
        $('#table-lineas-libres').show();
        libresLineas.forEach(function(linea, idx) {
            const total = (parseFloat(linea.cantidad) * parseFloat(linea.precio_unitario)).toFixed(2);
            tbody.append(`<tr>
                <td><input type="text" class="wpcfact-input libre-desc" data-idx="${idx}" value="${$('<div>').text(linea.descripcion).html()}" placeholder="Descripción del servicio o producto" style="width:100%; box-sizing:border-box;"></td>
                <td style="text-align:center;"><input type="number" class="wpcfact-input libre-cant" data-idx="${idx}" value="${linea.cantidad}" min="1" step="1" style="width:70px; text-align:center;"></td>
                <td style="text-align:right;"><input type="number" class="wpcfact-input libre-precio" data-idx="${idx}" value="${linea.precio_unitario}" min="0" step="0.01" style="width:120px; text-align:right;"></td>
                <td style="text-align:right; font-weight:600;">S/. ${total}</td>
                <td style="text-align:center;"><button type="button" class="btn-remove-linea" data-idx="${idx}" style="background:none; border:none; cursor:pointer; color:#ef4444; font-size:18px;" title="Eliminar">&times;</button></td>
            </tr>`);
        });
        calcularTotalesLibre();
    }

    function calcularTotalesLibre() {
        let total = 0;
        libresLineas.forEach(function(l) {
            total += parseFloat(l.cantidad) * parseFloat(l.precio_unitario);
        });
        const base = total / 1.18;
        const igv = total - base;
        $('#summary-base').text('S/. ' + base.toFixed(2));
        $('#summary-igv').text('S/. ' + igv.toFixed(2));
        $('#summary-total').text('S/. ' + total.toFixed(2));
        $('#btn-next-3').prop('disabled', total <= 0);
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
            $('#wpcfact-tipo-detectado').text('DNI detectado: completar datos manualmente').css('color', '#b45309');
            if (sel === '01') $('#wpcfact-tipo-doc').val('03');
        } else {
            $('#wpcfact-tipo-detectado').text('Documento invalido o vacio').css('color', 'red');
        }
    }

        var consultaDocTimer = null;

        function consultarDocSunat(numero) {
            clearTimeout(consultaDocTimer);
            consultaDocTimer = setTimeout(function() {
                $('#wpcfact-tipo-detectado').text('Consultando RUC...').css('color', '#888');
                $.post(wpcfact_ajax.url, {
                    action: 'wpcfact_consultar_doc',
                    nonce: wpcfact_ajax.nonce,
                    numero: numero
                }, function(res) {
                    if (res.success && res.data) {
                        if (res.data.nombre) {
                            $('#wpcfact-receptor-nombre').val(res.data.nombre);
                        }
                        if (res.data.direccion) {
                            $('#wpcfact-receptor-direccion').val(res.data.direccion);
                        }
                        $('#wpcfact-tipo-detectado').text('RUC encontrado ✓').css('color', 'green');
                    } else {
                        $('#wpcfact-tipo-detectado').text('RUC no encontrado').css('color', 'orange');
                    }
                }).fail(function() {
                    $('#wpcfact-tipo-detectado').text('Error al consultar RUC').css('color', 'red');
                });
            }, 600);
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
            $('#wpcfact-box-libre-info').hide();
        } else if (emissionMode === 'ocasional') {
            $('#wpcfact-box-cliente-registrado, #wpcfact-resultados-cliente').hide();
            $('#wpcfact-box-envio-ocasional, #wpcfact-resultados-ocasional').show();
            $('#wpcfact-box-libre-info').hide();
        } else {
            $('#wpcfact-box-cliente-registrado, #wpcfact-resultados-cliente').hide();
            $('#wpcfact-box-envio-ocasional, #wpcfact-resultados-ocasional').hide();
            $('#wpcfact-box-libre-info').show();
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
            $('#wpcfact-step2-libre').hide();
            $('#wpcfact-step2-shipments').show();
            cargarEnviosClienteRegistrado();
        } else if (emissionMode === 'ocasional') {
            selectedUser = { id: 0, name: '', doc: '', dir: '' };
            $('#wpcfact-step2-libre').hide();
            $('#wpcfact-step2-shipments').show();
            cargarEnviosOcasionalesSeleccionados();
        } else {
            // libre
            selectedUser = { id: 0, name: '', doc: '', dir: '' };
            libresLineas = [];
            $('#wpcfact-step2-shipments').hide();
            $('#wpcfact-step2-libre').show();
            renderLineasLibres();
        }

        showStep(2);
    });

    // Agregar línea libre
    $('#step-2').on('click', '#btn-add-linea-libre', function() {
        libresLineas.push({ descripcion: '', cantidad: 1, precio_unitario: 0 });
        renderLineasLibres();
        // Foco en la última descripción
        $('#tbody-lineas-libres tr:last-child .libre-desc').focus();
    });

    // Eliminar línea libre
    $('#step-2').on('click', '.btn-remove-linea', function() {
        const idx = parseInt($(this).data('idx'), 10);
        libresLineas.splice(idx, 1);
        renderLineasLibres();
    });

    // Editar campos de línea libre
    $('#step-2').on('input change', '.libre-desc, .libre-cant, .libre-precio', function() {
        const idx = parseInt($(this).data('idx'), 10);
        if ($(this).hasClass('libre-desc')) {
            libresLineas[idx].descripcion = $(this).val();
        } else if ($(this).hasClass('libre-cant')) {
            libresLineas[idx].cantidad = parseFloat($(this).val()) || 1;
        } else if ($(this).hasClass('libre-precio')) {
            libresLineas[idx].precio_unitario = parseFloat($(this).val()) || 0;
        }
        // Actualizar total de esa fila
        const total = (libresLineas[idx].cantidad * libresLineas[idx].precio_unitario).toFixed(2);
        $(this).closest('tr').find('td:nth-child(4)').text('S/. ' + total);
        calcularTotalesLibre();
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
        } else if (emissionMode === 'ocasional') {
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
        } else {
            // libre: campos vacíos para llenar manualmente
            $('#wpcfact-receptor-doc').val('');
            $('#wpcfact-receptor-nombre').val('');
            $('#wpcfact-receptor-direccion').val('');
        }

        detectarTipoDoc();
        $('#wpcfact-tipo-doc').trigger('change'); // Sincronizar visibilidad de campos de guía con el tipo seleccionado
        showStep(3);
    });

    $('#wpcfact-receptor-doc').on('input', function() {
        detectarTipoDoc();
        const doc = $(this).val().replace(/\s/g, '');
        if (emissionMode === 'libre' && doc.length === 11) {
            consultarDocSunat(doc);
        } else if (emissionMode === 'libre' && doc.length === 8) {
            clearTimeout(consultaDocTimer);
        }
    });

    // Emitir
    // Helpers de formato SUNAT
    function esPlacaValida(placa) {
        return /^[A-Z0-9]{2,3}-[A-Z0-9]{3,4}$/i.test(placa.trim());
    }
    function esDniValido(dni) {
        return /^\d{8}$/.test(dni.trim());
    }
    function esRucValido(ruc) {
        return /^(10|20)\d{9}$/.test(ruc.trim());
    }
    function esLicenciaValida(lic) {
        return /^[A-Z0-9\-]{5,15}$/i.test(lic.trim());
    }

    // Validar campos obligatorios según tipo de documento
    function validarCamposGuia() {
        const tipo = $('#wpcfact-tipo-doc').val();
        const camposFaltantes = [];

        // Campos comunes para guías
        if (!$('#wpcfact-guia-peso').val() || parseFloat($('#wpcfact-guia-peso').val()) <= 0) {
            camposFaltantes.push('Peso Bruto Total (debe ser mayor a 0)');
        }

        // Validaciones específicas para tipo 09
        if (tipo === '09') {
            // Ubicación con departamento/provincia/distrito
            if (!$('#wpcfact-guia-09-partida-departamento').val()) {
                camposFaltantes.push('Departamento Partida');
            }
            if (!$('#wpcfact-guia-09-partida-provincia').val()) {
                camposFaltantes.push('Provincia Partida');
            }
            if (!$('#wpcfact-guia-09-partida-distrito').val()) {
                camposFaltantes.push('Distrito Partida');
            }
            if (!$('#wpcfact-guia-09-llegada-departamento').val()) {
                camposFaltantes.push('Departamento Llegada');
            }
            if (!$('#wpcfact-guia-09-llegada-provincia').val()) {
                camposFaltantes.push('Provincia Llegada');
            }
            if (!$('#wpcfact-guia-09-llegada-distrito').val()) {
                camposFaltantes.push('Distrito Llegada');
            }
            if (!$('#wpcfact-guia-motivo').val()) {
                camposFaltantes.push('Motivo de Traslado');
            }
            if (!$('#wpcfact-guia-modalidad').val()) {
                camposFaltantes.push('Modalidad de Traslado');
            }
            // Transportista solo para modalidad 01, conductor solo para modalidad 02
            const modalidad09 = $('#wpcfact-guia-modalidad').val();
            if (modalidad09 !== '02') {
                const ruc09 = $('#wpcfact-guia-09-transportista-ruc').val();
                if (!ruc09) {
                    camposFaltantes.push('RUC del Transportista');
                } else if (!esRucValido(ruc09)) {
                    camposFaltantes.push('RUC del Transportista inválido (debe tener 11 dígitos y empezar con 10 o 20)');
                }
                if (!$('#wpcfact-guia-09-transportista-nombre').val()) {
                    camposFaltantes.push('Razón Social del Transportista');
                }
            }
            if (modalidad09 === '02') {
                const dni09 = $('#wpcfact-guia-09-conductor-dni').val();
                if (!dni09 || !esDniValido(dni09)) {
                    camposFaltantes.push('DNI del Conductor (8 dígitos numéricos)');
                }
                if (!$('#wpcfact-guia-09-conductor-nombre').val()) {
                    camposFaltantes.push('Nombre Completo del Conductor');
                }
                const lic09 = $('#wpcfact-guia-09-conductor-licencia').val();
                if (!lic09 || !esLicenciaValida(lic09)) {
                    camposFaltantes.push('Licencia de Conducir inválida (alfanumérica, 5-15 caracteres)');
                }
                const placa09 = $('#wpcfact-guia-09-vehiculo-placa').val();
                if (!placa09) {
                    camposFaltantes.push('Placa del Vehículo');
                } else if (!esPlacaValida(placa09)) {
                    camposFaltantes.push('Placa del Vehículo inválida (formato: ABC-123 o A1B-234)');
                }
            }
        }

        // Validaciones específicas para tipo 31
        if (tipo === '31') {
            // Punto de Partida
            if (!$('#wpcfact-guia-31-partida-departamento').val()) {
                camposFaltantes.push('Departamento Partida');
            }
            if (!$('#wpcfact-guia-31-partida-provincia').val()) {
                camposFaltantes.push('Provincia Partida');
            }
            if (!$('#wpcfact-guia-31-partida-distrito').val()) {
                camposFaltantes.push('Distrito Partida');
            }
            // Punto de Llegada
            if (!$('#wpcfact-guia-31-llegada-departamento').val()) {
                camposFaltantes.push('Departamento Llegada');
            }
            if (!$('#wpcfact-guia-31-llegada-provincia').val()) {
                camposFaltantes.push('Provincia Llegada');
            }
            if (!$('#wpcfact-guia-31-llegada-distrito').val()) {
                camposFaltantes.push('Distrito Llegada');
            }
            if (!$('#wpcfact-guia-remitente-doc').val()) {
                camposFaltantes.push('RUC / DNI del Remitente');
            }
            if (!$('#wpcfact-guia-remitente-nombre').val()) {
                camposFaltantes.push('Nombre / Razón Social del Remitente');
            }
            const dni31 = $('#wpcfact-guia-conductor-dni').val();
            if (!dni31 || !esDniValido(dni31)) {
                camposFaltantes.push('DNI del Conductor (8 dígitos numéricos)');
            }
            if (!$('#wpcfact-guia-conductor-nombre').val()) {
                camposFaltantes.push('Nombre Completo del Conductor');
            }
            const lic31 = $('#wpcfact-guia-conductor-licencia').val();
            if (!lic31 || !esLicenciaValida(lic31)) {
                camposFaltantes.push('Licencia de Conducir inválida (alfanumérica, 5-15 caracteres)');
            }
            const placa31 = $('#wpcfact-guia-vehiculo-placa').val();
            if (!placa31) {
                camposFaltantes.push('Placa del Vehículo');
            } else if (!esPlacaValida(placa31)) {
                camposFaltantes.push('Placa del Vehículo inválida (formato: ABC-123 o A1B-234)');
            }
        }

        return camposFaltantes;
    }

    $('#btn-emitir').click(function() {
        // Validar si es guía (09 o 31)
        const tipo = $('#wpcfact-tipo-doc').val();
        if (tipo === '09' || tipo === '31') {
            const camposFaltantes = validarCamposGuia();
            if (tipo === '09' || tipo === '31') {
            // Validar ubicaciones para guía
            if (tipo === '09') {
                if (!$('#wpcfact-guia-09-partida-departamento').val() || !$('#wpcfact-guia-09-llegada-departamento').val()) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Ubicaciones Incompletas',
                        html: 'Para guía tipo 09 debe seleccionar departamento de partida y llegada.',
                        confirmButtonText: 'Entendido'
                    });
                    return false;
                }
            } else if (tipo === '31') {
                if (!$('#wpcfact-guia-31-partida-departamento').val() || !$('#wpcfact-guia-31-partida-provincia').val() || !$('#wpcfact-guia-31-partida-distrito').val()) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Ubicación de Partida Incompleta',
                        html: 'Para guía tipo 31 debe seleccionar departamento, provincia y distrito de partida.',
                        confirmButtonText: 'Entendido'
                    });
                    return false;
                }
                if (!$('#wpcfact-guia-31-llegada-departamento').val() || !$('#wpcfact-guia-31-llegada-provincia').val() || !$('#wpcfact-guia-31-llegada-distrito').val()) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Ubicación de Llegada Incompleta',
                        html: 'Para guía tipo 31 debe seleccionar departamento, provincia y distrito de llegada.',
                        confirmButtonText: 'Entendido'
                    });
                    return false;
                }
            }
        }
        
        if (camposFaltantes.length > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Campos Obligatorios Incompletos',
                    html: '<strong>Por favor complete los siguientes campos:</strong><br><br>' + 
                          camposFaltantes.map(c => '• ' + c).join('<br>'),
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#ef4444'
                });
                return false;
            }
        }
        const data = {
            action: 'wpcfact_emitir_comprobante',
            nonce: wpcfact_ajax.nonce,
            modo: emissionMode,
            user_id: emissionMode === 'registrado' && selectedUser ? selectedUser.id : 0,
            envios: emissionMode !== 'libre' ? selectedEnvios : [],
            lineas: emissionMode === 'libre' ? JSON.stringify(libresLineas) : '',
            tipo: $('#wpcfact-tipo-doc').val(),
            doc_num: $('#wpcfact-receptor-doc').val(),
            nombre: $('#wpcfact-receptor-nombre').val(),
            direccion: $('#wpcfact-receptor-direccion').val(),
            forma_pago: $('#wpcfact-forma-pago').val(),
            guia_peso: $('#wpcfact-guia-peso').val(),
            guia_motivo: $('#wpcfact-guia-motivo').val(),
            guia_modalidad: $('#wpcfact-guia-modalidad').val(),
            guia_09_transportista_ruc: $('#wpcfact-guia-09-transportista-ruc').val() || '',
            guia_09_transportista_nombre: $('#wpcfact-guia-09-transportista-nombre').val() || '',
            guia_09_conductor_dni: $('#wpcfact-guia-09-conductor-dni').val() || '',
            guia_09_conductor_nombre: $('#wpcfact-guia-09-conductor-nombre').val() || '',
            guia_09_conductor_licencia: $('#wpcfact-guia-09-conductor-licencia').val() || '',
            guia_09_vehiculo_placa: $('#wpcfact-guia-09-vehiculo-placa').val() || '',
            guia_09_partida_departamento: $('#wpcfact-guia-09-partida-departamento').val() || '',
            guia_09_partida_provincia: $('#wpcfact-guia-09-partida-provincia').val() || '',
            guia_09_partida_distrito: $('#wpcfact-guia-09-partida-distrito').val() || '',
            guia_09_llegada_departamento: $('#wpcfact-guia-09-llegada-departamento').val() || '',
            guia_09_llegada_provincia: $('#wpcfact-guia-09-llegada-provincia').val() || '',
            guia_09_llegada_distrito: $('#wpcfact-guia-09-llegada-distrito').val() || '',
            guia_31_partida_departamento: $('#wpcfact-guia-31-partida-departamento').val() || '',
            guia_31_partida_provincia: $('#wpcfact-guia-31-partida-provincia').val() || '',
            guia_31_partida_distrito: $('#wpcfact-guia-31-partida-distrito').val() || '',
            guia_31_llegada_departamento: $('#wpcfact-guia-31-llegada-departamento').val() || '',
            guia_31_llegada_provincia: $('#wpcfact-guia-31-llegada-provincia').val() || '',
            guia_31_llegada_distrito: $('#wpcfact-guia-31-llegada-distrito').val() || '',
            guia_remitente_doc: $('#wpcfact-guia-remitente-doc').val() || '',
            guia_remitente_nombre: $('#wpcfact-guia-remitente-nombre').val() || '',
            guia_conductor_dni: $('#wpcfact-guia-conductor-dni').val() || '',
            guia_conductor_nombre: $('#wpcfact-guia-conductor-nombre').val() || '',
            guia_conductor_licencia: $('#wpcfact-guia-conductor-licencia').val() || '',
            guia_vehiculo_placa: $('#wpcfact-guia-vehiculo-placa').val() || ''
        };

        showStep(4);

        $.post(wpcfact_ajax.url, data, function(res) {
            $('#wpcfact-resultado-box .spinner').removeClass('is-active');
            console.log('Respuesta AJAX:', res);

            if (res && res.success) {
                $('#wpcfact-resultado-box').html(`
                    <div class="wpcfact-result-success">
                        <i class="dashicons dashicons-yes-alt"></i>
                        <h3>!Comprobante Emitido Exitosamente!</h3>
                        <p style="font-size:18px;">Documento: <strong>${res.data.serie}-${res.data.correlativo}</strong></p>
                        <p style="color:#64748b; margin-top:10px;">El comprobante ha sido procesado por APISUNAT y su estado actual es <strong>${res.data.estado}</strong>.</p>
                    </div>
                `);
            } else {
                const errorMsg = (res && res.data) ? res.data : 'Error desconocido al emitir';
                $('#wpcfact-resultado-box').html(`
                    <div class="wpcfact-result-success" style="color:#ef4444;">
                        <i class="dashicons dashicons-dismiss" style="color:#ef4444;"></i>
                        <h3 style="color:#ef4444;">Error al emitir</h3>
                        <p>${errorMsg}</p>
                    </div>
                `);
            }

            if (typeof wpcfact_ajax.url_emitir !== 'undefined') {
                $('#box-acciones-finales a').eq(0).attr('href', wpcfact_ajax.url_emitir);
                $('#box-acciones-finales a').eq(1).attr('href', wpcfact_ajax.url_listado);
            }
            $('#box-acciones-finales').show();
        }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
            $('#wpcfact-resultado-box .spinner').removeClass('is-active');
            console.error('Error AJAX:', textStatus, errorThrown, jqXHR.responseText);
            
            $('#wpcfact-resultado-box').html(`
                <div style="color:#ef4444;">
                    <i class="dashicons dashicons-dismiss" style="color:#ef4444;"></i>
                    <h3 style="color:#ef4444;">Error de conexión</h3>
                    <p>${textStatus}: ${errorThrown}</p>
                    <p style="font-size:12px; margin-top:10px; color:#64748b;">Respuesta: ${jqXHR.responseText}</p>
                </div>
            `);

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
