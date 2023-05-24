<?php
if (!$sel) die('Selector was not defined');
// if (!$userID) die('User ID was not defined');
$sel = trim(str_replace('..','',$sel));
if (!$thestyle) {
	echo "style not defined.";
	die();
}
$filename = str_replace('..','',$thestyle);

if (file_exists($filename)) $cssfile = file($filename);
if ($cssfile) {
	while (list (,$line) = each($cssfile)) {
		$line = trim($line);
		if ($line) {
			eregi('([^\{]*)\{([^\}]*)\}',$line,$reg);
			$selector = trim($reg[1]);
			$rules = trim($reg[2]);
			if ($selector && $rules) {
				$defs = explode(";",$rules);
				while (list(,$rule) = each($defs)) {
					list($prop,$def) = explode(":",$rule);
					$prop = trim($prop);
					$def = trim($def);
					if ($prop!='' && $def!='') $css[$selector][$prop] = $def;
				}
			}
		}
	}
}

if ($css) uksort($css,"selectorsort");

if ($save) {

	if ($newruleslist) {
		while (list($p,$d) = each($newruleslist))
		$css[$sel][$p] = trim($d);
	}

	if ($newrules) {
		while (list($p,$d) = each($newrules))
		if ($css[$sel][$p]==':other:') $css[$sel][$p] = trim($d);
	}

	if ($css) {
		@touch($filename);
		@chmod($filename,0666);
		$fp = fopen($filename,"w") or die("Cannot write to CSS file");
		while (list($s,$r) = each ($css)) {
			$rule = '';
			while (list($p,$d) = each($r))
			if ($d!='') $rule .= $p.": ".$d."; ";
			if ($rule) fwrite($fp,$s."\t{ ".$rule."}\n");
		}
		fclose($fp) or die("Cannot close the CSS file");
		//cleanup();
	}
} elseif($delete) {
	if($css) {
		unset($css[$sel]);
		touch($filename);
		chmod($filename,0666);
		$fp = fopen($filename,"w") or die("Cannot write to CSS file");
		while (list($s,$r) = each ($css)) {
			$rule = '';
			while (list($p,$d) = each($r))
			if ($d!='') $rule .= $p.": ".$d."; ";
			if ($rule) fwrite($fp,$s."\t{ ".$rule."}\n");
		}
		fclose($fp) or die("Cannot close the CSS file");
	}
}

if ($delete) {
	header("Location: editstylesheet.php?thepage=".rawurlencode($thepage)."&thestyle=".rawurlencode($thestyle));die();
}
if ($save || $cancel) {
	header("Location: test.php?thepage=".rawurlencode($thepage)."&thestyle=".rawurlencode($thestyle)."&editOn=yes");die();
}

function cleanup() {
	$dp = opendir('./css');
	$old = strtotime("-3 days");
	while (($file = readdir($dp))!==false) {
		if ($file != "." && $file != "..") {
			$file = "./css/$file";
			if (filemtime($file)<$old) unlink($file);
		}
	}
	closedir($dp);
}

function selectorsort($a,$b) {

	$pseudoVal = array(":link"=>10, ":visited"=>9, ":hover"=>8, ":active"=>7);

	//	-1 = $a<$b 	=>	-1 $a comes first
	//	+1 = $a>$b	=>	+1 $b comes first

	if ($a==$b) return 0;
	$roota = @eregi('body|html',$a);
	$rootb = @eregi('body|html',$b);
	if ($roota && !$rootb) return -1;
	if (!$roota && $rootb) return +1;
	$psa = @eregi(':',$a);
	$psb = @eregi(':',$b);
	if ($psa && !$psb) return +1;
	elseif (!$psa && $psb) return -1;
	elseif ($psa && $psb) {
		$psa = @eregi('.*(:[^ ]*).*',$a,$ra);
		$psb = @eregi('.*(:[^ ]*).*',$b,$rb);
		$psa = $pseudoVal[$ra[1]];
		$psb = $pseudoVal[$rb[1]];
		if ($psa>$psb) return -1;
		elseif ($psa<$psb) return +1;
		else return ($a>$b) ? +1:-1;
	}
	elseif (@eregi($a,$b)) return -1;
	elseif (@eregi($b,$a)) return +1;
	else return ($a>$b) ? +1:-1;
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/2000/REC-xhtml1-20000126/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="cs" lang="cs">
<head>
	<title>CSS Editor of <?php echo htmlspecialchars($sel) ?></title>
	<meta name="copyright" content="pixy@pixy.cz" />
	<script src="js/utils.js" type="text/javascript"></script>

<style type="text/css">
	strong {
		display:block;
		width:20%;
		clear:both;
		float:left;
		text-align:right;
		padding-right:1ex;
		}
	select {
		width:40%
		}
</style>

</head>

<body>

<?php
echo "<form action='$SCRIPT_NAME' method='post'>\n";

echo "<h2>Selector: &quot;".htmlspecialchars($sel)."&quot;</h2>";
echo "<hr />\n";

include('csssyntax.inc');

$pn = 0;
while ( list($grp,$propgrp) = each($PropGroups) ) {

	echo "<h3>$grp:</h3>\n";
	echo "<p style='white-space:nowrap'>";

	while ( list(,$prop) = each($propgrp) ) {
		echo "<strong>$prop->name: </strong>";
		$pn++;
		$found = false;
		$isDefined = ($css[$sel][$prop->name]!='');
		$hideInput = true;

		echo "<select name='newruleslist[$prop->name]' onchange='objGet(\"proptext$pn\").style.display=(this.value==\":other:\")?\"inline\":\"none\";'>\n";
		$vals = $prop->getValueList();
		while ( list(,$pd) = each($vals) ) {
			echo "<option";
			if ($css[$sel][$prop->name]==$pd) {
				echo " selected='selected'";
				$found = true;
			}
			if ($pd=='---') echo " value=''></option>";
			else echo " value='$pd'>$pd</option>\n";
		}
		echo "<option value=''";
		if (!$found && !$isDefined) echo " selected='selected'";
		echo ">* default (".$prop->getDefValue().") </option>\n";
		echo "<option value=':other:'";
		if (!$found && $isDefined) {
			echo " selected='selected'";
			$hideInput = false;
		}
		echo ">* other: $useOther</option>\n";
		echo "</select> \n";

		echo "<input id='proptext$pn' ";
		if ($hideInput) echo "style='display:none' ";
		echo "type='text' name='newrules[$prop->name]' value='".$css[$sel][$prop->name]."' /><br>\n";
	}

	echo "</p><hr />\n";
}

echo "<input type='hidden' name='thepage' value='$thepage' />\n";
echo "<input type='hidden' name='thestyle' value='$thestyle' />\n";
echo "<input type='hidden' name='sel' value='$sel' />\n";
echo "<p><input type='submit' name='save' value=' SAVE ' /> \n<input type='submit' name='delete' value=' DELETE $sel' /> \n";
echo "<input type='submit' name='cancel' value=' cancel ' /></p>\n";

echo "<hr />";

echo "CSS file: $thestyle";

?>
</form>

</body>
</html>
