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
if ($id){
	$user = @mysql_result(@mysql_query("SELECT rated_user_id FROM ".$DBPrefix."feedbacks WHERE id='$id'"),0,"rated_user_id");
	$sql="DELETE FROM ".$DBPrefix."feedbacks WHERE id='".$id."'";
	$res=mysql_query ($sql);
	if (!$res){
?>
	<TABLE WIDTH="100%" BGCOLOR="#FFFFFF" BORDER=0 CELLPADDING="0" CELLSPACING="0">
	<TR>
	<TD>
	<BR>
	<CENTER>
	<BR>
	<?php print $tlt_font; ?>
	<B><?php print $MSG_207; ?></B>
	</FONT>
	<BR>
	
	<?php
		echo $err_font.$ERR_066;
	?>
	</FONT>
<?php
	} else {
		#// Update user's record
		$query = "SELECT SUM(rate) as FSUM,count(feedback) as FNUM FROM ".$DBPrefix."feedbacks
				  WHERE rated_user_id='$user'";
		$res = mysql_query($query);
		if(!$res) {
			print "Error: $query<BR>".mysql_error();
			exit;
		} else {
			$SUM = mysql_result($res,0,"FSUM");
			$NUM = mysql_result($res,0,"FNUM");
			
			@mysql_query("UPDATE ".$DBPrefix."users SET rate_sum=$SUM, rate_num=$NUM,reg_date=reg_date WHERE id='$user'");
		}
	}			
}
?>
<script language="JavaScript">
window.location="userfeedback.php?id=<?=$user?>";
</script>
