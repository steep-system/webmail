<?php
require_once(APP_PATH.'/controllers/common.php');
require_once(APP_PATH.'/models/Setting.php');
require_once(APP_PATH.'/models/Log.php');

class indexController extends Common
{
	protected $sinfo;
	protected $diskauth = 0;

	public function __construct(){
		parent::init();
		$this->sinfo = $this->getSession($_REQUEST['ajax']);
		if(!empty($this->sinfo['privilege'])){
			$this->diskauth = $this->chkDiskRights($this->sinfo['privilege']);
		}
	}

	public function indexAction(){
		$user = $this->sinfo['realname'];
		$mail = strtolower($_COOKIE['SESSION_MARK']);

		if(!$_COOKIE['SET']){
			$set = new WebMail_Model_Setting();
			$set->setFile($this->sinfo['maildir']."/");
			$set->loadConfig();
		}

		require_once(APP_PATH.'/views/index.html');
	}

	public function langAction(){
		$php_version = explode('-', phpversion());
                $php_version = $php_version[0];
                $php_version_ge530 = strnatcasecmp($php_version, '5.3.0') >= 0 ? true : false;

		
		$langfile = "lang-".$_COOKIE['SET_LANG'].".js";
		if(!file_exists(APP_PATH.'/public/js/'.$langfile)){
			 if (false == $php_version_ge530) {
                                $lang = parse_ini_file(APP_PATH."/lang/".$_COOKIE['SET_LANG'].".ini",true);
                        } else {
                                if (0 == strcmp('en', $_COOKIE['SET_LANG'])) {
                                        $lang = parse_ini_file(APP_PATH."/lang/".$_COOKIE['SET_LANG'].".ini",true,INI_SCANNER_RAW);
                                } else {
                                        $lang = parse_ini_file(APP_PATH."/lang/".$_COOKIE['SET_LANG'].".ini",true);
                                }
                        }
			$strlang = "var lang = ".json_encode($lang).";";
			file_put_contents(APP_PATH.'/public/js/'.$langfile,$strlang);
		}

		if((time()-filemtime(APP_PATH.'/public/js/'.$langfile))>=(3600*24)){
			 if (false == $php_version_ge530) {
                                $lang = parse_ini_file(APP_PATH."/lang/".$_COOKIE['SET_LANG'].".ini",true);
                        } else {
                                if (0 == strcmp('en', $_COOKIE['SET_LANG'])) {
                                        $lang = parse_ini_file(APP_PATH."/lang/".$_COOKIE['SET_LANG'].".ini",true,INI_SCANNER_RAW);
                                } else {
                                        $lang = parse_ini_file(APP_PATH."/lang/".$_COOKIE['SET_LANG'].".ini",true);
                                }
                        }
			$strlang = "var lang = ".json_encode($lang).";";
			file_put_contents(APP_PATH.'/public/js/'.$langfile,$strlang);
		}

		header("content-type: application/x-javascript; charset: utf-8");
		header("cache-control: public");
		header("cache-control: max-age=86400");
		header("last-modified: Fri, 19 Jun 2018 00:00:00 +0000");
		include(APP_PATH."/public/js/".$langfile);
	}

	public function mainAction(){
		$curhour = date("G",time());
		if($curhour>=4&&$curhour<=12){
			$greeting = LANG_COMMON_COM017;
		}elseif ($curhour>12&&$curhour<=18){
			$greeting = LANG_COMMON_COM018;
		}else{
			$greeting = LANG_COMMON_COM019;
		}
		$user = $this->sinfo['realname'];
		if(!file_exists($this->sinfo['homedir']."/notice.txt")){
			$notice = file_get_contents(APP_PATH."/config/notice.txt");
		} else {
			$notice = file_get_contents($this->sinfo['homedir']."/notice.txt");
		}
		if (file_exists($this->sinfo['homedir']."/footer.txt")) {
			$footer = file_get_contents($this->sinfo['homedir']."/footer.txt");
		}
		require_once(APP_PATH.'/views/main.html');
	}

	public function getlogoAction(){
		if(!file_exists($this->sinfo['homedir']."/logo.gif")){
			$img = file_get_contents(APP_PATH."/public/image/logo.gif");
		}else{
			$img = file_get_contents($this->sinfo['homedir']."/logo.gif");
		}
		Header("Content-type: image/gif");
		Header("Accept-Ranges: bytes");
		Header("Accept-Length: ".strlen($img));
		echo $img;
	}

	public function getscriptAction(){
		ob_start();
		header("content-type: application/x-javascript; charset: utf-8");
		header("cache-control: public");
		header("cache-control: max-age=86400");
		header("last-modified: Fri, 19 Jun 2015 00:00:00 +0000");
		include(APP_PATH."/public/js/jquery-1.3.2.min.js");
		if($_GET['mod']=='readmail'){
			include(APP_PATH."/public/js/dialog/zDrag.js");
			include(APP_PATH."/public/js/dialog/zDialog.js");
			include(APP_PATH."/public/js/jquery.hotkeys-0.7.9.min.js");
			include(APP_PATH."/public/js/mail/readmail.js");
			include(APP_PATH."/public/js/lightbox/jquery.lightbox.js?show_helper_text=false");
		}elseif ($_GET['mod']=='editmail'){
			include(APP_PATH."/public/js/jqueryui/jquery-ui-1.8.5.custom.min.fixed.js");
			include(APP_PATH."/public/js/editor2.0/editor.js");
			include(APP_PATH."/public/js/dialog/zDrag.js");
			include(APP_PATH."/public/js/dialog/zDialog.js");
			include(APP_PATH."/public/js/ajaxfileupload.js");
		}elseif ($_GET['mod']=='mailbox'){
			include(APP_PATH."/public/js/dialog/zDrag.js");
			include(APP_PATH."/public/js/dialog/zDialog.js");
			include(APP_PATH."/public/js/jquery.hotkeys-0.7.9.min.js");
			include(APP_PATH."/public/js/mail/mailbox.js");
			include(APP_PATH."/public/js/disk/base.js");
			include(APP_PATH."/public/js/mail/inbox-iframe.js");
		}
		ob_end_flush();
	}

	public function getcssAction(){
		if($_GET['mod']=='readmail'){
			ob_start();
			header("content-type: text/css; charset: utf-8");
			header("cache-control: public");
			header("cache-control: max-age=86400");
			header("last-modified: Fri, 19 Jun 2015 00:00:00 +0000");
			include(APP_PATH."/public/style/mail_list.css");
			include(APP_PATH."/public/skin/default/mailList_skin.css");
			include(APP_PATH."/public/js/lightbox/jquery.lightbox.packed.css");
			include(APP_PATH."/public/skin/default/skin.css");
			ob_end_flush();
		}
	}
}
