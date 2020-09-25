<?php 
/**
* add_function_order_change funcion para detectar el cambio de estado de pedidos para ejecutar peticiones al api de aveonline
*
* @access public
* @return void
*/
function add_function_order_change($order_id) {
     sistem_update_guia(update_guia_order($order_id , (generate_guia($order_id))));
}
add_action('woocommerce_order_status_processing',   'add_function_order_change' , 10, 1);  

function sistem_update_guia($json_body = null){
     if($json_body == null) return;
     echo '<pre>';
     var_dump($json_body);
     echo '</pre>';

     $curl = curl_init();

     curl_setopt_array($curl, array(
     CURLOPT_URL => "https://aveonline.co/api/nal/v1.0/plugins/wordpress.php",
     CURLOPT_RETURNTRANSFER => true,
     CURLOPT_ENCODING => "",
     CURLOPT_MAXREDIRS => 10,
     CURLOPT_TIMEOUT => 0,
     CURLOPT_FOLLOWLOCATION => true,
     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
     CURLOPT_CUSTOMREQUEST => "POST",
     CURLOPT_POSTFIELDS =>$json_body,
     CURLOPT_HTTPHEADER => array(
     "Content-Type: application/json"
     ),
     ));

     $response = curl_exec($curl);

     curl_close($curl);
     echo $response;

}
function update_guia_order($order_id , $guia){
	global $wpdb, $woocommerce, $current_user;
     if($guia->status == "ok" && $guia->resultado->guia->codigo == 0){
          $list_pdf = get_post_meta( $order_id, 'order_pdf', true );
          if($list_pdf == null || $list_pdf == ""){
               $list_pdf = array();
          }
          $e =  $guia->resultado->guia;
          $new = array(
               'guias'   => '<a target="_blank" href="'.$e->rutaguia.'" >'.$e->numguia.'</a>',
               'rotulos' => '<a target="_blank" href="'.$e->rotulo.'" >'.$e->numguia.'</a>',
          );
          if(!in_array( $new , $list_pdf )){
               $list_pdf[] = $new;
               update_post_meta( $order_id, 'order_pdf', $list_pdf );
          }
          
          $order = new WC_Order($order_id);
          $order_data = $order->get_data();
          $od = array();
          foreach ($order->get_items( 'shipping' ) as $item) {
               foreach ($item->get_meta_data() as $data) {
                    $od[$data->get_data()["key"]] = json_decode(base64_decode($data->get_data()["value"]));
               }
          }
          $r = '{
               "tipo" : "guardarPedidos",
               "ruta":"'.plugin_dir_url( __FILE__ ).'class-aveonline-update-guia.php",
               "guia":"'.$e->numguia.'",
               "pedido_id":"'.$order_id.'",
               "cliente_id" : "'.$od['data']->idempresa.'"
          }';
          return $r;
     }
     return null;
}
function send_guia($json_body , $e , $order_data , $order_id){
     $curl = curl_init();

     curl_setopt_array($curl, array(
     CURLOPT_URL => "https://aveonline.co/api/nal/v1.0/generarGuiaTransporteNacional.php",
     CURLOPT_RETURNTRANSFER => true,
     CURLOPT_ENCODING => "",
     CURLOPT_MAXREDIRS => 10,
     CURLOPT_TIMEOUT => 0,
     CURLOPT_FOLLOWLOCATION => true,
     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
     CURLOPT_CUSTOMREQUEST => "POST",
     CURLOPT_POSTFIELDS => $json_body,
     CURLOPT_HTTPHEADER => array(
     "Content-Type: application/json"
     ),
     ));

     $response = curl_exec($curl);

     curl_close($curl);
     echo $json_body;
     echo '<hr>';
     echo $response;
     $response = json_decode($response);
     if($response->status=="ok"){
          $atts = array(
               'user'         => $e['settings']->user,
               'password'     => $e['settings']->password,
          );
          $data = '
          {
               "tipo":"generarRecogida",
               "token":"'.base64_encode(json_encode($atts)).'",
               "idempresa":"'.$e['data']->idempresa.'",
               "idagente":"'.$e['data']->idagente.'",
               "idtransportador":"'.$e['data']->idtransportador.'",
               "unidades":"'.$e['data']->unidades.'",
               "kilos":"'.$e['data']->kilos.'",
               "valordeclarado":"'.$e['data']->valordeclarado.'",
               "fecharecogida":"'.$e['data']->fecharecogida.'",
               "dscom":"'.$order_data['customer_note'].'"
          }
          ';
          update_post_meta( $order_id, 'solicitar_recogida', base64_encode($data) );
     }
     return $response;
}
function generate_guia($order_id){
     global $wpdb, $woocommerce, $current_user;
     $order = new WC_Order($order_id);
     $order_data = $order->get_data();
     $e = array();
     foreach ($order->get_items( 'shipping' ) as $item) {
          foreach ($item->get_meta_data() as $data) {
               $e[$data->get_data()["key"]] = json_decode(base64_decode($data->get_data()["value"]));
          }
     }
     
     $api = new AveonlineAPI(array(),false);
     $atts = array(
          'user'         => $e['settings']->user,
          'password'     => $e['settings']->password,
     );
     $table_package = json_decode($e['settings']->table_package);
     $data_product = [];
     $product_name = "";
     $volumen = 0;
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

          $_product       = wc_get_product($product_id);
          $data_product[] = array(
               'id'      => $product_id,
               "length"  => $_product->get_length(),
               "width"   => $_product->get_width(),
               "height"  => $_product->get_height(),
               "quantity"=> $quantity,
               "name"    => $name,
          );

          $volumen += $_product->get_length() * $_product->get_width() * $_product->get_height() * $quantity;

          $product_name .= $name.", ";
     }
     $idalto = pow($volumen, 1/3);
     $idancho = pow($volumen, 1/3);
     $idlargo = pow($volumen, 1/3);
     // $package_calcule = calculate_package($table_package , $data_product);
     // echo '<pre>';
     // echo "<hr>table_package<hr>";
     // var_dump($package_calcule);
     // echo '</pre>';
     // return;
     $json_body =  '
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
          "dstel":"'.$order->get_billing_phone().'",
          "dscelular":"'.$order->get_billing_phone().'",

          "idtransportador":"'.$e['data']->idtransportador.'",

          "idalto":"'.$idalto.'",
          "idancho":"'.$idancho.'",
          "idlargo":"'.$idlargo.'",

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
     
     $e['data']->fecharecogida = get_post_meta( $order_id, '_fecharecogida', true );
     return send_guia($json_body , $e , $order_data , $order_id);
}
function calculate_package($table_package , $data_product){
     // echo '<pre>';
     // echo "<hr>table_package<hr>";
     // var_dump($table_package);
     // echo "<hr>data_product<hr>";
     // var_dump($data_product);
     // echo '</pre>';
     for ($i=0; $i < count($table_package); $i++) { 
          $table_package[$i]->products = array();
          for ($j=0; $j < count($data_product); $j++) { 
               if(  $table_package[$i]->length > $data_product[$j]["length"] &&
                    $table_package[$i]->width > $data_product[$j]["width"] &&
                    $table_package[$i]->height > $data_product[$j]["height"] &&
                    $data_product[$j]['quantity'] > 0
               ){
                    // $k_ = 1 ;
                    // for ($k=1; $k <= $data_product[$j]['quantity']; $k++) { 
                    //      if(  $table_package[$i]->length > $data_product[$j]["length"] * $k &&
                    //           $table_package[$i]->width > $data_product[$j]["width"] * $k &&
                    //           $table_package[$i]->height > $data_product[$j]["height"] * $k 
                    //      ){
                    //           $k_ = $k;
                    //      }
                    // }
                    // $aux_product = $data_product[$j];
                    // $aux_product['quantity'] = $k_;
                    // $table_package[$i]->products[] = $aux_product;
                    // $data_product[$j]['quantity'] -= $k_;
                    array_push($table_package[$i]->products,  $data_product[$j]);
                    //$table_package[$i]->products[] = $data_product[$j];
                    $data_product[$j]['quantity'] = 0;
               }
          }
     }

     echo '<pre>';
     echo "<hr>table_package<hr>";
     var_dump($table_package);
     echo "<hr>data_product<hr>";
     var_dump($data_product);
     echo '</pre>';
     return $table_package;
}
//show
add_action( 'woocommerce_admin_order_data_after_billing_address', 'order_pdf', 10, 1 );
function order_pdf( $order ) {    
	$order_id = $order->get_id();
	$e = get_post_meta( $order_id, 'order_pdf', true );
	if ( $e ) {
		if(count($e) > 0){
			echo '
			<hr>
			<strong>
				Pdf
			</strong> 
			<hr>
			<strong>
				Guias
			</strong> 
			<br>';
			for ($i=0; $i < count($e); $i++) { 
				echo $e[$i]['guias'].'<br>';
			}
			echo '<hr>
			<strong>
				Rotulos
			</strong> 
			<br>';
			for ($i=0; $i < count($e); $i++) { 
				echo $e[$i]['rotulos'].'<br>';
			}
			echo '<hr>';
		}
	}
}
function wp_aveonline() { 
     add_function_order_change(418);
     //generate_guia(418);
} 
add_shortcode('wp_aveonline', 'wp_aveonline'); 