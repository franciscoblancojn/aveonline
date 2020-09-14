<?php
//fecha de recogida
//add
add_action( 'woocommerce_before_order_notes', 'field_guias' );  
function field_guias( $checkout ) { 
   $current_user = wp_get_current_user();
   $fecharecogida = $current_user->fecharecogida;
   woocommerce_form_field( 'fecharecogida', array(        
      'type' => 'date',               
      'label' => __('Fecha de Recogida'),     
      'required' => true,        
      'default' => $fecharecogida,        
   ), $checkout->get_value( 'fecharecogida' ) ); 
}
//valiadate
add_action( 'woocommerce_checkout_process', 'validate_field_guias' );
function validate_field_guias() {    
    
}
//save
add_action( 'woocommerce_checkout_update_order_meta', 'save_field_guias' );
function save_field_guias( $order_id ) { 
    if ( $_POST['fecharecogida'] ) update_post_meta( $order_id, '_fecharecogida', esc_attr( $_POST['fecharecogida'] ) );
}
//show
add_action( 'woocommerce_admin_order_data_after_billing_address', 'show_field_guias', 10, 1 );
function show_field_guias( $order ) {    
   $order_id = $order->get_id();
   if ( get_post_meta( $order_id, '_fecharecogida', true ) ) echo '<p><strong>Fecha de recogida:</strong> ' . get_post_meta( $order_id, '_fecharecogida', true ) . '</p>';
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
add_action('woocommerce_product_options_general_product_data', 'woocommerce_custom_valor_declarado');
// Save Fields
function woocommerce_custom_valor_declarado_save($post_id)
{
    $woocommerce_custom_valor_declarado = $_POST['_custom_valor_declarado'];
    if (!empty($woocommerce_custom_valor_declarado))
        update_post_meta($post_id, '_custom_valor_declarado', esc_attr($woocommerce_custom_valor_declarado));
}
add_action('woocommerce_process_product_meta', 'woocommerce_custom_valor_declarado_save');
