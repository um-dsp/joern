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
include "../includes/config.inc.php";
include "loggedin.inc.php";
include "../includes/countries.inc.php";

$username = $_REQUEST['name'];
$userid = $_REQUEST['userid'];

//-- Data check
if(empty($userid)) {
  header("Location: listusers.php");
  exit;
}

#// Retrieve users signup settings
$query = "SELECT * FROM ".$DBPrefix."usersettings";
$res_s = @mysql_query($query);
if(!$res_s){
  MySQLError($query);
  exit;
}else{
  $REQUESTED_FIELDS = unserialize(mysql_result($res_s,0,"requested_fields"));
  $MANDATORY_FIELDS = unserialize(mysql_result($res_s,0,"mandatory_fields"));
}

if($_POST['action'] == "update" && strstr(basename($_SERVER['HTTP_REFERER']),basename($_SERVER['PHP_SELF']))) {
    if ($_POST['name'] && $_POST['email']) {
        $DATE = explode("/",$_POST['birthdate']);
        if($SETTINGS[datesformat] == "USA") {
          $birth_day = $DATE[1];
          $birth_month = $DATE[0];
          $birth_year = $DATE[2];
        } else {
          $birth_day = $DATE[0];
          $birth_month = $DATE[1];
          $birth_year = $DATE[2];
        }

        if(strlen($birth_year) == 2) {
            $birth_year = "19".$birth_year;
        }
        
        if ($_POST['password'] != $_POST['repeat_password']) {
          $ERR = "ERR_006";
        } elseif (strlen($_POST['email'])<5) { //Primitive mail check
          $ERR = "ERR_110";
        } elseif(!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+([\.][a-z0-9-]+)+$",$_POST['email'])) {
          $ERR = "ERR_008";
        } elseif (!ereg("^[0-9]{2}/[0-9]{2}/[0-9]{4}$",$_POST['birthdate']) &&
        !ereg("^[0-9]{2}/[0-9]{2}/[0-9]{2}$",$_POST['birthdate']) && $MANDATORY_FIELDS['birthdate']=='y' &&   $REQUESTED_FIELDS['birthdate']=='y') { //Birthdate check
          $ERR = "ERR_043";
        } elseif (strlen($_POST['zip'])<4 && $MANDATORY_FIELDS['zip']=='y' && $REQUESTED_FIELDS['zip']=='y') { //Primitive zip check
          $ERR = "ERR_616";
        } elseif (strlen($_POST['phone'])<3 && $MANDATORY_FIELDS['tel']=='y' && $REQUESTED_FIELDS['tel']=='y') { //Primitive phone check
          $ERR = "ERR_617";
        } else {
            $birthdate = "$birth_year"."$birth_month"."$birth_day";
            $sql="UPDATE ".$DBPrefix."users SET 
                  name=\""   .AddSlashes($_POST['name'])
            ."\", email=\""  .AddSlashes($_POST['email'])
            ."\", address=\"".AddSlashes($_POST['address'])
            ."\", city=\""   .AddSlashes($_POST['city'])
            ."\", prov=\""   .AddSlashes($_POST['prov'])
            ."\", country=\"".AddSlashes($_POST['country'])
            ."\", zip=\""    .AddSlashes($_POST['zip'])
            ."\", phone=\""  .AddSlashes($_POST['phone'])
            ."\", birthdate=\"".AddSlashes($birthdate)
            ."\", reg_date=reg_date ";
            if (strlen($_POST['password']) > 0) {
                $sql .=  ", password=\"". md5($MD5_PREFIX.AddSlashes($_POST['password'])) . "\"";
            }
            $sql .=  ",reg_date=reg_date WHERE id='".AddSlashes($userid)."'";
            $res=mysql_query ($sql);
            $updated = 1;

            $URL = $_SESSION["RETURN_LIST"]."?PAGE=".$_SESSION['RETURN_LIST_PAGE'];
            unset($_SESSION["RETURN_LIST"]);
            header("Location: $URL");
            exit;
        }
    } else {
        $ERR = "ERR_112";
    }
}

if(!$_POST['action'] || ($_POST['action'] && $updated)) {
    $query = "select * from ".$DBPrefix."users where id=\"$userid\"";
    $result = mysql_query($query);

    if(!$result){
        print "Database access error: abnormal termination".mysql_error();
        exit;
    }
    $username = mysql_result($result,0,"name");
    
    $nick = mysql_result($result,0,"nick");
    $password = mysql_result($result,0,"password");
    $email = mysql_result($result,0,"email");
    $address = mysql_result($result,0,"address");
    $city = mysql_result($result,0,"city");
    $prov = mysql_result($result,0,"prov");
    $suspended = mysql_result($result,0,"suspended");
    
    $country = mysql_result($result,0,"country");
    $country_list="";
    while (list ($code, $descr) = each ($countries)) {
        $country_list .= "<option value=\"$descr\"";
        if ($descr == $country) {
            $country_list .= " selected";
        }
        $country_list .= ">$descr</option>\n";
    }
    
    $prov = mysql_result($result,0,"prov");
    $zip = mysql_result($result,0,"zip");
    
    $birthdate = mysql_result($result,0,"birthdate");
    $birth_day = substr($birthdate,6,2);
    $birth_month = substr($birthdate,4,2);
    $birth_year = substr($birthdate,0,4);
    $birthdate = $SETTINGS[datesformat] == "USA" ? "$birth_month/$birth_day/$birth_year" : "$birth_day/$birth_month/$birth_year";
    
    $phone = mysql_result($result,0,"phone");
    
    $rate_num = mysql_result($result,0,"rate_num");
    $rate_sum = mysql_result($result,0,"rate_sum");
    if ($rate_num) {
        $rate = round($rate_sum / $rate_num);
    } else {
        $rate=0;
    }
}
?>
<HTML>
<HEAD>
<link rel='stylesheet' type='text/css' href='style.css' />
<STYLE TYPE="text/css">
body {
scrollbar-face-color: #aaaaaa;
scrollbar-shadow-color: #666666;
scrollbar-highlight-color: #aaaaaa;
scrollbar-3dlight-color: #dddddd;
scrollbar-darkshadow-color: #444444;
scrollbar-track-color: #cccccc;
scrollbar-arrow-color: #ffffff;
}</STYLE>
</HEAD>
<body bgcolor="#FFFFFF" text="#000000" link="#0066FF" vlink="#666666" alink="#000066" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr> 
    <td background="images/bac_barint.gif"><table width="100%" border="0" cellspacing="5" cellpadding="0">
        <tr>
          <td width="30"><img src="images/i_use.gif" ></td>
          <td class=white><?=$MSG_25_0010?>&nbsp;&gt;&gt;&nbsp;<?=$MSG_045?></td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td align="center" valign="middle">&nbsp;</td>
  </tr>
  <tr> 
    <td align="center" valign="middle">
      <table width="95%" border="0" cellspacing="0" cellpadding="1" bgcolor="#0083d7" align="center">
        <tr>
          <td align=center colspan=5><table width="100%" border="0" cellspacing="0" cellpadding="1" bgcolor="#0083d7" align="center">
              <tr>
                <td><table width="100%" border="0" cellpadding="5" align="center" cellspacing="0">
              <tr>
                <td colspan="2" valign="top" align="center" class=title><?php print $MSG_511; ?></td>
              </tr>
              <?php
              if($ERR || $updated) {
                print "<TR BGCOLOR=#ffffff><TD></TD><TD WIDTH=486>";
                if($$ERR) print $$ERR;
                if($updated) print "Users data updated";
                print "</TD>
                </TR>";
                }
                ?>
            <form name=details action="edituser.php" method="POST">
              <tr bgcolor="#FFFFFF">
                <td width="204" valign="top" align="right"> <?php print "$MSG_302 *"; ?> </td>
                <td width="486"><input type=text name=name size=40 maxlength=255 value="<?php print $username; ?>">
                </td>
              </tr>
              <tr bgcolor="#FFFFFF">
                <td width="204" valign="top" align="right"> <?php print "$MSG_003 *"; ?> </td>
                <td width="486"> <b> <?php echo $nick; ?> </b> </td>
              </tr>
              <tr>
                <td width="204" valign="top" bgcolor="#EEEEEE" >&nbsp;</td>
                <td width="486" bgcolor="#EEEEEE"> <?php print "$MSG_243" ?> </td>
              </tr>
              <tr>
                <td width="204" valign="top" align="right" bgcolor="#EEEEEE"> <?php print " $MSG_004 *"; ?> </td>
                <td width="486" bgcolor="#EEEEEE"><input type=text name=password size=20 maxlength=20>
                </td>
              </tr>
              <tr>
                <td width="204" valign="top" align="right" bgcolor="#EEEEEE"> <?php print " $MSG_004 *"; ?> </td>
                <td width="486" bgcolor="#EEEEEE"><input type=text name=repeat_password size=20 maxlength=20>
                </td>
              </tr>
              <tr bgcolor="#FFFFFF">
                <td width="204"  valign="top" align="right"> <?php print "$MSG_303 *"; ?> </td>
                <td width="486"><input type=text name=email size=50 maxlength=50 value="<?php echo $email; ?>">
                </td>
              </tr>
              <?php if($MANDATORY_FIELDS['birthdate']=='y') { //Birthdate check ?>
              <tr bgcolor="#FFFFFF">
                <td width="204"  valign="top" align="right"> <?php print "$MSG_252 *"; ?> </td>
                <td width="486"><input type=text name=birthdate size=10 maxlength=10 value="<?php echo $birthdate; ?>">
                </td>
              </tr>
              <?php } ?>
              <tr bgcolor="#FFFFFF">
                <td width="204" valign="top" align="right"> <?php print "$MSG_009 *"; ?> </td>
                <td width="486"><input type=text name=address size=40 maxlength=255 value="<?php echo $address; ?>">
                </td>
              </tr>
              <tr bgcolor="#FFFFFF">
                <td width="204" valign="top" align="right"> <?php print "$MSG_010 *"; ?> </td>
                <td width="486"><input type=text name=city size=40 maxlength=255 value="<?echo $city; ?>">
                </td>
              </tr>
              <tr bgcolor="#FFFFFF">
                <td width="204" valign="top" align="right"> <?php print "$MSG_011 *"; ?> </td>
                <td width="486"><input type=text name=prov size=40 maxlength=255 value="<?echo $prov; ?>">
                </td>
              </tr>
              <tr bgcolor="#FFFFFF">
                <td width="204" valign="top" align="right"> <?php print "$MSG_014 *"; ?> </td>
                <td width="486"><select name=country>
                    <option value=""> </option>
                    <?php  echo $country_list; ?>
                  </select>
                </td>
              </tr>
              <tr bgcolor="#FFFFFF">
                <td width="204" valign="top" align="right"> <?php print "$MSG_012 *"; ?> </td>
                <td width="486"><input type=text name=zip size=15 maxlength=15 value="<?php echo $zip; ?>">
                </td>
              </tr>
              <tr bgcolor="#FFFFFF">
                <td width="204" valign="top" align="right"> <?php print "$MSG_013 *"; ?> </td>
                <td width="486"><input type=text name=phone size=40 maxlength=40 value="<?php echo $phone; ?>">
                </td>
              </tr>
              <tr bgcolor="#FFFFFF">
                <td width="204" valign="top" align="right"> <?php print "$MSG_300"; ?> </td>
                <td width="486"><?php
                  if($suspended == 0)
                  print "$MSG_029";
                  else
                  print "$MSG_030";
                  ?>
                </td>
              </tr>
              <tr bgcolor="#FFFFFF">
                <td width="204" valign="top" align="right">&nbsp;</td>
                <td width="486">
                <A HREF=userfeedback.php?id=<?=$userid?>><?=$MSG_208?></A>
                </td>
              </tr>
              <tr bgcolor="#FFFFFF">
                <td width="204">&nbsp;</td>
                <td width="486"><br>
                  <br>
                  <input TYPE="submit" NAME="act" value="<?php print $MSG_089; ?>">
                </td>
              </tr>
              <input type="hidden" name="userid" value="<?=$_GET[userid]; ?>">
              <input type="hidden" name="offset" value="<?php echo $_GET[offset]; ?>">
             <input type="hidden" name="idhidden" value="<?=($_GET['userid'])?$_GET['userid']:$_POST['idhidden']?>">
             <input type="hidden" name="action" value="update">
            </form>
          </table></TD>
      </TR>
    </TABLE>
    </TD>
</TR>
</TABLE>
</TD>
</TR>
</TABLE>
</BODY>
</HTML>