<?php
require_once('common.php');

class errorController extends Common
{
	public function __construct(){
		parent::init();
	}
	
	public function chksessionAction(){
		if(!$this->checkSession()){
			echo PANDORA_PATH_WWWROOT."/index.php/error/session";
		}else{
			echo 1;
		}
	}
	
	public function sessionAction(){
		//echo 123;
		//echo "Session 已过期";
		header("Location: ../auth/login");
	}
	
	public function authAction(){
		$errormsg = LANG_COMMON_COM023;
		require_once(APP_PATH.'/views/error/error01.html');
	}
	
	public function authdomainAction(){
		$errormsg = LANG_COMMON_COM023;
		require_once(APP_PATH.'/views/error/error01.html');
	}
}