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
function wp_insert() { 
    global $wpdb, $woocommerce, $current_user;
    $order = new WC_Order(144);
    $json = '{
        "tipo":"generarRecogida",
        ';
     $data_ = array(
          'user' => '',
          'password' => '',
     );
     foreach ($order->get_items( 'shipping' ) as $item) {
          foreach ($item->get_meta_data() as $data) {
               if($data->get_data()["key"] == 'token_1'){
                    $data_['user'] = base64_decode($data->get_data()["value"]);
               }else if($data->get_data()["key"] == 'token_2'){
                    $data_['password'] = base64_decode($data->get_data()["value"]);
               }else{
                    $json .= '"' . $data->get_data()["key"] . '":"' . $data->get_data()["value"] . '", ' ;
               }
          }
     }
     $fecharecogida = "";
     foreach ($order->get_meta_data() as $meta) {
          if($meta->get_data()["key"] == '_fecharecogida'){
               $fecharecogida = $meta->get_data()["value"];
          }
     }
     $api = new AveonlineAPI(array(),false);
     $json .= '
          "token":"'.$api->get_token($data_).'",
          "dscom":"'.$order->get_customer_note().'",
          "fecharecogida":"'.date("Y/m/d" , strtotime($fecharecogida)).'"
          }';
     echo $json;
} 
add_shortcode('wp_insert', 'wp_insert'); 