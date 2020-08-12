<?php

class AveonlineShippingPlugin
{
    private static $initiated = false;
    private static $locations_table;

    public function __construct($file)
    {
        global $wpdb;

        if (!$this->validate_requirements()) {
            return;
        }

        $this->plugin_name = plugin_basename($file);
        self::$locations_table = $wpdb->prefix."wc_aveonline_locations";
    }

    public function init()
    {
        if (self::$initiated) {
            return;
        } else {
            self::$initiated = true;
        }

        require_once WC_AVEONLINE_SHIPPING_DIR . 'src/includes/class-aveonline-shipping-method.php';
        // add plugin settings button
        add_filter('plugin_action_links_' . $this->plugin_name, array('AveonlineShippingPlugin', 'set_action_links'));
        add_filter('woocommerce_shipping_methods', array('AveonlineShippingPlugin', 'add_aveonline_shipping_method'));
        add_action('woocommerce_order_status_changed', array(new WCAveonlineShippingMethod(), 'generate_guide'), 20, 4);

        // if WC-vendor plugin is active, add WC-vendor shipping type
        if (in_array('wc-vendors-pro/wcvendors-pro.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            add_filter('wcv_custom_user_shiping_fields', array('AveonlineShippingPlugin', 'add_wc_vendors_shipping_type'), 10, 1);
        }
        // if Elementor plugin is active, add shipping tracking widget
        if (did_action('elementor/loaded')) {
            // add_action('elementor/widgets/widgets_registered', array('AveonlineShippingPlugin', 'add_elementor_widgets'));
            // add_action('elementor/frontend/after_enqueue_styles', array('AveonlineShippingPlugin', 'add_elementor_widget_styles'));
            // add_action('elementor/frontend/after_register_scripts', array('AveonlineShippingPlugin', 'add_elementor_widget_js'));
        }
    }

    /**
     * Attached to activate_{ plugin_basename( __FILES__ ) } by register_activation_hook()
     * @static
     */
    public static function activate()
    {
        // create locations table
        self::set_locations_table();
        self::update_locations();
        // add hook to create cron that update locations
        // add_action('wc_aveonline_update_locations', 'update_locations');
        // // schedule cron
        // if (!wp_next_scheduled('wc_aveonline_update_locations')) {
        //     // run it now, then run according to daily (24 hours) interval
        //     wp_schedule_event(time(), 'daily', 'wc_aveonline_update_locations');
        // }
        flush_rewrite_rules();
    }

    /**
     * Attached to deactivate_{ plugin_basename( __FILES__ ) } by register_deactivation_hook()
     * @static
     */
    public static function deactivate()
    {
        // // remove scheduled taks
        // $timestamp = wp_next_scheduled('wc_aveonline_update_locations');
        // if ($timestamp) {
        //     wp_unschedule_event($timestamp, 'wc_aveonline_update_locations');
        // }

        // flush rewrite rules
        flush_rewrite_rules();

    }

    /**
     * Attached to uninstall plugin
     * @static
     */
    public static function uninstall()
    {
        require_once WC_AVEONLINE_SHIPPING_DIR."src/includes/class-sql.php";
        // remove locations table
        Sql::dropTable(self::$locations_table);
    }

    private static function set_locations_table() {
        require_once WC_AVEONLINE_SHIPPING_DIR."src/includes/class-sql.php";
        Sql::dropTable(self::$locations_table);
        
        $columns = [
            "id INT NOT NULL AUTO_INCREMENT",
            "label VARCHAR(255) NOT NULL",
            "municipality  VARCHAR(255) NOT NULL",
            "city VARCHAR(255) NOT NULL",
            "region  VARCHAR(255) NOT NULL",
            "code INT(11) NOT NULL",  // dane code
            "full_code INT(11) NOT NULL", // full dane code (city + region)
        ];

        Sql::createTable(self::$locations_table, $columns);
    }

    /**
     * Returns locations table name used to store available locations
     *
     * @return void
     */
    public static function getLocationTableName() {
        return self::$locations_table;
    }

    public static function update_locations() {
        require_once WC_AVEONLINE_SHIPPING_DIR . "src/includes/class-sql.php";
        require_once WC_AVEONLINE_SHIPPING_DIR . "src/aveonline/class-aveonline-api.php";

        try {
            $locations = AveonlineAPI::consultarciudades();
        }catch (\Exception $exception){
            self::log($exception->getMessage());
        }

        if (empty($locations)) {
            self::log("*** ERROR: Aveonline ciudades (consultarciudades) service return empty");
        }

        $locations = $locations["ciudad"];

        foreach ((array) $locations as $column){
            $labelParts = explode("(", $column["datanombre1"]);
            $city = $labelParts[0];
            
            $insert = [
                "label" => $column["datanombre1"],
                "municipality" => $column["datanombre2"],
                "city" => $city,
                "region" => $column["datanombre3"],
                "code" => $column["datanumero5"],
                "full_code" => $column["datanumero6"],
            ];

            if ($city) {
                Sql::upsert(self::$locations_table, $insert);
            }
        }
    }

    /**
     * setup plugin action links
     */
    public static function set_action_links($links)
    {
        $plugin_links = array();
        // settings link
        $plugin_links[] = '<a href="' . admin_url('admin.php?page=wc-settings&tab=shipping&section=wc_aveonline_shipping') . '">' . 'Settings' . '</a>';

        return array_merge($plugin_links, $links);
    }

    /**
     * Display an admin notice
     */
    public static function admin_notice($notice, $type)
    {
        $class = "notice-info";
        if ($type == "error") {
            $class = "notice-error";
        } elseif ($type == "warning") {
            $class = "notice-warning";
        } elseif ($type == "success") {
            $class = "notice-success";
        }

        echo "<div class='notice $class'>" .
        '<p>' . esc_html($notice) . '</p>' .
            '</div>';
    }

    public static function log($msg)
    {
        if (is_array($msg) || is_object($msg)) {
            $msg = print_r($msg, true);
        }

        $logger = new WC_Logger();
        $logger->add('wc-aveonline-plugin', $msg);
    }

    public static function validate_requirements()
    {
        if (version_compare(PHP_VERSION, '7.1.0', '<')) {
            if (is_admin() && !defined('DOING_AJAX')) {
                add_action(
                    'admin_notices',
                    function () {
                        AveonlineShippingPlugin::admin_notice('Woocommerce Aveonline Plugin: plugin fue desarrollado usando PHP 7, algunas funcionalidades podrían fallar en esta versión', 'warning');
                    }
                );
            }
        }

        if (!extension_loaded('soap')) {
            if (is_admin() && !defined('DOING_AJAX')) {
                add_action(
                    'admin_notices',
                    function () {
                        AveonlineShippingPlugin::admin_notice('Woocommerce Aveonline Plugin: Requiere la extensión soap se encuentre instalada', 'error');
                    }
                );
            }
            return false;
        }

        if (!in_array('departamentos-y-ciudades-de-colombia-para-woocommerce/departamentos-y-ciudades-de-colombia-para-woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true)) {
            if (is_admin() && !defined('DOING_AJAX')) {
                add_action(
                    'admin_notices',
                    function () {
                        AveonlineShippingPlugin::admin_notice('Woocommerce Aveonline Plugin: Requiere que se encuentre instalado y activo el plugin: Departamentos y ciudades de Colombia para Woocommerce', 'error');
                    }
                );
            }
            return false;
        }

        if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true)) {
            if (is_admin() && !defined('DOING_AJAX')) {
                add_action(
                    'admin_notices',
                    function () {
                        AveonlineShippingPlugin::admin_notice('Woocommerce Aveonline Plugin: Requiere que se encuentre instalado y activo el plugin: Woocommerce', 'error');
                    }
                );
            }
            return false;
        }

        return true;
    }

    public static function add_aveonline_shipping_method($methods)
    {
        $methods['your_shipping_method'] = null;
        $methods['wc_aveonline_shipping'] = 'WCAveonlineShippingMethod';
        return $methods;
    }

    public static function add_wc_vendors_shipping_type($params)
    {
        // save original shipping types
        $shipping_types = $params['shipping_general']['fields']['_wcv_shipping_type']['options'];

        // set custom shipping type
        $custom_shipping = array('aveonline' => 'Aveonline Shipping');

        // add new shipping type
        $params['shipping_general']['fields']['_wcv_shipping_type']['options'] = array_merge($shipping_types, $custom_shipping);

        return $params;
    }

    public static function add_elementor_widgets()
    {
        // Include Widget files
        require_once WC_AVEONLINE_SHIPPING_DIR . 'src/widgets/shipping_traking.php';
        // Register widget
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new ShippingTracking());
    }

    public static function add_elementor_widget_styles()
    {
        wp_enqueue_style(
            'shippingTrackingStylesheet',
            WC_AVEONLINE_SHIPPING_URL . '/src/widgets/css/shipping_tracking.css'
        );
    }

    public static function add_elementor_widget_js()
    {
        wp_enqueue_script(
            'shippingTrackingJS',
            WC_AVEONLINE_SHIPPING_URL . '/src/widgets/js/shipping_tracking.js',
            array('jquery')
        );
    }
}

function add_aveonline_shipping_shipping_init()
{
    require_once WC_AVEONLINE_SHIPPING_DIR . 'src/includes/class-aveonline-shipping-method.php';
}
add_action( 'woocommerce_shipping_init', 'add_aveonline_shipping_shipping_init' );

function add_aveonline_shipping_shipping_method( $methods ) {
    $methods['your_shipping_method'] = 'WCAveonlineShippingMethod';
    return $methods;
}

add_filter( 'woocommerce_shipping_methods', 'add_aveonline_shipping_shipping_method' );