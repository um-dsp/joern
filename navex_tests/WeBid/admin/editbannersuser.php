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

$id = $_REQUEST[id];

#//
if($_POST[action] == "update")
{
	if(empty($_POST[name]) || empty($_POST[company]) || empty($_POST[email]))
	{
		$ERR = $ERR_047;
		$USER = $_POST;
	}
	elseif(!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+([\.][a-z0-9-]+)+$",$_POST[email]))
	{
		$ERR = $ERR_008;
		$USER = $_POST;
	}
	else
	{
		#// Update database
		$query = "UPDATE ".$DBPrefix."bannersusers
					  SET
					  name='".addslashes($_POST[name])."',
					  company='".addslashes($_POST[company])."',
					  email='$_POST[email]'
					  WHERE id=$id";
		$res = @mysql_query($query);
		if(!$res)
		{
			print "Error: $query<BR>".mysql_error();
			exit;
		}
		else
		{
			$ID = mysql_insert_id();
			Header("Location: managebanners.php");
			exit;
		}
	}
}
else
{
	#//
	$query = "SELECT * FROM ".$DBPrefix."bannersusers WHERE id=$id";
	$res_ = @mysql_query($query);
	if(!$res_)
	{
		print "$query<BR>".mysql_error();
		exit;
	}
	elseif(mysql_num_rows($res_) > 0)
	{
		$USER = mysql_fetch_array($res_);
	}
}

?>
<HTML>
<HEAD>
<link rel='stylesheet' type='text/css' href='style.css' />
</HEAD>
<body bgcolor="#FFFFFF" text="#000000" link="#0066FF" vlink="#666666" alink="#000066" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr> 
    <td background="images/bac_barint.gif"><table width="100%" border="0" cellspacing="5" cellpadding="0">
        <tr> 
          <td width="30"><img src="images/i_ban.gif" ></td>
          <td class=white><?=$MSG_25_0011?>&nbsp;&gt;&gt;&nbsp;<?=$MSG__0008?></td>
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
	<FORM NAME=conf ACTION=<?=basename($_SERVER[PHP_SELF])?> METHOD=POST>
		<TABLE WIDTH="95%" BORDER="0" CELLSPACING="0" CELLPADDING="1" BGCOLOR="#0083D7">
			<TR>
				<TD ALIGN=CENTER class=title>
					<?php print $MSG_511; ?>
				</TD>
			</TR>
			<TR>
				<TD>

                <TABLE WIDTH=100% CELLPADDING=2 ALIGN="CENTER" BGCOLOR="#FFFFFF">
                  <?php
                  if(!empty($ERR))
                  {
						?>
                  <TR>
                    <TD COLSPAN="2" ALIGN=CENTER BGCOLOR=yellow> <B>
                      <?=$ERR?>
                       </B></TD>
                  </TR>
                  <?php
                  }
						?>
                  <TR VALIGN="TOP">
                    <TD COLSPAN="2" ALIGN=CENTER>
                      <A HREF=managebanners.php><?=$MSG_270?></A>
                      </TD>
                  </TR>
                  <TR VALIGN="TOP">
                    <TD WIDTH="101" HEIGHT="22">
                      <?=$MSG_302?>
                      </TD>
                    <TD HEIGHT="22" WIDTH="531">
                      <INPUT TYPE=text NAME=name SIZE=40 VALUE="<?=$USER[name]?>">
                      </TD>
                  </TR>
                  <TR VALIGN="TOP">
                    <TD WIDTH="101" HEIGHT="22">
                      <?=$MSG__0022?>
                      </TD>
                    <TD HEIGHT="22" WIDTH="531">
                      <INPUT TYPE=text NAME=company SIZE=40 VALUE="<?=$USER[company]?>">
                      </TD>
                  </TR>
                  <TR VALIGN="TOP">
                    <TD WIDTH="101" HEIGHT="22">
                      <?=$MSG_107?>
                      </TD>
                    <TD HEIGHT="22" WIDTH="531">
                      <INPUT TYPE=text NAME=email SIZE=40 VALUE="<?=$USER[email]?>">
                      </TD>
                  </TR>
                  <TR>
                    <TD WIDTH="101">&nbsp; </TD>
                    <TD WIDTH="531">
                      <INPUT TYPE="hidden" NAME="action" VALUE="update">
                      <INPUT TYPE="hidden" NAME="id" VALUE="<?=$id?>">
                      <INPUT TYPE="submit" NAME="submit" VALUE="<?=$MSG_530?>">
                    </TD>
                  </TR>
                  <TR>
                    <TD COLSPAN="2"> </TD>
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
</BODY>
</HTML>