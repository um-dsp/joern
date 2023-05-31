<!-- doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN"-->
<html>
<head>
	<TITLE>FaqForge Admin Center Login</TITLE>
	<link rel=stylesheet type="text/css" href="admin-Login-Only.css">
	<meta name="robots" content="noindex,nofollow">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<!--
/*
** FaqForge
** Copyright (C) 2004-2006 Scott Grayban <sgrayban@users.sourceforge.net>
** Copyright (C) 2004 Andrew C. Bertola <drewb@users.sourceforge.net>
**          All Rights Reserved
** 
** FaqForge is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** FaqForge is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with FaqForge; if not, write to the Free Software
** Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
**
**
** $Id: adminLoginForm.php 16 2006-06-27 23:20:45Z sgrayban $
*/
-->


<body onload="window.document.adminLoginForm.formuser.focus()">
<!--START OF adminLoginForm.php -->
<blockquote>
	<p><br></p><center>
	<!--  -->
	<form method="post" name="adminLoginForm" action="adminLogin.php">
	<?php $loginAttempts = !isset($_POST['loginAttempts'])?1:$_POST['loginAttempts'] + 1;?>
		<input type="hidden" name="loginAttempts" value="<?php echo $loginAttempts;?>">
		<table border="0" cellpadding="5">
			<tr>
				<th colspan=2><center>Login to FaqForge Admin Center</center></th>
			</tr>

			<tr>
				<td align="left">Admin :</td>
				<td>
					<input type="text" name="formuser" value="<?php echo $formuser;?>">
				</td>
			</tr>

			<tr>
				<td>Admin Password :</td>
				<td>
					<input type="password" name="formpassword" value="<?php echo $formpassword;?>">
				</td>
			</tr>

			<tr>
				<td colspan=2>
					<input class="submit" type="submit" name="submit" value="Login to FaqForge Admin Center">
				</td>
			</tr>

			<tr>
				<td align="center" colspan=2>Back to <a href="../">FaqForge</td>
			</tr>
		</table>
	</form>
	</center>
</blockquote>		
<!--END of adminLoginForm.php -->
</body>
</html>
