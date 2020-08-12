<?php

/**
 * AveonlineAPI class
 *
 * Handless Aveonline API calls and authorization.
 *
 */
class AveonlineAPI 
{
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

        if($load){
            $this->authenticate();
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
    public function get_API_URL_CITY()
    {
        return $this->API_URL_CITY;
    }
    public function get_city()
    {
        if ($this->city_api === null) {
            return null;
        }
        $json_body = '
            {
                "tipo": "listar",
                "data": "' . $this->city_api . '",
                "registros": "999"
            }
        ';
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->get_API_URL_CITY(),
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

        $this->city_data = json_decode($response);

        return json_decode($response);
    }
    public function get_API_URL_QUOTE()
    {
        return $this->API_URL_QUOTE;
    }
    public function quote($data = null)
    {
        if ($data === null) {
            return "Invalid";
        }
        //validar usuario
        if ($this->authenticate_data === null) {
            return "Invalid";
        }
        if ($this->authenticate_data->status !== "ok") {
            return "Invalid";
        }
        //validar agentes
        if ($this->agente_data === null) {
            return "Invalid";
        }
        if ($this->agente_data->status !== "ok") {
            return "Invalid";
        }
        //validar city
        if ($this->city_data === null) {
            return "Invalid";
        }
        if ($this->city_data->status !== "ok") {
            return "Invalid";
        }
        //validar parametros
        if ($data["quantity"] === null) {
            return "Invalid";
        }
        //"destino":"'.$this->city_data->ciudades[0]->nombre.'",
        //"destino":"BOGOTA(CUNDINAMARCA)",
        // "origen":"' . $data["origen"] . '",
        // "destino":"' . $data["destino"] . '",
        // "origen":"MEDELLIN(ANTIOQUIA)",
        // "destino":"BOGOTA(CUNDINAMARCA)",
        $json_body = '
            {
                "tipo":"cotizar",
                "token":"' . $this->authenticate_data->token . '",
                "idempresa":"' . $this->authenticate_data->cuentas[0]->usuarios[0]->id . '",
                
                "origen":"' . $data["origen"] . '",
                "destino":"' . $data["destino"] . '",
                "unidades":"' . $data["quantity"] . '",
                "kilos":"' . $data["weight"] . '",
                "valordeclarado":"' . $data["total"] * $this->p_valordeclarado . '",
                "contraentrega":"' . $data["contraentrega"] . '",
                "valorrecaudo":"' . $data["total"] . '"
            }
        ';
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->get_API_URL_QUOTE(),
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
        $this->quote_data = json_decode($response);
        curl_close($curl);
        return $response;
    }
    public function get_rate($data = null)
    {
        if ($data === null) {
            return array();
        }
        $rates = [];
        $rates[0] = array(
            'id'        => "s2",
            'label'     => "wa",
            'cost'      => 9999999,
            'calc_tax'  => 'per_item',
        );
        return $rates;
        
        // ob_start();
        // var_dump($this->agente_data->agentes);
        // var_dump($this->city_data->ciudades);
        // $_agentes =  ob_get_clean();
        // $rates = [];
        // $rates[0] = array(
        //     'label' => "c".$_agentes,
        //     'cost' => '999999990',
        //     'calc_tax' => 'per_item',
        //     'nombreTransportadora' => "s"
        // );
        // return $rates;
        $_agentes = $this->agente_data->agentes;
        $_ciudades = $this->city_data->ciudades;
        $rates = [];
        $id_rate = 0;
        $data["origen"] = null;
        $data["destino"] = null;
        $data["contraentrega"] = 0;
        $rates[0] = array(
            'id'        => "s",
            'label'     => "w",
            'cost'      => 9999999,
            'calc_tax'  => 'per_item',
        );
        return $rates;
        for ($i = 0 ; $i < count($_agentes) ; $i++) {
            $data["origen"] = $_agentes[$i]->idciudad;
            $rates[0] = array(
                'id'        => "Pre_c",
                'label'     => "Pre_c",
                'cost'      => 9999999,
                'calc_tax'  => 'per_item',
            );
            for ($j = 0 ; $j < count($_ciudades) ; $j++) {
                $data["destino"] = $_ciudades->nombre;
                $rates[1] = array(
                    'id'        => "Pos_c",
                    'label'     => "Pos_c",
                    'cost'      => 9999999,
                    'calc_tax'  => 'per_item',
                );
                return $rates;
                for ($i = 0; $i < 2; $i++) {
                    $data["contraentrega"] = $i;
                    if($data["weight"] <= 0){
                        $data["weight"] = 1;
                    }
                    $data["weight"] = 1;
                    //$quote_data_ = json_decode($this->quote($data));
                    $quote_data_ = $this->quote($data);
                    $rates[$id_rate++] = array(
                        'id'        => "s=".$id_rate,
                        'label'     => "Prueba",
                        'cost'      => 9999999,
                        'calc_tax'  => 'per_item',
                    );
                    // foreach ($quote_data_->cotizaciones as $clave => $e) {
                    //     $rates[$id_rate++] = array(
                    //         'id'        => $id_rate.$e->codTransportadora . "WC_contraentrega_" . (($i == 0) ? "off" : "on"),
                    //         'label'     => $e->nombreTransportadora . "-" . $data["origen"] . "->" . $data["destino"],
                    //         'cost'      => $e->total,
                    //         'calc_tax'  => 'per_item',
                    //         'logo'      => $e->logoTransportadora,
                    //         'nombreTransportadora' => $e->nombreTransportadora
                    //     );
                    // }
                }
            }
        }
        return $rates;
        return array(
            'label' => "none",
            'cost' => '0',
            'calc_tax' => 'per_item'
        );
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
    public function get_Accounts($css = '', $css2 = '')
    {
        $tags = [];
        $tags['Accounts_tag'] = array(
            'title' => '',
            'type' => 'text',
            'css' => $css,
            'default' => __( 'Accounts' ),
        );
        if(!isset($this->authenticate_data->cuentas)){
            $tags['Accounts_info'] = array(
                'title' => '',
                'type' => 'text',
                'css' => $css2,
                'default' => __( 'Complete the api key configuration' ),
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
            'title' => '',
            'type' => 'text',
            'css' => $css,
            'default' => __( 'Agents' ),
        );
        if(!isset($this->authenticate_data->cuentas)){
            $tags['Agents_info'] = array(
                'title' => '',
                'type' => 'text',
                'css' => $css2,
                'default' => __( 'Complete the api key configuration' ),
            );
            return $tags;
        }
        $sw = false;
        $user_yes = [];
        for ($i=0; $i < count($this->user) ; $i++) { 
            if($this->atts['Cuentas'.$this->user[0]] == "yes"){
                $sw = true;
                array_push($user_yes,$this->user[0]);
            }
        }
        if(!$sw){
            $tags['Agents_info2'] = array(
                'title' => '',
                'type' => 'text',
                'css' => $css2,
                'default' => __( 'Select an Agents' ),
            );
        }else{
            $this->get_agentes($user_yes);
            $agentes = $this->agente_data;
            for ($i=0; $i < count($agentes); $i++) { 
                $this->agentes_id = $agentes[$i]->id;
                $tags['Agents_'.$agentes[$i]->id] = array(
                    'title' => $agentes[$i]->nombre,
					'description' => 'Origen:'.$agentes[$i]->idciudad,
                    'type' => 'checkbox',
                    'class' => 'agents_',
                );
            }
        }
        return $tags;
    }
}