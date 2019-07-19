<?php
require_once(APP_PATH."/models/Base.php");
require_once(APP_PATH."/models/JsonOper.php");
require_once(APP_PATH.'/models/Log.php');

class WebMail_Model_Setting extends WebMail_Model_Base
{
	protected $path;
	protected $setfile;
	protected $lock;
	protected $res;
	protected $log;
	protected $basic = array('mailshow'=>1,'contactshow'=>2,'diskshow'=>1,'sign'=>0,
				'signcontent'=>'','mailcode'=>'Z2Jr','delaytime'=>0,'autocontact'=>1);
	protected $setmark = array(1=>'mailshow',2=>'contactshow',3=>'diskshow',4=>'sign',5=>'nickname',6=>'mailcode',7=>'delaytime');

	function __construct(){

	}

	function setFile($path){
		$this->path = $path;
		$this->setfile = $this->path."config/setting.cfg";
		if(!file_exists($this->setfile)){
			$this->create();
		}else{
			$jsonoper = new JsonOper($this->setfile);
			$jsonoper->check($this->basic);
		}
		
		$this->res = $_COOKIE['SESSION_MARK']."-MAILBOX";
		$this->log = new WebMail_Model_Log();
	}

	function create(){
		return file_put_contents($this->setfile,json_encode($this->basic));
	}

	function getConfig(){
		$jsonoper = new JsonOper($this->setfile);
		$data = $jsonoper->getAllRecords();
		$autoreply = parse_ini_file($this->path . "config/autoreply.cfg", false, INI_SCANNER_RAW);
		if ($autoreply) {
			$pos = strpos($autoreply['DURATION_DATE'], '~');
			$data['startday'] = substr($autoreply['DURATION_DATE'], 0, $pos);
			$data['endday'] = substr($autoreply['DURATION_DATE'], $pos + 1);
			$pos = strpos($autoreply['DURATION_TIME'], '~');
			$data['starttime'] = substr($autoreply['DURATION_TIME'], 0, $pos);
			$data['endtime'] = substr($autoreply['DURATION_TIME'], $pos + 1);
			$data['inreply'] = $autoreply['REPLY_SWITCH'];
			$data['exreply'] = $autoreply['EXTERNAL_SWITCH'];
			$data['excheck'] = $autoreply['EXTERNAL_CHECK'];
			$data['duration'] = $autoreply['DURATION_SWITCH'];
			$content = file_get_contents($this->path . "config/internal-reply");
			if ($content) {
				$pos = strpos($content, "\r\n\r\n");
				$data['inreplycontent'] = substr($content, $pos + 4);
			}
			$content = file_get_contents($this->path . "config/external-reply");
			if ($content) {
				$pos = strpos($content, "\r\n\r\n");
				$data['exreplycontent'] = substr($content, $pos + 4);
			}
		}
		$param = array('user'=>PANDORA_DATABASE_USER,'password'=>PANDORA_DATABASE_PASSWORD,'host'=>PANDORA_DATABASE_HOST,'dbname'=>PANDORA_DATABASE_DBNAME);
		$db = new Mysql($param);
		$username = $_COOKIE['SESSION_MARK'];
		$domainuser = $db->select("select lang, timezone, cell, tel, nickname, homeaddress, memo from users where username='$username'");
		$data['lang'] = $domainuser[0]['lang'];
		if ('' != $domainuser[0]['timezone']) {
			$data['timezone'] = $domainuser[0]['timezone'];
		} else {
			$data['timezone'] = PANDORA_SYSTEM_TIMEZONE;
		}
		$data['cell'] = $domainuser[0]['cell'];
		$data['tel'] = $domainuser[0]['tel'];
		$data['nickname'] = $domainuser[0]['nickname'];
		$data['homeaddress'] = $domainuser[0]['homeaddress'];
		$data['memo'] = $domainuser[0]['memo'];
		$fwdsetting = $db->select("select forward_type, destination from forwards where username='$username'");
		if (0 == count($fwdsetting)) {
			$data['autofwd'] = 0;
			$data['fwdtype'] = 0;
			$data['fwdaddress'] = "";
		} else {
			$data['autofwd'] = 1;
			$data['fwdtype'] = $fwdsetting[0]['forward_type'];
			$data['fwdaddress'] = $fwdsetting[0]['destination'];
		}
		$db->close();
		return $data;
	}

	function saveConfig($data){
		$encodekey = array(1=>'signcontent',4=>'mailcode');
		$jsonoper = new JsonOper($this->setfile);
		
		
		foreach ($data as $key=>$val){

			if(array_search($key,$encodekey)){
				$jsonoper->update($key,$val,'base64');
			}else{
				$jsonoper->update($key,$val);
			}
		}
		
		$this->lock = new WebMail_Model_Socket(PANDORA_SOCKET_LOCK);

		if($this->lock->lock($this->res)){
			$res = $jsonoper->save($this->basic);
			if($res){
				$this->loadConfig();
			}else{
				$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Setting: update failed, can not saved",0);
			}
			$this->lock->unlock();
		}
		return $res;
	}

	function update($key,$val,$type=""){
		$jsonoper = new JsonOper($this->setfile);
		if($_COOKIE['USED_USERNAME']&&($key=='lang')){
			setcookie("USED_LANG",$val,(time()+31536000),"/");
		}
		
		$this->lock = new WebMail_Model_Socket(PANDORA_SOCKET_LOCK);

		if(array_key_exists($key,$this->basic)){
			if($jsonoper->update($key,$val,$type)){
				if($this->lock->lock($this->res)){
					$oper = $jsonoper->save($this->basic);
					if($oper){
						$this->loadConfig();
					}else{
						$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Setting: update $key to $val failed, can not saved",0);
					}
					$this->lock->unlock();
				}
			}else{
				$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Setting: update $key to $val",0);
			}
		}
	}

	function loadConfig(){
		$jsonoper = new JsonOper($this->setfile);
		$data = $jsonoper->getAllRecords();
		foreach ($data as $k=>$v){
			if(array_search($k,$this->setmark)){
				setcookie('SET_'.strtoupper($k),$v,0,"/");
			}
		}
	}

	function checkVal($key){
		$jsonoper = new JsonOper($this->setfile);
		return $jsonoper->getOneRecord($key);
	}

	function setFwd($username,$autofwd,$fwdtype,$fwdaddress){
		$param = array('user'=>PANDORA_DATABASE_USER,'password'=>PANDORA_DATABASE_PASSWORD,'host'=>PANDORA_DATABASE_HOST,'dbname'=>PANDORA_DATABASE_DBNAME);
		$db = new Mysql($param);
		if($autofwd){
			$num = $db->total("select id from forwards where username='$username'");
			if($num){
				$db->update("update forwards set forward_type=$fwdtype,destination='$fwdaddress' where username='$username'");
			}else{
				$db->insert("insert into forwards (username,forward_type,destination) values ('$username',$fwdtype,'$fwdaddress')");
			}
		}else{
			$db->delete("delete from forwards where username='$username'");
		}
	}

	//更新pop
	function editPop($mailaddress,$mailpass,$host,$port=110,$isdupl=1){
		$popcfg = $this->path."config/pop.cfg";
		if (!file_exists($popcfg)){
			`touch $popcfg`;
		}
		$pop = file_get_contents($popcfg);
		$arrpop = explode("\n",$pop);
		foreach ($arrpop as $k => $p){
			list($hostOld, $portOld, $mailaddressOld, $mailpassOld, $isduplOld) = explode(" ", $p);
			if ($mailaddressOld == $mailaddress) {
				$arrpop[$k] = implode(" ", array($host, $port, $mailaddress, $mailpass, $isdupl));
				$content = implode("\n", $arrpop);
				if(file_put_contents($popcfg,$content)){
					return 1;
				}else{
					return 0;
				}
			}
		}

		if ($pop == ""){
			$pop .= "$host $port $mailaddress $mailpass $isdupl";
		} else {
			$pop .= "\n$host $port $mailaddress $mailpass $isdupl";
		}
		if(file_put_contents($popcfg,$pop)){
			$user = strtolower($_COOKIE["SESSION_MARK"]);
			$command = "ADD $user\r\n";
			$ret = $this->popBackend($command);
			return 1;
		}else{
			return 0;
		}
	}
	
	function delPop($address){
		$popcfg = $this->path."config/pop.cfg";
		if (!file_exists($popcfg)){
			return 0;
		}
		$pop = explode("\n",file_get_contents($popcfg));
		for($i=0;$i<count($pop);$i++){
			list($hostOld, $portOld, $mailaddressOld, $mailpassOld, $isduplOld) = explode(" ", $pop[$i]);
			if($mailaddressOld == $address){
				array_splice($pop,$i,1);
			}
		}
		$content = implode("\n", $pop);
		if ($content == "") {
			$user = strtolower($_COOKIE["SESSION_MARK"]);
			$command = "REMOVE $user\r\n";
			$ret = $this->popBackend($command);
		}
		if (file_put_contents($popcfg,$content) !== false){
			return 1;
		}else{
			return 0;
		}
	}

	function popBackend($command){
		$connection = fsockopen(PANDORA_POP_HOST,PANDORA_POP_PORT,$err_no, $err_str, 5);
		if ($connection){
			$resp=fgets($connection,256);
			if($resp){
				if(fputs($connection,"$command")){
					$resp=fgets($connection,256);
					fputs($connection,"QUIT\r\n");
					fclose($connection);
					return $resp;
				}
			}
		}
	}
		
	function listPop(){
		$ret = array();
		$popcfg = $this->path."config/pop.cfg";
		if(file_exists($popcfg)){
			$data = file_get_contents($popcfg);
			$ret = explode("\n", $data);
			foreach($ret as $k => $v){
				$ret[$k] = explode(" ", $v);
			}
		}
		if (!empty($ret)){
			return json_encode($ret);
		} else {
			return 0;
		}
	}

	function getPopInfo($address){
		$ret = array();
		if (isset($address) && $address != ""){
			$popcfg = $this->path."config/pop.cfg";
			if (file_exists($popcfg)){
				$data = file_get_contents($popcfg);
				if ($data != ""){
					$dataAry = explode("\n",$data);
					foreach($dataAry as $v){
						$arrv = explode(" ",$v);
						if($arrv[2] == $address){
							array_push($ret,$arrv);
						}
					}
				}
			}
		}
		if (!empty($ret)){
			return json_encode($ret);
		} else {
			return 0;
		}
	}

	/**
     * 测试POP邮箱帐号
     * @param object $account 帐号对象信息
     * @return bool
     */
	function popAccountTest($host, $port, $address, $pass){
		$ip = gethostbyname($host);
		if ($ip == $host) {
			return 0;
		}
		$connection = fsockopen($ip,$port,$err_no, $err_str, 5);
		if ($connection === false) {
			return 0;
		}
		$conResp=fgets($connection,256);
		if (substr($conResp,0,3) != "+OK") {
			fclose($connection);
			return 0;
		}
		if(fputs($connection,"user $address\r\n")){
			$usrResp=fgets($connection,256);
			if (substr($usrResp,0,3) != "+OK"){
				fclose($connection);
				return 0;
			}
		}
		if(fputs($connection,"pass $pass\r\n")){
			$passResp=fgets($connection,256);
			if(substr($passResp,0,3) == "+OK"){
				fclose($connection);
				return 1;
			} else {
				fclose($connection);
				return 0;
			}
		}
	}
	
	

	function divideString($str,$mark="\r\n\t"){
		if(strlen($str)>76){
			for ($i=0;$i<strlen($str);$i+=76){
				$dividedstr.=substr($str,$i,76).$mark;
			}
			return $dividedstr;
		}else{
			return $str;
		}
	}
}
