<?php

function slqquery($p){
    query($p);
}
if(isset($_GET['name'])){
    $param = $_GET['name'] ;
    $paramB = $_GET['nameB'] ;
    $a = "hello ".$param;
    $a_san = "hello" . floatval($paramB);
    $another_propagated_san = $a_san;
    $propagated_san = $a_san + $a;

    slqquery($a);
}
?>