<?php 
if( isset( $_GET['param'])){
    $param = $_GET['param'];
    $sql = round($param);
    query('SELECT * FROM employees WHERE employeeId = ' . $sql);
}
?>
