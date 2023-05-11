<?php
if(isset($_GET['param'])){
    $param = $_GET['param'];
    $output = backticks($param);
}
?>
