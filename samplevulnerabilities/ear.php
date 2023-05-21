<?php 
if( isset( $_GET['param'])){
    $param = $_GET['param'];
    echo "welcome";
    header('Location' + $param);
    exit ; 
}

?>
