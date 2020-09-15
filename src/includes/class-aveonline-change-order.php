<?php 
/**
* add_function_order_change funcion para detectar el cambio de estado de pedidos para ejecutar peticiones al api de aveonline
*
* @access public
* @return void
*/
function add_function_order_change($order_id) {
     global $wpdb, $woocommerce, $current_user;
     $order = new WC_Order($order_id);
     
     $curl = curl_init();
     // de momento, esta peticion para probar 
     curl_setopt_array($curl, array(
     CURLOPT_URL => "http://46.101.124.148/prueba-test/",
     CURLOPT_RETURNTRANSFER => true,
     CURLOPT_ENCODING => "",
     CURLOPT_MAXREDIRS => 10,
     CURLOPT_TIMEOUT => 0,
     CURLOPT_FOLLOWLOCATION => true,
     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
     CURLOPT_CUSTOMREQUEST => "POST",
     CURLOPT_POSTFIELDS => array('test' => $order->get_id()),
     ));

     $response = curl_exec($curl);

     curl_close($curl);
     echo $response;
}

add_action('woocommerce_order_status_completed',   'add_function_order_change');  
function wp_aveonline() { 
     global $wpdb, $woocommerce, $current_user;
     $order = new WC_Order(250);
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
     echo "<pre>";
     echo '
          "idempresa":"'.$e['data']->idempresa.'",
     {
          "tipo":"generarGuia",
          "codigo":"'.$e['settings']->user.'",
          "dsclavex":"'.$e['settings']->password.'",

          "origen":"'.$e['data']->origen.'",
          "dsdirre":"",
          "dsbarrioo":"",

          "destino":"'.$e['data']->destino.'",
          "dsdir":"'.$order->get_address().'",
          "dsbarrio":"",

          "dsnitre":"",
          "dstelre":"",
          "dscelularre":"",
          "dscorreopre":"",

          "dsnit":"",
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
          "dscontenido":"",
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