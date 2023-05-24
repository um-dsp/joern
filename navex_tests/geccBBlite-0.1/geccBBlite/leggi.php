<?
include("database.php");
include("header.php");

$id=$_GET[id];
$rd=$_GET[rd];
if($rd)
{
    $query_caga_messaggio="SELECT * FROM geccBB_forum WHERE id=$rd";
}
elseif(!$rd)
{
    $query_caga_messaggio="SELECT * FROM geccBB_forum WHERE id=$id";
}
echo "<hr />\n";
$w=mysql_query($query_caga_messaggio);
while($leggi=mysql_fetch_assoc($w))
{
    $data=date("Y/m/d-H:i:s", $leggi[data]);
    echo "<b>$leggi[titolo]</b> - <i>$leggi[postatoda]</i> - $data<br />";
    echo nl2br($leggi[testo]);
    if($leggi[rispostadel]==0)
    {
	$idrisp=$leggi[id];
    }
    else
    {
        $idrisp=$leggi[rispostadel];
    }
    ?>
    <hr />
    <form method="post" action="rispondi.php">
    <input type="text" name="postatoda" />nome<br />
    <input type="text" name="titolo" value="<? echo "re: " . $leggi[titolo]; ?>" />titolo<br />
    <textarea name="testo"></textarea>
    <input type="hidden" name="rispostadel" value="<? echo $leggi[id]; ?>" />
    <input type="submit" value="rispondi" />
    </form>
    <?
}

echo "<hr />\n";
$query_caga_thread="SELECT id,rispostadel FROM geccBB_forum WHERE id=$id";
$r=mysql_query($query_caga_thread);
while($risp=mysql_fetch_assoc($r))
{
    sbobba($risp[id], $id, $rd);
}
	    	    
function sbobba($rif, $idpost, $rd)
{
    $query_sbobba="SELECT * FROM geccBB_forum WHERE id=$rif";
    $f=mysql_query($query_sbobba);
    while($sbobba=mysql_fetch_assoc($f))
    {
	$data=date("Y/m/d-H:i:s", $sbobba[data]);
	echo "<ul>\n";
	if(!$rd)
	{
	    echo "<li>$sbobba[titolo]\n";
	    echo " postato da <i>$sbobba[postatoda]</i> il $data</li>\n";
	}
	elseif($rd)
	{
	    echo "<li><a href=\"leggi.php?id=$sbobba[id]\">$sbobba[titolo]</a>\n";
	    echo " postato da <i>$risp[postatoda]</i> il $data</li>\n";
        }
	risposte($sbobba[id], $idpost, $rd);
	echo "</ul>\n";
    }
}

function risposte($rif, $idpost, $rd)
{
    $query_risposte="SELECT * FROM geccBB_forum WHERE rispostadel=$rif";
    $re=mysql_query($query_risposte);
    while($risp=mysql_fetch_assoc($re))
    {
	$data=date("Y/m/d-H:i:s", $risp[data]);
	echo "<ul>\n";
	if($risp[id] != $rd)
	{
	    echo "<li><a href=\"leggi.php?rd=$risp[id]&id=$idpost\">$risp[titolo]</a>\n";
	}
	elseif($risp[id] == $rd)
	{
	    echo "<li>$risp[titolo]";
	}
	echo " postato da <i>$risp[postatoda]</i> il $data</li>\n";
	risposte($risp[id], $idpost, $rd);
	echo "</ul>\n";
    }
}
include("footer.php");
?>
