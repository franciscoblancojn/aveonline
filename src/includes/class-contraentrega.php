<?php

function AVSHME_add_Contraentrega($methods)
{
    $methods[] = 'WC_Contraentrega';
    return $methods;
}
add_filter('woocommerce_payment_gateways', 'AVSHME_add_Contraentrega');
function AVSHME_woocommerce_Contraentrega_gateway()
{
	if (!class_exists('WC_Payment_Gateway')) return;

	class WC_Contraentrega extends WC_Payment_Gateway
	{
        /**
 		 * Class constructor, more about it in Step 3
 		 */
 		public function __construct() {

            $this->id = 'Contraentrega'; // payment gateway plugin ID
            
            //$this->icon = plugin_dir_url( __FILE__ )."../img/c21.svg";
            $this->has_fields = false; // in case you need a custom credit card form
            $this->method_title = 'Contraentrega Gateway';
            $this->title = 'Contraentrega Gateway';
            $this->method_description = 'Description of Contraentrega payment gateway'; // will be displayed on the options page
         
            // gateways can support subscriptions, refunds, saved payment methods,
            // but in this tutorial we begin with simple payments
            $this->supports = array(
                'products'
            );
         
            // Method with all the options fields
            $this->init_form_fields();
         
            // Load the settings.
            $this->init_settings();
            // This action hook saves the settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
         
            // We need custom JavaScript to obtain a token
            //add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
         
            // You can also register a webhook here
            // add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
        }

		/**
		 * Funcion que define los campos que iran en el formulario en la configuracion
		 * de la pasarela de Contraentrega
		 *
		 * @access public
		 * @return void
		 */
		function init_form_fields()
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

        /**
         * You will need it if you want your custom credit card form, Step 4 is about it
         */
        public function payment_fields() {
            ob_start();
            ?>
            <?php
            echo ob_get_clean();
        }
    
        /*
        * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
        */
        public function payment_scripts() {
            
        }
    
        /*
        * Fields validation, more in Step 5
        */
        public function validate_fields() {

        }
    
        /*
        * We're processing the payments here, everything about it is in Step 5
        */
        public function process_payment( $order_id ) {
            global $woocommerce;
            $order = new WC_Order( $order_id );
        
            // Mark as on-hold (we're awaiting the cheque)
            $order->update_status('processing', __( 'Awaiting cheque payment', 'woocommerce' ));
        
            // Remove cart
            $woocommerce->cart->empty_cart();
        
            // Return thankyou redirect
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url( $order )
            );
        }
    
        /*
        * In case you need a webhook, like PayPal IPN etc
        */
        public function webhook() {
        }
    }

}
add_action('plugins_loaded', 'AVSHME_woocommerce_Contraentrega_gateway', 0);