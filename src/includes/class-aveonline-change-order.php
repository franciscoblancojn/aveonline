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
     $order = new WC_Order(242);
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
     {
          "tipo":"",
          "token":"'.$api->get_token($atts).'",
          "idempresa":"'.$e['data']->idempresa.'",
          "idagente":"'.$e['data']->idagente.'",
          "idtransportador":"'.$e['data']->idtransportador.'",
          "destino":"'.$e['data']->destino.'",
          "kilos":"'.$e['data']->kilos.'", 
          "unidades":"'.$e['data']->unidades.'", 
          "valordeclarado":"'.$e['data']->valordeclarado.'", 
          "dscontenido":"", //¿Cual es contenido de tu envío?
          "dsnit":"", //Nit seleccionado (COTIZACION)
          "dscom":"'.$order_data['customer_note'].'",
          "dsbarrioo":"", //Escribe tu barrio
          "dsnitre":"", //Escribe el nit del destinatario
          "dscorreopre":"", //Escribe el correo del destinatario
          "dsnombrecompleto":"", //Nombre del destinatario
          "dsdirre":"", //Escribe la direccion del destinatario
          "dsbarrio":"", //Escribe el barrio del destinatario
          "dstelre":"", //Escribe el telefono del destinatario
          "dscelularre":"", //Escribe el telefono del destinatario
          "valorrecaudo":"", //Escribe el valor del recaudo (En caso de el usuario realice el recaudo en caso que no 0)
          "contraentrega":"", //Campo seleccionado (1[si] o 0[no]) (COTIZACION)
          "cartaporte":"0", ////Desactivado en 0
          "idalto":"", //¿Cuanto mide de alto el paquete en centimetros?
          "idancho":"", //¿Cuanto mide de ancho el paquete en centimetros?
          "idlargo":"", //Cuando mide de largo el paquete en centimetros?
          "dsreferencia":"", //Dejar vacio
          "dsordendecompra":"", //Dejar vacio
          "bloquegenerarguia":1, //Activado en 1
          "relacion_envios":1, //Activado en 1
          "enviarcorreos":1, //Activado en 1
          "idasumecosto":"" ¿El cliente asume el costo de transporte? (1[si] o0[no])
         }
     ';
     echo "</pre>";
} 
add_shortcode('wp_aveonline', 'wp_aveonline'); 