<?php
function aveonline_shipping_method() {
    if ( ! class_exists( 'WC_aveonline_Shipping_Method' ) ) {
        class WC_aveonline_Shipping_Method extends WC_Shipping_Method {
            public function __construct( $instance_id = 0 ) {
                $this->instance_id 	  = absint( $instance_id );
                $this->id                 = 'wc_aveonline_shipping';
                $this->method_title       = __( 'Aveonline Shipping' );
                $this->method_description = __( 'Servicios especializados en logística' );
                
                $this->title = __( 'Aveonline Shipping' );
                $this->enabled = 'yes';
                
                $this->init();     
            }

            function init() {
                // Load the settings API
                $this->init_settings();
                $this->init_form_fields();
                // Save settings in admin if you have any defined
                add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
            }
            //Fields for the settings page
            function init_form_fields() {
                $this->form_fields = array(
                    'enabled' => array(
                        'title' => __( 'Enabled' ),
                        'type' => 'checkbox',
                        'desc_tip' => __( 'Enabled/Disabled' ),
                        'default' => 'yes',
                    ),
                    'user' => array(
                        'title' => __( 'User' ),
                        'type' => 'text',
                        'desc_tip' => __( 'Registered user in Aveonline' ),
                        'default' => '',
                    ),
                );
                var_dump($this->settings);
            }

            public function calculate_shipping( $package = array()) {
                $this->add_rate( array(
                    'id'      => "Prueba",
                    'label'   => "Prueba",
                    'cost'    => "10",
                    )
                );
            }
        }
    }

    //add your shipping method to WooCommers list of Shipping methods
    add_filter( 'woocommerce_shipping_methods', 'add_aveonline_shipping_method' );
    function add_aveonline_shipping_method( $methods ) {
        $methods['wc_aveonline_shipping'] = 'WC_aveonline_Shipping_Method';
        return $methods;
    }
}
add_action( 'woocommerce_shipping_init', 'aveonline_shipping_method' );