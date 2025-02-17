<?
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
** $Id: index.php 11 2006-06-27 22:14:54Z sgrayban $
*/

$libPath = "./lib/";

require ( $libPath . "faqforge-config.inc" );
require ( $libPath . "functions.inc" );

if ( ! $helpContext )
{
  $helpContext = $defaultwebTitle;
}

$title = "FaqForge - $helpContext";


$dbLink = mysql_connect ($dbServer, $dbUser, $dbPass);
mysql_select_db ($dbName);

require ( $libPath . "pub_header.inc" );

switch ( $context )
{
case "Topics List":
  {
    require ( $libPath . "pub_topics.inc" );
    break;
  }
case "View Document":
  {
    include ( $libPath . "view-doc.inc" );
    break;
  }
default:
  {
    require ( $libPath . "pub_topics.inc" );
    break;
  }
}

mysql_close ( $dbLink );

?>
