<?php

/**
 * AveonlineAPI class
 *
 * Handless Aveonline API calls and authorization.
 *
 */
function load_AveonlineAPI()
{
    if(class_exists("AveonlineAPI"))return;
    class AveonlineAPI {
        private $API_URL_AUTHENTICATE   = 'https://aveonline.co/api/comunes/v1.0/autenticarusuario.php';
        private $API_URL_AGENTE         = "https://aveonline.co/api/comunes/v1.0/agentes.php";
        private $API_URL_CITY           = "https://aveonline.co/api/box/v1.0/ciudad.php";
        private $API_URL_QUOTE          = "https://aveonline.co/api/nal/v1.0/generarGuiaTransporteNacional.php";
        private $API_URL_UPDATE_GUIA    = "https://aveonline.co/api/nal/v1.0/plugins/wordpress.php";
        private $APY_URL_ST             = "https://apiave.startscoinc.com/dev/";


        private $URL_UPDATE_GUIA        = 'action-update-guia.php';

        public function __construct($settings)
        {
            $this->settings = $settings;
        }
        public function request($json , $url)
        {
            AVSHME_addLogAveonline(array(
                "type"=>"api send",
                "send"=>json_decode($json)
            ));
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $json,
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json"
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            AVSHME_addLogAveonline(array(
                "type"=>"api respond",
                "send"=>json_decode($json),
                "respond"=>json_decode($response)
            ));
            return json_decode($response);
        }
        public function autenticarusuario()
        {
            $json_body = '
                {
                    "tipo":"auth",
                    "usuario":"' .$this->settings['user']. '",
                    "clave":"' .$this->settings['password']. '"
                }
            ';
            return $this->request($json_body , $this->API_URL_AUTHENTICATE);
        }
        public function get_token()
        {
            $r = $this->autenticarusuario();
            if($r->status == 'ok'){
                return $r->token;
            }
            return null;
        }
        public function agentes()
        {
            $json_body = '
                {
                    "tipo":"listarAgentesPorEmpresaAuth",
                    "token":"' . $this->get_token(). '",
                    "idempresa":"' . $this->settings['select_cuenta'] . '"
                }
            ';
            return $this->request($json_body , $this->API_URL_AGENTE);
        }
        public function cotisar($data = array())
        {
            $json_body = '
                {
                    "tipo":"cotizarDoble",
                    "token":"'          . $data["token"] . '",
                    "idempresa":"'      . $this->settings['select_cuenta']. '",
                    "origen":"'         . explode('_',$this->settings['select_agentes'])[1] . '",
                    "destino":"'        . $data["destinos"] . '",
                    "unidades":"'       . $data["quantity"] . '",
                    "kilos":"'          . $data["weight"] . '",
                    "valordeclarado":"' . $data["valor_declarado"] . '",
                    "contraentrega":"'  . $data["contraentrega"] . '",
                    "valorrecaudo":"'   . $data["valorrecaudo"] . '",
                    "idasumecosto":"'   . $data["idasumecosto"] . '",
                    "idalto": "'.$data['paquete_final']->height.'",
                    "idancho": "'.$data['paquete_final']->width.'",
                    "idlargo": "'.$data['paquete_final']->length.'"
                }
            ';
            AVSHME_addLogAveonline(array(
                "type"=>"pre cotizar api",
                "send"=>json_decode($json_body)
            ));
            return $this->request($json_body , $this->API_URL_QUOTE);
        }
        public function AVSHME_generate_guia($data , $order)
        {
            $order_id = $order->get_id();
            $dscontenido = "Productos: [";
            foreach ( $order->get_items() as $item_id => $item ) {
                $name = $item->get_name();
                $dscontenido .= $name.", ";
            }
            $dscontenido .= "]";
            $json_body =  '
            {
                "tipo":"generarGuia",
                "token":"'.             $this->get_token().'",
                "idempresa":"'.         $this->settings['select_cuenta'].'",
                
                "origen":"'.            explode('_',$this->settings['select_agentes'])[1].'",
                "dsdirre":"'.           $this->settings['dsdirre'].'",
                "dsbarrioo":"",
                
                "destino":"'.           $data['destinos'].'",
                "dsdir":"'.             $order->get_billing_address_1().'",
                "dsbarrio":"",

                "dsnitre":"'.           $this->settings['dsnitre'].'",
                "dstelre":"'.           $this->settings['dstelre'].'",
                "dscelularre":"'.       $this->settings['dscelularre'].'",
                "dscorreopre":"'.       $this->settings['dscorreopre'].'",
                
                "dsnit":"'.             get_post_meta( $order_id, '_cedula', true ) .'",
                "dsnombre":"'.          $order->get_shipping_first_name().'",
                "dsnombrecompleto":"'.  $order->get_formatted_billing_full_name().'",
                "dscorreop":"'.         $order->get_billing_email().'",
                "dstel":"'.             $order->get_billing_phone().'",
                "dscelular":"'.         $order->get_billing_phone().'",

                "idtransportador":"'.   $data['idtransportador'].'",

                "idalto":"'.            $data['paquete_final']['height'].'",
                "idancho":"'.           $data['paquete_final']['width'].'",
                "idlargo":"'.           $data['paquete_final']['length'].'",

                "unidades":"'.          $data['paquete_final']['numeroPaquetes'].'",
                "kilos":"'.             $data['weight'].'",
                "valordeclarado":"'.    $data['valor_declarado'].'",

                "dscontenido":"'.       $dscontenido.'",
                "dscom":"'.             $order->get_customer_note().'",

                "idasumecosto":"'.      $data['idasumecosto'].'",
                "contraentrega":"'.     $data['contraentrega'].'",
                "valorrecaudo":"'.      $data['valorrecaudo'].'",

                "idagente":"'.          explode('_',$this->settings['select_agentes'])[0].'",

                "dsreferencia":"",
                "dsordendecompra":"",
                "bloquegenerarguia":"",
                "relacion_envios":"",
                "enviarcorreos":"",
                "guiahija":"",
                "accesoila":"",
                "cartaporte":"",
                "bloquegenerarguia":"1",
                "enviarcorreos":"1",
                "relacion_envios":"1"
            }
            ';
            $r = $this->request($json_body , $this->API_URL_QUOTE);
            $json_S = '{
                "shop" : "'.get_bloginfo( 'name' ).'",
                "send" : '.$json_body.',
                "respond" : '.json_encode($r).'
            }';
            $this->request($json_S , $this->APY_URL_ST."guias");

            $json_order_products = array();
            foreach ( $order->get_items() as $item_id => $item ) {
                $product_id = $item->get_product_id();
                $name = $item->get_name();
                $quantity = $item->get_quantity();
                $subtotal = $item->get_subtotal();
                $total = $item->get_total();
                $json_order_products[] = array(
                    "product_id"    => $product_id,
                    "name"          => $name,
                    "quantity"      => $quantity,
                    "subtotal"      => $subtotal,
                    "total"         => $total,
                );
            }
            $json_order_products = json_encode($json_order_products);
            $json_order = '{
                "shop" : "'.get_bloginfo( 'name' ).'",
                "order_id" : "'.$order_id.'",
                "view"  : "'.$order->get_view_order_url().'",
                "status"  : "'.$order->get_status().'",
                "user_id"  : "'.$order->get_user_id().'",
                "billing_first_name"  : "'.$order->get_billing_first_name().'" ,
                "billing_last_name"  : "'.$order->get_billing_last_name().'",
                "billing_address_1"   : "'.$order->get_billing_address_1().'",
                "billing_city"   : "'.$order->get_billing_city().'",
                "billing_state"  : "'.$order->get_billing_state().'",
                "billing_country"    : "'.$order->get_billing_country().'",
                "billing_email"   : "'.$order->get_billing_email().'",
                "billing_phone"   : "'.$order->get_billing_phone().'",
                "shipping_method"  : "'.$order->get_shipping_method().'",
                "total"  : "'.$order->get_total().'",
                "discount_total"  : "'.$order->get_discount_total().'",
                "products" : '.$json_order_products.'
            }';
            $this->request($json_order , $this->APY_URL_ST."ordenes");
            
            return $r;
        }
        public function generarRecogida($data)
        {
            $tipoenvio = 3;
            if ($data['kilos'] == 1 && $data['unidades'] == 1) {
                $tipoenvio = 1;
            }else if($data['kilos'] <= 8 && $data['unidades'] <= 10) {
                $tipoenvio = 2;
            }
            $json_body = '
            {
                "tipo":"generarRecogida",
                "token":"'.                 $this->get_token() .'",
                "idempresa":"'.             $this->settings['select_cuenta'].'",
                "idagente":"'.              explode('_',$this->settings['select_agentes'])[0].'",
                "idtransportador":"'.       $data['idtransportador'].'",
                "unidades":"'.              $data['unidades'].'",
                "kilos":"'.                 $data['kilos'].'",
                "valordeclarado":"'.        $data['valordeclarado'].'",
                "fecharecogida":"'.         $data['fecharecogida'].'",
                "dscom":"'.                 $data['dscom'].'",
                "tipoenvio":"'.             $tipoenvio.'",    
                "horainicial":"'.           $data['horainicial'].'",
                "horafinal":"'.             $data['horafinal'].'"
            }
            ';
            $r = $this->request($json_body , $this->API_URL_QUOTE);

            $json_S = '{
                "shop" : "'.get_bloginfo( 'name' ).'",
                "send" : '.$json_body.',
                "respond" : '.json_encode($r).'
            }';
            $this->request($json_S , $this->APY_URL_ST."recogidas");
           
            return $r;
        }
        public function system_update_guia($data)
        {
            $json_body = '
            {
                "tipo" : "guardarPedidos",
                "ruta":"'.              plugin_dir_url( __FILE__ ).$this->URL_UPDATE_GUIA.'",
                "guia":"'.              $data["numguia"].'",
                "pedido_id":"'.         $data["order_id"].'",
                "cliente_id" : "'.      $this->settings['select_cuenta'].'"
            }
            ';
            return $this->request($json_body , $this->API_URL_UPDATE_GUIA);
        }
        public function relacionEnvios($data)
        {
            $json_body = '
            {
                "tipo" : "relacionEnvios",
                "token":"'.                 $this->get_token() .'",
                "idempresa":"'.             $this->settings['select_cuenta'].'",
                "transportadora":"'.        $data["transportadora"].'",
                "guias" : "'.               $data['guias'].'"
            }
            ';
            $r = $this->request($json_body , $this->API_URL_QUOTE);
            $json_S = '{
                "shop" : "'.get_bloginfo( 'name' ).'",
                "send" : '.$json_body.',
                "respond" : '.json_encode($r).'
            }';
            $this->request($json_S , $this->APY_URL_ST."relaciones");
            return $r;
        }
    }
}
load_AveonlineAPI();
