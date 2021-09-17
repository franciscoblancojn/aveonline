<?php
/**
 * @package WoocommerceAveOnlineShipping
 */
/*
Plugin Name: Aveonline Shipping
Plugin URI: https://startscoinc.com/es/woocommerceaveonlineshipping
Description: Integración de woocommerce con los servicios de envío de Aveonline.
Author: Startsco
Version: 1.21.9.17
Author URI: https://startscoinc.com/es/#
License: 
Text Domain: wc-aveonline-shipping
 */

/*
License...

Copyright 2020 Startsco, Inc.
 */

require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://gitlab.com/franciscoblancojn/aveonline',
	__FILE__,
	'elios'
);
$myUpdateChecker->setAuthentication('MqiAAZzo9WNBAGzgwc3L');
$myUpdateChecker->setBranch('master');


//AVSHME_
define("AVSHME_LOG",false);

require_once plugin_dir_path( __FILE__ ) . 'departamentos_ciudades/departamentos-y-ciudades-de-colombia-para-woocommerce.php';
require_once plugin_dir_path( __FILE__ ) . 'src/includes/class-admin.php';
