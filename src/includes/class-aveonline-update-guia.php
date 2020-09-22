<?php
require_once '../../../../../wp-blog-header.php';
$data = json_decode(file_get_contents('php://input'), true);
if(isset($data)){
    $_POST = $data;
}
if(isset($_POST["status"]) && $_POST["status"] == "ok"){
    if(!isset($_POST["guia"])){
        echo "Error, guia requerida";
        exit;
    }
    if(!isset($_POST["pedido_id"])){
        echo "Error, pedido_id requerida";
        exit;
    }
    if(!isset($_POST["estado"])){
        echo "Error, estado requerida";
        exit;
    }

    $guia = $_POST["guia"];
    $order_id = $_POST["pedido_id"];
    $estado = $_POST["estado"];

    $order = wc_get_order($order_id);

    if($order == null){
        echo "Error, invalid pedido_id";
        exit;
    }
    $state_guia = get_post_meta( $order_id, 'state_guia', true );
    if($state_guia == null){
        $state_guia = array();
    }
    $state_guia[] = $_POST;
    echo (update_post_meta( $order_id, 'state_guia', $state_guia ))?"ok":"Error, update fail";
}