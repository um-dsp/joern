<?php 

function printM($a){
    print($a);
}
if(isset($_GET['name'])){
    $param =   $_GET['name'];
    printM($param);
    # 'Hello, welcome '  .$param  ;

}
?>