<?php
  $Prve=strtolower($_SERVER['HTTP_REFERER']);
  $Onpage=strtolower($_SERVER["HTTP_HOST"]);
  $Onnym=strpos($Prve,$Onpage);
  //if(!$Onnym) exit("<script language='javascript'>alert('非法操作！');history.go(-1);</script>");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<title>上传文件</title>
<style type="text/css">
 body {font:12px Tahoma;font-family:"宋体";margin:0px;background:#FFF;}
 body,form,ul,li,p,dl,dd,dt,h,td,th,h3{padding:0;font-size:12px;color:#444;}
</style>
</head>
<body>
<?
$app_path = str_replace("/public/js/editor","",realpath(dirname(__FILE__)."/../"));
require_once($app_path.'/controllers/common.php');
//$common = new Common();
//$sinfo = $common->getSession($_REQUEST['ajax']);
//
//echo "<pre>";print_r($sinfo);

//$uptypes=array('image/jpg','image/jpeg','image/png','image/pjpeg','image/gif','image/bmp','image/x-png');
//
//$UpPath_Url = "http://".$_SERVER["SERVER_NAME"]. $_SERVER["PHP_SELF"];
//$UpPath_Url = explode("/upapi/upfile.php",$UpPath_Url);
//$UpPath_Url = explode("/",$UpPath_Url[0]);
//$UpPath     = "";
//for($u=0;$u<count($UpPath_Url)-1;$u++){
//	$UpPath .= $UpPath_Url[$u]."/";
//}
//$UpPath .= "Api_Uppic";
//
//$max_file_size=2000000; //上传文件大小限制, 单位BYTE
//$pathpre = $sinfo['maildir']."/";
//$destination_folder=$_REQUEST['mid']."/"; //上传文件路径
//
//$authnum=rand()%10000;
//
//if ($_SERVER['REQUEST_METHOD'] == 'POST')
//{ 
//   if (!is_uploaded_file($_FILES["file"][tmp_name])){//是否存在文件
//       echo "<script language=javascript>alert('请先选择你要上传的文件！');history.go(-1);</script>";
//       exit();
//   }
//   $file = $_FILES["file"];
//
//   if($max_file_size < $file["size"]){//检查文件大小
//       echo "<script language=javascript>alert('文件大小不能超过2M！');history.go(-1);</script>";
//       exit();
//   }
//
//   if(!in_array($file["type"], $uptypes)){//检查文件类型
//       echo "文件类型不符!".$file["type"];
//       exit();
//   }
//
//   if(!file_exists($pathpre.$destination_folder)){ 
//       mkdir($pathpre.$destination_folder);
//   }
//
//   $filename=$file["tmp_name"];
//   $image_size = getimagesize($filename);
//   $pinfo=pathinfo($file["name"]);
//   $ftype=$pinfo['extension'];
//   $destination = $pathpre.$destination_folder.base64_encode("innerimg-".date("YmdHis",time()).$authnum.".".$ftype);
//
//   if (file_exists($destination) && $overwrite != true){ 
//       echo "<script language=javascript>alert('同名文件已经存在了！');history.go(-1);</script>";
//       exit();
//   }
//
//   if(!move_uploaded_file ($filename, $destination)){ 
//       echo "<script language=javascript>alert('移动文件出错！');history.go(-1);</script>";
//       exit();
//   }
//
//   $pinfo=pathinfo($destination);
//   $fname=$pinfo[basename];
//   
//   //$picture_name = $UpPath.$destination_folder.$fname;
//   $picture_name = "http://".$_SERVER["SERVER_NAME"]."/index.php/mail/uploadinnerimg?param=".base64_encode($destination_folder.basename($destination));
////   echo "<script language=javascript>\r\n";
////   echo "window.parent.document.getElementById('picture').value='$picture_name';\r\n";
////   echo "window.location.href='upload.php?mid=".$_REQUEST['mid']."';\r\n";
////   echo "</script>\r\n";
//
//   
//   // " 宽度:".$image_size[0];
//   // " 长度:".$image_size[1];
//   // "<br> 大小:".$file["size"]." bytes";
//
//}
?>
</body>
</html>