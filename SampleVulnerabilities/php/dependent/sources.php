<?php
#this files contains all the sources that ocild trigger a vulknerbaility
#testing purposes
require "functionCalls.php";


if(isset($_GET['name'])){
    if (NULL == $_GET['name']) $_GET['name'] = "Guest! ";
    $param = $_GET['name'] ;
    sayWelcome($param);
}

?>