<?php

/**
 * AveonlineAPI class
 *
 * Handless Aveonline API calls and authorization.
 *
 */
class AveonlineAPI extends AveonlineShippingPlugin
{
    private $API_URL_AUTHENTICATE = 'https://aveonline.co/api/comunes/v1.0/autenticarusuario.php';
    private $authenticate_data = null; //get object of api 
    private $API_URL_AGENTE = "https://aveonline.co/api/comunes/v1.0/agentes.php";
    private $agente_data = null;
    private $API_URL_CITY = "https://aveonline.co/api/box/v1.0/ciudad.php";
    private $city_data = null; //get object of api
    private $city_api = null;
    private $API_URL_QUOTE = "https://aveonline.co/api/nal/v1.0/generarGuiaTransporteNacional.php";
    private $quote = null;

    private $p_valordeclarado = 100;


    const API_URL_COTIZAR   = 'https://www.aveonline.co/app/modulos/webservices/ws.cotizar.transporte.php?wsdl';
    const API_URL_TRAYECTOS = 'https://www.aveonline.co/app/modulos/webservices/ws.listar.trayectos.php?wsdl';
    const API_URL_CIUDADES  = 'https://www.aveonline.co/app/modulos/webservices/ws.consultar.ciudades.php?wsdl';
    const API_URL_GUIAS     = 'https://aveonline.co/app/modulos/webservices/ws.generar.guia.recaudos.php?wsdl';

    private $user; // api key for API authentication (Provided by Coordinadora)
    private $password; // api password for API authentication (Provided by Coordinadora)

    private $locations; // array of available cities for Coordinadora

    private $testing = true; // flag to switch between testing and production Coordinadora endpoints



    public function __construct($user = "", $password = "", $agentId = "", $city_api = null, $p_valordeclarado = 100)
    {
        // api variables
        $this->user     = $user;
        $this->password = $password;
        $this->agentId  = $agentId;
        $this->city_api = $city_api;
        $this->p_valordeclarado = $p_valordeclarado;
        // caching variables
        $this->locations = array();

        // internal useful variables
        $this->testing = true;
    }

    /**
     * Transfrom alternative location names to simple ones
     *
     * @param string $location
     * @return string
     */
    public static function clean_location_name($location)
    {
        $location = self::clean_string($location);
        return $location == 'Bogota D.C' ? 'Bogota' : $location;
    }

    /**
     * Remove some sepcial charecters from strings just like accents and umlauts
     *
     * @param sting $str
     * @return string
     */
    public static function clean_string($str)
    {
        $forbidden_chars = array("á", "é", "í", "ó", "ú", "Á", "É", "Í", "Ó", "Ú", "ñ", "ü");
        $allowed_chars = array("a", "e", "i", "o", "u", "A", "E", "I", "O", "U", "n", "u");
        $out = str_replace($forbidden_chars, $allowed_chars, $str);
        return $out;
    }


    /**
     * Returns the location info according to Aveonline available locations
     * web services
     *
     * @param string $region Colombia state or department
     * @param string $city  State's city
     * @return string
     */
    public static function get_location($regionCode, $city)
    {
        require_once WC_AVEONLINE_SHIPPING_DIR . "src/includes/class-sql.php";

        // normalize location names
        $city           = self::clean_location_name($city);
        $regionCode     = self::clean_location_name($regionCode);
        $region         = self::clean_location_name(self::get_regions_conversion($regionCode));

        // sanitize variables
        $city   = Sql::clean($city);
        $region  = Sql::clean($region);

        // get locations table name
        $locationsTable = self::getLocationTableName();

        if (empty($region)) {
            logAveonline("Error: No conversions for region code {$regionCode}");
            return false;
        }

        if (empty($city)) {
            logAveonline("Error: No city");
            return false;
        }

        $location = Sql::query("SELECT * FROM {$locationsTable} WHERE city = '{$city}' AND region = '{$region}' LIMIT 1");

        return $location;
    }

    /**
     * Returns the location code according to Aveonline requirements for consuming
     * web services
     *
     * @param string $region Colombia state or department
     * @param string $city  State's city
     * @return string
     */
    public static function get_location_code($regionCode, $city)
    {
        $location = self::get_location($regionCode, $city);

        if (!$location[0]["label"]) logAveonline("*** ERROR: No code for: {$regionCode} -  {$city}");

        return $location[0]["label"];
    }

    public function listartrayectos($courrierId = "")
    {
        $params = [
            "codigo" => $this->user,
            "dsclavex" => $this->password,
            "idtransportador" => $courrierId,
        ];

        return self::api_call(__FUNCTION__, $params, self::API_URL_TRAYECTOS);
    }

    public static function consultarciudades()
    {
        $params = [];

        $out = self::api_call(__FUNCTION__, $params, self::API_URL_CIUDADES);

        return $out;
    }

    /**
     * Consumes the Cotizador_cotizar SOAP endpoint
     * Reutns the cost of a shipping for a pair origin-destination
     *
     * @param string $originLoc   DANE code for the origin city
     * @param string $destLoc     DANE code for the destination city
     * @param array $products       array of shipping'products, they must have height, width, length, weight and quantity
     * @return array
     */
    public function cotizartransporte($originLoc, $destLoc, $products = array(), $meta = array())
    {
        if (empty($products)) {
            return false;
        }

        $courrier       = isset($meta['courrier']) ? $meta['courrier'] : 0;
        $cashOnDelivery = isset($meta["cod"]) ?: 0;
        $valueToCollect = isset($meta["vtc"]) ?: 0;
        $chargeCustomer = isset($meta["chargeCustomer"]) ?: 0;
        $totalValue     = 0;
        $totalQty       = 0;
        $totalWeight    = 0;
        $totalHeight    = 0;
        $totalWidth     = 0;
        $totalLength    = 0;
        $content        = array();

        foreach ($products as $key => $value) {
            $qty = $value["quantity"] > 1 ? $value["quantity"] : 1;
            $totalValue += $value["value"] * $qty;
            $totalQty += $qty;
            $totalWeight += $value["weight"];
        }

        $params = [
            // autentication
            "codigo"                => (string) $this->user,
            "dsclavex"              => (string) $this->password,
            // "idagente"              => (string) $this->agentId,
            // locations
            "origen"                => (string) $originLoc,
            "destino"               => (string) $destLoc,
            // packages
            "unidades"              => $totalQty,
            "kilos"                 => $totalWeight,
            "valordeclarado"        => $totalValue,
            "idsercontraentrega"    => $cashOnDelivery,
            "dsvalorrecaudo"        => $valueToCollect,
            "idasumecosto"          => $chargeCustomer
        ];

        if ($courrier) {
            $params["idtransportador"] = $courrier;
        }

        return self::api_call(__FUNCTION__, $params, self::API_URL_COTIZAR);
    }

    public function generarguia($origin, $receiver, $meta, $products = array())
    {
        if (empty($products)) {
            return false;
        }

        $courrier       = isset($meta["courrier"]) ? $meta["courrier"] : 0;
        $cashOnDelivery = isset($meta["cod"]) ? $meta["cod"] : 0;
        $valueToCollect = isset($meta["vtc"]) ? $meta["vtc"] : 0;
        $chargeCustomer = isset($meta["chargeCustomer"]) ? $meta["chargeCustomer"] : 0;
        $totalValue     = 0;
        $totalQty       = 0;
        $totalWeight    = 0;
        $totalHeight    = 0;
        $totalWidth     = 0;
        $totalLength    = 0;
        $content        = array();

        foreach ($products as $value) {
            $qty = $value["quantity"] > 1 ? $value["quantity"] : 1;

            // array of products names      
            $content[] = $value["name"];

            // calculate totals
            $totalValue         += $value["value"] * $qty;
            $totalQty           += $qty;
            $totalWeight        += $value["weight"];
            $totalHeight        += $value["height"];
            $totalWidth         += $value["width"];
            $totalLength        += $value["length"];
        }

        // TODO: verificar pago contraentrega en parámetros
        // TODO: verificar cálculo de peso, precio y cantidad total

        // Se debe indexar en el array en el mismo orden en que lista el api las variables
        // Se debe respetar ese orden
        $params = array(
            "codigo"                => (string) $this->user,             // autentication
            "dsclavex"              => (string) $this->password,         // autentication
            // locations info
            "origen"                => (string) $origin["location"],
            "destino"               => (string) $receiver["location"],
            // packages info
            "unidades"              => (string) $totalQty,
            "kilos"                 => (string) $totalWeight,    //kg
            "valordeclarado"        => (string) $totalValue,
            "dscontenido"           => self::clean_string(implode(", ", $content)),
            "idagente"              => (string) $this->agentId,          // autentication
            "dsnit"                 => $origin["nit"],
            "dsnombre"              => $origin["name"],
            "dsdir"                 => $origin["address"],
            "dscorreop"             => $origin["email"],
            "dstel"                 => $origin["phone"],
            "dscelular"             => $origin["phone"],
            "dsbarrioo"             => "",
            "dsnombrecompleto"      => $receiver["name"],
            "dsnitre"               => $receiver["nit"],
            "dsdirre"               => $receiver["address"],
            "dsbarrio"              => "",
            "dstelre"               => $receiver["phone"],
            "dscelularre"           => $receiver["phone"],
            "dscorreopre"           => $receiver["email"],
            "dscom"                 => self::clean_string(implode(", ", $content)),
            "idtransportador"       => (string) $courrier,
            "valorrecaudo"          => (string) $valueToCollect,
            "contraentrega"         => (string) $cashOnDelivery, // cambiado de "1"
            "cartaporte"            => "0",
            "idalto"                => (string) $totalHeight,    //cm
            "idancho"               => (string) $totalWidth,     //cm
            "idlargo"               => (string) $totalLength,    //cm
            "dsreferencia"          => (string) $meta["id"], // cambiado de ""
            "dsordendecompra"       => "",
            "bloquegenerarguia"       => 1,
            "relacion_envios"       => 1,
            "enviarcorreos"       => 0,
            "idasumecosto"       => "",
            "guiahija"       => "",
            "accesoila"       => "",
            "servicio"       => "1",    // 1: mensajeria, 2: domicilio
            // "idayvuelta"       => "",
            // "tipopago"       => "",
            // "tipopaquete"       => "",
            // "valordomicilio"       => "",
            // "fechaenvio"       => "",
            // "horaenvio"       => "",
        );

        if ($chargeCustomer) {
            $params["idasumecosto"] = 1;
        }

        logAveonline($params);

        return self::api_call(__FUNCTION__, $params, self::API_URL_GUIAS);
    }

    /**
     * Returns API URL according to flag testing and endpoints to consume
     *
     * @return void
     */
    private function get_api_url()
    {
        $url = self::GUIDE_API_URL;

        return $url;
    }

    private static function parseXmlResponse($xmlString)
    {
        $xml = simplexml_load_string($xmlString, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $array = json_decode($json, true);
        return $array;
    }

    /**
     * Handles all SOAP request
     *
     * @param string $function_name     SOAP endpoint to consume
     * @param array $params             SOAP body 
     * @param string $url               API URL
     * @return void
     */
    private static function api_call($function_name, $params = array(), $url = "")
    {
        if (empty($url)) {
            return;
        }

        $opts = array(
            "trace" => true,
            "soap_version"  => SOAP_1_1,
            "connection_timeout" => 60,
            "encoding" => "utf-8",
            'stream_context' => stream_context_create(array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            )),
            'cache_wsdl' => WSDL_CACHE_NONE
        );
        //aqui tengo que colocar la peticion de la api
        $client = new SoapClient($url, $opts);

        try {
            $response = $client->__soapCall($function_name, $params);
            return self::parseXmlResponse($response);
        } catch (\Exception $exception) {
            echo "======================================================";
            print_r(json_encode($client->__getLastRequest()));
            echo "======================================================";
            throw new  \Exception($exception->getMessage());
        }
    }

    /**
     * List available functions of the SOAP Web Service
     *
     * @param string $url   SOAP endpoint
     * @return void
     */
    private static function get_functions($url)
    {
        if (empty($url)) {
            return;
        }

        $opts = array(
            "trace" => true,
            "soap_version"  => SOAP_1_1,
            "connection_timeout" => 60,
            "encoding" => "utf-8",
            'stream_context' => stream_context_create(array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            )),
            'cache_wsdl' => WSDL_CACHE_NONE
        );

        $client = new SoapClient($url, $opts);

        try {
            $response = $client->__getFunctions();
            return $response;
        } catch (\Exception $exception) {
            throw new  \Exception($exception->getMessage());
        }
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
    public function get_API_URL_AUTHENTICATE()
    {
        return $this->API_URL_AUTHENTICATE;
    }
    public function authenticate()
    {
        $json_body = '
            {
                "tipo":"auth",
                "usuario":"' . $this->user . '",
                "clave":"' . $this->password . '"
            }
        ';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->get_API_URL_AUTHENTICATE(),
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

        $this->authenticate_data = json_decode($response);

        return json_decode($response);
    }
    public function get_API_URL_AGENTE()
    {
        return $this->API_URL_AGENTE;
    }
    public function get_agentes()
    {
        //validar usuario
        if ($this->authenticate_data === null) {
            return "Invalid";
        }
        if ($this->authenticate_data->status !== "ok") {
            return "Invalid";
        }

        $json_body = '
            {
                "tipo":"listarAgentesPorEmpresaAuth",
                "token":"' . $this->authenticate_data->token . '",
                "idempresa":"' . $this->authenticate_data->cuentas[0]->usuarios[0]->id . '"
            }
        ';
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->get_API_URL_AGENTE(),
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
        $this->agente_data = json_decode($response);
        curl_close($curl);
        return $response;
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
    public function FunctionName(Type $var = null)
    {
        # code...
    }
}