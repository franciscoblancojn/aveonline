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
    $order = new WC_Order(117);
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
     // ob_start();
     //var_dump($order->get_items( 'shipping' ));
     // foreach( $order->get_items() as $item ){
     //     echo '$item->get_meta_data \n';
     //     var_dump($item->get_meta_data());
     // }
     // echo 'get_items \n';
     // var_dump($order->get_items());
     // echo 'get_meta_data \n';
     // var_dump($order->get_meta_data());
     // var_dump($order->get_meta('_shipments'));
     // var_dump($order->get_meta('name'));
     // $result = ob_get_clean();
     // wp_insert_post(array(
     //     'post_title'    => (isset($_POST['test']))?$_POST['test']:"Prueba",
     //     'post_content'  => $result,
     //     'post_status'   => 'publish',
     //     'post_author'   => 1,
     //     'post_category' => array( 1 )
     // ));
     // return "ok";
} 
add_shortcode('wp_insert', 'wp_insert'); 

//add
add_action( 'woocommerce_before_order_notes', 'custon_field_checkout' );  
function custon_field_checkout( $checkout ) { 
   $current_user = wp_get_current_user();
   $fecharecogida = $current_user->fecharecogida;
   woocommerce_form_field( 'fecharecogida', array(        
      'type' => 'date',               
      'label' => __('Fecha de Recogida'),     
      'required' => true,        
      'default' => $fecharecogida,        
   ), $checkout->get_value( 'fecharecogida' ) ); 
}
//valiadate
add_action( 'woocommerce_checkout_process', 'validate_custom_field_checkout' );
function validate_custom_field_checkout() {    
     if ( ! $_POST['fecharecogida'] ) {
          wc_add_notice( 'Por favor ingrese la fecha de recogida  ', 'error' );
     }
}
//save
add_action( 'woocommerce_checkout_update_order_meta', 'save_custom_field_checkout' );
function save_custom_field_checkout( $order_id ) { 
    if ( $_POST['fecharecogida'] ) update_post_meta( $order_id, '_fecharecogida', esc_attr( $_POST['fecharecogida'] ) );
}
//show
add_action( 'woocommerce_admin_order_data_after_billing_address', 'show_custom_field_checkout', 10, 1 );
function show_custom_field_checkout( $order ) {    
   $order_id = $order->get_id();
   if ( get_post_meta( $order_id, '_license_no', true ) ) echo '<p><strong>License Number:</strong> ' . get_post_meta( $order_id, '_license_no', true ) . '</p>';
}
 
// add_action( 'woocommerce_email_after_order_table', 'bbloomer_show_new_checkout_field_emails', 20, 4 );
// function bbloomer_show_new_checkout_field_emails( $order, $sent_to_admin, $plain_text, $email ) {
//     if ( get_post_meta( $order->get_id(), '_license_no', true ) ) echo '<p><strong>License Number:</strong> ' . get_post_meta( $order->get_id(), '_license_no', true ) . '</p>';
// }