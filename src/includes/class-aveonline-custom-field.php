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

 /*
//destino
//add
add_action( 'woocommerce_before_order_notes', 'destino_custom_field_checkout' );  
function destino_custom_field_checkout( $checkout ) { 
   $current_user = wp_get_current_user();
   $destino = $current_user->destino;
   woocommerce_form_field( 'destino', array(        
      'type' => 'select',               
      'label' => __('Destino'),     
      'required' => true,        
      'options' => array(
        "Default" => "Select",
        "s" => "s"
      ),        
   ), $checkout->get_value( 'destino' ) ); 
}
//valiadate
add_action( 'woocommerce_checkout_process', 'destino_validate_custom_field_checkout' );
function destino_validate_custom_field_checkout() {    
    if ( ! $_POST['destino'] ) {
        wc_add_notice( 'Por favor ingrese el destino despues de ingresar la ciudad', 'error' );
    }else if ( $_POST['destino'] == "Default" ) {
        wc_add_notice( 'Por favor ingrese el destino despues de ingresar la ciudad', 'error' );
    }
}
//save
add_action( 'woocommerce_checkout_update_order_meta', 'destino_save_custom_field_checkout' );
function destino_save_custom_field_checkout( $order_id ) { 
    if ( $_POST['destino'] ) update_post_meta( $order_id, '_destino', esc_attr( $_POST['destino'] ) );
}
//show
add_action( 'woocommerce_admin_order_data_after_billing_address', 'destino_show_custom_field_checkout', 10, 1 );
function destino_show_custom_field_checkout( $order ) {    
   $order_id = $order->get_id();
   if ( get_post_meta( $order_id, '_destino', true ) ) echo '<p><strong>Destino:</strong> ' . get_post_meta( $order_id, '_destino', true ) . '</p>';
}
*/

/**
 * Change the checkout city field to a dropdown field.
 */
function ace_change_city_to_dropdown( $fields ) {

	$cities = array(
		'BETANIA(BOGOTA D.C.)',
		'BOGOTA(CUNDINAMARCA)',
		'BOSA(BOGOTA)',
		'ENGATIVA(BOGOTA)',
		'FONTIBON(BOGOTA)',
		'LA UNION(BOGOTA D.C.)',
		'PASQUILLA(BOGOTA D.C.)',
		'PUERTO BOGOTA(CUNDNAMARCA)',
		'SAN JUAN DE SUMAPAZ(BOGOTA D.C.)',
		'SAN JUAN(BOGOTA D.C.)',
		'USAQUEN(BOGOTA)',
	);

	$city_args = wp_parse_args( array(
		'type' => 'select',
		'options' => array_combine( $cities, $cities ),
	), $fields['shipping']['shipping_city'] );

	$fields['shipping']['shipping_city'] = $city_args;
	$fields['billing']['billing_city'] = $city_args;
    
    unset($fields['billing']['billing_state']);
    //var_dump($fields['billing']['billing_city']);
	return $fields;

}
add_filter( 'woocommerce_checkout_fields', 'ace_change_city_to_dropdown' );