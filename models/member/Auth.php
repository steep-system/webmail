<?php
require_once(APP_PATH."/models/Socket.php");
require_once(APP_PATH.'/models/Setting.php');
require_once(APP_PATH.'/models/Log.php');

class WebMail_Model_Auth
{
	protected $log;

	function __construct(){
		$this->log = new WebMail_Model_Log();
	}

	/**
     * 用户登录检验
     *
     * @param string $username
     * @param string $password
     * @return array
     */
	function checkAuth($username,$password){
		$tmp = explode('@',$username);
		$domain = $tmp[1];
		$socket = new WebMail_Model_Socket(PANDORA_SOCKET_USER);
		$cmd = "USER-LOGIN ".base64_encode($username)." ".base64_encode($password)."\r\n";
		$res = $socket->send($cmd);
		$res = explode(" ",$res);
		if(count($res)>2){
			$auth = array("state"=>1,
			"tip"=>$res[1],
			"userid"=>$res[2],
			"realname"=>($res[3]&&"NIL"!=$res[3])?base64_decode($res[3]):"",
			"nickname"=>($res[4]&&"NIL"!=$res[4])?base64_decode($res[4]):"",
			"lang"=>($res[5]&&"NIL"!=$res[5])?base64_decode($res[5]):"",
			"timezone"=>($res[6]&&"NIL"!=$res[6])?base64_decode($res[6]):"",
			"domaintitle"=>($res[7]&&"NIL"!=$res[7])?base64_decode($res[7]):"",
			"domain"=>$res[8],
			"group"=>$res[9],
			"maildir"=>$res[10],
			"homedir"=>$res[11],
			"maxsize"=>$res[12],
			"maxfile"=>$res[13],
			"privilege"=>$res[14],
			"domainname"=>$domain);
		}else{
			$auth = array("state"=>0,"tip"=>trim($res[1]));
		}
		return $auth;
	}

	/**
     * 用户登录
     *
     * @param string $username
     * @param string $password
     * @return array
     */
	function login($user,$password,$screen,$setlang,$rem){
		$tmp = explode('@',$user);
		$domain = trim($tmp[1]);
		$username = trim($tmp[0]);
		$username = $username."@".$domain;
		$socket = new WebMail_Model_Socket(PANDORA_SOCKET_SESSION);
		$userinfo = $this->checkAuth($username,$password);
		if(empty($userinfo['tip']))$userinfo['tip'] = "Service unavailable";
		if(!$userinfo['state']){
			if($userinfo['tip']=="PASSWORD-EMPTY"){
				$setpass = $this->setPassword($username,$password);
				if(0 == $setpass['state']){
					$userinfo = $this->checkAuth($username,$password);
				}else{
					$userinfo['tip'] = "Fail to set password";
				}
			}
		}

		if($userinfo['state']){
			$key = strtoupper($username);
			$res = $socket->getSession($key);
			if(($res[0])||(!empty($res[1]))){
				setcookie ("SESSION_ID","",(time()-3600));
				setcookie ("SESSION_MARK","",(time()-3600));
				$sid = $res[1];
				$sinfo = $userinfo;
				unset($sinfo['state']);
				unset($sinfo['tip']);
				$dblang = $sinfo['lang'];
				unset($sinfo['lang']);
				$dbtz = $sinfo['timezone'];
				unset($sinfo['timezone']);
				$sess = $socket->setSession($key,$sid,$sinfo);
				$pixel = explode("*",$screen);
				setcookie("SESSION_MARK",$key,0,"/",$_SERVER['SERVER_NAME']);
				setcookie("SESSION_ID",$sid,0,"/",$_SERVER['SERVER_NAME']);
				setcookie("CLIENT_X_SCREEN",$pixel[0],0,"/");
				setcookie("CLIENT_Y_SCREEN",$pixel[1],0,"/");
				setcookie("USED_DOMAIN",$sinfo['domainname'],(time()+31536000),"/");
				setcookie("USED_DOMAINTITLE",$sinfo['domaintitle'],(time()+31536000),"/");
				$setting = new WebMail_Model_Setting();
				$setting->setFile($sinfo['maildir']."/");
				
				include(APP_PATH."/public/cookie.php?sid=".$sid);

				//初始化语言
				if($dblang == ""){
					if($setlang!="rand"){
						$this->setLang($username, $setlang);
						setcookie('SET_LANG',$setlang,0,"/");
						$dblang = $setlang;
					} else {
						$curlang = $this->getBrowserLang();
						$this->setLang($username, $curlang);
						setcookie('SET_LANG',$curlang,0,"/");
						$dblang = $curlang;
					}
				} else {
					if($setlang!="rand"){
						$this->setLang($username, $setlang);
						setcookie('SET_LANG',$setlang,0,"/");
						$dblang = $setlang;
					} else {
						if ($_COOKIE['SET_LANG'] != $dblang) {
							setcookie('SET_LANG',$dblang,0,"/");
						}
					}
				}
				
				//初始化时区
				if ($dbtz == ""){
					setcookie('SET_TIMEZONE',PANDORA_SYSTEM_TIMEZONE,0,"/");
				} else {
					setcookie('SET_TIMEZONE',$dbtz,0,"/");
				}
				
				if($setting->checkVal('mailcode')==""){
					$curmailcode = $this->getMailcode($dblang);
					$setting->update('mailcode',$curmailcode,'base64');
				}
				
				if($rem){
					$pos = strpos($username,'@');
					$tusername = substr($username,0,$pos);
					setcookie("USED_USERNAME",$tusername,(time()+31536000),"/");
					setcookie("USED_LANG",$setting->checkVal('lang'),(time()+31536000),"/");
				}else{
					setcookie("USED_USERNAME",'',(time()+31536000),"/");
					setcookie("USED_LANG",'',(time()+31536000),"/");
				}
				$this->log->sendLog($username,'login',1);
			}else{
				$userinfo['state'] = 0;
				$userinfo['tip'] = $res[1];
				$this->log->sendLog($username,'login',0);
			}
		}else{
			$this->log->sendLog($username,'login',0);
		}

		$tip = "";
		if($userinfo['tip']=='USER-EMPTY'){
			$tip = LANG_TIP_C0001;
		}elseif ($userinfo['tip']=='PASSWORD-WRONG'){
			$tip = LANG_TIP_C0002;
		}elseif ($userinfo['tip']=='DOMAIN-EMPTY'){
			$tip = LANG_TIP_C0003;
		}else{
			$tip = $userinfo['tip'];
		}
		return array('state'=>$userinfo['state'],'tip'=>$tip);
	}

	function logout(){
		$socket = new WebMail_Model_Socket(PANDORA_SOCKET_SESSION);
		if(!empty($_COOKIE['SESSION_MARK'])&&(!empty($_COOKIE['SESSION_ID']))){
			if($socket->freeSession($_COOKIE['SESSION_MARK'],$_COOKIE['SESSION_ID'])){
				require_once(APP_PATH.'/models/mail/Sensor.php');
				$mail = new WebMail_Model_Sensor();
				$mail->rem($_COOKIE['SESSION_MARK']);
				
				setcookie ("SESSION_ID","",(time()-3600));
				setcookie ("SESSION_MARK","",(time()-3600));

				$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),'logout',1);

				return true;
			}else{
				$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),'logout',0);
			}
		}
	}

	function setPassword($username,$password){
		$param = array('user'=>PANDORA_DATABASE_USER,'password'=>PANDORA_DATABASE_PASSWORD,'host'=>PANDORA_DATABASE_HOST,'dbname'=>PANDORA_DATABASE_DBNAME);
		$_db = new Mysql($param);
		$row = $_db->select("select id, password from users where username='$username'");
		if ($row[0]['password'] && 0 == strcmp($row[0]['password'], crypt($password, $row[0]['password']))) {
			return array("state"=>1,"code"=>"P1000");
		}
		$uid = $row[0]['id'];
		$key = $this->getKey();
		$ps = crypt($password,$key);
		if($_db->update("update users set password='".$ps."' where id=".$uid)){
			$aliases_user = $this->getAliasesUser($_db, $username);
			$aliases_domain = $this->getAliasesDomain($_db, $username);
			$this->setAliasesUserPassword($_db, $aliases_user,$ps);
			$this->setAliasesDomainPassword($_db, $aliases_domain,$aliases_user,$username,$ps);
			return array("state"=>0,"code"=>$uid);
		}else{
			return array("state"=>2,"code"=>"E1000");
		}
	}
	
	function setLang($username,$lang){
		$param = array('user'=>PANDORA_DATABASE_USER,'password'=>PANDORA_DATABASE_PASSWORD,'host'=>PANDORA_DATABASE_HOST,'dbname'=>PANDORA_DATABASE_DBNAME);
		$_db = new Mysql($param);
		$_db->update("update users set lang='".$lang."' where username='".$username."'");
	}
	
	function setTZ($username,$timezone){
		$param = array('user'=>PANDORA_DATABASE_USER,'password'=>PANDORA_DATABASE_PASSWORD,'host'=>PANDORA_DATABASE_HOST,'dbname'=>PANDORA_DATABASE_DBNAME);
		$_db = new Mysql($param);
		$_db->update("update users set timezone='".$timezone."' where username='".$username."'");
	}

	function updateUserInfo($username,$strset){
		$param = array('user'=>PANDORA_DATABASE_USER,'password'=>PANDORA_DATABASE_PASSWORD,'host'=>PANDORA_DATABASE_HOST,'dbname'=>PANDORA_DATABASE_DBNAME);
		$_db = new Mysql($param);
		$sql = "update users set ".$strset." where username='".$username."'";
		$row = $_db->update($sql);
	}

	function getKey(){
		$chars = array("a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
		"l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
		"w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
		"H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
		"S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
		"3", "4", "5", "6", "7", "8", "9",".","/");
		$key = "$1$";
		for ($i=0;$i<5;$i++){
			$key.=$chars[rand(0,count($chars))];
		}
		$key.="$";
		return $key;
	}

	function getAliasesUser($_db, $username){
		$aliases = $_db->select("select aliasname from aliases where mainname='$username'");
		for ($i=0;$i<count($aliases);$i++){
			$data[$i] = $aliases[$i]['aliasname'];
		}
		return $data;
	}

	function getAliasesDomain($_db, $username){
		$tmp = explode("@",$username);
		$domain = $tmp[1];
		$aliases = $_db->select("select aliasname from aliases where mainname='$domain'");
		for ($i=0;$i<count($aliases);$i++){
			$data[$i] = $aliases[$i]['aliasname'];
		}
		return $data;
	}

	function setAliasesUserPassword($_db, $aliases,$password){
		for ($i=0;$i<count($aliases);$i++){
			$_db->update("update users set password='".$password."' where username='".$aliases[$i]."'");
		}
	}

	function setAliasesDomainPassword($_db, $aliases_domain,$aliases_user,$username,$password){
		$aliases_user = array_merge($aliases_user,array($username));
		for ($i=0;$i<count($aliases_domain);$i++){
			for ($j=0;$j<count($aliases_user);$j++){
				$tmp = explode("@",$aliases_user[$j]);
				$aliasuser = $tmp[0]."@".$aliases_domain[$i];
				$_db->update("update users set password='".$password."' where username='".$aliasuser."'");
			}
		}
	}

	function getBrowserLang(){
		$langs = array();
        preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)s*(;s*qs*=s*(1|0.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);
        if (count($lang_parse[1])) {
                $langs = array_combine($lang_parse[1], $lang_parse[4]);
                foreach ($langs as $lang => $val) {
                        if ($val === '') $langs[$lang] = 1;
                }
                arsort($langs, SORT_NUMERIC);
                $tlang = key($langs);
        } else {
                $tlang = "zh-cn";
        }

        if (preg_match("/zh-c/i",$tlang) || preg_match("/zh-h/i")){
                $curlang = 'zh';
        }elseif (preg_match("/zh/i", $tlang)){
                $curlang = 'cn';
        }elseif (preg_match("/en/i", $tlang)){
                $curlang = 'en';
        }elseif (preg_match("/jp/i", $tlang)){
                $curlang = 'jp';
        }elseif (preg_match("/ja/i", $tlang)){
                $curlang = 'jp';
        }else{
                $curlang = 'zh';
        }
		return $curlang;
	}

	function getMailcode($browserlang){
		switch ($browserlang){
			case 'zh-cn':$curmailcode = 'gbk';break;
			default:$curmailcode = 'utf-8';break;
		}
		if(empty($curmailcode)){
			$curmailcode = 'utf-8';
		}
		return $curmailcode;
	}
}
