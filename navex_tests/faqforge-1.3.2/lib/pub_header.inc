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
** $Id: pub_header.inc 17 2006-06-27 23:24:45Z sgrayban $
*/

print ("<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n");
print ("<!-- Copyright 2000 Andrew C. Bertola, All Rights Reserved -->\n");
print ("<!-- Copyright 2004-2006 Scott Grayban, All Rights Reserved -->\n");
print ("<html>\n" .
       "<head>\n" .
       "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">\n" .
       "<style type=\"text/css\">\n" .
       "<!--\n" .
       "body  { font-face: verdana, arial, helvetica, times }\n" .
       "td    { font-face: verdana, arial, helvetica, times ; }\n" .
       "a     { color: #0000ff ;\n" .
       "        text-decoration: none }\n" .
       "-->\n" .
       "</style>\n" .
       "<title>$title</title>\n" .
       "<meta http-equiv=\"pragma\" content=\"no-cache\">\n" .
       "</head>\n" .
       "<body bgcolor=\"white\">\n" .
       "<center>\n\n" .
       "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\">\n" .
       "<tr><td width=\"75%\" align=left>\n" .
       "<a href=\"http://sourceforge.net/projects/faqforge/\" target=\"_blank\"><img src=\"/images/faqforge.png\" alt=\"FaqForge\" align=\"top\" border=\"0\"></a> v $version\n" .
       "<a name=\"top\">&nbsp;</a>\n" .
       "</td>\n" .
       "<td width=\"25%\" align=\"right\">\n");

if ( $context != "Topics List" )
{
  print ("<a href=\"./?context=Topics+List\">Back to Topics List</a><br>\n");
}

print ("<a href=\"./admin/\">Administration</a><br>\n");


print ("</td></tr>\n" .
       "</table>\n\n" .
       "<br>\n");
?>
