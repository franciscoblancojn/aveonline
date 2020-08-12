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
                $css = '
                    font-size: 30px;
                    border: 0;
                    background: transparent;
                    font-weight: 600;
                    padding: 0;
                    height: auto;
                    line-height: 1;
                    background: #23282d;
                    color: #fff;
                    border-radius: 0;
                    box-shadow: -100vw 0 #23282d;
                    pointer-events: none;
                    width: 100vw;
                ';
                $css2 = '
                    border: 0;
                    background: transparent;
                    pointer-events: none;
                ';
                $Aveonline_API = new AveonlineAPI($this->settings);
                $accounts = $Aveonline_API->get_Accounts($css,$css2);
                $agents = $Aveonline_API->get_Agents($css,$css2);
                $form_fields = array(
                    'tag_1' => array(
                        'title' => '',
                        'type' => 'text',
                        'css' => $css,
                        'default' => __( 'Settings' ),
                    ),
                    'enabled' => array(
                        'title' => __( 'Enabled' ),
                        'type' => 'checkbox',
                        'desc_tip' => __( 'Enabled/Disabled' ),
                        'default' => 'yes',
                    ),
                    'declared_value' => array(
                        'title' => __( 'Declared value' ),
                        'type' => 'number',
                        'desc_tip' => __( 'Percentage of Declared Value' ),
                        'default' => '100',
                        'custom_attributes' => array(
                            'min' => '0',
                            'max' => '100',
                        ),
                    ),
                    'tag_2' => array(
                        'title' => '',
                        'type' => 'text',
                        'css' => $css,
                        'default' => __( 'API KEY' ),
                    ),
                    'user' => array(
                        'title' => __( 'User' ),
                        'type' => 'text',
                        'desc_tip' => __( 'Registered user in Aveonline' ),
                        'default' => '',
                    ),
                    'password' => array(
                        'title' => __( 'Password' ),
                        'type' => 'password',
                        'desc_tip' => __( 'Password in API Aveonline' ),
                        'default' => '',
                    ),
                    // 'agent_id' => array(
                    //     'title' => __( 'Agent ID' ),
                    //     'type' => 'text',
                    //     'desc_tip' => __( 'Agent ID in API Aveonline' ),
                    //     'default' => '',
                    // ),
                    // 'nit' => array(
                    //     'title' => __( 'Nit' ),
                    //     'type' => 'text',
                    //     'desc_tip' => __( 'Nit of the client registered in Aveonline' ),
                    //     'default' => '',
                    // ),
                );
                $form_fields = array_merge($form_fields,$accounts,$agents);
                $this->form_fields = $form_fields;
                var_dump($this->settings);
            }

            public function calculate_shipping( $package = array()) {
                //if($this-)
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