<?php
require_once(APP_PATH.'/controllers/common.php');
require_once(APP_PATH.'/models/disk/File.php');
require_once(APP_PATH.'/models/mail/MailBase.php');
class fileuploadController extends Common
{
	public function __construct(){
		parent::init();
//		$this->getSession();
	}

	/**
     * 文件上传
     */
	public function uploadAction(){
		$param = explode("|",$_REQUEST['param']);
		$tsess = $this->session->querySession(base64_decode($param[0]),$param[1]);
		if(!empty($tsess['maildir'])){
			$file = new WebMail_Model_File($tsess['maildir'].'/disk/');
			echo $file->uploadFile($param[2],$_FILES,$tsess['maxsize']);
		}else{
			echo 0;
		}
	}


	public function attachAction(){
		$param = explode("|",$_REQUEST['param']);
		$tsess = $this->session->querySession(base64_decode($param[0]),$param[1]);
		if(!empty($tsess['maildir'])){
			$mail = new WebMail_Model_MailBase($tsess['maildir']);
			echo $mail->uploadFile($_FILES,$param[2]);
		}else{
			echo 0;
		}
	}

	public function normalattachAction(){
		$param = explode("|",$_REQUEST['param']);
		$tsess = $this->session->querySession(base64_decode($param[0]),$param[1]);
		if(!empty($tsess['maildir'])){
			$mail = new WebMail_Model_MailBase($tsess['maildir']);
			$res = $mail->uploadFile($_FILES,$param[2]);
		}else{
			$res = 0;
		}
		echo "{state:'".$res."'}";
	}
}
