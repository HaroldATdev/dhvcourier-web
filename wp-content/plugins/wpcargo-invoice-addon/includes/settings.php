<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
function wpcinvoice_register_settings() {
    global $WPCCF_Fields;
    register_setting( 'wpcinvoice_settings_group', 'wpcinvoice_settings' );
} 
add_action( 'admin_init', 'wpcinvoice_register_settings' );
function wpcinvoice_settings_navigation(){
    $view = $_GET['page'];
    ?>
    <a class="nav-tab <?php echo ( $view == 'wpcinvoice-settings') ? 'nav-tab-active' : '' ;  ?>" href="<?php echo admin_url().'admin.php?page=wpcinvoice-settings'; ?>" ><?php echo wpcinvoice_invoice_setting_label(); ?></a>
    <?php
}
//** Add plugin Setting navigation to the WPCargo settings
add_action( 'wpc_add_settings_nav', 'wpcinvoice_settings_navigation' );