<?php

function add_tracking_notification()
{
    echo '<h5 style="margin-bottom:10px">This is a custom message</h5>';
}

//add_action('woocommerce_checkout_process', 'add_tracking_notification');
class WCAveonlineShippingMethod extends WC_Shipping_Method
{
    const SETTINGS_KEY = 'wc_aveonline_shipping_settings';
    const POSTMETA_GUIDE = '_shipping_guide';

    public function __construct($instance_id = 0)
    {
        $this->id                 = 'wc_aveonline_shipping';
        $this->instance_id        = absint($instance_id);
        $this->method_title       = __('Aveonline Shipping');
        $this->method_description = __('Servicios especializados en logística');
        $this->p_valordeclarado = 100;

        $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
        //$this->enabled = 'yes';
        $this->title = isset($this->settings['title']) ? $this->settings['title'] : __('Aveonline Shipping');

        $this->debug = false;

        $settings = get_option(self::SETTINGS_KEY);

        $this->_api_key = isset($settings["api_key"]) ? $settings["api_key"] : "";
        $this->_api_pwd = isset($settings["api_pass"]) ? $settings["api_pass"] : "";
        $this->_client_id = isset($settings["client_id"]) ? $settings["client_id"] : "";

        // $this->availability = 'including';
        // $this->countries = array(
        //     'CO'
        // );

        $this->supports = array(
            'settings',
            'shipping-zones',
            'instance-settings',
        );

        //$this->init_form_fields();
        $this->init();
    }
    /**
     * Init your settings
     *
     * @access public
     * @return void
     */
    public function init()
    {
        $this->init_form_fields();
        $this->init_settings();
        // Save settings in admin if you have any defined
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }
    /**
     * Define settings field for this shipping
     * @return void 
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'aveonline_settings' => array(
                'type' => 'aveonline_shipping_settings',
            )
        );
    }

    /**
     * Load settings HTML for this shipping
     * @return void 
     */
    public function generate_aveonline_shipping_settings_html()
    {
        include(WC_AVEONLINE_SHIPPING_DIR . 'src/templates/admin/settings.php');
    }

    /**
     * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
     *
     * @access public
     * @param mixed $package
     * @return void
     */
    public function calculate_shipping($package = array())
    {
        // $ue = new WC_contraentrega();
        // ob_start();
        // var_dump(($package));
        // $u = ob_get_clean();
        // $this->add_rate( array(
        //     'id'        => "ss",
        //     'label'     => "ss",
        //     'cost'      => "9999999999",
        //     'calc_tax'  => 'per_item'
        // ) );
        // return;
        // $this->add_rate(array(
        //     'id'        => "none",
        //     'label'     => "none".$u,
        //     'cost'      => 999999999999,
        //     'calc_tax'  => 'per_item'
        // ));
        // return;
        require_once WC_AVEONLINE_SHIPPING_DIR . 'src/aveonline/class-aveonline-api.php';
        $aveonlineSettings = get_option(self::SETTINGS_KEY);
        //load api
        $api = new AveonlineAPI(
            $aveonlineSettings["user"],
            $aveonlineSettings["password"],
            $aveonlineSettings["agent_id"],
            $package["destination"]["city"],
            //strtoupper($package["destination"]["city"]."(".$package["destination"]["state"]),
            $this->p_valordeclarado
        );
        //get autenticate
        $authenticate = $api->authenticate();
        //get agentes for origen
        $agentes = $api->get_agentes();
        //get code city
        $city = $api->get_city();
        //load weight
        $weight = 0;
        foreach ($package["contents"] as $clave => $valor) {
            $_product = wc_get_product($valor["product_id"]);
            $weight += $_product->get_weight();
        }
        //load rates WC_contraentrega_on
        $rates = $api->get_rate(
            array(
                "quantity" => count($package["contents"]),
                "weight" => $weight,
                "total" => $package['cart_subtotal'],
            )
        );
        //return;
        // ob_start();
        // var_dump($rates);
        // $result = ob_get_clean();
        // $this->add_rate(array(
        //     'id'        => "none",
        //     'label'     => $result,
        //     'cost'      => 9999999,
        //     'calc_tax'  => 'per_item'
        // ));
        // return;
        foreach ($rates as $rate) {
            if ($rate['nombreTransportadora'] !== null)
                $this->add_rate($rate);
        }
        return;
        $this->add_rate(array(
            'id'        => "none",
            'label'     => "none",
            'cost'      => 0,
            'calc_tax'  => 'per_item'
        ));
        return;
        $u =  plugin_dir_path(__FILE__) . 'src/includes/class-aveonline-contraentrega.php';
        $this->add_rate(array(
            'id'        => "u",
            'label'     => "U=" . $u,
            'cost'      => "10",
            'calc_tax'  => 'per_item'
        ));
        return;
        require_once WC_AVEONLINE_SHIPPING_DIR . 'src/aveonline/class-aveonline-api.php';

        if ($this->debug) logAveonline("################ START CALCULATE SHIPPING ################");

        // Initializing variables
        $cost = 0;
        $time = 0;
        $products = array();
        $vendorAddress = array();

        // Get coordinadora settings
        $aveonlineSettings = get_option(self::SETTINGS_KEY);

        if (
            empty($aveonlineSettings["user"]) ||
            empty($aveonlineSettings["password"]) ||
            empty($aveonlineSettings["agent_id"])
        ) {
            logAveonline("Missing some plugin settings (user, password)");
            return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', false, $package, $this);
        }

        // get an instance of the api caller
        $api = new AveonlineAPI(
            $aveonlineSettings["user"],
            $aveonlineSettings["password"],
            $aveonlineSettings["agent_id"]
        );

        // get destination location
        $countryDest = $package["destination"]["country"];
        $regionDest = $package['destination']['state'];
        $cityDest = $package['destination']['city'];
        $destCode = $api->get_location_code($regionDest, $cityDest);

        // get vendor information
        $vendorId = $package["vendor_id"];
        $vendor = get_userdata($vendorId);
        $vendorShippingType = get_user_meta($vendorId, '_wcv_shipping_type');

        // check if Aveonline shippint type is enabled when WC Vendors plugin is install
        if (in_array('wc-vendors-pro/wcvendors-pro.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            if (!$vendorShippingType || $vendorShippingType[0] != "aveonline") {
                if ($this->debug) logAveonline("Aveonline shipping is inactivated for vendor: {$vendor->display_name}");
                return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', false, $package, $this);
            }
        }

        if ($vendor->roles[0] == "wcfm_vendor") {
            $regionVendor = $api->clean_location_name(WC()->countries->get_base_state());
            $cityVendor  = $api->clean_location_name(WC()->countries->get_base_city());
        } else {
            $regionVendor = $api->clean_location_name(get_user_meta($vendorId, 'billing_state', true));
            $cityVendor = $api->clean_location_name(get_user_meta($vendorId, 'billing_city', true));
        }
        $originCode = $api->get_location_code($regionVendor, $cityVendor);

        if ($this->debug) {
            logAveonline(sprintf(
                "Vendor: %s (%s) sending from %s - %s : %s",
                (string) $vendor->display_name,
                (string) $vendorId,
                (string) $regionVendor,
                (string) $cityVendor,
                (string) $originCode
            ));

            logAveonline(sprintf(
                "Sending to: %s - %s : %s",
                $regionDest,
                $cityDest,
                $destCode
            ));
        }

        // validate origin locations
        if (empty($originCode)) {
            if ($this->debug) logAveonline("ERROR: No origin code");
            return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', false, $package, $this);
        }

        // validate destination locations
        if ('CO' !== $countryDest) {
            if ($this->debug) logAveonline("ERROR: invalid coutnry {$countryDest}");
            return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', false, $package, $this);
        } else if (empty($destCode)) {
            if ($this->debug) logAveonline("ERROR: No destination code");
            return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', false, $package, $this);
        }

        // Get car items
        $carItems = WC()->cart->get_cart();

        // Loop through cart items to prepare products array needed by APi
        foreach ((array) $carItems as $cartItem) {
            // Get an instance of the WC_Product object and cart quantity
            $product = $cartItem['data'];

            // Only consider products for the actual vendor
            if (get_post_field('post_author', $product->get_id()) != $vendorId) continue;

            $qty = intval($cartItem['quantity']) > 1 ?: 1;

            // Get product dimensions
            $weight = wc_get_weight($product->get_weight(), 'kg');
            $value = $product->get_price();

            if (!$weight || !$value) {
                if ($this->debug) logAveonline("ERROR: product without weight or value " . $product->get_id());
                return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', false, $package, $this);
            }

            $products[] = array(
                "quantity" => $qty,
                "weight" => $weight,
                "value" => $value,
            );
        }

        try {
            $quotation = $api->cotizartransporte($originCode, $destCode, $products);
            // if ($this->debug) logAveonline($quotation);
        } catch (\Exception $e) {
            logAveonline("*** ERROR GETTING QUITATION: {$e->getMessage()}");
        }


        foreach ((array) $quotation["servicio"] as $key => $value) {
            // get quotation info
            $cost = $value["grantotal"];
            $courrier = $value["transportadora"];
            $courrierId = $value["codigotransportadora"];

            // // look for latest date
            $days = $value["diasentrega"] ? $value["diasentrega"] : 0;
            $timeStr = $days > 1 ? "Entrega estimada: {$days} días" : "Entrega estimada: {$days} día";

            // add store tax for shipping
            $cost += isset($aveonlineSettings["collection_tax"]) ? ((int) $aveonlineSettings["collection_tax"]) : 0;

            if (!$cost) {
                if ($this->debug) {
                    logAveonline('ERROR: missing shipping cost');
                    logAveonline($value);
                }
                return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', false, $package, $this);
            }

            // build rate
            $rate = array(
                'id' => "{$this->id}-{$this->instance_id}-{$vendorId}-{$courrierId}",
                'label' => $courrier . ($days ? " ($timeStr) " : ""),
                'cost' => $cost,
                'package' => $package,
                'meta_data' => array(
                    'courrier_id' => $courrierId,
                    'vendor_id' => $vendorId
                )
            );

            $rateCopy = $rate;
            unset($rateCopy["package"]);
            logAveonline($rateCopy);

            // Register the rate
            $this->add_rate($rate);
        }

        if ($this->debug) logAveonline("################ END CALCULATE SHIPPING ################");
    }

    public function generate_guide($orderId, $oldStatus, $newStatus, WC_Order $order)
    {
        require_once WC_AVEONLINE_SHIPPING_DIR . 'src/aveonline/class-aveonline-api.php';

        if ($this->debug) logAveonline("################ START GENERATE GUIDE ################");

        $existingGuide = get_post_meta($orderId, self::POSTMETA_GUIDE, true);

        // TODO: improve validation according to free shipping and other conditions
        if ($order->has_shipping_method($this->id) && (empty($existingGuide) || count($existingGuide) === 0) && 'processing' === $newStatus) {
            // Get coordinadora settings
            $aveonlineSettings = get_option(self::SETTINGS_KEY);

            // Array que almacena la o las guias generadas
            update_post_meta($orderId, self::POSTMETA_GUIDE, array());

            // get an instance of the api caller
            $api = new AveonlineAPI(
                $aveonlineSettings["user"],
                $aveonlineSettings["password"],
                $aveonlineSettings["agent_id"]
            );

            // array with meta data (shipping settings)
            $meta = array();
            // origin data
            $origin = array();
            // destination data
            $receiver = array();
            // packages data
            $packages = array();

            $collectionTax = $aveonlineSettings["collection_tax"] ? $aveonlineSettings["collection_tax"] : 0; // 11 digits

            // get shippings costs by vendor id
            $shippingsArray = $order->get_items('shipping');
            $shippings = [];
            foreach ($shippingsArray as $key => $value) {
                $courrierId = $value->get_meta('courrier_id');
                $shippings[$courrierId]["total"] = $value->get_total();
                $shippings[$courrierId]["taxes"] = $value->get_total_tax();
            }
            $meta["courrier"] = reset($order->get_items('shipping'))->get_meta('courrier_id');

            // receiver info
            if ($order->get_shipping_first_name()) {
                $receiver['name'] = sprintf(
                    "%s %s",
                    $order->get_shipping_first_name(),
                    $order->get_shipping_last_name()
                );
            } else {
                $receiver['name'] = sprintf(
                    '%s %s',
                    $order->get_billing_first_name(),
                    $order->get_billing_last_name()
                );
            }

            if ($order->get_shipping_address_1()) {
                $receiver['address'] = sprintf(
                    "%s %s",
                    $order->get_shipping_address_1(),
                    $order->get_shipping_address_2()
                );
            } else {
                $receiver['address'] = sprintf(
                    "%s %s",
                    $order->get_billing_address_1(),
                    $order->get_billing_address_2()
                );
            }

            $receiver["phone"]  = $order->get_billing_phone();
            $receiver["nit"]    = get_post_meta($order->get_id(), "_billing_documento_de_identid")[0];
            $receiver["email"]  = $order->get_billing_email();

            // receiver location (destination)
            $destRegion = $order->get_shipping_state() ? $order->get_shipping_state() : $order->get_billing_state();
            $destCity   = $order->get_shipping_city() ? $order->get_shipping_city() : $order->get_billing_city();
            $receiver["location"]   = $api->get_location_code($destRegion, $destCity);

            // Get the dimetion unit set in Woocommerce
            $dimensionUnit = get_option('woocommerce_dimension_unit');
            // Calculate the rate to be applied for volume in m3
            if ($dimensionUnit == 'mm') {
                $dimensionRate = pow(10, 1);
            } elseif ($dimensionUnit == 'cm') {
                $dimensionRate = 1;
            } elseif ($dimensionUnit == 'm') {
                $dimensionRate = pow(10, -2);
            }

            // set payment method
            $meta["cod"]            = $order->get_payment_method() == "cod" ? 1 : 0;
            $meta["notify"]         = $aveonlineSettings["notify_customer"] ? $aveonlineSettings["notify_customer"] : 0; // default 1
            $meta["chargeCustomer"] = $aveonlineSettings["charge_customer"] ? $aveonlineSettings["charge_customer"] : 0; // default 1

            // get order items
            $items = $order->get_items();
            // TODO: optimizar uso de get_userdata para vendors
            // TODO: optimizar queries de vendors si se tiene $vendors con la información
            // iterate over each cart item
            foreach ((array) $items as $key => $item) {
                $product = $item->get_product();
                $qty = intval($item->get_quantity()) > 1 ? intval($item->get_quantity()) : 1;

                // get vendor data
                $vendorId = get_post_field('post_author', $product->get_id());
                // $vendor = get_userdata($vendorId);
                // logAveonline("VENDOR ===============================");
                // logAveonline($vendor);
                // logAveonline("VENDOR ===============================");

                if (!isset($packages[$vendorId])) $packages[$vendorId] = array();

                // group packages by vendor
                $packages[$vendorId][] = array(
                    "height"    => $product->get_height() / $dimensionRate,
                    "width"     => $product->get_width() / $dimensionRate,
                    "length"    => $product->get_length() / $dimensionRate,
                    "weight"    => wc_get_weight($product->get_weight(), 'kg'),
                    "value"     => $product->get_price(),
                    "id"        => $product->get_id(),
                    "name"      => $item->get_name(),
                    "quantity"  => $qty,
                );
            }

            // iterate over each vendor to generates a shipping guide by vendor
            foreach ((array) $packages as $vendorId => $vendorPackages) {
                $vendor = get_userdata($vendorId);

                $totalPrice = 0;
                foreach ($vendorPackages as $package) {
                    $totalPrice += $package["value"];
                }

                // origin location (source)
                if ($vendor->roles[0] == "wcfm_vendor") {
                    $regionVendor   = $api->clean_location_name(WC()->countries->get_base_state());
                    $cityVendor    = $api->clean_location_name(WC()->countries->get_base_city());
                    $vendorAddress = sprintf(
                        "%s %s",
                        WC()->countries->get_base_address(),
                        WC()->countries->get_base_address_2()
                    );
                    $vendorName    = get_bloginfo('name');
                } else {
                    $regionVendor   = $api->clean_location_name(get_user_meta($vendorId, 'billing_state', true));
                    $cityVendor    = $api->clean_location_name(get_user_meta($vendorId, 'billing_city', true));
                    $vendorAddress = get_user_meta($vendorId, 'billing_address_1', true);
                    $vendorName    = $vendor->display_name;
                }

                // set origin parameters
                $origin["name"]     = $vendorName;
                $origin["address"]  = $vendorAddress;
                $origin["nit"]      = $aveonlineSettings["nit"];
                $origin["email"]    = get_user_meta($vendorId, 'billing_email', true);
                $origin["phone"]    = get_user_meta($vendorId, 'billing_phone', true);
                $origin["location"] = $api->get_location_code($regionVendor, $cityVendor);


                // set reference for shipping (order_id)
                $meta["id"] = $order->get_id();
                // set unique reference for shipping (vendor_id - order_id)
                // $meta["id"] = $vendorId."-".$order->get_id();

                // set value to collect 
                // TODO: agregar el costo del envío
                // TODO: revisar si hay algun otro caso de valueToCollect por ejemplo con charge_customer
                $meta["vtc"] = $meta["cod"] ? ($totalPrice + $collectionTax) : 0;

                if ($this->debug) {
                    logAveonline("Origin:");
                    logAveonline($origin);
                    logAveonline("Destination:");
                    logAveonline($receiver);
                    logAveonline("Meta:");
                    logAveonline($meta);
                    logAveonline("Packages:");
                    logAveonline($vendorPackages);
                }

                try {
                    $guide = $api->generarguia($origin, $receiver, $meta, $vendorPackages);
                    $existingGuides = get_post_meta($orderId, self::POSTMETA_GUIDE, true);
                    $existingGuides[] = $guide["guia"]["numguia"];
                    if ($this->debug) logAveonline($guide);
                    EventHttpRequest::cancel();
                    update_post_meta($orderId, self::POSTMETA_GUIDE, $existingGuides);
                    if ($this->debug) logAveonline(get_post_meta($orderId, self::POSTMETA_GUIDE, true));
                } catch (\Exception $e) {
                    logAveonline("ERROR GENERATING GUIDE: {$e->getMessage()}");
                    throw new Exception("Error generating guide");
                }

                if (empty($guide)) return;

                // if (!$guide->codigo_remision) return;

                // $guide_number = $guide->codigo_remision;

                // $guide_url = sprintf( __( 'Código de seguimiento Coordinadora ('.$vendorName.')  <a target="_blank" href="%1$s">'.$guide_number.'</a>.' ), "http://sandbox.coordinadora.com/vmi/?guia=$guide_number" );

                // update_post_meta($order->get_id(), self::POSTMETA_GUIDE, $guide_number);
                // $order->add_order_note($guide_url);
            }
        }
        if ($this->debug) logAveonline("################ END GENERATE GUIDE ################");
    }
}