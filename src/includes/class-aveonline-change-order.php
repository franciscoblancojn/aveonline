<?php 
   function add_credits($order_id) {
        global $wpdb, $woocommerce, $current_user;
        $order = new WC_Order($order_id);
        
        $curl = curl_init();

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

   add_action('woocommerce_order_status_completed',   'add_credits');  