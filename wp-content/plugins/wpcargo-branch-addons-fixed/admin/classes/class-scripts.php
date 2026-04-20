<?php
if(!defined('ABSPATH')){   
 exit; //Exit if accessed directly
}
class WPC_Brach_Addon_Scripts{	
	function __construct(){
		add_action( 'admin_enqueue_scripts', array( $this, 'wpc_branch_admin_script' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'wpc_branch_frontend_script' ) );
	}
	function wpc_branch_admin_script( $hook ){
	    if( isset($_GET['page'] ) && ($_GET['page'] == 'manage_branch' || $_GET['page'] == 'branch_transfer' )  ) {
		//** Styles
	    wp_enqueue_style( 'branch-manager-style', WPC_BRANCHES_URL . 'admin/assets/css/branch-manager-admin.css', array(), WPC_BRANCHES_VERSION );
		//** Scripts
		wp_enqueue_script('jquery');
		//if( $hook == 'toplevel_page_manage_branch' || $hook  == 'admin_page_branch_transfer' ){
			wp_enqueue_style('wpcbm-select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', WPC_BRANCHES_VERSION);
			wp_enqueue_script('wpcbm-select2-min-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), WPC_BRANCHES_VERSION);
			wp_enqueue_script( 'branch-manager-scripts', WPC_BRANCHES_URL . 'admin/assets/js/branch-manager-admin.js', array('jquery', 'wpcbm-select2-min-js'), WPC_BRANCHES_VERSION, TRUE );
			$translation = array(
				'ajaxurl' 				=> admin_url( 'admin-ajax.php' ),
				'errormessage' 			=> __('Something went wrong, Please reload the page and try again.', 'wpcargo-branches' ),
				'deleteConfirmation' 	=> __('Are you sure you want to delete this data?', 'wpcargo-branches' ),
				'transferSuccess' 		=> __('Shipment transfer successfully completed.', 'wpcargo-branches' ),
				'transferError'			=> __('Shipment transfer Failed!', 'wpcargo-branches' ),
			);
			wp_localize_script( 'branch-manager-scripts', 'wpcBMAjaxHandler', $translation );
		//}
	    }
	}
	function wpc_branch_frontend_script(){
		wp_enqueue_script( 'wpcbm-frontend-scripts', WPC_BRANCHES_URL . 'assets/js/scripts.js', array('jquery'), WPC_BRANCHES_VERSION, TRUE );
		wp_localize_script( 'wpcbm-frontend-scripts', 'wpcBMFrontendAjaxHandler', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'wpc_bm_ajax' ) ) );
	}
}
new WPC_Brach_Addon_Scripts;