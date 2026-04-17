jQuery(document).ready(function( $ ){
    'use strict';

  /*  $.ajax({
        type: 'POST', 
        data: {
            action: 'get_current_branch',
        }, 
        url: wpcBMAjaxHandler.ajaxurl, 
        success: function(response) {
            $('#wpc-user-branch').val(response).change();
        }
    })*/

    $('#wpcbranch-restriction').on('click', '.wpcbranch_access', function(){
        var optValue    = $(this).prop("checked") === true ? 1 : 0 ;
        var optName     = $(this).attr('name');
        $.ajax({
            type:"POST",
            data:{
                action:'wpcbranch_access',
                optValue: optValue,
                optName: optName,
            },
            url : wpcBMAjaxHandler.ajaxurl,
            beforeSend:function(){
                $('body').append('<div class="wpc-loading">Loading...</div>');
            },
            success:function( response ){
                $('body .wpc-loading').remove();
            }
        });
    });

    $('#add-branch').on('click', function( e ){
        e.preventDefault();
        $('#addBranchModal').css({'display':'block'});
        $('.select-bm').each(function(){
            let $label = $(this).attr('data-el_label');
            $(this).select2({
                placeholder: `Select ${$label}`,
                width: '50%',
            });
        });
    });

    $('#wpc-branch-wrapper').on('click', '.edit', function( e ){
        e.preventDefault();
        var branchID = $(this).attr('data-id');
        $.ajax({
            type:"POST",
            dataType: "json",
            data:{
                action:'get_branch',
                branchID: branchID,
            },
            url : wpcBMAjaxHandler.ajaxurl,
            beforeSend:function(){
                //** Proccessing
                $('body').append('<div class="wpc-loading">Loading...</div>');
            },
            success:function( response ){
                if(response){
                    for (let key in response) {
                        if (Object.hasOwnProperty.call(response, key)) {
                            let value = response[key];
                            if(key == 'id'){
                                key = 'branchid';
                            } else {
                                key = `update-${key}`;
                            }
                            $(`#edit-branch #${key}`).val(value);
                        }
                    }
                    $('#editBranchModal').css({'display':'block'});
                    $('body .wpc-loading').remove();
                    $('.select-bm').each(function(){
                        let $label = $(this).attr('data-el_label');
                        $(this).select2({
                            placeholder: `Select ${$label}`,
                            width: '50%',
                        });
                    });
                }
            }
        });
    });
	$('.modal .close').on('click', function(e){
		e.preventDefault();
		$('.modal').css({'display':'none'});
	});

	$('form#add-branch').submit(function( e ){
		e.preventDefault();
        let formData = $(this).serialize();
		//** Process Data
		$.ajax({
            type:"POST",
            data:{
                action: 'add_branch',
                formData: formData,
            },
            url : wpcBMAjaxHandler.ajaxurl,
            beforeSend:function(){
				$('body').append('<div class="wpc-loading">Loading...</div>');
            },
            success:function(response){
            	if(response){
            		location.reload();
            	}else{
            		alert(wpcBMAjaxHandler.errormessage);
            	}
            	$('.modal .close').trigger('click');
            	$('body .wpc-loading').remove();
            }
        });
	});
    $('form#edit-branch').submit(function( e ){
        e.preventDefault();
        let formData = $(this).serialize();
        //** Process Data
        $.ajax({
            type:"POST",
            data:{
                action			: 'update_branch',
                formData		: formData
            },
            url : wpcBMAjaxHandler.ajaxurl,
            beforeSend:function(){
                //** Proccessing
                $('body').append('<div class="wpc-loading">Loading...</div>');
            },
            success:function( response ){
                if( response ){
                    location.reload();               
                }else{
                    alert(wpcBMAjaxHandler.errormessage);
                }
                $('.modal .close').trigger('click');
                $('body .wpc-loading').remove();
            }
        });
    });
	$('#wpc-branch-wrapper').on( 'click', '.delete', function( e ){
		e.preventDefault();
		var branchID = $(this).attr('data-id');
        if(confirm( wpcBMAjaxHandler.deleteConfirmation)){
            //** Process Data
            $.ajax({
                type:"POST",
                data:{
                    action		: 'delete_branch',
                    branchID	: branchID,
                },
                url : wpcBMAjaxHandler.ajaxurl,
                beforeSend:function(){
                    //** Proccessing
                    $('body').append('<div class="wpc-loading">Loading...</div>');
                },
                success:function( response ){
                    if( response ){
                        $('#wpc-branch-wrapper #branch-'+branchID).fadeOut();
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    }else{
                        alert(wpcBMAjaxHandler.errormessage);
                    }
                    $('body .wpc-loading').remove();
                }
            });
        }
	});
    $('input#shipment-number').bind('paste', function(e){       
        setTimeout(function() { 
            var branch          = $('#shipment-branch').val();
            var shipmentNumber  = $('#shipment-number').val();
            if( !branch ){
                $('#shipment-branch').focus();
                return false;
            }   
            //console.log(shipmentNumber);     
            transfer_shipment_branch(branch, shipmentNumber);
        }, 100);
        
    });
    $('#transfer-shipment-branch').keypress(function( e ){
        if(e.which == 13) {     
            var branch          = $('#shipment-branch').val();
            var shipmentNumber  = $('#shipment-number').val();
            if( !branch ){
                $('#shipment-branch').focus();
                return false;
            }
            //console.log(shipmentNumber);
            transfer_shipment_branch( branch, shipmentNumber );
        }
    });
    $('#transfer-shipment-branch #shipment-branch').on('change', function(){
        $('#shipment-number').focus();
    });
    function transfer_shipment_branch( branch, shipmentNumber ){
        //** Process Data
        $.ajax({
            type:"POST",
            data:{
                action:'transfer_branch',
                branch: branch,
                shipmentNumber:shipmentNumber
            },
            url : wpcBMAjaxHandler.ajaxurl,
            beforeSend:function(){
                //** Proccessing
                $('body').append('<div class="wpc-loading">Loading...</div>');
            },
            success:function( response ){
                if(response){
                    $('body').prepend( '<div class="transfer-message success"><p>'+wpcBMAjaxHandler.transferSuccess+'</p></div>' );
                }else{
                    $('body').prepend( '<div class="transfer-message error"><p>'+wpcBMAjaxHandler.transferError+'</p></div>' );
                }
                $('#transfer-shipment-branch input#shipment-number').val('');
                setTimeout(function(){ $('body .transfer-message').remove(); }, 2000);
                $('body .wpc-loading').remove();
            }
        });
    }

    $('.misc-pub-section #wpc-user-branch').change(function(){
		var selectedBranch = $('#wpc-user-branch option:selected').val();
		$.ajax({
            type:"POST",
            data:{
                action : 'display_branch_manager',
                selectedBranch : selectedBranch,
            },
            url : wpcBMAjaxHandler.ajaxurl,
            beforeSend:function(){
                //** Proccessing
                $('body').append('<div class="wpc-loading">Loading...</div>');
                $('#wpcargo_branch_manager').children('option:not(:first)').remove();
            },
            success:function(response){
				$('#wpcargo_branch_manager').html(response);
				$('#wpcargo_branch_manager').attr('disabled', false);
				$('.empty-branch-notice').hide();
				$('.wpc-loading').remove();
                $('#wpc-user-branch').attr('disabled', 'disabled');
                removeAffixes();
            },
             error: function(error)
                           {
                    $('#wpc-user-branch').attr('disabled', false);
                              } 
        });
	});

    const removeAffixes = function() {
        $.ajax({
            type: "POST", 
            dataType: 'json', 
            data: {
                action: 'get_affixes', 
            },
            url : wpcBMAjaxHandler.ajaxurl,
            success: function (response) {
                
                let baseTitle = $('#titlewrap #title').val();
                baseTitle = baseTitle.replace(response.prefix, '').replace(response.suffix, '');
                $('#titlewrap #title').val(baseTitle);
                modifyAffixes();

            }
        });
      }

    const modifyAffixes = function() {

        var selectedBranch = $('#wpc-user-branch option:selected').val();

        $.ajax({
            type: 'POST', 
            data: {
                
                action: 'modify_affixes', 
                selectedBranch: selectedBranch,

            }, 
            url : wpcBMAjaxHandler.ajaxurl, 
            success: function() {
                setBranchAffixes();
            }
        })

    }

    const setBranchAffixes = function() {
        $.ajax({
            type: "POST", 
            dataType: 'json', 
            data: {
                action: 'get_affixes', 
            },
            url : wpcBMAjaxHandler.ajaxurl,
            success: function (response) {
                
                let affixedTitle = $('#titlewrap #title').val();
                affixedTitle = response.prefix + affixedTitle + response.suffix;
                $('#titlewrap #title').val(affixedTitle);
                setCurrentBranch();         

            }
        });
    }

    const setCurrentBranch = function() {
        var branchID = $('#wpc-user-branch option:selected').val();
        $.ajax({
            type: "POST", 
            data: {
                action: 'set_current_branch', 
                branchID : branchID,
            },
            url : wpcBMAjaxHandler.ajaxurl, 
            success: function() {
                $('#wpc-user-branch').removeAttr('disabled');
            }
        });
    }

});