<?

// Blog Script - File Name : recent.php
// Copyright (C) myBloggie Sean
// http://www.mywebland.com , http://mybloggie.mywebland.com

// You are requested to retain this copyright notice in order to use
// this software.


//This program is free software; you can redistribute it and/or
//modify it under the terms of the GNU General Public License
//as published by the Free Software Foundation; either version 2
//of the License, or (at your option) any later version.

//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.

//You should have received a copy of the GNU General Public License
//along with this program; if not, write to the Free Software
//Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

if ( !defined('IN_MYBLOGGIE') )
{
    die("You are not allowed to access this page directly !");
}


$sql = "SELECT post_id, subject FROM ".POST_TBL."
WHERE ".POST_TBL.".timestamp<='".$timestamp."'
ORDER BY timestamp DESC limit 0 , ".$recentlimit;

$result = $db->sql_query($sql) ;

$template->set_filenames(array(
        'recent' => 'recent.tpl',
        ));
$template->assign_vars(array(
     'RECENT'  => $lang['Recent'],
));
while ($recent = $db->sql_fetchrow($result)) {

   $template->assign_block_vars('recent', array(
   'SUBJECT' => $recent['subject'],
   'U_RECENT'  => $_SERVER['PHP_SELF']."?mode=viewid&amp;post_id=".$recent['post_id'] ,
    )
    );
}

$template->pparse('recent');

?>

