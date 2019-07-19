<?php
require_once('common.php');
require_once(APP_PATH.'/models/member/Auth.php');

class authController extends Common{
	protected $auth;

	public function __construct(){
		parent::init();
		$this->auth = new WebMail_Model_Auth();
	}

	public function dologinAction(){
		$info = $this->auth->login($_POST['username'],$_POST['password'],$_POST['screen'],$_POST['lang'],$_POST['remember']);
		header('Content-type: text/html; charset=utf-8'); 
		echo json_encode($info);
	}

	public function logoutAction(){
		if($this->auth->logout()){
			header("Location: ../auth/login");
		}else{
			echo "error";
		}
	}

	public function loginAction(){
		if(empty($_POST['lang'])){
			$setlang = 'rand';
		}else{
			$setlang = $_POST['lang'];
		}
		$title = LANG_COMMON_COM070;
		if(!empty($_COOKIE['USED_DOMAINTITLE'])){
			$title = $_COOKIE['USED_DOMAINTITLE'];
		}

		$username_chk = '';
		if(!empty($_COOKIE['USED_USERNAME'])){
			$username_chk = 'checked';
		}

		$login_info = parse_ini_file(APP_PATH."/config/login.ini",true);
		$loadfile = APP_PATH.'/views/login.html';
		foreach ($login_info as $k=>$v){
			if($k == $_COOKIE['LOGIN_DOMAIN']){
				$loadfile = APP_PATH . '/views/'.$v;
				break;
			}
		}
		require_once($loadfile);
	}

	public function domainloginAction(){
		$title = LANG_COMMON_COM070;
		require_once(APP_PATH.'/views/domainlogin.html');
	}

	public function grouploginAction(){
		$title = LANG_COMMON_COM070;
		require_once(APP_PATH.'/views/grouplogin.html');
	}
}
