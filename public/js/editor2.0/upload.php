<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>无标题文档</title>
<style type="text/css">
<!--
* {margin:0;padding:0;background:#fff;font:12px Verdana;}
.input  {width:200px;height:22px;}
.button {width:50px;border:1px solid #718da6;height:22px;}
--> 
</style>
<!--[if IE]>
<style type="text/css">
.input{border:1px solid #718da6;}
</style>
<![endif]-->
<script type="text/javascript">
function checkform(){
  var strs=document.upform.file.value;
  if(strs==""){
      alert("请选择要上传的图片!");
	  return false;     
  }  
  var n1=strs.lastIndexOf('.')+1;
  var fileExt=strs.substring(n1,n1+3).toLowerCase()
  if(fileExt!="jpg"&&fileExt!="gif"&&fileExt!="jpe"&&fileExt!="bmp"&&fileExt!="png"){
	  alert('目前系统仅支持jpg、gif、bmp、png后缀图片上传!');
	  return false;
  }
}
</script>
</head>
<body>
<form action="http://192.168.0.201/index.php/mail/douploadinnerimg?mid=<?php echo $_REQUEST['mid'];?>" method="post" enctype="multipart/form-data" name="upform" onSubmit="return checkform();">
	<input name="file" id="file" type="file" class="input" />
	<input name="Submit" type="submit" class="button" value="上 传" />
</form>
</body>
</html>
