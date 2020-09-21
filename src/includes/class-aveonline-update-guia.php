<?php

$data = json_decode(file_get_contents('php://input'), true);
if(isset($data)){
    $_POST = $data;
}