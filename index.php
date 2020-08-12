<?php
/**
 * @package WoocommerceAveOnlineShipping
 */
/*
Plugin Name: Woocommerce Aveonline Shipping
Plugin URI: https://startscoinc.com/es/woocommerceaveonlineshipping
Description: Integración de woocommerce con los servicios de envío de Aveonline.
Author: Startsco
Version: 0.2
Author URI: https://startscoinc.com/es/#
License: 
Text Domain: wc-aveonline-shipping
 */

/*
License...

Copyright 2020 Startsco, Inc.
 */

// Make sure we don't expose any info if called directly

if (!function_exists('add_action')) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}


require_once plugin_dir_path( __FILE__ ) . 'src/includes/class-aveonline-contraentrega.php';
require_once plugin_dir_path( __FILE__ ) . 'src/includes/class-aveonline-change-order.php';
require_once plugin_dir_path( __FILE__ ) . 'src/includes/class-aveonline-plugin.php';

// initialize plugin
function init_aveonline()
{
	//custom css and js
	wp_enqueue_style('WCAveonlineShippingMethod', plugin_dir_url( __FILE__ )."src/css/WCAveonlineShippingMethod.css",array(),null );
	wp_enqueue_script('WC_contraentrega', plugin_dir_url( __FILE__ )."src/js/contraentrega.js",array(),null,true );
}
add_action('init', 'init_aveonline');