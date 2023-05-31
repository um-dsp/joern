<?

// Blog Script - File Name : viewmode.php
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

//Include checks.php for security
include_once($mybloggie_root_path.'includes/checks.php');

if (isset($_GET['year'])){ $year = intval($_GET['year']); }
if (isset($_GET['month_no'])){ $month_no = intval($_GET['month_no']); }

// Added for pagination
$pageno = ( isset($HTTP_GET_VARS['pageno']) ) ? intval($HTTP_GET_VARS['pageno']) : 0;

if (isset($mode)) {
    switch ($mode) {
    case "viewdate" :

    // Security Update --> intval
    if (isset($_GET['date_no'])){ $date_no = intval($_GET['date_no']); }
    if (isset($_GET['year'])){ $year = intval($_GET['year']); }
    if (isset($_GET['month_no'])){ $month_no = intval($_GET['month_no']); }

    else
    { message($lang['Error'], $lang['Msg_no_date'] ); }
    $condition =  "DATE_FORMAT(FROM_UNIXTIME(".POST_TBL.".timestamp) , '%e') = ".$date_no."  AND
                  DATE_FORMAT(FROM_UNIXTIME(".POST_TBL.".timestamp) , '%Y') = ".$year." AND
                  DATE_FORMAT(FROM_UNIXTIME(".POST_TBL.".timestamp) , '%c') = ".$month_no ;

    $urlvar =  "viewdate&date_no=".$date_no."&month_no=".$month_no."&year=".$year;

    $sql = "SELECT ".CAT_TBL.".cat_desc, ".POST_TBL.".cat_id, ".CAT_TBL.".cat_id, ".POST_TBL.".post_id, ".POST_TBL.".subject,".POST_TBL.".message, ".USER_TBL.".user, ".POST_TBL.".timestamp, ".USER_TBL.".id, ".POST_TBL.".user_id FROM ".POST_TBL.", ".USER_TBL.", ".CAT_TBL."
    WHERE ".POST_TBL.".user_id=".USER_TBL.".id  AND  ".CAT_TBL.".cat_id=".POST_TBL.".cat_id AND
    DATE_FORMAT(FROM_UNIXTIME(".POST_TBL.".timestamp) , '%e') = $date_no  AND
    DATE_FORMAT(FROM_UNIXTIME(".POST_TBL.".timestamp) , '%Y') = $year AND
    DATE_FORMAT(FROM_UNIXTIME(".POST_TBL.".timestamp) , '%c') = $month_no
    Order by ".POST_TBL.".post_id DESC LIMIT $pageno, $bloglimit";
    break;
    case "viewmonth":
      // Security Update
    if (isset($_GET['year'])){ $year = intval($_GET['year']); }
    if (isset($_GET['month_no'])){ $month_no = intval($_GET['month_no']); }
    else
    { message($lang['Error'], $lang['Msg_no_mth'] ); }

    $urlvar =  "viewmonth&month_no=".$month_no."&year=".$year;

    $condition =  "DATE_FORMAT(FROM_UNIXTIME(".POST_TBL.".timestamp) , '%Y') = ".$year." AND
                   DATE_FORMAT(FROM_UNIXTIME(".POST_TBL.".timestamp) , '%c') = ".$month_no ;

    $sql = "SELECT ".CAT_TBL.".cat_desc, ".POST_TBL.".cat_id, ".CAT_TBL.".cat_id, ".POST_TBL.".post_id, ".POST_TBL.".subject,".POST_TBL.".message, ".USER_TBL.".user, ".POST_TBL.".timestamp, ".USER_TBL.".id, ".POST_TBL.".user_id FROM ".POST_TBL.", ".USER_TBL.", ".CAT_TBL."
    WHERE ".POST_TBL.".user_id=".USER_TBL.".id  AND  ".CAT_TBL.".cat_id=".POST_TBL.".cat_id AND
    DATE_FORMAT(FROM_UNIXTIME(".POST_TBL.".timestamp) , '%Y') = $year AND
    DATE_FORMAT(FROM_UNIXTIME(".POST_TBL.".timestamp) , '%c') = $month_no
    Order by ".POST_TBL.".post_id DESC LIMIT $pageno, $bloglimit";
    break;
    case "viewcat":
        if (isset($_GET['cat_id'])){ $cat_id = intval($_GET['cat_id']); }
    else
      { message($lang['Error'], $lang['Msg_no_cat'] ); }

    $urlvar =  "viewcat&amp;cat_id=".$cat_id;

    $condition =  POST_TBL.".cat_id=".$cat_id;

    $sql = "SELECT ".CAT_TBL.".cat_desc, ".POST_TBL.".cat_id, ".CAT_TBL.".cat_id, ".POST_TBL.".post_id, ".POST_TBL.".subject,".POST_TBL.".message, ".USER_TBL.".user, ".POST_TBL.".timestamp, ".USER_TBL.".id, ".POST_TBL.".user_id FROM ".POST_TBL.", ".USER_TBL.", ".CAT_TBL."
    WHERE ".POST_TBL.".user_id=".USER_TBL.".id  AND  ".CAT_TBL.".cat_id=".POST_TBL.".cat_id AND ".POST_TBL.".cat_id=$cat_id
    Order by ".POST_TBL.".post_id DESC LIMIT $pageno, $bloglimit";
    check_catid($cat_id);
    break;
    }
}


$sqlpagecount = "SELECT COUNT(".POST_TBL.".post_id) AS num_posts
FROM ".POST_TBL."
WHERE ".$condition;

if ( !($result = $db->sql_query($sqlpagecount)) )
    { $sql_error = $db->sql_error();  error($lang['Error'], 'SQL Query Error : '.$sql_error['message'].' !');; }
$numrow= ( $row = $db->sql_fetchrow($result) ) ? intval($row['num_posts']) : 0;

$pagination = pagelink($urlvar, $pageno, $bloglimit, $numrow);
// end pagination

if( !($result = $db->sql_query($sql)) )
   {
    $sql_error = $db->sql_error();
    error($lang['Error'], 'SQL Query Error : '.$sql_error['message'].' !'); //214
   }

// Add Security
if ( $db->sql_numrows($result)== 0  )
   {
    error( 'Error', 'Post(s) not found ' ) ;
   }

while ($row = $db->sql_fetchrow($result)) {

$message=$row['message'];
$message=viewsmile($message);
$message=viewbbcode($message);
$message=viewlink($message);
$message=autolink($message);
$message = nl2br($message);


$time = date("h:i:s a", $row['timestamp']) ;
$date = date("d M Y", $row['timestamp']) ;

/// Query for No of Comments/Trackbacks //
$querycomment  = "SELECT comment_id FROM ".COMMENT_TBL."
WHERE ".COMMENT_TBL.".post_id = ".$row['post_id']." and comment_type!='trackback'";

if ( !($commentresult = $db->sql_query($querycomment)) )
    {
    $sql_error = $db->sql_error();
    error($lang['Error'], 'SQL Query Error : '.$sql_error['message'].' !'); //214
    }
$nocom = $db->sql_numrows($commentresult) ;

$querytb  = "SELECT comment_id FROM ".COMMENT_TBL."
WHERE ".COMMENT_TBL.".post_id = ".$row['post_id']." and comment_type='trackback'";

if ( !($commentresult = $db->sql_query($querytb)) )
    {
    $sql_error = $db->sql_error();
    error($lang['Error'], 'SQL Query Error : '.$sql_error['message'].' !'); //214
    }
$notb = $db->sql_numrows($commentresult) ;

/////////////////////////////////

$template->assign_block_vars('blogparse', array(

          'USER_NAME'        => $row['user'],
          'SUBJECT'          => $row['subject'],
          'MESSAGE'          => $message,
          'TIME'             => $time ,
          'DATE'             => $date ,
          'L_COMMENT'        => $lang['Comments'],
          'COMMENT_NO'       => "[".$nocom."]",
          'L_CATEGORY'       => $lang['Category'],
          'CATEGORY'         => $row['cat_desc'],
          'U_CATEGORY'       => "<a class=\"std\" href=\"".$_SERVER['PHP_SELF']."?mode=viewcat&amp;cat_id=".$row['cat_id']."\">".$row['cat_desc']."</a>",
          'U_COMMENT'        => $_SERVER['PHP_SELF']."?mode=viewid&amp;post_id=".$row['post_id'],
          'L_POSTED_BY'      => $lang['Posted By'],
          'L_TIME'           => $lang['Time'],
          /// Add in Trackback
          'L_TRACKBACK'      => $lang['Trackback'],
          'L_TB_URL_DESC'    => $lang['Tb_url_desc'],
          'TRACKBACK_URL'    => self_url()."/trackback.php/".$row['post_id'],
          'TB_RDF'           => tb_rdf($row['post_id'],$row['subject']),
          'PERMALINK'        => "<a href=\"".gen_permalink($row['post_id'])."\"><img src=\"templates/".$style."/images/permalink.gif\" border=\"0\" title=\"".$lang['Permalink']."\"></a>" ,
          'TRACKBACK_NO'     => " [".$notb."]",
          /// End Trackback
 )
 );
}
$template->assign_vars(array(
          'PAGE'        => $pagination,
));
$template->pparse('blog_body');

?>