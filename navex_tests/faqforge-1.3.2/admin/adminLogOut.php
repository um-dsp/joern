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
** $Id: adminLogOut.php 16 2006-06-27 23:20:45Z sgrayban $
*/
/*
If you enable register_globals, session_unregister() should be used since session 
variables are registered as global variables when session data is deserialized.
http://www.php.net/manual/en/ref.session.php
*/
session_start();
function session_clear() {
// if session exists, unregister all variables that exist and destroy session
  $exists = "no";
  $session_array = explode(";",session_encode());
  for ($x = 0; $x < count($session_array); $x++) {
    $name  = substr($session_array[$x], 0, strpos($session_array[$x],"|")); 
	if (session_is_registered($name)) {
	  session_unregister('$name');
	  $exists = "yes";
	}
  }
if ($exists != "no") {
    session_destroy();
	}
}
session_clear();
if(!session_is_registered(session_name())) {
	echo"<html><head>";
	echo"<META HTTP-EQUIV=\"Refresh\" CONTENT=\"5; URL=./\">";
	echo"</head><body>";
	echo"<center>";
	echo"<br><br><h2>Successfully logged out<br>of FaqForge Admin Center</h2>";
	echo"</center></body></html>";
}else{
	echo"<h1 style=\"color:red;\">NOT Logged Out</h1>";
	echo"Please contact the system administrator.";
}
?>
