<?
include("header.php");
include("database.php");
$postatoda=strip_tags($_POST[postatoda]);
$titolo=$_POST[titolo];
$testo=strip_tags($_POST[testo]);

$destroy=explode(" ", $testo);
$pezzi=count($destroy);
for($i=0; $i<$pezzi; $i++)
{
    if(preg_match("/(^(http:\/\/).+(\..+)$)/i", $destroy[$i]))
    {
	$testonuovo=$testonuovo . " <a href=\"$destroy\">$destroy[$i]</a> \n";
    }
    else
    {
	$testonuovo=$testonuovo . " $destroy[$i]";
    }
}

$rispostadel=$_POST[rispostadel];
$data=date("U");
$query_ins_risposta="INSERT INTO geccBB_forum VALUES('', '$postatoda', '$data', '$titolo', '$testonuovo', '$rispostadel')";
$r=mysql_query($query_ins_risposta);
echo "<hr />\nrisposta inserita correttamente, <a href=\"forum.php\">torna al forum</a>...\n";
include("footer.php");
?>
