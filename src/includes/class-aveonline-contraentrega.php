<?php
add_action( 'plugins_loaded', 'init_WC_contraentrega' );
function init_WC_contraentrega() {
    class WC_contraentrega extends WC_Payment_Gateway {
        /**
        * Constructor de Pago a destino
        *
        * @access public
        * @return void
        */
        public function __construct($show_load=true)
        {
            $this->id = 'WC_contraentrega';
            $this->icon = plugin_dir_url( __FILE__ ) . '../img/class-aveonline-contraentrega.png';
            $this->has_fields = true;
            $this->method_title = __('Pago a Destino');
            $this->method_description = '<img src="'.plugin_dir_url( __FILE__ ) . '../img/class-aveonline-contraentrega.png'.'" />'.__('Pago a Destino');

            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->enabled = $this->get_option( 'enabled' );
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            
	        add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
        }
        /**
        * init_form_fields iniciando el Formulario de Configuracion del metodo de Pago
        *
        * @access public
        * @return void
        */
        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __( 'Enable/Disable', 'woocommerce' ),
                    'type' => 'checkbox',
                    'label' => __( 'Enable', 'woocommerce' ),
                    'default' => isset($this->enabled)?$this->enabled:'yes'
                ),
                'title' => array(
                    'title' => __( 'Title', 'woocommerce' ),
                    'type' => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                    'default' => isset($this->title)?$this->title:__( 'Pago Contraentrega', 'woocommerce' ),
                    'desc_tip'      => true,
                ),
                'description' => array(
                    'title' => __( 'Customer Message', 'woocommerce' ),
                    'type' => 'textarea',
                    'default' => __("Pago a Destino, de esta forma usted paga al momento de recibir el pedido")
                )
            );
        }
        function process_payment( $order_id ) 
        {
            global $woocommerce;
            $order = new WC_Order( $order_id );
        
            // Mark as on-hold (we're awaiting the cheque)
            $order->update_status('on-hold', __( 'Awaiting cheque payment', 'woocommerce' ));
        
            // Remove cart
            $woocommerce->cart->empty_cart();
        
            // Return thankyou redirect
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url( $order )
            );
        }
        public function payment_scripts() {
            
            if ( ! is_cart() && ! is_checkout()) {
                return;
            }
        
            if ( 'no' === $this->enabled ) {
                return;
            }
            
	        //wp_enqueue_script( 'WC_contraentrega_js', plugin_dir_url( __FILE__ )."../js/WC_contraentrega.js",array(),null,true );

        }
     
    }
    function add_WC_contraentrega( $methods ) {
        $methods[] = 'WC_contraentrega'; 
        return $methods;
    }
    
    add_filter( 'woocommerce_payment_gateways', 'add_WC_contraentrega' );
}