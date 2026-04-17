<?php
/*
 * Plugin Name: WPCargo Invoice Addon
 * Plugin URI: http://wptaskforce.com/
 * Description: Create invoice for each shipment created
 * Author: <a href="http://www.wptaskforce.com/">WPTaskForce</a>
 * Text Domain: wpcargo-invoice
 * Domain Path: /languages
 * Version: 4.0.0
 */
// Make sure we don't expose any info if called directly

if (!function_exists('add_action')) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}
//* Defined constant
define('WPC_INVOICE_URL', plugin_dir_url(__FILE__));
define('WPC_INVOICE_PATH', plugin_dir_path(__FILE__));
define('WPC_INVOICE_VERSION', '4.0.0');
define('WPC_INVOICE_BASENAME', plugin_basename(__FILE__));

define('WPC_INVOICE_UPDATE_REMOTE', 'updates-8.1');


// Includes files

require_once(WPC_INVOICE_PATH . 'includes/post_type.php');
require_once(WPC_INVOICE_PATH . 'includes/functions.php');
require_once(WPC_INVOICE_PATH . 'includes/intl.php');
require_once(WPC_INVOICE_PATH . 'includes/pages.php');
require_once(WPC_INVOICE_PATH . 'includes/invoice.php');
require_once(WPC_INVOICE_PATH . 'includes/scripts.php');
require_once(WPC_INVOICE_PATH . 'includes/hooks.php');
require_once(WPC_INVOICE_PATH . 'includes/shortcode.php');
require_once(WPC_INVOICE_PATH . 'includes/settings.php');
require_once(WPC_INVOICE_PATH . 'includes/print-hooks.php');

// Hooks & Filters
function wpcinvoice_row_action_callback($actions)
{
	$mylinks = array(
		'<a href="' . admin_url('admin.php?page=wptaskforce-helper') . '" aria-label="' . __('License', 'wpcargo-invoice') . '">' . __('License', 'wpcargo-invoice') . '</a>'
	);
	$actions = array_merge($actions, $mylinks);
	return $actions;
}
add_filter('plugin_action_links_' . WPC_INVOICE_BASENAME, 'wpcinvoice_row_action_callback', 10);
//** Load Plugin text domain
add_action('plugins_loaded', 'wpcinvoice_load_textdomain');
function wpcinvoice_load_textdomain()
{
	load_plugin_textdomain('wpcargo-invoice', false, '/wpcargo-invoice-addon/languages');
}
add_action('wp_loaded', 'wpcinvoice_create_default_pages');

// ==========================================
// BYPASS: Force license to appear as active
// Modified on 2025-10-20 to allow usage without license restrictions
// ==========================================
add_action('init', function() {
	if (!get_option(WPC_INVOICE_BASENAME)) {
		update_option(WPC_INVOICE_BASENAME, 'BYPASS-LICENSE-KEY-2025');
	}
}, 1);

add_filter('pre_option_' . WPC_INVOICE_BASENAME, function($value) {
	return 'BYPASS-LICENSE-KEY-2025';
}, 999);
