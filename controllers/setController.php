<?php
require_once(APP_PATH.'/controllers/common.php');
require_once(APP_PATH.'/models/member/Auth.php');
require_once(APP_PATH.'/models/Setting.php');

class setController extends Common
{
	protected $sinfo;
	protected $setting;

	public function __construct(){
		parent::init();
		$this->sinfo = $this->getSession($_REQUEST['ajax']);
		$this->setting = new WebMail_Model_Setting();
		$this->setting->setFile($this->sinfo['maildir']."/");
	}

	/**
     * 修改密码
     */
	public function changepasswordAction(){
		if(!empty($_REQUEST['password'])){
			$auth = new WebMail_Model_Auth();
			$res = $auth->setPassword(strtolower($_COOKIE['SESSION_MARK']),$_REQUEST['password']);
			echo json_encode($res);
		}
	}

	/**
     * 保存用户信息
     */
	public function saveuserinfo($uinfo){
		$str = "";
		foreach ($uinfo as $key=>$val){
				$str.= $key."='".$val."',";
		}
		if(!empty($str)){
			$str = substr($str,0,-1);
			$auth = new WebMail_Model_Auth();
			$res = $auth->updateUserInfo(strtolower($_COOKIE['SESSION_MARK']),$str);
		}
	}

	public function settingAction(){
		$data = $this->setting->getConfig();
		if($_REQUEST['type']==1){
			require_once(APP_PATH.'/views/setting/setting01.html');
		}elseif ($_REQUEST['type']==2){
			$mark = "";
			if(!empty($this->sinfo['privilege'])){
				$privilege = $this->getRights($this->sinfo['privilege'],22);
				if(!$privilege){
					$mark = 'disabled';
				}
				$privilege = $this->getRights($this->sinfo['privilege'],3);
				if($privilege){
					$extpasswd = true;
				}
			}

			$acmark = array(0=>'checked',1=>'');
			if(!$data['autocontact']){
				$acmark[0] = '';
				$acmark[1] = 'checked';
			}
			/*
			$regions = array(
				'Africa' => DateTimeZone::AFRICA,
				'America' => DateTimeZone::AMERICA,
				'Antarctica' => DateTimeZone::ANTARCTICA,
				'Aisa' => DateTimeZone::ASIA,
				'Atlantic' => DateTimeZone::ATLANTIC,
				'Europe' => DateTimeZone::EUROPE,
				'Indian' => DateTimeZone::INDIAN,
				'Pacific' => DateTimeZone::PACIFIC
			);

			$timezones = array();
			foreach ($regions as $name => $mask) {
				$zones = DateTimeZone::listIdentifiers($mask);
				foreach($zones as $timezone) {
					$timezones[$name][$timezone] = substr($timezone, strlen($name) + 1);
				}
			}
			*/
			
			
			$timezones = array();
			$tab = file('/usr/share/zoneinfo/zone.tab');
			foreach ($tab as $buf) {
				if (substr($buf,0,1)=='#') continue;
				$rec = preg_split('/\s+/',$buf);
				$key = $rec[2];
				$val = '';
				$c = count($rec);
				for ($i=3;$i<$c;$i++) {
					$val.= ' '.$rec[$i];
				}
				$timezones[$key] = trim($val);
				ksort($timezones);
			}
			
			require_once(APP_PATH.'/views/setting/setting02.html');
		}
	}

	public function saveAction(){
		$data = $_POST;

		if ($_GET['type']==1) {
			//设置签名	
			$this->clearSignpic($data['signcontent']);
			if (empty($data['excheck'])) {
				$data['excheck'] = 0;
			}
			if (empty($data['exinterval'])) {
				$data['exinterval'] = 0;
			}
			if (empty($data['ininterval'])) {
				$data['ininterval'] = 0;
			}
			//设置自动回复内容
			if ($data['exreplycontent']) {
				$this->ActivateAutoreply('external-reply', $data['exreplycontent']);
			} else {
				$this->DeactivateAutoreply('external-reply');
			}
			
			if ($data['inreplycontent']) {
				$this->ActivateAutoreply('internal-reply', $data['inreplycontent']);
			} else {
				$this->DeactivateAutoreply('internal-reply');
			}
			
			if ($data['inreply']) {
				$cfgcontent = "REPLY_SWITCH = " . $data['inreply'] . "\n";
			} else {
				$cfgcontent = "REPLY_SWITCH = 0\n";
			}
			if ($data['exreply']) {
				$cfgcontent .= "EXTERNAL_SWITCH = " . $data['exreply'] . "\n";
			} else {
				$cfgcontent .= "EXTERNAL_SWITCH = 0\n";
			}
			if ($data['excheck']) {
				$cfgcontent .= "EXTERNAL_CHECK = " . $data['excheck'] . "\n";
			} else {
				$cfgcontent .= "EXTERNAL_CHECK = 0\n";
			}
			if ($data['duration']) {
				$cfgcontent .= "DURATION_SWITCH = " . $data['duration'] . "\n";
			} else {
				$cfgcontent .= "DURATION_SWITCH = 0\n";
			}
			if ($data['startday'] && $data['endday']) {
				$cfgcontent .= "DURATION_DATE = " . $data['startday'] . "~" . $data['endday'] . "\n";
			}
			if ($data['starttime'] && $data['endtime']) {
				$cfgcontent .= "DURATION_TIME = " . $data['starttime'] . "~" . $data['endtime'] . "\n";
			}
			file_put_contents($this->sinfo['maildir']  . "/config/autoreply.cfg", $cfgcontent);
		} else if($_GET['type']==2){
			if(empty($data['fwdaddress']) || $data['fwdaddress'] == '') {
				$data['autofwd'] = 0;
			}
			
			$auth = new WebMail_Model_Auth();
			
			//设置语言
			$auth->setLang(strtolower($_COOKIE['SESSION_MARK']),$data['lang']);
			setcookie('SET_LANG',$data['lang'],0,"/");
			
			//设置时区
			$auth->setTZ(strtolower($_COOKIE['SESSION_MARK']),$data['timezone']);
			setcookie('SET_TIMEZONE',$data['timezone'],0,"/");
			
			//设置自动回复
			$this->setting->setFwd(strtolower($_COOKIE['SESSION_MARK']),$data['autofwd'],$data['fwdtype'],$data['fwdaddress']);
		}

		foreach ($data as $key=>$val){
			//设置用户信息
			if(($key=='nickname')||($key=='cell')||($key=='tel')||($key=='homeaddress')||($key=='memo')){
				$uinfo[$key] = $val;
				if($key=='nickname'){
					$sess = $this->getSession($_REQUEST['ajax']);
					$sess['nickname'] = $val;
					$this->session->setSession($_COOKIE['SESSION_MARK'],$_COOKIE['SESSION_ID'],$sess);
				}
			}
		}
		if($_GET['type']==2){
			$this->saveuserinfo($uinfo);
		}

		$this->setting->saveConfig($data);
		echo "<script>window.location.href = 'setting?type=".$_GET['type']."';</script>";
	}
	
	//清理签名内嵌图片
	function clearSignpic($sign){
		$path = $this->sinfo['maildir']  . "/config/sign.pic/";
		if(file_exists($path)){
			$files = scandir($path);
			foreach($files as $file) {
				if(file_exists($path . "/" . $file) && $file != '.' && $file != '..') {
					if(!strstr($sign,base64_encode($file))){
						unlink($path . $file);
					}
				}
			}
		}
	}
	
	
	function ActivateAutoreply($name, $content){
		$autoreplyfile = $this->sinfo['maildir'] . "/config/" . $name;
		$mimestr.="Content-Type: text/html;\r\n\tcharset=\"utf-8\""."\r\n\r\n";
		$mimestr.=$content."\r\n";
		$mimestr.="\r\n";
		file_put_contents($autoreplyfile,$mimestr);
	}
	
	function DeactivateAutoreply($name){
		$autoreplyfile = $this->sinfo['maildir'] . "/config/" . $name;
		unlink($autoreplyfile);
	}


	//更新pop信息
	function editpopAction(){
		echo $this->setting->editPop($_POST['popmailaddress'],$_POST['popmailpass'],$_POST['pophost'],$_POST['popport'],$_POST['isdupl']);
	}
	
	//单个Email的pop信息
	function popinfoAction(){
		echo $this->setting->getPopInfo($_POST['address']);
	}
	
	//pop信息列表
	function listpopAction(){
		echo $this->setting->listPop();
	}
	
	//删除pop
	function delpopAction(){
		echo $this->setting->delPop($_POST['address']);
	}

	//检测pop邮箱
	function popaccounttestAction(){
		echo $this->setting->popAccountTest($_POST['pophost'],$_POST['popport'],$_POST['popmailaddress'],$_POST['popmailpass']);
	}

	//签名图片上传界面
	function showsignpicAction(){
		require_once(APP_PATH.'/views/setting/uploadsignpic.html');
	}

	//显示上传的签名图片
	function uploadsingpicAction(){
		if(!empty($_REQUEST['param'])){
			$innrimg = base64_decode($_REQUEST['param']);
			$file = $this->sinfo['maildir']."/config/sign.pic/".$innrimg;
			$filename = basename($file);
			$tmp = explode(".",$filename);
			Header("Content-type: ".$tmp[count($tmp)-1]);
			Header("Accept-Ranges: bytes");
			Header("Accept-Length: ".filesize($file));
			echo file_get_contents($file);
		}
	}

	//签名图片上传
	function douploadsignpicAction(){
		$uptypes=array('image/jpg','image/jpeg','image/png','image/pjpeg','image/gif','image/bmp','image/x-png');

		$max_file_size=204800; //上传文件大小限制, 单位BYTE

		$pathpre = $this->sinfo['maildir']."/config/sign.pic/";
		if(!file_exists($pathpre)){
			mkdir($pathpre);
		}

		$authnum=rand()%10000;

		if ($_SERVER['REQUEST_METHOD'] == 'POST')
		{
			if (!is_uploaded_file($_FILES["file"][tmp_name])){//是否存在文件
				echo "<script language=javascript>alert('".LANG_MAIL_M0111."');history.go(-1);</script>";
				exit();
			}
			$file = $_FILES["file"];

			if($max_file_size < $file["size"]){//检查文件大小
				echo "<script language=javascript>alert('".LANG_MAIL_M0112."');history.go(-1);</script>";
				exit();
			}

			if(!in_array($file["type"], $uptypes)){//检查文件类型
				echo LANG_MAIL_M0113.$file["type"];
				exit();
			}

			if(!file_exists($pathpre.$destination_folder)){
				mkdir($pathpre.$destination_folder);
			}

			$filename=$file["tmp_name"];
			$image_size = getimagesize($filename);
			$pinfo=pathinfo($file["name"]);
			$ftype=$pinfo['extension'];
			$destination = $pathpre.("sign-".date("YmdHis",time()).$authnum.".".$ftype);

			if (file_exists($destination) && $overwrite != true){
				echo "<script language=javascript>alert('"+lang.mail.M0114+"');history.go(-1);</script>";
				exit();
			}

			if(!move_uploaded_file ($filename, $destination)){
				echo "<script language=javascript>alert('"+lang.mail.M0115+"');history.go(-1);</script>";
				exit();
			}

			$pinfo=pathinfo($destination);
			$fname=$pinfo[basename];

			$picture_name = "http://".$_SERVER["SERVER_NAME"]."/index.php/set/uploadsingpic?param=".base64_encode(basename($destination));
			echo "<script language=javascript>\r\n";
			echo "window.parent.document.getElementById('signcontent_picture').value='$picture_name';\r\n";
			echo "window.location.href='showsignpic';\r\n";
			echo "</script>\r\n";
		}
	}
}
