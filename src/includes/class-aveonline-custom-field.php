<?php
//fecha de recogida
//add
add_action( 'woocommerce_before_order_notes', 'custom_field_checkout' );  
function custom_field_checkout( $checkout ) { 
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
add_action( 'woocommerce_checkout_process', 'validate_custom_field_checkout' );
function validate_custom_field_checkout() {    
    if ( ! $_POST['fecharecogida'] ) {
        wc_add_notice( 'Por favor ingrese la fecha de recogida  ', 'error' );
        return;
    }
    $fecha = strtotime($_POST['fecharecogida']);
    $Sunday = getdate($fecha);
    if ( $Sunday['weekday'] == "Sunday" ) {
    wc_add_notice( 'La fecha que ingreso corresponde a un domingo y la transportadora no recoge ese dia.', 'error' );
        return;
    }
     date_default_timezone_set('America/Bogota'); //Cambia a tu ciudad
    $server = getdate();
    $date = $server['year'].'-'.str_pad($server['mon'], 2, "0", STR_PAD_LEFT).'-'.str_pad($server['mday'], 2, "0", STR_PAD_LEFT);
    $hoy = strtotime($date);
    if(!($fecha > $hoy)){
        wc_add_notice("La fecha que ingreso es igual o menor a la fecha actual", 'error' );
    }
}
//save
add_action( 'woocommerce_checkout_update_order_meta', 'save_custom_field_checkout' );
function save_custom_field_checkout( $order_id ) { 
    if ( $_POST['fecharecogida'] ) update_post_meta( $order_id, '_fecharecogida', esc_attr( $_POST['fecharecogida'] ) );
}
//show
add_action( 'woocommerce_admin_order_data_after_billing_address', 'show_custom_field_checkout', 10, 1 );
function show_custom_field_checkout( $order ) {    
   $order_id = $order->get_id();
   if ( get_post_meta( $order_id, '_fecharecogida', true ) ) echo '<p><strong>Fecha de recogida:</strong> ' . get_post_meta( $order_id, '_fecharecogida', true ) . '</p>';
}


//
//add
// add_action( 'woocommerce_before_order_notes', 'contraentrega_checkbox' );  
// function contraentrega_checkbox( $checkout ) { 
//    $current_user = wp_get_current_user();
//    $contraentrega_checkbox = $current_user->contraentrega_checkbox;
//    woocommerce_form_field( 'contraentrega_checkbox', array(        
//       'type' => 'checkbox',               
//       'label' => __('contraentrega_checkbox'),     
//       'default' => $contraentrega_checkbox,        
//    ), $checkout->get_value( 'contraentrega_checkbox' ) ); 
// }