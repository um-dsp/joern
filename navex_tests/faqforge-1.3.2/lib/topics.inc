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
** $Id: topics.inc 16 2006-06-27 23:20:45Z sgrayban $
*/

print ("<center><big>Topics List</big><br>\n");

if ( $message )
{
  print ("<br>\n" .
	 "<table bgcolor=\"#ff0000\" cellpadding=\"3\" cellspacing=\"0\" border=\"0\">\n" .
	 "<tr><td>\n" .
	 "<table bgcolor=\"#eeeeee\" cellpadding=\"20\" cellspacing=\"0\" border=\"0\">\n".
	 "<tr><td>$message</td></tr>\n" .
	 "</table>\n" .
	 "</td></tr>\n" .
	 "</table><br>\n\n");
}

print ("<br>\n\n" .
       "<table bgcolor=\"#000000\" cellpadding=\"2\" cellspacing=\"0\" border=\"0\">\n" .
       "<tr><td>\n\n" .
       "<table bgcolor=\"#eeeeee\" cellpadding=\"4\" cellspacing=\"1\" border=\"0\">\n" .
       "<tr bgcolor=\"#dddddd\">\n" .
       "<th width=\"200\">Title</th>\n" .
       "<th width=\"60\">ID</th>\n" .
       "<th width=\"80\">Parent ID</th>\n" .
       "<th width=\"80\">List Order</th>\n" .
       "<th width=\"60\">Publish</th>\n" .
       "<th width=\"60\">Delete</th>\n" .
       "</tr>\n");

$q  = "SELECT * FROM Faq WHERE parent_id = 0 ORDER BY list_order";
$result0 = mysql_query ($q, $dbLink);

while ( $faq0 = mysql_fetch_array ($result0) )
{
  if ( $faq0["title"] == "" ) $faq0["title"] = "???";

  $idList[] = $faq0["id"];

  print ("<tr bgcolor=\"#eeeeee\">\n" .
	 "<td width=\"200\">\n" .
	 "<small><a href=\"./?context=Edit+Page&id=" . 
	 $faq0["id"] . "\">" .
	 $faq0["title"] .
	 "</a></td>\n" .
	 "<td width=\"60\" align=\"center\">" .
	 "<small>" . $faq0["id"] . "</small></td>\n" .
	 "<td width=\"80\" align=\"center\"><small>Top</small></td>\n" .
	 "<td width=\"80\" align=\"right\"><small>" .
	 $faq0["list_order"] . "&nbsp;&nbsp;</td>\n" .
	 "<td width=\"60\" align=\"center\"><small>" .
	 strtoupper($faq0["publish"]) . "</td>\n" .
	 "<td width=\"60\" align=\"center\"><small>" .
	 "<a href=\"./?action=deleteTopic&id=" . $faq0["id"] . "\">" .
	 "<img src=\"../images/del1.gif\" border=\"0\" alt=\"Delete Topic\" " .
	 "align=\"absmiddle\"></a>\n" .
	 "</td></tr>\n");

  $q  = "SELECT * from Faq WHERE parent_id = " . $faq0["id"] . " ";
  $q .= "ORDER BY list_order";
  $result1 = mysql_query($q, $dbLink);
  
  while ( $faq1 = mysql_fetch_array ($result1) )
    {
      if ( $faq1["title"] == "" ) $faq1["title"] = "???";

      $idList[] = $faq1["id"];

      print ("<tr bgcolor=\"#eeeeee\">\n" .
	     "<td width=\"200\">\n" .
	     //"&nbsp;&nbsp;&nbsp;&nbsp;" .
	     " -> " .
	     "<small><a href=\"./?context=Edit+Page&id=" . 
	     $faq1["id"] ."\">" .
	     $faq1["title"] . 
	     "</a></td>\n" .
	     "<td width=\"60\" align=\"center\">" .
	     "<small>" . $faq1["id"] . "</small></td>\n" .
	     "<td width=\"80\" align=\"center\"><small>" . $faq1["parent_id"] .
	     "</small></td>\n" .
	     "<td width=\"80\" align=\"right\"><small>" .
	     $faq1["list_order"] . "&nbsp;&nbsp;</td>\n".
	     "<td width=\"60\" align=\"center\"><small>" .
	     strtoupper($faq1["publish"]) . "</td>\n" .
	     "<td width=\"60\" align=\"center\"><small>" .
	     "<a href=\"./?action=deleteTopic&id=" . $faq1["id"] . "\">" .
	     "<img src=\"../images/del1.gif\" border=\"0\" alt=\"Delete Topic\" ".
	     "align=\"absmiddle\"></a>\n" .
	     "</td></tr>\n");

      $q  = "SELECT * from Faq WHERE parent_id = " . $faq1["id"] . " ";
      $q .= "ORDER BY list_order";
      $result2 = mysql_query($q, $dbLink);
      
      while ( $faq2 = mysql_fetch_array ($result2) )
	{
	  if ( $faq2["title"] == "" ) $faq2["title"] = "???";
	  
	  $idList[] = $faq2["id"];
	  
	  print ("<tr bgcolor=\"#eeeeee\">\n" .
		 "<td width=\"200\">\n" .
		 //"&nbsp;&nbsp;&nbsp;&nbsp;" .
		 " ---> " .
		 "<small><a href=\"./?context=Edit+Page&id=" . 
		 $faq2["id"] ."\">" .
		 $faq2["title"] . 
		 "</a></td>\n" .
		 "<td width=\"60\" align=\"center\">" .
		 "<small>" . $faq2["id"] . "</small></td>\n" .
		 "<td width=\"80\" align=\"center\"><small>" . $faq2["parent_id"] .
		 "</small></td>\n" .
		 "<td width=\"80\" align=\"right\"><small>" .
		 $faq2["list_order"] . "&nbsp;&nbsp;</td>\n".
		 "<td width=\"60\" align=\"center\"><small>" .
		 strtoupper($faq2["publish"]) . "</td>\n" .
		 "<td width=\"60\" align=\"center\"><small>" .
		 "<a href=\"./?action=deleteTopic&id=" . $faq2["id"] . "\">" .
		 "<img src=\"../images/del1.gif\" border=\"0\" alt=\"Delete Topic\" ".
		 "align=\"absmiddle\"></a>\n" .
		 "</td></tr>\n");
	}
    }
}

print ("</table>\n\n" .
       "</td></tr>\n" .
       "</table>\n\n" .
       "<br><br>\n" .
       "<center>\n\n" .
       "<table bgcolor=\"#000000\" cellpadding=\"2\" cellspacing=\"0\" border=\"0\">\n" .
       "<tr><td>\n\n" .
       "<table bgcolor=\"#eeeeee\" cellpadding=\"8\" cellspacing=\"0\" border=\"0\">\n" .
       "<tr><td colspan=\"2\" align=\"center\">" .
       "<strong>Add New Topic:<strong></td></tr>\n" .
       "<form action=\"./\" method=\"post\">\n" .
       "<input type=\"hidden\" name=\"context\" value=\"$context\">\n" .
       "<input type=\"hidden\" name=\"action\" value=\"addNewTopic\">\n" .
       "<tr><td align=\"right\"><small>Title:</td>\n" .
       "<td><small>" .
       "<input type=\"text\" name=\"newTitle\" size=\"24\" maxlength=\"32\"></td></tr>\n" .
       "<tr><td align=\"right\"><small>Context:</td>\n" .
       "<td><small>" .
       "<input type=\"text\" name=\"newContext\" size=\"24\" maxlength=\"32\"></td></tr>\n" .
       "<tr><td align=\"right\"><small>Parent:</td>\n" .
       "<td><small>" .
       "<select name=\"newParent\">\n" .
       "<option value=\"0\">Top Level (id=0)</option>\n");

if ( count ($idList) > 0 ) sort ( $idList );

for ( $i = 0; $i < count ($idList); $i++ )
{
  $q  = "SELECT title FROM Faq WHERE id = " . $idList[$i];
  $titleResult = mysql_query ($q, $dbLink);
  $row = mysql_fetch_row ($titleResult);
  
  print ("<option value=\"" . $idList[$i] . "\">" . $row[0] . " (id=" .
	 $idList[$i] . ")</option>\n");
}

print ("</select>\n" .
       "</td></tr>\n" .
       "<tr><td align=\"right\"><small>List Order:</td>\n" .
       "<td><small>" .
       "<input type=\"text\" name=\"newOrder\" value=\"10000\" size=\"5\" maxlength=\"5\">" .
       "</td></tr>\n" . 
       "<tr><td colspan=\"2\" align=\"center\">" .
       "<input type=\"submit\" value=\"Add Topic\">\n" .
       "</td></tr>\n" .
       "</form>\n" .
       "</table>\n\n" .
       "</td></tr>\n" .
       "</table>\n\n" .
       "</body>\n");

?>
