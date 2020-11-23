<?php
/**
 * @package WoocommerceAveOnlineShipping
 */
/*
Plugin Name: Aveonline Shipping
Plugin URI: https://startscoinc.com/es/woocommerceaveonlineshipping
Description: Integración de woocommerce con los servicios de envío de Aveonline.
Author: Startsco
Version: 0.1
Author URI: https://startscoinc.com/es/#
License: 
Text Domain: wc-aveonline-shipping
 */

/*
License...

Copyright 2020 Startsco, Inc.
 */

// Make sure we don't expose any info if called directly

// if (!function_exists('add_action')) {
//     echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
//     exit;
// }
//AVSHME_
require_once plugin_dir_path( __FILE__ ) . 'src/includes/class-admin.php';

