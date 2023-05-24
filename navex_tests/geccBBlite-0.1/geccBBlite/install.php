<?
include("database.php");
$cosa=$HTTP_POST_VARS[cosa];
if($cosa)
{
  create_tables($dbname);
  echo "<br /><b>installazione completata</b><br />\n";
  echo "vai alla <a href=\"index.php\">vai al forum</a><br />\n";
  echo "ricordati di cancellare il file install.php<br />\n";
  return;
}
elseif (!$cosa)
{
  ?>
  <form method="post" action="install.php">
  <input type="hidden" name="cosa" value="qualsiasicosa" />
  <input type="submit" value="installa"/>
  </form>
  basta premere il bottone
  <?
}
else
{
  printf ("Error creating database: %s\n", mysql_error ());
}

function create_tables($dbname)
{
$db = mysql_select_db("$dbname") or die("Database sbagliato");

$query_table_forum="CREATE TABLE geccBB_forum (
  `id` int(5) NOT NULL auto_increment,
  `postatoda` varchar(255) NOT NULL default '',
  `data` int(12) NOT NULL default '0',
  `titolo` varchar(255) NOT NULL default '',
  `testo` text NOT NULL,
  `rispostadel` int(5) NOT NULL default '0',
  PRIMARY KEY  (`id`)
  )" ;

$result=mysql_query("$query_table_forum") or die ("creazione tabella forum fallita");
}
?>

