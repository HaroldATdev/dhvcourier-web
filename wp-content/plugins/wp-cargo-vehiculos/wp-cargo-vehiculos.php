<?php
/**
 * Plugin Name:  WP Cargo Vehículos
 * Description:  Registro de vehículos y control de mantenimiento por kilometraje.
 * Version:      1.0.0
 * Author:       DHV Courier
 * Text Domain:  wp-cargo-vehiculos
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WPCVH_VERSION', '1.0.0' );
define( 'WPCVH_PATH',    plugin_dir_path( __FILE__ ) );
define( 'WPCVH_URL',     plugin_dir_url( __FILE__ ) );

require_once WPCVH_PATH . 'includes/functions.php';
require_once WPCVH_PATH . 'admin/classes/class-database.php';
require_once WPCVH_PATH . 'admin/classes/class-vehiculo.php';
require_once WPCVH_PATH . 'admin/classes/class-mantenimiento.php';
require_once WPCVH_PATH . 'admin/classes/class-admin.php';
require_once WPCVH_PATH . 'admin/classes/class-frontend.php';

register_activation_hook( __FILE__, [ 'WPCVH_Database', 'instalar' ] );
