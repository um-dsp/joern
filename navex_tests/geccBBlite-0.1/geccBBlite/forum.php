<?
include("database.php");
include("header.php");
echo "<hr />\n";
$query_selecta_topic="SELECT * FROM geccBB_forum WHERE rispostadel='' ORDER BY id DESC";
$r=mysql_query($query_selecta_topic) or die ("devi eseguire prima <a href=\"install.php\">install.php</a>\n");
echo "<ul>\n";
while($roba=mysql_fetch_assoc($r))
{
    $data=date("Y/m/d-H:i:s", $roba[data]);
    if(!$_GET[azione])
    {	
	echo "<li>";
        echo "<a href=\"forum.php?azione=espandi&id=$roba[id]\">+</a> ";
	echo "<a href=\"leggi.php?id=$roba[id]\">$roba[titolo]</a>, 
	       postato da <i>$roba[postatoda]</i> il $data";
	echo "</li>\n";
    }
    elseif($_GET[azione] == "espandi")
    {
	if($roba[id]==$_GET[id])
	{
	    echo "<li><a href=\"forum.php\">-</a> ";
	    echo "<a href=\"leggi.php?id=$roba[id]\">$roba[titolo]</a>, 
		   postato da $roba[postatoda] il $data";
	    echo "</li>\n";
	    risposte($roba[id]);
	}
	elseif($roba[id]!=$_GET[id])
	{
	    echo "<li>";
	    echo "<a href=\"forum.php?azione=espandi&id=$roba[id]\">+</a> ";
	    echo "<a href=\"leggi.php?id=$roba[id]\">$roba[titolo]</a>,
		   postato da <i>$roba[postatoda]</i> il $data";
	    echo "</li>\n";
	}
    }
}
echo "</ul>\n";

function risposte($id_risposta)
{
    $query_risposte="SELECT * FROM geccBB_forum WHERE rispostadel = '$id_risposta'";
    $result=mysql_query($query_risposte);
    while($risp=mysql_fetch_assoc($result))
    {
	$data=date("Y/m/d-H:i:s", $risp[data]);
	echo "<ul>\n<li><a href=\"leggi.php?id=$risp[rispostadel]&rd=$risp[id]\">
	       $risp[titolo]</a>
	       postato da <i>$risp[postatoda]</i> il $data </li>\n";
	$rere=mysql_query($query_risposte);
	if(mysql_affected_rows() > 0)
	{
	    $query_re_re="SELECT * FROM geccBB_forum WHERE ripostadel = '$risp[id]'";
	    risposte($risp[id]);
	    echo "</ul>\n";
	}
	else
	{
	    echo "</ul>\n";
	    return;
	}
    }
}
include("footer.php");
?>

	
