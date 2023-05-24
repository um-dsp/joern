<?
include("header.php");
include("database.php");
echo "<hr />\n";
$postatoda=strip_tags($_POST[postatoda]);
$titolo=strip_tags($_POST[titolo]);
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

if(!$postatoda && !$titolo && !$testo)
{
    ?>
    <form method="post" action="scrivi.php">
<input type="text" name="postatoda" />nome<br />
    <input type="text" name="titolo" />titolo<br />
    <textarea name="testo">
    </textarea>
    <input type="submit" value="invia" />
    </form>
    <?
}
elseif($postatoda && $titolo && $testo)
{
    $data=date("U");
    $query_ins_post="INSERT INTO geccBB_forum VALUES('', '$postatoda', '$data', '$titolo', '$testonuovo', '')";
    $i=mysql_query($query_ins_post);
    echo "messaggio inserito correttamente, <a href=\"forum.php\">torna all'elenco dei messaggi...";
}
include("footer.php");
?>
