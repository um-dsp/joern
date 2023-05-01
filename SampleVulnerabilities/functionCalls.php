<?php 

 function sayWelcome($a){
    print 'Hello, welcome '. $a;

}
if(isset($_GET['name'])){
    $param = $_GET['name'] ;
    sayWelcome($param);
}
    
?>