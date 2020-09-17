<?php 
/**
* add_function_order_change funcion para detectar el cambio de estado de pedidos para ejecutar peticiones al api de aveonline
*
* @access public
* @return void
*/
function add_function_order_change($order_id) {
     
}

add_action('woocommerce_order_status_completed',   'add_function_order_change');  
function wp_aveonline() { 
     global $wpdb, $woocommerce, $current_user;
     $order_id = 272;
     $order = new WC_Order($order_id);
     $order_data = $order->get_data();
     $e = array();
     foreach ($order->get_items( 'shipping' ) as $item) {
          foreach ($item->get_meta_data() as $data) {
               $e[$data->get_data()["key"]] = json_decode(base64_decode($data->get_data()["value"]));
          }
     }
     
     $api = new AveonlineAPI(array(),false);

     echo "<pre>";
     var_dump($e);
     var_dump($order_data);
     echo "</pre>";
     $atts = array(
          'user'         => $e['settings']->user,
          'password'     => $e['settings']->password,
     );
     $product_name = "";
     foreach ( $order->get_items() as $item_id => $item ) {
          $product_id = $item->get_product_id();
          $variation_id = $item->get_variation_id();
          $product = $item->get_product();
          $name = $item->get_name();
          $quantity = $item->get_quantity();
          $subtotal = $item->get_subtotal();
          $total = $item->get_total();
          $tax = $item->get_subtotal_tax();
          $taxclass = $item->get_tax_class();
          $taxstat = $item->get_tax_status();
          $allmeta = $item->get_meta_data();
          $somemeta = $item->get_meta( '_whatever', true );
          $type = $item->get_type();

          $product_name .= $name.", ";
       }
     echo "<pre>";
     echo '
     {
          "tipo":"generarGuia",
          "token":"'.$api->get_token($atts).'",
          "idempresa":"'.$e['data']->idempresa.'",
          
          "origen":"'.$e['data']->origen.'",
          "dsdirre":"",
          "dsbarrioo":"",
          
          "destino":"'.$e['data']->destino.'",
          "dsdir":"'.$order->get_billing_address_1().'",
          "dsbarrio":"",

          "dsnitre":"'.$e['settings']->dsnitre.'",
          "dstelre":"'.$e['settings']->dstelre.'",
          "dscelularre":"'.$e['settings']->dscelularre.'",
          "dscorreopre":"'.$e['settings']->dscorreopre.'",
          
          "dsnit":"'. get_post_meta( $order_id, '_cedula', true ) .'",
          "dsnombre":"'.$order->get_shipping_first_name().'",
          "dsnombrecompleto":"'.$order->get_formatted_billing_full_name().'",
          "dscorreop":"'.$order->get_billing_email().'",
          "dstel":"",
          "dscelular":"'.$order->get_billing_phone().'",

          "idtransportador":"'.$e['data']->idtransportador.'",

          "idalto":"",
          "idancho":"",
          "idlargo":"",

          "unidades":"'.$e['data']->unidades.'",
          "kilos":"'.$e['data']->kilos.'",
          "valordeclarado":"'.$e['data']->valordeclarado.'",

          "dscontenido":"Product Name: ['.$product_name.']",
          "dscom":"'.$order_data['customer_note'].'",

          "idasumecosto":"'.$e['data']->idasumecosto.'",
          "contraentrega":"'.$e['data']->contraentrega.'",
          "valorrecaudo":"'.$e['data']->valorrecaudo.'",

          "idagente":"'.$e['data']->idagente.'",

          "dsreferencia":"",
          "dsordendecompra":"",
          "bloquegenerarguia":"",
          "relacion_envios":"",
          "enviarcorreos":"",
          "guiahija":"",
          "accesoila":"",
          "cartaporte":""
     }
     ';
     echo "</pre>";
} 
add_shortcode('wp_aveonline', 'wp_aveonline'); 