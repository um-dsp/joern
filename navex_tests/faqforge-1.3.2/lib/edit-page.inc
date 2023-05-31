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
** $Id: edit-page.inc 16 2006-06-27 23:20:45Z sgrayban $
*/

$faqText = stripslashes(stripslashes($faqText));

$idList = array();

$q = "SELECT id FROM Faq WHERE parent_id = 0 ORDER BY id";
$result0 = mysql_query ($q, $dbLink);

while ( $pidList0 = mysql_fetch_array($result0) ) 
{
  if ( ( ! array_intersect ($idList, $pidList0) ) & 
       ( $pidList0["id"] != $id ) )
    {
      $idList[] = $pidList0["id"];

      /* The nested query and functionality allow for three tier heirarchy...*/

      $q  = "SELECT id FROM Faq where parent_id = " . $pidList0["id"] . " ";
      $q .= "ORDER BY id";
      $result1 = mysql_query ($q, $dbLink);

      while ( $pidList1 = mysql_fetch_array($result1) ) 
	{
	  if ( ( ! array_intersect ($idList, $pidList1) ) & 
	       ( $pidList1["id"] != $id ) )
	    {
	      $idList[] = $pidList1["id"];
	    }
	}
    }
}

print ("<center><big>Section Editor</big><br>\n");

if ( $message )
{
  print ("<br>\n" .
	 "<table bgcolor=\"#ff0000\" cellpadding=\"3\" cellspacing=\"0\" border=\"0\">\n" .
	 "<tr><td>\n" .
	 "<table bgcolor=\"#eeeeee\" cellpadding=\"20\" cellspacing=\"0\" border=\"0\">\n".
	 "<tr><td>$message</td></tr>\n" .
	 "</table>\n" .
	 "</td></tr>\n" .
	 "</table>\n\n");
}

$q = "SELECT * FROM Faq WHERE id = $id";
$result = mysql_query ($q, $dbLink);
$faq = mysql_fetch_array ($result);

print ("</table>\n\n" .
       "</td></tr>\n" .
       "</table>\n\n" .
       "<br><br>\n" .
       "<center>\n\n" .
       "<table bgcolor=\"#000000\" cellpadding=\"2\" cellspacing=\"0\" border=\"0\">\n" .
       "<tr><td>\n\n" .
       "<table bgcolor=\"#eeeeee\" cellpadding=\"8\" cellspacing=\"0\" border=\"0\">\n" .
       "<tr><td colspan=\"2\" align=\"center\">\n" .
       "<strong>Update Topic:<strong></td></tr>\n" .
       "<form action=\"./\" method=post>\n" .
       "<input type=\"hidden\" name=\"context\" value=\"$context\">\n" .
       "<input type=\"hidden\" name=\"id\" value=\"$id\">\n" .
       "<tr><td align=\"right\"><small>Title:</td>\n" .
       "<td><small>" .
       "<input type=\"text\" name=\"topicTitle\" value=\"" . $faq["title"] . 
       "\" size=\"24\" maxlength=\"32\"></td></tr>\n" .
       "<tr><td align=\"right\"><small>Context:</td>\n" .
       "<td><small>" .
       "<input type=\"text\" name=\"topicContext\" value=\"" . $faq["context"] . 
       "\" size=\"24\" maxlength=\"32\"></td></tr>\n" .
       "<tr><td align=right><small>Parent:</td>\n" .
       "<td><small>" .
       "<select name=\"topicParent\">\n" .
       "<option value=\"0\">Top Level (id=0)</option>\n");

sort ( $idList );
for ( $i = 0; $i < count ($idList); $i++ )
{
  $q  = "SELECT title FROM Faq WHERE id = " . $idList[$i];
  $titleResult = mysql_query ($q, $dbLink);
  $row = mysql_fetch_row ($titleResult);
  
  print ("<option value=\"" . $idList[$i] . "\" ");

  if ( $idList[$i] == $faq["parent_id"] )
    print ("selected ");

  print (">" . $row[0] . " (id=" . $idList[$i] . ")</option>\n");
}

print ("</select>\n" .
       "</td></tr>\n" .
       "<tr><td align=\"right\"><small>List Order:</td>\n" .
       "<td><small>" .
       "<input type=\"text\" name=\"topicOrder\" value=\"" . $faq["list_order"] . 
       "\" size=\"5\" maxlength=\"5\">" .
       "</td></tr>\n" . 
       "<tr><td align=\"right\"><small>Publish:</td>\n" .
       "<td><small><input type=\"checkbox\" name=\"topicPublish\"");

if ( $faq["publish"] == "y" ) print (" checked");

print ("></td></tr>\n" .
       "<tr><td colspan=\"2\" align=\"center\">" .
       "<input type=\"submit\" name=\"action\" value=\"Update Topic\">\n" .
       "</td></tr>\n" .
       "</form>\n" .
       "</table>\n\n" .
       "</td></tr>\n" .
       "</table><br><br>\n\n" .
       "<a href=\"./?context=Edit+Page&id=$id&action=addPage\">" .
       "<big>Add Page</big></a><br>\n");

$q = "SELECT * FROM FaqPage WHERE owner_id = $id ORDER BY page_num";
$result = mysql_query ($q, $dbLink);
$pageTotal = mysql_num_rows($result);

while ( $page = mysql_fetch_array ($result) )
{
  print ("<a name=" . $page["page_num"] . ">&nbsp;</a>\n" .
	 "<br>\n\n" .
	 "<table bgcolor=\"#000000\" cellpadding=\"2\" cellspacing=\"0\" border=\"0\">\n" .
	 "<tr><td>\n\n" .
	 "<table width=\"540\" bgcolor=\"#eeeeee\" cellpadding=\"4\" " .
	 "cellspacing=\"0\" border=\"0\">\n" .
	 "<tr><td valign=\"center\"><b>Page " . ($page["page_num"] + 1) .
	 " of $pageTotal");
  
  if ( $page["page_num"] > 0 )
    {
      print ("&nbsp;&nbsp;&nbsp;&nbsp;" .
	     "<a href=\"./?context=Edit+Page&action=moveUp&page_num=" .
	     $page["page_num"] . "&id=$id\">" .
	     "<img src=\"../images/up1.gif\" border=\"0\" alt=\"Move Page Up\" " .
	     "align=\"absmiddle\"></a>\n");
    }
  else print ("&nbsp;&nbsp;&nbsp;&nbsp;");

  if ( $page["page_num"] < ( $pageTotal - 1 ) )
    {
      print ("<a href=\"./?context=Edit+Page&action=moveDown&page_num=" .
	     $page["page_num"] . "&id=$id\">" .
	     "<img src=\"../images/down1.gif\" border=\"0\" alt=\"Move Page Down\" " .
	     "align=\"absmiddle\"></a>\n");
    }

  print ("</b></td>\n" .
	 "<td align=\"right\">" .
	 "<a href=\"./?context=Edit+Page&action=deletePage&page_num=" . 
	 $page["page_num"] . "&id=$id\">" .
	 "<img src=\"../images/del1.gif\" border=\"0\" alt=\"Delete Page\" " .
	 "align=\"absmiddle\"></a>&nbsp;&nbsp;</td>\n" .
	 "</tr>\n" .
	 "<form action=\"./\" method=\"post\">\n" .
	 "<input type=\"hidden\" name=\"context\" value=\"Preview Page\">\n" .
	 "<input type=\"hidden\" name=\"pageTitle\" value=\"" . $faq["title"] .
	 "\">\n" .
	 "<input type=\"hidden\" name=\"faqId\" value=\"" . $faq["id"] . "\">\n" .
	 "<input type=\"hidden\" name=\"id\" value=\"" . $page["id"] . "\">\n" .
	 "<input type=\"hidden\" name=\"pageNum\" value=\"" .
	 $page["page_num"] . "\">\n" .
	 "<tr><td colspan=\"2\">\n" .
	 "<small>\n" .
	 "<textarea name=\"faqText\" wrap=\"virtual\" cols=\"80\" rows=\"20\">");

  if ( ( $faqText ) && ( $page["page_num"] == $pageId ) )
    {
      print ($faqText);
    }
  else
    {
      print ($page["faqText"]);
    }

  print ("</textarea>\n" .
	 "</td></tr>\n" .
	 "<tr><td align=\"center\" colspan=\"2\">\n" .
	 "<input type=\"submit\" value=\"Preview\" name=action>\n" .
	 "</form>\n" .
	 "</td></tr>\n" .
	 "</table>\n\n" .
	 "</td></tr>\n" .
	 "</table>\n\n" .
	 "<br>\n");
}

print ("<a href=\"./?context=Edit+Page&id=$id&action=addPage\">" .
       "<big>Add Page</big></a><br><br>\n" .
       "<a href=\"#top\">[ Top ]</a><br>\n" .
       "</center>\n" .
       "</body>\n" .
       "</html>\n");
?>
