<?

if ( !defined('IN_MYBLOGGIE') )
  {
    die("You are not allowed to access this page directly !");
  }
$message ="";
$del ="";
if(isset($_SESSION['username']) && isset($_SESSION['passwd'])) {
  $username = $_SESSION['username'];
  $result = mysql_query( "SELECT id, user, level FROM ".USER_TBL." WHERE user='$username'" ) or error( mysql_error() );
  $userid = mysql_fetch_array( $result );
  $_SESSION['user_id'] =   $userid['id'];
  $level =   $userid['level'];
}
else { echo "<meta http-equiv=\"Refresh\" content=\"0;url=login.php\" />";  }

if ( $level==1 || ($level==2 && $enable_user_upload) ) {

function do_upload($upload_dir, $upload_url) {

  $temp_name = $_FILES['userfile']['tmp_name'];
  $file_name = $_FILES['userfile']['name']; 
  $file_name = str_replace("\\","",$file_name);
  $file_name = str_replace("'","",$file_name);
  $file_path = $upload_dir.$file_name;

  //File Name Check
  if ( $file_name =="") { 
    $message = "Invalid File Name Specified";
    return $message;
  }

  $result  =  move_uploaded_file($temp_name, $file_path);
  if (!chmod($file_path,0755))
     $message = "change permission to 755 failed 1.";
  else
    $message = ($result)?"$file_name uploaded successfully." :
             "Somthing is wrong with uploading a file.";
  return $message;
}    //

function thumbnail() {

global  $max_width, $max_height;

$file_name = $_FILES['userfile']['name'];
$tempfilename = $_FILES['userfile']['tmp_name'];  // temporary file at server side


if ( extension_loaded( 'gd' ) ) {

// read image
$tempfile = fopen($tempfilename, "r");
$binaryimage = fread($tempfile, fileSize($tempfilename)); // Try to read image
$old_error_reporting = error_reporting(E_ALL & ~(E_WARNING));// ingore warnings
$src_img = imagecreatefromstring($binaryimage);  // try to create image
error_reporting($old_error_reporting);

$size=GetImageSize($tempfilename);

$width_ratio  = ($size[0] / $max_width);
$height_ratio = ($size[1] / $max_height);

if($width_ratio >=$height_ratio)
{
   $ratio = $width_ratio;
}
else
{
   $ratio = $height_ratio;
}

$new_width    = ($size[0] / $ratio);
$new_height   = ($size[1] / $ratio);

if ($file_name) // file uploaded
{ $filenameparts = explode(".", $file_name);
   $ext =  array_pop($filenameparts);
   $new_name =  $filenameparts[0]."_thumb.".$ext ;
}

$thumb = ImageCreateTrueColor($new_width,$new_height);
ImageCopyResampled($thumb, $src_img, 0,0,0,0,($new_width),($new_height),$size[0],$size[1]);

if ( strtolower($ext) == "png" )
{
   imagepng($thumb, "./files/".$new_name);
}
else if ( strtolower($ext)== "jpg" || strtolower($ext)== "jpeg")
{
   imagejpeg($thumb, "./files/".$new_name);
}
else if ( strtolower($ext)== "gif"  )
{
   imagegif($thumb, "./files/".$new_name);
}
else echo 'Cannot find a suitable output format';

ImageDestroy($src_img);
ImageDestroy($thumb);

}
else { echo 'GD-library support is not available'; }

}

$template->set_filenames(array(
        'upload' => 'admin/upload.tpl'));

//print_r($_FILES['userfile']);

$template->assign_vars(array(
     'L_UPLOAD'       => $lang['Upload'],
     'L_FILE'         => $lang['File'],
     'L_ACTION'       => $lang['Del'],
     //'L_REENTER_PASS'       => $lang['Reenter_password'],
     ));
$upload_dir    = "files/";
$site_name = $_SERVER['HTTP_HOST'];
$url_dir = "http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']);
$url_this =  "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];

$upload_url = $url_dir."/".$upload_dir;
$message ="";

// Create Upload Directory
if (!is_dir("files")) {
  if (!mkdir($upload_dir))
    die ("upload_files directory doesn't exist and creation failed");
  if (!chmod($upload_dir,0755))
    die ("change permission to 755 failed.");
}


//   Process User's Request
if (!empty($_REQUEST['del'])) echo  "Request".$_REQUEST['del'];
if (!empty($_REQUEST['del']) && $allow_delete)  {
  $resource = fopen("log.txt","a");
  fwrite($resource,date("Ymd h:i:s")."DELETE - ".$_SERVER['REMOTE_ADDR'].$_REQUEST['del']."\n");
  fclose($resource);  
  
  if (strpos($_REQUEST['del'],"/.")>0);                  //possible hacking
  else if (strpos($_REQUEST['del'],"files/") === false); //possible hacking
  else if (substr($_REQUEST['del'],0,6)=="files/") {
    unlink($_REQUEST['del']);
    //echo "<script>window.location.href='$url_this?mode=upload&amp;message=deleted successfully'</script>";
    echo "<meta http-equiv=\"Refresh\" content=\"0;url=".$url_this."?mode=upload\" />";
  }
}
else if (!empty($_FILES['userfile'])) {
  $resource = fopen("log.txt","a");
  fwrite($resource,date("Ymd h:i:s")."UPLOAD - ".$_SERVER['REMOTE_ADDR']
            .$_FILES['userfile']['name']." "
            .$_FILES['userfile']['type']."\n");
  fclose($resource);

  $file_type = $_FILES['userfile']['type']; 
  $file_name = $_FILES['userfile']['name'];
  //$file_ext = substr($file_name,strrpos($file_name,"."));
  $filenameparts = explode(".", $file_name);
  $file_ext =  array_pop($filenameparts);

  //File Size Check
  if ( $_FILES['userfile']['size'] > $max_size)
     $message = "The file size is over 2MB.";
  //File Type/Extension Check
  else if (!in_array($file_type, $file_mimes)
          && !in_array($file_ext, $file_exts) )
     $message = "Sorry, $file_name($file_type) is not allowed to be uploaded.";
  else {
      if (isset($_POST['thumb'])) { if  ($_POST['thumb']== 1) { thumbnail(); } }
     $message = do_upload($upload_dir, $upload_url);
     }
  //echo "<script>window.location.href='$url_this?mode=upload&amp;message=$message'</script>";
}
else if (empty($_FILES['userfile']));
else 
  $message = "Invalid File Specified.";

//  List Files

$handle=opendir($upload_dir);
//$filelist = "";

while ($file = readdir($handle)) {

   if(!is_dir($file) && !is_link($file)) {
      $filename = "<a class=\"std\" href=\"$upload_dir$file\">".$file."</a>";
      //$filelist .= "<a href=\"$upload_dir$file\">".$file."</a>";
      if ($allow_delete)
           $u_delete = "<a class=\"std\" href=\"?mode=upload&amp;del=$upload_dir$file\" title=\"delete dile\">delete</a>";
        //$filelist .= " <a class=\"std\" href=\"?mode=upload&amp;del=$upload_dir$file\" title=\"delete dile\">delete</a><br />";

   $template->assign_block_vars('listing', array(
      'FILE' => $filename,
      'DELETE' => $u_delete,
       ));
   }
}

if (!isset($filename)) {
   $template->assign_block_vars('listing', array(
      'FILE' => "<b><font color=\"red\">".$lang['No_File']."</font></b>" ,
       ));
       }
$template->pparse('upload');
?>

<center>

   <font color="red"><? if (!empty($_REQUEST['message'])) echo $_REQUEST['message'] ?></font>
   <br><!--action="<? //echo $_SERVER['PHP_SELF'] ?>-->
   <form name="upload" id="upload" ENCTYPE="multipart/form-data" method="post">
     Upload File <input type="file" id="userfile" name="userfile"><br />
    Auto generate thumbnail <input type="checkbox"  name="thumb" value="1"><br />
     <input type="submit" name="upload" value="Upload">
   </form>
</center>
<?
}