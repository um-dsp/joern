<?

/* inserisci qua sotto i tuoi dati */
$host="IL.TUO.OST";
$user="USER";
$password="PASSWORD";
$dbname="NOME_DATABASE";

$link=mysql_connect($host, $user, $password) or die("Could not connect: " . mysql_error());
mysql_select_db($dbname) or die ("cosa?");
?>
