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
include $include_path.'status.inc.php';

#//
$ERR = "&nbsp;";

$id = $_REQUEST[id];
if($_POST[action] == "update" && strstr(basename($_SERVER['HTTP_REFERER']), basename($_SERVER['PHP_SELF'])))
{
  if((!empty($_POST[password]) && empty($_POST[repeatpassword])) ||
  (empty($_POST[password]) && !empty($_POST[repeatpassword])))
  {
    $ERR = $ERR_054;
    $USER = $_POST;
  }
  elseif($_POST[password] != $_POST[repeatpassword])
  {
    $ERR = $ERR_006;
    $USER = $_POST;
  }
  else
  { 
    #// Update
    $query = "update ".$DBPrefix."adminusers set";
    if(!empty($_POST[password]))
    {
      $PASS = md5($MD5_PREFIX.$_POST[password]);
      $query .= " password='$PASS', ";
    }
    $query .= " status=".intval($_POST[status])."
                where id=$_POST[id]";

    $res = @mysql_query($query);
    if(!$res)
    {
      print "Error: $query<BR>".mysql_error();
      exit;
    }
    else
    {
      Header("Location: adminusers.php");
      exit;
    }
  }
}

#//
$query = "SELECT * FROM ".$DBPrefix."adminusers where id=$id";
$res = @mysql_query($query);
if(!$res)
{
  print "Error: $query<BR>".mysql_error();
  exit;
}
$USER = mysql_fetch_array($res);
$CREATED = substr($USER[created],4,2)."/".
substr($USER[created],6,2)."/".
substr($USER[created],0,4);
if($USER[lastlogin] == 0)
{
  $LASTLOGIN = $MSG_570;
}
else
{
  $LASTLOGIN = substr($USER[lastlogin],4,2)."/".
  substr($USER[lastlogin],6,2)."/".
  substr($USER[lastlogin],0,4)." ".
  substr($USER[lastlogin],8,2).":".
  substr($USER[lastlogin],10,2).":".
  substr($USER[lastlogin],12,2);
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
          <td class=white><?=$MSG_25_0010?>&nbsp;&gt;&gt;&nbsp;<?=$MSG_525?></td>
        </tr>
      </table></td>
  </tr>
  <tr>
    <td align="center" valign="middle">&nbsp;</td>
  </tr>
    <tr> 
    <td align="center" valign="middle">

<TABLE BORDER=0 WIDTH=100% CELLPADDING=0 CELLSPACING=0 BGCOLOR="#FFFFFF">
<TR>
<TD align="center">
<BR>
<FORM NAME=conf ACTION=<?=basename($_SERVER['PHP_SELF'])?> METHOD=POST>
<TABLE WIDTH="95%" BORDER="0" CELLSPACING="0" CELLPADDING="1" BGCOLOR="#0083D7" ALIGN="CENTER">
  <TR>
    <TD ALIGN=CENTER class=title>
      <?php print $MSG_562; ?>
    </TD>
  </TR>
  <TR>
    <TD>
      <TABLE WIDTH=100% CELLPADDING=2 ALIGN="CENTER" BGCOLOR="#FFFFFF">
        <TR>
          <TD COLSPAN="2"><A HREF="./increments.php" CLASS="links">
            </A>
            <TABLE WIDTH=100% CELLPADDING=2 ALIGN="CENTER" BGCOLOR="#FFFFFF">
              <TR>
                <TD COLSPAN="2" ALIGN=CENTER><B>
                  <?php print $ERR; ?>
                  </B></TD>
              </TR>
              <TR>
                <TD WIDTH="123"> 
                  <?php print $MSG_003; ?>
                  </TD>
                <TD WIDTH="411">
                  <B>
                  <?php print $USER[username]; ?>
                  </B> </TD>
              </TR>
              <TR>
                <TD WIDTH="123"> 
                  <?php print $MSG_558; ?>
                  </TD>
                <TD WIDTH="411">
                  <?php print $CREATED;?>
                  </TD>
              </TR>
              <TR>
                <TD WIDTH="123"> 
                  <?php print $MSG_559; ?>
                  </TD>
                <TD WIDTH="411">
                  <?php print $LASTLOGIN; ?>
                  </TD>
              </TR>
              <TR>
                <TD COLSPAN="2" BGCOLOR="#EEEEEE">
                  <?php print $MSG_563; ?>
                  </TD>
              </TR>
              <TR>
                <TD WIDTH="123" BGCOLOR="#EEEEEE">
                  <?php print $MSG_004; ?>
                  </TD>
                <TD WIDTH="411" BGCOLOR="#EEEEEE">
                  <INPUT TYPE="PASSWORD" NAME="password" SIZE="25">
                </TD>
              </TR>
              <TR>
                <TD WIDTH="123" BGCOLOR="#EEEEEE">
                  <?php print $MSG_564; ?>
                  </TD>
                <TD WIDTH="411" BGCOLOR="#EEEEEE">
                  <INPUT TYPE="PASSWORD" NAME="repeatpassword" SIZE="25">
                </TD>
              </TR>
              <TR>
                <TD WIDTH="123">
                  <?php print $MSG_565; ?>
                  </TD>
                <TD WIDTH="411">
                  <INPUT TYPE="radio" NAME="status" VALUE="1"
      <?php if($USER[status] == 1) print " CHECKED";?>
      >
                  
                  <?php print $MSG_566; ?>
                  
                  <INPUT TYPE="radio" NAME="status" VALUE="2"
      <?php if($USER[status] == 2) print " CHECKED";?>
      >
                  
                  <?php print $MSG_567; ?>
                   </TD>
              </TR>
              <TR>
                <TD WIDTH=123>&nbsp;</TD>
                <TD WIDTH="411">&nbsp;</TD>
              </TR>
              <TR>
                <TD WIDTH=123>
                  <INPUT TYPE="hidden" NAME="action" VALUE="update">
                  <INPUT TYPE="hidden" NAME="id" VALUE="<?=$id?>">
                </TD>
                <TD WIDTH="411">
                  <INPUT TYPE="submit" NAME="act" VALUE="<?php print $MSG_530; ?>">
                </TD>
              </TR>
              <TR>
                <TD WIDTH=123></TD>
                <TD WIDTH="411"> </TD>
              </TR>
            </TABLE>
            <A HREF="./increments.php" CLASS="links">
            </A>  </TD>
        </TR>
        <TR>
          <TD WIDTH=169>
            <INPUT TYPE="hidden" NAME="action" VALUE="update">
          </TD>
          <TD WIDTH="365">&nbsp; </TD>
        </TR>
        <TR>
          <TD WIDTH=169></TD>
          <TD WIDTH="365"> </TD>
        </TR>
      </TABLE>
    </TD>
  </TR>
</TABLE>
</FORM>
</TD>
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