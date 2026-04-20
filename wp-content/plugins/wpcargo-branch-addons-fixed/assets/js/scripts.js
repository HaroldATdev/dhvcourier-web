jQuery(document).ready(function( $ ){

   /*$.ajax({
        type: 'POST', 
        data: {
            action: 'get_current_branch',
        }, 
        url: wpcBMFrontendAjaxHandler.ajaxurl, 
        success: function(response) {
            $('#wpc-user-branch').val(response).change();
        }
    })
*/
	$('.after-shipments-info #wpc-user-branch').change(function(){
		var selectedBranch = $('#wpc-user-branch option:selected').val();
		$('.after-shipments-info #wpcargo_branch_manager').attr('disabled', 'disabled');
		$.ajax({
            type:"POST",
            data:{
                action : 'display_branch_manager',
                selectedBranch : selectedBranch,
            },
            url : wpcBMFrontendAjaxHandler.ajaxurl,
            beforeSend:function(){
                //** Proccessing
                $('body').append('<div class="wpc-loading">Loading...</div>');
                $('.after-shipments-info #wpcargo_branch_manager').children('option:not(:first)').remove();
            },
            success:function(response){
				$('.after-shipments-info #wpcargo_branch_manager').html(response);
				$('.after-shipments-info #wpcargo_branch_manager').attr('disabled', false);
				$('.empty-branch-notice').hide();
				$('.wpc-loading').remove();
                //$('#wpc-user-branch').attr('disabled', 'disabled');
               // modifyAffixes();
            }, 
             error: function(error)
                           {
                    $('#wpc-user-branch').attr('disabled', false);
                              } 
            
        });
	});

  /*  const modifyAffixes = function() {

        var selectedBranch = $('#wpc-user-branch option:selected').val();

        $.ajax({
            type: 'POST', 
            data: {
                
                action: 'modify_affixes', 
                selectedBranch: selectedBranch,

            }, 
            url : wpcBMFrontendAjaxHandler.ajaxurl, 
            success: function() {
                setCurrentBranch();
            }
        })

    }
  */  
 const setCurrentBranch = function() {
        var branchID = $('#wpc-user-branch option:selected').val();
        $.ajax({
            type: "POST", 
            data: {
                action: 'set_current_branch', 
                branchID : branchID,
            },
            url : wpcBMFrontendAjaxHandler.ajaxurl, 
            success: function() {
                $('#wpc-user-branch').removeAttr('disabled');
            }
        });
    }

});