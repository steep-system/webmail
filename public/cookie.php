<?php
if(($_COOKIE["SESSION_ID"]!=$_GET['sid'])||(empty($_COOKIE["SESSION_MARK"]))){
	return array('state'=>0,'tip'=>'cookie write error');
	exit();
}
