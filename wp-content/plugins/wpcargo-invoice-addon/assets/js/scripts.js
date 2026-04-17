jQuery(document).ready( function( $ ){
    let wpcargoTax      = wpcinvoiceAjaxHandler.wpcargo_tax;
    let priceMetakey    = wpcinvoiceAjaxHandler.priceMetakey;
    let amountMetakey   = wpcinvoiceAjaxHandler.amountMetakey;
    let wpcpqAdditionalCharges = wpcinvoiceAjaxHandler.wpcpqAdditionalCharges;
    let wpcpqAdditionalChargesKeys = wpcpqAdditionalCharges ? Object.keys(wpcpqAdditionalCharges) : {};
    let wpcpqActivated = wpcinvoiceAjaxHandler.wpcpqActivated == 1 ? true : false;
    apply_readyonly_prop();
    // Repeater Scripts
    $('#wpcinvoice_package #wpcfe-packages-repeater').repeater({
        show: function () {
            apply_readyonly_prop();
        }
    });
    // Add Readonly property in to input that has .readonly class
    function apply_readyonly_prop(){
        $('.wpcargo-dashboard').find('input.readonly').prop('readonly', true);
    }
    function reset_shipment( ){
        $('#invoiceUpdateModal .invoice-list-wrapper .invoice-list').html('');
    }
    function reset_checked_shipment( ){
        $("#wpcfe-select-all")[0].checked = false;
        $('.wpcfe-shipments').each( function(){ //iterate all listed checkbox items
            this.checked = false; //change ".checkbox" checked status
        });
    }
    function generate_shipment( id, number ){
        $('#invoiceUpdateModal .invoice-list-wrapper .invoice-list').append( 
            '<li class="list-group-item w-50 list-group-item-action" data-id="'+id+'">'+number+' <span class="fa fa-trash float-right text-danger"></span></li>'
        );
    }
    function count_shipment(){
        return  $('.invoice-list .list-group-item').length;
    }
    // Bulk Update
    $('#wpcinvoice-table-wrapper').on('click', '#invoiceBulkUpdate', function(e){
        e.preventDefault();
        reset_shipment();
        const shipmentsElem =  $('#wpcinvoice-list .wpcfe-shipments:checked');
        $( '#invoiceUpdateModal #invoiceUpdate-form.modal-body' ).find('input[type="text"], select, textarea').val('');
        if( shipmentsElem.length > 0 ){
            shipmentsElem.each( function(){ //iterate all listed checkbox items
                let shipmentID      = $(this).val();
                let shipmentNumber  = $(this).data('number');
                generate_shipment(shipmentID, shipmentNumber);
            });
        }else{
            alert( wpcinvoiceAjaxHandler.noShipmentSelected );
            return false;
        }
    });
    // Single update
    $('#wpcinvoice-table-wrapper').on('click', '.wpcinvoice-update', function(e){
        e.preventDefault();
        reset_shipment();
        generate_shipment($(this).data('id'),$(this).data('number'));
    });
    $('#invoiceUpdateModal .invoice-list').on('click', '.list-group-item .fa-trash', function () {
        $(this).closest('li').remove();
        let listLength = $('#invoiceUpdateModal .invoice-list-wrapper .invoice-list list-group-item').length;   
        if( count_shipment() === 0 ){
            $("#invoiceUpdateModal .close").trigger( 'click' );
        }
    });
    $("#invoiceUpdateModal").on('hidden.bs.modal', function(){
        reset_checked_shipment();
    });
    // Modal Submitt
    $('#invoiceUpdateModal').on('submit', '#invoiceUpdate-form', function( e ){
        e.preventDefault();
        let updateShipments = [];
        let currElem        = $( this );
        let updateFields    = currElem.serializeArray();
        let status          = $('select[name="__wpcinvoice_status"]').val();
        $('#invoiceUpdateModal .invoice-list-wrapper .invoice-list .list-group-item').each(function( index ){
            let shipmentID = $(this).data('id');
            updateShipments.push( shipmentID );
        });
        // Check if shipment is selected
        if( updateShipments.length < 1 ){
            alert( wpcfeAjaxhandler.noShipmentSelected );
            return false;
        }
        // Process shipment to update
        $.ajax({
            type:"POST",
            datatype: 'json',
            data:{
                action              : 'update_invoice',    
                updateShipments     : updateShipments,
                updateFields        : updateFields
            },
            url : wpcinvoiceAjaxHandler.ajaxurl,
            beforeSend:function(){
                $('body').append('<div class="wpcfe-spinner">Loading...</div>');
            },
            success:function( response ){
                $('body .wpcfe-spinner').remove();
                let data = JSON.parse( response );
                $.each(data, function( index, value ){
                    const hasWaybillOpt = $(`#wpcinvoice-list #shipment-${value.id} .dropdown-menu`).find('a.print-waybill').length;
                    if( hasWaybillOpt == 0 && status == 'wpci-paid'){
                        $(`#wpcinvoice-list #shipment-${value.id} .dropdown-menu`).prepend(
                            `<a class="dropdown-item print-waybill py-1" data-id="${value.sid}" data-type="waybill" data-status="wpci-paid" href="#">${wpcinvoiceAjaxHandler.waybillLabel}</a>`
                        );
                    }else{
                        $(`#wpcinvoice-list #shipment-${value.id} .dropdown-menu a.print-waybill`).remove();
                    }

                    $(`#wpcinvoice-list #shipment-${value.id} .form-check input.form-check-input`).attr('data-status', status);
                    $(`#wpcinvoice-list #shipment-${value.id} .wpcinv_data-status`).html(
                        `
                        <a href="#" data-id="${value.id}" data-sid="${value.sid}" data-number="${value.number}" class="wpcinvoice-update btn btn-info btn-sm py-1 px-2 mr-2 waves-effect waves-light" data-toggle="modal" data-target="#invoiceUpdateModal" title="Edit"><i class="fa fa-edit text-white"></i></a> ${value.status}
                        `
                    );
                });
                currElem.find('.modal-body input[name="text"], .modal-body input[name="email"], .modal-body input[name="number"], .modal-body select, .modal-body textarea').val('');
                $("#invoiceUpdateModal .close").trigger( 'click' );
            }
        });
    });
    /* Helper function */
    function download_file(fileURL, fileName) {
        // for non-IE
        if (!window.ActiveXObject) {
            let save = document.createElement('a');
            save.href = fileURL;
            save.target = '_blank';
            let filename = fileURL.substring(fileURL.lastIndexOf('/')+1);
            save.download = fileName || filename;
            if ( navigator.userAgent.toLowerCase().match(/(ipad|iphone|safari)/) && navigator.userAgent.search("Chrome") < 0) {
                    document.location = save.href; 
                // window event not working here
                }else{
                    let evt = new MouseEvent('click', {
                        'view': window,
                        'bubbles': true,
                        'cancelable': false
                    });
                    save.dispatchEvent(evt);
                    (window.URL || window.webkitURL).revokeObjectURL(save.href);
                }	
        }
        // for IE < 11
        else if ( !! window.ActiveXObject && document.execCommand)     {
            let _window = window.open(fileURL, '_blank');
            _window.document.close();
            _window.document.execCommand('SaveAs', true, fileName || fileURL)
            _window.close();
        }
    }
    // Print PDF template
    function print_pdf( selectedShipment, printType, restrictInvoice ){
        $.ajax({
            type:"POST",
            data:{
                action  : 'wpcfe_bulkprint',    
                selectedShipment   : selectedShipment,
                printType : printType
            },
            url : wpcinvoiceAjaxHandler.ajaxurl,
            beforeSend:function(){
                $('body').append('<div class="wpcfe-spinner">Loading...</div>');
            },
            success:function( response ){
                $('body .wpcfe-spinner').remove();
                $data = JSON.parse(response);
                if($.isEmptyObject($data)) {
                    alert( wpcinvoiceAjaxHandler.noShipmentSelected );
                    return;
                } else {
                    if( $("#wpcfe-select-all").length ){
                        reset_checked_shipment();
                    }                    
                    download_file( $data.file_url, $data.file_name );
                    if( restrictInvoice.length > 0 ){
                        let invoiceTable = document.getElementById("wpcinvoice-list");
                        invoiceTable.insertAdjacentHTML('beforebegin', 
                            `
                            <div class="wpcinvoice-printError alert alert-danger">
                                <p class="m-0">${wpcinvoiceAjaxHandler.cantPrint}.</p>
                                <p class="m-0">${restrictInvoice.join(', ')}</p>
                            </div>
                            `    
                        );
                        setTimeout(function(){
                            $('body .wpcinvoice-printError').remove();
                        }, 3000);
                    }
                    return;
                }
            }
        }); 
    }
    // Print Invoice
    function print_invoice( selectedShipment ){
        const printType = 'invoice';
        $.ajax({
            type:"POST",
            data:{
                action  : 'wpcfe_bulkprint',    
                selectedShipment   : selectedShipment,
                printType : printType
            },
            url : wpcinvoiceAjaxHandler.ajaxurl,
            beforeSend:function(){
                $('body').append('<div class="wpcfe-spinner">Loading...</div>');
            },
            success:function( response ){
                $('body .wpcfe-spinner').remove();
                $data = JSON.parse(response);
                if($.isEmptyObject($data)) {
                    alert( wpcinvoiceAjaxHandler.noShipmentSelected );
                    return;
                } else {
                    if( $("#wpcfe-select-all").length ){
                        reset_checked_shipment();
                    }                    
                    download_file( $data.file_url, $data.file_name );
                    return;
                }
            }
        }); 
    }

    // parcel quotation integration start
     
    let wpcpqTotalAddCharges = () => {
        let total = 0;
        if( !wpcpqActivated ){ return 0; }
        wpcpqAdditionalChargesKeys.forEach((el) => {
            el = '#' + el;
            let val = $(el).val();
            if( val ) {
                total += parseFloat( val );
            } else {
                return;
            }
        });
        return total;
    }

    // parcel quotation integration end

    $('#wpcinvoice-table-wrapper .wpcinvoice-bulk-print-wrapper').on('click', '.wpcinvoice-bulk-print', function(e){
        e.preventDefault();
        const shipmentsElem     =  $('#wpcinvoice-list .wpcfe-shipments:checked');
        const printType         = $(this).data('type');
        let selectedShipment    = [];
        let restrictInvoice     = [];
        if( shipmentsElem.length > 0 ){
            shipmentsElem.each( function(){ //iterate all listed checkbox items
                const invoiceStatus = $(this).data('status');
                if( invoiceStatus != 'wpci-paid' && printType == 'waybill' ){
                    restrictInvoice.push( $(this).data('number') );
                    return;
                }
                selectedShipment.push( $(this).data('sid') );
            });
            print_pdf( selectedShipment, printType, restrictInvoice );
        }else{
            alert( wpcinvoiceAjaxHandler.noShipmentSelected );
            return false;
        }
    });
    $('#wpcinvoice-list').on('click', '.wpcinv_data-print .btn', function(e){
        e.preventDefault();
        print_pdf( [$(this).data('id')], 'invoice', [] );
    });
    $('.wpcinvoice-print').on('click', function(e){
        e.preventDefault();
        print_pdf( [$(this).data('id')], 'invoice', [] );
    });
    // edit invoice auto calculate
    $('#wpcinvoice_package table').on('keyup change', `.wpc-pm-qty, .${priceMetakey}`, function(){
        let subtotal = 0;
        $('#wpcinvoice_package table tbody tr').each( function(){
            let item_unit_amount    = 0;
            let item_qty            = parseFloat( $(this).find( 'input.wpc-pm-qty' ).val() );
            let item_unit_price     = parseFloat( $(this).find( `input.${priceMetakey}` ).val() );
            
            if( !isNaN( item_qty ) && !isNaN( item_unit_price ) ){
                item_unit_amount += item_unit_price * item_qty;
                subtotal += item_unit_amount;
            }
            $(this).find( 'input.unit-amount' ).val( item_unit_amount.toFixed(2) );
        });
        let tax_value       = subtotal * wpcargoTax;
        let total_amount    = subtotal + tax_value;
        total_amount += parseFloat( wpcpqTotalAddCharges() );
        $('#wpcinvoice_package .total-detail').find( 'input#sub_total' ).val( subtotal.toFixed(2) );
        $('#wpcinvoice_package .total-detail').find( 'input#tax' ).val( tax_value.toFixed(2) );
        $('#wpcinvoice_package .total-detail').find( 'input#total' ).val( total_amount.toFixed(2) );
    });

    //Export Files Script
    const downloadFile =  function(fileURL, fileName) {
        // for non-IE
        if (!window.ActiveXObject) {
            var save = document.createElement('a');
            save.href = fileURL;
            save.target = '_blank';
            var filename = fileURL.substring(fileURL.lastIndexOf('/')+1);
            save.download = fileName || filename;
            if ( navigator.userAgent.toLowerCase().match(/(ipad|iphone|safari)/) && navigator.userAgent.search("Chrome") < 0) {
                    document.location = save.href; 
                // window event not working here
                }else{
                    var evt = new MouseEvent('click', {
                        'view': window,
                        'bubbles': true,
                        'cancelable': false
                    });
                    save.dispatchEvent(evt);
                    (window.URL || window.webkitURL).revokeObjectURL(save.href);
                }	
        }
        // for IE < 11
        else if ( !! window.ActiveXObject && document.execCommand)     {
            var _window = window.open(fileURL, '_blank');
            _window.document.close();
            _window.document.execCommand('SaveAs', true, fileName || fileURL)
            _window.close();
        }
    }

    // Export Form Submission
    $('#wpcinvoice-ie-export-form').on('submit', function( e ){
        e.preventDefault();
        const formData      = $(this).serializeArray();
        $.ajax({
            type:"POST",
            data:{
                action:'wpcinv_export_invoice',  
                formData:formData,
            },
            url : wpcinvoiceAjaxHandler.ajaxurl,
            beforeSend:function(){
            },
            success:function(response){

                if( response.response == 'error'){
                    $('#wpcinvoice-ie-export-form').prepend( response.message );
                    setTimeout(() => { 
                        // downloadFile( response.file.file_url, response.file.file_name );
                        $( '.notice' ).remove();
                    }, 2000);
                }else{
                    $('#wpcinvoice-ie-export-form').prepend( response.message );
                    setTimeout(() => { 
                        downloadFile( response.file.file_url, response.file.file_name );
                        $( '.notice' ).remove();
                    }, 2000);
                }
            }
        });
    });
});