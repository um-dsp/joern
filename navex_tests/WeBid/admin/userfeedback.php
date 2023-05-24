<?php
/***************************************************************************
 *   copyright				: (C) 2008 WeBid
 *   site					: http://sourceforge.net/projects/simpleauction
 ***************************************************************************/

/***************************************************************************
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version. Although none of the code may be
 *   sold. If you have been sold this script, get a refund.
 ***************************************************************************/

require('../includes/config.inc.php');
include "loggedin.inc.php";
include $include_path.'membertypes.inc.php';
foreach($membertypes as $idm => $memtypearr) {
	$memtypesarr[$memtypearr['feedbacks']]=$memtypearr;
}
ksort($memtypesarr,SORT_NUMERIC);

if (!isset($_POST['auction_id']) && !isset($_GET['auction_id'])) {
	$auction_id = $_SESSION["CURRENT_ITEM"];
} else {
	$_SESSION["CURRENT_ITEM"]=$auction_id;
}
if (empty($_GET['pg'])) {
	$pg=1;
} else {
	$pg=$_GET['pg'];
}

if (($_SERVER['REQUEST_METHOD']=="GET" || $TPL_err) ) {
	$secid = AddSlashes($_GET['id']);
	$sql="SELECT nick, rate_sum, rate_num FROM ".$DBPrefix."users WHERE id='$secid'";
	$res=mysql_query ($sql);
	if ($res) {
		if (mysql_num_rows($res)>0) {
			$arr=mysql_fetch_array ($res);
			$i=0;
			foreach ($memtypesarr as $k=>$l) {
				if($k >= $arr['rate_sum'] || $i++==(count($memtypesarr)-1)) {
					$TPL_rate_ratio_value="<IMG src=\"../images/icons/".$l['icon']."\">";
					break;
				}
			}
		}
	}	
	$sql="SELECT nick, rate_sum, rate_num FROM ".$DBPrefix."users WHERE id='$secid'";
	$res=mysql_query ($sql);
	if ($res) {
		if (mysql_num_rows($res)>0) {
			$arr=mysql_fetch_array ($res);
			$sql="SELECT * FROM ".$DBPrefix."feedbacks WHERE rated_user_id='$secid' ORDER by feedbackdate DESC";
			$res=mysql_query ($sql);
			$i=0;
			while ($arrfeed=mysql_fetch_array($res)) {
				$arr_feedback[$i]["username"]=$arrfeed['rater_user_nick'];
				//$arr_feedback[$i]["title"]=htmlentities(substr($arrfeed['feedback'], 0, 50));
				$arr_feedback[$i]["feedback"]=nl2br($arrfeed['feedback']);
				$arr_feedback[$i]["rate"]=$arrfeed['rate'];
				$arr_feedback[$i]["id"]=$arrfeed["id"];
				
				if (mysql_get_client_info() < 4.1 || !strstr($arrfeed['feedbackdate'],"-")){
					$tmp_year = substr($arrfeed['feedbackdate'],0,4);
					$tmp_month = substr($arrfeed['feedbackdate'],4,2);
					$tmp_day = substr($arrfeed['feedbackdate'],6,2);
				}else{
					$tmp_year = substr($arrfeed['feedbackdate'],0,4);
					$tmp_month = substr($arrfeed['feedbackdate'],5,2);
					$tmp_day = substr($arrfeed['feedbackdate'],8,2);
				}
				if($SETTINGS[datesformat] == "USA") {
					$arr_feedback[$i]["feedbackdate"] = "$tmp_month/$tmp_day/$tmp_year";
				} else {
					$arr_feedback[$i]["feedbackdate"] = "$tmp_day/$tmp_month/$tmp_year";
				}
				$i++;
			}

			$echofeed="";
			$bgcolor = "#FFFFFF";
			for ($ind=($pg-1)*5; $ind+1<=$pg*5 && $ind<=$i-1; $ind++) {
				if($bgcolor == "#FFFFFF") {
					$bgcolor = "#EEEEEE";
				} else {
					$bgcolor = "#FFFFFF";
				}
				
				$echofeed .="<tr bgcolor=$bgcolor><td valign=\"top\"><FONT FACE=\"Verdana,Arial,Helvetica\" SIZE=2>";
				switch($arr_feedback[$ind]['rate']) {
					case 1:
						$echofeed .= "<IMG ALIGN=MIDDLE SRC=\"../images/positive.gif\">&nbsp;";
						break;
					case -1:
						$echofeed .= "<IMG ALIGN=MIDDLE SRC=\"../images/negative.gif\">&nbsp;";
						break;
					case 0:
						$echofeed .= "<IMG ALIGN=MIDDLE SRC=\"../images/neutral.gif\">&nbsp;";
						break;
				}
				$echofeed .="<B>".$arr_feedback[$ind]['username']."</B>&nbsp;&nbsp;$sml_font($MSG_506".$arr_feedback[$ind]['feedbackdate'].")";
				$echofeed .="<BR>";
				$echofeed .="<FONT FACE=\"Verdana,Arial,Helvetica\" SIZE=2>".$arr_feedback[$ind]['feedback'];
				$echofeed .="<BR><BR></td><td align=\"center\">
				<a href=\"edituserfeed.php?id=".$arr_feedback[$ind]["id"]."\"><FONT FACE=\"Verdana,Arial,Helvetica\" SIZE=2>".$MSG_298."</a>
				 | <a href=\"deleteuserfeed.php?id=".$arr_feedback[$ind]["id"]."&user=".$secid."\"><FONT FACE=\"Verdana,Arial,Helvetica\" SIZE=2>".$MSG_008."</a>
				</td></tr>";
				
				$echofeed .= "<TR";
				if($bgcolor == "#FFFFFF"){
					$echofeed .= "  BGCOLOR=#EEEEEE";
				}else{
					$echofeed .= "  BGCOLOR=#FFFFFF";
				}
				$echofeed .= "><TD ALIGN=right colspan=\"2\">";
			}
			if (round(($i/5-floor($i/5))*10)) {
				$num_pages=floor($i/5);
			} else {
				$num_pages=floor($i/5)-1;
			}
			
			for ($ind2=1; $ind2<=$num_pages+1; $ind2++) {
				if ($pg!=$ind2) {
					$echofeed .="<FONT FACE=\"Verdana,Arial,Helvetica\" SIZE=2>
					             <a href=\"userfeedback.php?id=$id&pg=$ind2&faction=show\">
					             $ind2</a>";
					if($ind2 != $num_pages+1) {
						$echofeed .= " | ";
					}
					
				} else {
					$echofeed .="<FONT FACE=\"Verdana,Arial,Helvetica\" SIZE=2 COLOR=\"#777777\">
					             $ind2";
					if($ind2 != $num_pages+1){
						$echofeed .= " | ";
					}
				}
			}
			$echofeed .= "</td></tr>";
			$TPL_feedbacks_num=$i;
			$TPL_nick=$arr['nick'];
			if ($arr['rate_num']) {
				$TPL_total_ratio=round($arr['rate_sum']/$arr['rate_num']);
			} else {
				$TPL_total_ratio=0;
			}
			
			if ($arr['rate_num']) {
				$rate_ratio=round($arr['rate_sum']/$arr['rate_num']);
			} else {
				$rate_ratio=0;
			}
			//$TPL_rate_ratio_value	="<IMG src=\"../images/estrella_".$rate_ratio.".gif\">";
		} else {
			$TPL_err=1;
			$TPL_errmsg=$err_font."$ERR_105";
		}
	} else {
		$TPL_err=1;
		$TPL_errmsg=$err_font."$ERR_106";
	}
	
}

if ($_SERVER['REQUEST_METHOD']=="GET" && $faction=="show") {
	$secid = AddSlashes($id);
	$sql="SELECT * FROM ".$DBPrefix."feedbacks WHERE rated_user_id='$secid' ORDER by feedbackdate DESC";
	$res=mysql_query ($sql);
	$i=0;
	while ($arrfeed=mysql_fetch_array($res)) {
		$arr_feedback[$i]["username"]=$arrfeed['rater_user_nick'];
		$arr_feedback[$i]["title"]=substr($arrfeed['feedback'], 0, 50);
		$arr_feedback[$i]["feedback"]=htmlentities(nl2br($arrfeed['feedback']));
		$arr_feedback[$i]["rate"]=$arrfeed['rate'];
		$i++;
	}
	$nick=$arr_feedback[intval($f_id)]['username'];
	$feedback=$arr_feedback[intval($f_id)]['feedback'];
	$rate=$arr_feedback[intval($f_id)]['rate'];
	
}


?>
<HTML>
<HEAD>
<link rel='stylesheet' type='text/css' href='style.css' />
<SCRIPT Language="JavaScript">
function SubmitForm(){
	document.addfeedback.submit();
}
function ResetForm(){
	document.addfeedback.reset();
}
//-->
</script>
</HEAD>
<body bgcolor="#FFFFFF" text="#000000" link="#0066FF" vlink="#666666" alink="#000066" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr> 
    <td background="images/bac_barint.gif"><table width="100%" border="0" cellspacing="5" cellpadding="0">
        <tr> 
          <td width="30"><img src="images/i_use.gif" ></td>
          <td class=white><?=$MSG_25_0010?>&nbsp;&gt;&gt;&nbsp;<?=$MSG_045?></td>
        </tr>
      </table></td>
  </tr>
  <tr>
    <td align="center" valign="middle">&nbsp;</td>
  </tr>
    <tr> 
    <td align="center" valign="middle">

<TABLE WIDTH="95%" BORDER=0 CELLPADDING="1" CELLSPACING="1" BGCOLOR=#0083D7>
<TR>
  <TD ALIGN=CENTER class=title><?php print $MSG_222; ?></TD>
</TR>
<TR BGCOLOR=#FFFFFF>
    <TD><BR>
      <CENTER>
      <FORM name=addfeedback action="userfeedback.php?id=<?php echo $id; ?>" method="POST">
        <TABLE width="100%" CELLSPACING="0" CELLPADDING="0" BGCOLOR="#EEEEEE">
          <TR>
            <TD><TABLE width="100%" CELLSPACING="1" CELLPADDING="1">
                <tr  BGCOLOR="#FFFFFF">
                  <td ALIGN=right colspan="2"><B> <?php echo "<B>$TPL_nick</B>
		           ($TPL_feedbacks_num)
		           <BR>$TPL_rate_ratio_value
		           <BR><BR>";
		  ?> </B> </td>
                </tr>
                <?php echo stripslashes($echofeed); ?>
              </TABLE></TD>
          </TR>
        </TABLE>
        <INPUT type="hidden" name="TPL_nick_hidden" value="<?php echo $TPL_nick; ?>">
      </FORM>
    </TD>
  </TR>
</TABLE>
</TD>
</TR>
</TABLE>
</BODY>
</HTML>