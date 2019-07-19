<?php
require_once(APP_PATH."/models/Socket.php");
require_once(APP_PATH.'/models/Log.php');

class Common
{
	protected $session;
	protected $exprie;
	protected $log;

	function init(){
		umask(0);
		$this->session = new WebMail_Model_Socket(PANDORA_SOCKET_SESSION);
		$this->log = new WebMail_Model_Log();
	}

	function checkSession(){
		if(empty($_COOKIE['SESSION_ID'])||empty($_COOKIE['SESSION_MARK'])){
			return false;
		}elseif (!$this->session->checkSession($_COOKIE['SESSION_MARK'],$_COOKIE['SESSION_ID'])){
			return false;
		}else{
			return true;
		}
	}

	function getSession($ajax=''){
		$state = array('code'=>0,'url'=>"/index.php/auth/login");
		if(empty($_COOKIE['SESSION_ID'])||empty($_COOKIE['SESSION_MARK'])){
			setcookie("LOGIN_DOMAIN", $_REQUEST['domain'], (time()+31536000), "/");
			if(empty($ajax)){
				//$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"[common.php:line 31] cookie error, value:".$_COOKIE['SESSION_ID']."|".$_COOKIE['SESSION_MARK'],0);
				echo "<script>window.top.location.href='/index.php/auth/login'</script>";
				exit(0);
			}else{
				//$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"[common.php:line 35] cookie error[ajax], value:".$_COOKIE['SESSION_ID']."|".$_COOKIE['SESSION_MARK'],0);
				echo json_encode($state);
				exit(0);
			}
		}else{
			$sess = $this->session->querySession($_COOKIE['SESSION_MARK'],$_COOKIE['SESSION_ID']);
			if(!$sess){
				setcookie("LOGIN_DOMAIN", $_REQUEST['domain'], (time()+31536000), "/");
				if(empty($ajax)){
					//$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"[common.php:line 41] session error, value:".$sess,0);
					echo "<script>window.top.location.href='/index.php/auth/login'</script>";
					exit(0);
				}else{
					//$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"[common.php:line 45] session error[ajax], value:".$sess,0);
					echo json_encode($state);
					exit(0);
				}
			}else{
				return $sess;
			}
		}
	}
	
	function filterVar($var,$type,$isempty=0){
		switch ($type){
			case "string":$filter = FILTER_SANITIZE_STRING;break;
			case "int":$filter = FILTER_VALIDATE_INT;break;
		}
		if($isempty){
			return $var;
		}else{
			$fvar = filter_var($var,$filter);
			if(!$fvar){
				echo "filter error";
				exit(0);
			}else{
				return $fvar;
			}
		}
	}

	function getPageAmount($type,$folder){
		$vscreen = $_COOKIE['CLIENT_Y_SCREEN'];
		$hscreen = $_COOKIE['CLIENT_X_SCREEN'];
		$commsize = 250;
		$amount = 10;
		if($vscreen>=800)$vscreen-=60;
		switch ($type){
			case 1:$amount = floor(($vscreen-$commsize-30)/71);break;
			case 2:{
				$hamount = floor(($hscreen-380)/165);
				$vamount = floor(($vscreen-$commsize)/200);
				if($vamount<1)$vamount = 1;
				$amount = $hamount*$vamount;
				if(!empty($folder)) $amount-=1;
				break;
			}
			case 3:{
				$hamount = floor(($hscreen-400)/165);
				$vamount = floor(($vscreen-$commsize)/140);
				if($vamount<1)$vamount = 1;
				$amount = $hamount*$vamount;
				if(!empty($folder)) $amount-=1;
				break;
			}
			case 4:{
				$hamount = floor(($hscreen-400)/165);
				$vamount = floor(($vscreen-$commsize)/140);
				if($vamount<1)$vamount = 1;
				$amount = $hamount*$vamount;
				$amount-=1;
				break;
			}
			case 5:{
				$amount = floor(($vscreen-200)/26);break;
			}
			case 6:{
				$amount = floor((($vscreen-190)/2)/26);break;
			}
			//网盘-共享文件列表
			case 7:$amount = floor(($vscreen-$commsize)/71);break;
		}
		return $amount;
	}

	function microtime_float()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}

	function redirect($url){
		header("Location: ".PANDORA_PATH_WWWROOT.$url);
	}

	function chkRights($privilege,$type){
		$privilege = decbin($privilege);
		$privilege = sprintf("%+024s",$privilege);
		$privilege = str_split($privilege);
		switch ($type){
			case 'disk':{
				if(!$privilege[3]){
					$this->redirect("/index.php/error/authdomain");
					break;
				}elseif (!$privilege[19]){
					$this->redirect("/index.php/error/auth");
					break;
				}
			}
		}
	}

	function getRights($privilege,$bit){
		$bit-=1;
		$privilege = decbin($privilege);
		$privilege = sprintf("%+024s",$privilege);
		$privilege = str_split($privilege);
		return $privilege[$bit];
	}

	function chkDiskRights($privilege){
		$privilege = decbin($privilege);
		$privilege = sprintf("%+024s",$privilege);
		$privilege = str_split($privilege);
		return $privilege[19];
	}
	
	function chkArchiveRights($privilege){
		$privilege = decbin($privilege);
		$privilege = sprintf("%+024s",$privilege);
		$privilege = str_split($privilege);
		return $privilege[7];
	}

	function getbrowser()
	{
		global $_SERVER;

		$agent           = $_SERVER['HTTP_USER_AGENT'];
		$browser         = '';
		$browser_ver     = '';

		if (preg_match('/OmniWeb\/(v*)([^\s|;]+)/i', $agent, $regs))
		{
			$browser         = 'OmniWeb';
			$browser_ver     = $regs[2];
		}

		if (preg_match('/Netscape([\d]*)\/([^\s]+)/i', $agent, $regs))
		{
			$browser         = 'Netscape';
			$browser_ver     = $regs[2];
		}

		if (preg_match('/safari\/([^\s]+)/i', $agent, $regs))
		{
			$browser         = 'Safari';
			$browser_ver     = $regs[1];
		}

		if (preg_match('/MSIE\s([^\s|;]+)/i', $agent, $regs))
		{
			$browser         = 'Internet Explorer';
			$browser_ver     = $regs[1];
		}

		if (preg_match('/Opera[\s|\/]([^\s]+)/i', $agent, $regs))
		{
			$browser         = 'Opera';
			$browser_ver     = $regs[1];
		}

		if (preg_match('/FireFox\/([^\s]+)/i', $agent, $regs))
		{
			$browser         = 'FireFox';
			$browser_ver     = $regs[1];
		}

		if (preg_match('/Lynx\/([^\s]+)/i', $agent, $regs))
		{
			$browser         = 'Lynx';
			$browser_ver     = $regs[1];
		}

		if ($browser != '')
		{
			return $browser;
		}
		else
		{
			return 'Unknow browser';
		}
	}

	function strToAscii($str){
		$tmp = str_split($str);
		$newstr = '';
		for ($i=0;$i<count($tmp);$i++){
			$newstr.= dechex(ord($tmp[$i]));
		}
		return $newstr;
	}

	function asciiToStr($str){
		$tmp = str_split($str);
		$newstr = '';
		for ($i=0;$i<count($tmp);$i+=2){
			$newstr.= chr(hexdec($tmp[$i].$tmp[$i+1]));
		}
		return $newstr;
	}
}
