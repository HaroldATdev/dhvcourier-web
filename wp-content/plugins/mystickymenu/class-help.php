<?php
/**
 * Help And Footer Menu Class
 *
 * @author  : Premio <contact@premio.io>
 * @license : GPL2
 * */

 if (defined('ABSPATH') === false) {
	exit;
}

// Class for help and footer menu
class MSB_HELP {


    // Allowed pages for showing the help menu
    private static $allowed_pages = ['my-stickymenu-welcomebar', 'my-stickymenu-new-welcomebar',  'my-sticky-menu-analytics', 'my-sticky-menu-leads', 'my-stickymenu-settings', 'my-stickymenu-upgrade' ]; 
    
    // constructor
    public function __construct() {  
         
        $page = $_GET['page'] ?? ''; 
        // Check if we're on one of those pages
        if (in_array($page, self::$allowed_pages, true)) {
            // register enqueue  css
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts')); 
            // add need help in footer
            add_action('admin_footer', array($this, 'admin_footer_need_help_content'));
        } 
  
	}//end __construct()

    // load help settings
    public function load_help_settings(){
        define('MSB_FOOTER_HELP_DATA', array(
            'help_icon' => esc_url(MYSTICKYMENU_URL."images/help/help-icon.svg"),
            'close_icon' => esc_url(MYSTICKYMENU_URL."images/help/close.svg"), 
            'premio_site_info' => esc_url('https://premio.io/'),
            'help_center_link' => esc_url('https://premio.io/help/mystickymenu/?utm_source=pluginspage'),
            'footer_menu' => array( 
                'support' => array(
                    'title' => esc_html("Get Support", "mystickymenu"),
                    'link' =>  esc_url("https://wordpress.org/support/plugin/mystickymenu/"),
                    'status' => true,
                ),
                'upgrade_to_pro' => array(
                    'title' => esc_html("Upgrade to Pro", "mystickymenu"),
                    'link' =>  esc_url(admin_url("admin.php?page=my-stickymenu-upgrade")),
                    'status' => true,
                ),
                'recommended_plugins' => array(
                    'title' => esc_html("Recommended Plugins", "mystickymenu"),
                    'link' =>  esc_url(admin_url("admin.php?page=msm-recommended-plugins")),
                    'status' => get_option("hide_msmrecommended_plugin") ? false : true,
                ), 
                'live_link' => array(
                    'title' => esc_html("Add Poptin Popups", "mystickymenu"),
                    'link' =>  esc_url(admin_url("admin.php?page=install-poptin-plugin")),
                    'status' => class_exists( 'POPTIN_Plugin_Base' ) ? false : true,
                ), 
            ),
            'support_widget' => array(
                'upgrade_to_pro' => array(
                    'title' => esc_html("Upgrade to Pro", "mystickymenu"),
                    'link' =>  esc_url(admin_url("admin.php?page=my-stickymenu-upgrade")),
                    'icon' => esc_url(MYSTICKYMENU_URL."images/help/pro.svg"),
                ),
                'get_support' => array(
                    'title' => esc_html("Get Support", "mystickymenu"),
                    'link' =>   esc_url("https://wordpress.org/support/plugin/mystickymenu/"),
                    'icon' => esc_url(MYSTICKYMENU_URL."images/help/help-circle.svg"),
                ),
                'contact' => array(
                    'title' => esc_html("Contact Us", "mystickymenu"),
                    'link' =>  false,
                    'icon' => esc_url(MYSTICKYMENU_URL."images/help/headphones.svg"),
                ),
            ),
        ));  
    }

    // enqueue scripts
    public function admin_enqueue_scripts(){ 
        $suffix     = MSM_DEV_MODE ? '' : '.min';
        // enqueue css
        wp_enqueue_style('mystickymenu-help-css', MYSTICKYMENU_URL . 'css/help'.$suffix.'.css', array(), MYSTICKY_VERSION);   

    } 

    // Need Help Footer Content
    public function admin_footer_need_help_content(){ 
        $this->load_help_settings(); 

        include_once MYSTICKYMENU_PATH.'/admin/help.php';
    } 
    
}
new MSB_HELP();