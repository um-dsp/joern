<?php
/*
** FaqForge
** Copyright (C) 2004-2006 Scott Grayban <sgrayban@users.sourceforge.net>
** Copyright (C) 2000 Andrew C. Bertola <drewb@users.sourceforge.net>
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
** $Id: adminLogin.php 16 2006-06-27 23:20:45Z sgrayban $
*/
require_once("admin-config.php");

// begin SECURITY - DO NOT CHANGE!
// initialize or retrieve the current values for the login variables
$loginAttempts = !isset($_POST['loginAttempts'])?1:$_POST['loginAttempts'];
$formuser = !isset($_POST['formuser'])?NULL:$_POST['formuser'];
$formpassword = !isset($_POST['formpassword'])?NULL:$_POST['formpassword'];
if(($formuser != ADMINUSER ) || ($formpassword != ADMINPASSWORD )) {
	if ($loginAttempts == 0) { /* 3 strikes and they're out */
		$_POST['loginAttempts'] = 1;
		include("adminLoginForm.php");
		exit;
	}else{
		if ( $loginAttempts >= 3 ) {
			echo "<blink><p align='center' style=\"font-weight:bold;font-size:170px;color:red;font-family:sans-serif;\">Log In<br>Failed.</p></blink>";		
			exit;
		}else{
			include("adminLoginForm.php");
			exit;
		}
	}
}
/* test for valid username and password
   if valid then initialize the session
	register the username and password variables
	and redirect to the ADMINHOME page
*/
if (($formuser == ADMINUSER ) && ($formpassword == ADMINPASSWORD )) {	// test for valid username and password
	session_start();
	$_SESSION['adminUser'] = ADMINUSER;
	$_SESSION['adminPassword'] = ADMINPASSWORD;
	$SID = session_id();
	$adminHome = ADMINHOME;
	include($adminHome);
}	
?>
