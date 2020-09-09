<?php


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