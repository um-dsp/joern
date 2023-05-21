<?php 

 function sayWelcome($a){
    printStmt('Hello, welcome '. $a);
}
function printStmt($b){
    print( $b);
}
if(isset($_GET['name'])){
    $param = $_GET['name'] ;
   // sayWelcome($param);
}
    
?>
