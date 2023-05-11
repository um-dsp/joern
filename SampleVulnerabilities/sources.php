<?php
#this files contains all the sources that ocild trigger a vulknerbaility
#testing purposes
$server_vars = $_SERVER;
$env_vars = $_ENV;
$coockies = $_COOKIE;


if(isset($_GET['name'])){
    if (NULL == $_GET['name']) $_GET['name'] = "Guest! ";
    $param = $_GET['name'] ;
    echo $param;
}
if(isset($_POST['name'])){
    if (NULL == $_POST['name']) $_POST['name'] = "Guest! ";
}
if(isset($_REQUEST['name'])){
        If (NULL == $_REQUEST['name']) $_REQUEST['name'] = "Guest! ";
}
        

?>