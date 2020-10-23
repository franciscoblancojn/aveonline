<?php
//cedula
//add
add_action( 'woocommerce_before_order_notes', 'cedula_checkout' , 10, 1);  
function cedula_checkout( $checkout ) { 
   $current_user = wp_get_current_user();
   $cedula = $current_user->cedula;
   woocommerce_form_field( 'cedula', array(        
      'type' => 'text',               
      'label' => __('Cedula'),     
      'required' => true,        
      'default' => $cedula,        
   ), $checkout->get_value( 'cedula' ) ); 
}
//valiadate
add_action( 'woocommerce_checkout_process', 'validate_cedula_checkout' , 10, 1);
function validate_cedula_checkout() {    
    if ( ! $_POST['cedula'] ) {
        wc_add_notice( 'Por favor ingrese la Cedula  ', 'error' );
        return;
    }
}
//save
add_action( 'woocommerce_checkout_update_order_meta', 'save_cedula_checkout' , 10, 1);
function save_cedula_checkout( $order_id ) { 
    if ( $_POST['cedula'] ) update_post_meta( $order_id, '_cedula', esc_attr( $_POST['cedula'] ) );
}
//show
add_action( 'woocommerce_admin_order_data_after_billing_address', 'show_cedula_checkout', 10, 1 );
function show_cedula_checkout( $order ) {    
   $order_id = $order->get_id();
   if ( get_post_meta( $order_id, '_cedula', true ) ) echo '<p><strong>Cedula:</strong> ' . get_post_meta( $order_id, '_cedula', true ) . '</p>';
}
// custom_valor_declarado_aveonline
function woocommerce_custom_valor_declarado()
{
    global $woocommerce, $post;
    echo '<div class="product_custom_field">';
    woocommerce_wp_text_input(
        array(
            'id' => '_custom_valor_declarado',
            'placeholder' => 'Custom Valor declarado',
            'label' => __('Custom Valor declarado', 'woocommerce'),
            'desc_tip' => 'true',
            'type' => 'number',
            'min' => '0'
        )
    );
    echo '</div>';
}
add_action('woocommerce_product_options_general_product_data', 'woocommerce_custom_valor_declarado', 10, 1);
// Save Fields
function woocommerce_custom_valor_declarado_save($post_id)
{
    $woocommerce_custom_valor_declarado = $_POST['_custom_valor_declarado'];
    if (!empty($woocommerce_custom_valor_declarado))
        update_post_meta($post_id, '_custom_valor_declarado', esc_attr($woocommerce_custom_valor_declarado));
}
add_action('woocommerce_process_product_meta', 'woocommerce_custom_valor_declarado_save', 10, 1);



//state_guia
//show
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'show_state_guia_order', 10, 1 );
function show_state_guia_order( $order ) {    
    $order_id = $order->get_id();
    $e = get_post_meta( $order_id, 'state_guia', true );
    if ( $e ) {
        echo "State Guia <hr>";
        for ($i=0; $i < count($e) ; $i++) { 
            if(isset($e[$i]["status"]) && $e[$i]["status"] == "ok"){
                $estado = $e[$i]["estado"];
                for ($j=0; $j < count($estado); $j++) { 
                    echo "<p>";
                    echo $estado[$j]["nombre_estado"];
                    echo "<br>";
                    echo $estado[$j]["fecha"];
                    echo "</p>";
                }
                echo "<hr>";
            }
        }
        echo "<hr>";
    }
}