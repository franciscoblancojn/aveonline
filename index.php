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

if ( ! function_exists( 'is_plugin_active' ) )
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
if(is_plugin_active( 'departamentos-y-ciudades-de-colombia-para-woocommerce/departamentos-y-ciudades-de-colombia-para-woocommerce.php' )){
    require_once plugin_dir_path( __FILE__ ) . 'src/includes/class-admin.php';
}else{
    function AVSHME_log_dependencia() {
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
            Aveonline Shipping requiere el plugin  "Departamentos y Ciudades de Colombia para Woocommerce"
            </p>
        </div>
        <?php
    }
    add_action( 'admin_notices', 'AVSHME_log_dependencia' );
}
