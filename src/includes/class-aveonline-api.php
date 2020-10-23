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
        private $API_URL_AUTHENTICATE = 'https://aveonline.co/api/comunes/v1.0/autenticarusuario.php';
        private $API_URL_AGENTE = "https://aveonline.co/api/comunes/v1.0/agentes.php";
        private $API_URL_CITY = "https://aveonline.co/api/box/v1.0/ciudad.php";
        private $API_URL_QUOTE = "https://aveonline.co/api/nal/v1.0/generarGuiaTransporteNacional.php";



        private $authenticate_data = null; //get object of api 
        private $agente_data = null;
        private $city_data = null; //get object of api
        private $city_api = null;
        private $quote = null;

        private $p_valordeclarado = 100;

        private $user; // api key for API authentication (Provided by Coordinadora)
        private $password; // api password for API authentication (Provided by Coordinadora)

        private $locations; // array of available cities for Coordinadora

        private $testing = true; // flag to switch between testing and production Coordinadora endpoints



        public function __construct($atts = array(), $load = true)
        {
            // api variables
            $this->atts = $atts;
            $this->debug = true;
            if($load){
                $this->authenticate();
            }
        }
        function pre( $e , $key = "none" ){
            if($this->debug){
                echo "<hr>";
                echo $key;
                echo "</hr>";
                echo "<pre>";
                var_dump($e);
                echo "</pre>";
            }
        }
        public function get_token($atts = array())
        {
            $json_body = '
                {
                    "tipo":"auth",
                    "usuario":"' . (isset($atts['user'])?$atts['user']:$this->atts['user']) . '",
                    "clave":"' . (isset($atts['password'])?$atts['password']:$this->atts['password']) . '"
                }
            ';
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->API_URL_AUTHENTICATE,
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
            $r = json_decode($response);
            if($r->status == 'ok'){
                return json_decode($response)->token;
            }else{
                return null;
            }
        }
        public function authenticate($atts = array(), $set = true)
        {
            $json_body = '
                {
                    "tipo":"auth",
                    "usuario":"' . (isset($atts['user'])?$atts['user']:$this->atts['user']) . '",
                    "clave":"' . (isset($atts['password'])?$atts['password']:$this->atts['password']) . '"
                }
            ';
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->API_URL_AUTHENTICATE,
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
            if($set){
                $this->authenticate_data = json_decode($response);
            }else{
                return json_decode($response);
            }
        }
        public function get_agentes($atts = null, $set = true)
        {
            //validar usuario
            if ($this->authenticate_data === null) {
                return array('status'=>'error','error'=>'no user data');
            }
            if ($this->authenticate_data->status !== "ok") {
                return array('status'=>'error','error'=>'no user data');
            }
            if($atts == null){
                $atts = [];
                $count_user = 0;
                $cuentas = $this->authenticate_data->cuentas;
                for ($i=0; $i < count($cuentas); $i++) { 
                    $usuarios = $cuentas[$i]->usuarios ;
                    for ($j=0; $j < count($usuarios); $j++) { 
                        $atts[$count_user++] = $usuarios[$i]->id;
                    }
                }
            }
            $r_agentes = [];
            for ($i=0; $i < count($atts); $i++) { 
                $json_body = '
                    {
                        "tipo":"listarAgentesPorEmpresaAuth",
                        "token":"' . $this->authenticate_data->token . '",
                        "idempresa":"' . $atts[$i] . '"
                    }
                ';
                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => $this->API_URL_AGENTE,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_POSTFIELDS => $json_body,
                    CURLOPT_HTTPHEADER => array(
                        "Content-Type: application/json"
                    ),
                ));

                $response = curl_exec($curl);
                $aux_agente = json_decode($response);
                if($aux_agente->status="ok"){
                    $r_agentes = array_merge($r_agentes,$aux_agente->agentes);
                }
                curl_close($curl);
            }
            if($set){
                $this->agente_data = $r_agentes;
            }else{
                return $r_agentes;
            }
        }
        /**
         * Remove some sepcial charecters from strings just like accents and umlauts
         *
         * @param sting $str
         * @return string
         */
        function clean_string($str)
        {
            $forbidden_chars = array("á", "é", "í", "ó", "ú", "Á", "É", "Í", "Ó", "Ú", "ñ", "ü");
            $allowed_chars = array("a", "e", "i", "o", "u", "A", "E", "I", "O", "U", "n", "u");
            $out = str_replace($forbidden_chars, $allowed_chars, $str);
            return $out;
        }

        
        /**
         * Returns a state's full name from ISO state code
         *
         * @param string $code  ISO state code
         * @return string
         */
        private static function get_regions_conversion($code)
        {
            $regions = array(
                'AMZ' => 'amazonas',
                'ANT' => 'antioquia',
                'ARU' => 'arauca',
                'ATL' => 'atlantico',
                'BOL' => 'bolivar',
                'BOY' => 'boyaca',
                'CAL' => 'caldas',
                'CAQ' => 'caqueta',
                'CAS' => 'casanare',
                'CAU' => 'cauca',
                'CES' => 'cesar',
                'CHOC' => 'choco',
                'COR' => 'cordoba',
                'CUN' => 'cundinamarca',
                'GUA' => 'guainia',
                'GUV' => 'guaviare',
                'HUI' => 'huila',
                'GUJ' => 'la Guajira',
                'MAG' => 'magdalena',
                'MET' => 'meta',
                'NAR' => 'nariño',
                'NOR' => 'norte de Santander',
                'PUT' => 'putumayo',
                'QUI' => 'quindio',
                'RIS' => 'risaralda',
                'SAP' => 'san Andres',
                'SAN' => 'santander',
                'SUC' => 'sucre',
                'TOL' => 'tolima',
                'VAC' => 'valle del Cauca',
                'VAU' => 'vaupes',
                'VIC' => 'vichada',
            );

            $code = strtoupper($code);

            if (isset($regions[$code])) {
                $region = $regions[$code];
            } elseif (in_array(strtolower($code), array_values($regions))) {
                $region = $code;
            }

            return $region;
        }
        public function get_city($data = null)
        {
            if($data == null){
                return [];
            }
            $destinos = [];

            $json_body = '
                {
                    "tipo": "listar",
                    "data": "' . $this->clean_string($data['city']) . '",
                    "registros": "999"
                }
            ';
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->API_URL_CITY,
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
            
            $aux_r = json_decode($response);
            if($aux_r->status="ok"){
                for ($i=0; $i < count($aux_r->ciudades); $i++) { 
                    array_push($destinos,$aux_r->ciudades[$i]->nombre);
                }
            }

            return $destinos;
        }
        public function quote($data = null)
        {
            if ($data === null) {
                return "Invalid";
            }
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->API_URL_QUOTE,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json"
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            return json_decode($response);
        }
        public function get_rate($data = null)
        {
            if ($data === null) {
                return [];
            }
            $rates = [];
            for ($i = 0 ; $i < count($data['idempresas']) ; $i++) {
                for ($j = 0 ; $j < count($data['origenes']) ; $j++) {
                    $l = $data['contraentrega'];
                    $json_body = '
                        {
                            "tipo":"cotizar",
                            "token":"' . $this->get_token() . '",
                            "idempresa":"' .$data['idempresas'][$i]. '",
                            "origen":"' . $data["origenes"][$j] . '",
                            "destino":"' . $data["destinos"] . '",
                            "unidades":"' . $data["quantity"] . '",
                            "kilos":"' . $data["weight"] . '",
                            "valordeclarado":"' . $data["valor_declarado"] . '",
                            "contraentrega":"' . $l . '",
                            "valorrecaudo":"' . $data["valorrecaudo"] . '",
                            "idasumecosto":"' . $data["idasumecosto"] . '"
                        }
                    ';
                    $this->pre($json_body);

                    $aux_rate = $this->quote($json_body);
                    $this->pre($aux_rate);

                    if($aux_rate->status == 'ok'){
                        $cotizaciones = $aux_rate->cotizaciones;
                        for ($m=0; $m < count($cotizaciones); $m++) { 
                            if($cotizaciones[$m]->total!=0){
                                $id = $i.$j.$cotizaciones[$m]->codTransportadora."WC_contraentrega_" . (($l == 0) ? "off" : "on");
                                
                                $json_meta = json_decode($json_body);
                                $json_meta->idagente        = $data["agentes"][$j];
                                $json_meta->idtransportador = $cotizaciones[$m]->codTransportadora;

                                $rates[] = array(
                                    'id'      => $id,
                                    'label'   => (($l == 0) ? "" : "Contraentrega ").$cotizaciones[$m]->nombreTransportadora."[".$data["origenes"][$j]."]"."[".$data["destinos"]."]",
                                    'cost'    => $cotizaciones[$m]->total,
                                    'meta_data' => array(
                                        "data"      => base64_encode(json_encode($json_meta)),
                                        "settings"  => base64_encode(json_encode($data['settings'])),
                                    ),
                                );
                            }
                        }
                    } 
                }
            }
            return $rates;
        }
        public function get_guia($data = null)
        {
            if ($data === null) {
                return "Invalid";
            }
            //"idempresa":"' . $this->authenticate_data->cuentas[0]->usuarios[0]->id . '",
            $json_body = '
            {
                "tipo":"obtenerEstadoAuth",
                "token":"' . $this->authenticate_data->token . '",
                "id":"' . $this->authenticate_data->cuentas[0]->usuarios[0]->id . '",
                "guia":"'.$data['guia'].'"
            }
            ';
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => "https://aveonline.co/api/nal/v1.0/guia.php",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => $json_body,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json"
            ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            $this->guia = json_decode($response);
            return json_decode($response);
        }
        public function get_Accounts()
        {
            $tags = [];
            $tags['tag_Cuentas'] = array(
                'id'    => 'tag',
                'type'  => 'tag',
                'title' => __( 'Cuentas'),
            );
            if(!isset($this->authenticate_data->cuentas)){
                $tags['Accounts_info'] = array(
                    'type' => 'info',
                    'title' => __( 'Completa la configuración de la clave api' ),
                );
                return $tags;
            }
            $cuentas = $this->authenticate_data->cuentas;
            $this->user = [];
            $count_user = 0;
            for ($i=0; $i < count($cuentas); $i++) { 
                $usuarios = $cuentas[$i]->usuarios ;
                for ($j=0; $j < count($usuarios); $j++) { 
                    $this->user[$count_user++] = $usuarios[$i]->id;
                    $tags['Cuentas'.$usuarios[$i]->id] = array(
                        'title' => $cuentas[$i]->servicio,
                        'description' => 'User ID:'.$usuarios[$i]->id,
                        'type' => 'checkbox',
                        'class' => 'accounts_',
                    );
                }
            }
            return $tags;
        }
        public function get_Agents($css = '', $css2 = '')
        {
            $tags = [];
            $tags['Agents_tag'] = array(
                'id'    => 'tag',
                'type'  => 'tag',
                'title' => __( 'Agentes'),
            );
            if(!isset($this->authenticate_data->cuentas)){
                $tags['Agents_info'] = array(
                    'type' => 'info',
                    'title' => __( 'Completa la configuración de la clave api' ),
                );;
                return $tags;
            }
            $sw = false;
            $user_yes = [];
            for ($i=0; $i < count($this->user) ; $i++) { 
                if(isset($this->atts['Cuentas'.$this->user[$i]]) && $this->atts['Cuentas'.$this->user[$i]] == "yes"){
                    $sw = true;
                    array_push($user_yes,$this->user[$i]);
                }
            }
            $this->user_yes = $user_yes;
            if(!$sw){
                $tags['Agents_info2'] = array(
                    'type' => 'info',
                    'title' => __( 'Seleccione un Agente' ),
                );
            }else{
                $this->get_agentes($user_yes);
                $agentes = $this->agente_data;
                $ag = [];
                for ($i=0; $i < count($agentes); $i++) { 
                    $ag[$agentes[$i]->idciudad.'_()_'.$agentes[$i]->id] = $agentes[$i]->nombre;
                }
                
                $tags['Agente_selected'] = array(
                    'title' => 'Lista de Agentes',
                    'type' => 'select',
                    'class' => 'agents_',
                    'options' => $ag,
                );
            }
            return $tags;
        }
        public function solicitar_recogida($data = null)
        {
            if($data == null) return;
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
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json"
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            return json_decode($response);
        }
    }
}
load_AveonlineAPI();
