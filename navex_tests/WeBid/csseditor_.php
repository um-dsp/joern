<?php
require "./includes/config.inc.php";

$thestyle = $_GET[thestyle];
$sel = $_GET[sel];
$from = $_GET[from];
$font = $_GET[font];
$color = $_GET[color];

if(!empty($_POST)){
  $newruleslist = $_POST[newruleslist];
  $newrules = $_POST[newrules];
  $from = $_POST[from];
  $font = $_POST[font];
  $thepage = $_POST[thepage];
  $thestyle = $_POST[thestyle];
  $sel = $_POST[sel];
  $save = $_POST[save];
}

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

	if($newrules) {
    if(array_key_exists("background-image", $newrules) && !ereg("..\/..\/",$newrules['background-image'], $regs)){
      $newrules['background-image'] = ereg_replace("\((.*)\)","(../../\\1)",$newrules['background-image']);
    }
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
	header("Location: admin/$from");die();
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
<link rel='stylesheet' type='text/css' href='admin/style.css' />
</head>
<body>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td background="./admin/images/bac_barint.gif">
            <table width="100%" border="0" cellspacing="5" cellpadding="0">
                <tr>
                    <td width="30"><img src="./admin/images/i_gra.gif" width="21" height="19"></td>
                    <td class=white><?=$MSG_25_0009?>&nbsp;&gt;&gt;&nbsp;<?=$MSG_5001?></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="center" valign="middle">&nbsp;</td>
    </tr>
</TABLE>
<?php
if(!empty($font)){
	switch($font) {
		case 'standard': $TLT='Edit Font Properties: Standard Font';break;
		case 'error': $TLT='Edit Font Properties: Error Font';break;
		case 'title': $TLT='Edit Font Properties: Titlte Font';break;
		case 'navigation': $TLT='Edit Font Properties: Navigation Font';break;
		case 'small': $TLT='Edit Font Properties: Small Font';break;
		case 'footer': $TLT='Edit Font Properties: Footer Font';break;
	}
}elseif(!empty($color)){
	switch($color) {
		case 'border': $TLT='Edit Color Properties: Border Color';break;
		case 'tittable': $TLT='Edit Color Properties: Table Header';break;
		case 'hg': $TLT='Edit Color Properties: Highlighted Items Background';break;
		case 'a:link': $TLT='Edit Color Properties: Links';break;
		case 'a:visited': $TLT='Edit Color Properties: Visited Links';break;
		case 'body': $TLT='Edit Color Properties: Page Background';break;
		case 'container': $TLT='Edit Color Properties: Container Background';break;
	}
}elseif(!empty($image)){
	switch($image) {
		case 'background': $TLT='Edit Background Image Properties';break;
	}
	
}


echo "<form action='".$_SERVER['PHP_SELF']."' method='post'>\n";
echo "<TABLE WIDTH=100% BORDER=0 CELLSPACING=0 CELLPADDING=0>
<TR><TD>
<h2>$TLT</h2></TD>
<TD ALIGN=RIGHT><a href=./admin/$from>Back&nbsp;>></A></TD></TR></TABLE>";
echo "<hr />\n";

include('csssyntax.inc');

$pn = 0;
while ( list($grp,$propgrp) = each($PropGroups) ) {
	if(($from == 'fonts.php' && ($grp=='Font properties' || $grp=='Color properties')) ||
		($from == 'colors.php' && $grp=='Box properties' && ($color=='border'))||
		($from == 'homepage.php' && $grp=='Box properties' && ($image=='background'))||
		($from == 'colors.php' && $grp=='Color properties' && ($color=='tittable' || $color=='hg' || $color=='a:link' || $color=='a:visited' || $color=='body' || $color=='container'))){
		echo "<h3>$grp:</h3>\n";
		echo "<p style='white-space:nowrap'>";
	
		while ( list(,$prop) = each($propgrp) ) {
				if(($color == 'border' && $grp=='Box properties' && $prop->name=='border-color')||
					($color == 'tittable' && $grp=='Color properties' && $prop->name=='background-color')||
					($color == 'hg' && $grp=='Color properties' && $prop->name=='background-color')||
					($color == 'a:link' && $grp=='Color properties' && $prop->name=='color')||
					($color == 'a:visited' && $grp=='Color properties' && $prop->name=='color') ||
					($color == 'body' && $grp=='Color properties' && $prop->name=='background-color') ||
					($color == 'container' && $grp=='Color properties' && $prop->name=='background-color') ||
					($font == 'standard' && ($grp=='Color properties'  || $grp=='Font properties')) ||
					($font == 'error' && ($grp=='Color properties'  || $grp=='Font properties')) ||
					($font == 'title' && ($grp=='Color properties'  || $grp=='Font properties')) ||
					($font == 'navigation' && ($grp=='Color properties'  || $grp=='Font properties')) ||
					($font == 'footer' && ($grp=='Color properties'  || $grp=='Font properties')) ||
					($image == 'background' && $grp=='Box properties' && ($prop->name=='background-image' || $prop->name=='background-repeat'))){
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
		}
	
		echo "</p><hr />\n";
	}
}

echo "<input type='hidden' name='from' value='$from' />\n";
echo "<input type='hidden' name='font' value='$font' />\n";
echo "<input type='hidden' name='thepage' value='$thepage' />\n";
echo "<input type='hidden' name='thestyle' value='$thestyle' />\n";
echo "<input type='hidden' name='sel' value='$sel' />\n";
echo "<p align=center><input type='submit' name='save' value=' Save changes ' /> \n";
echo "<input type='submit' name='cancel' value=' Cancel ' /></p>\n";

echo "<hr />";

echo "CSS file: $thestyle";

?>
</form>

</body>
</html>
