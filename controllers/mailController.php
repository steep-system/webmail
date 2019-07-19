<?php
require_once(APP_PATH.'/controllers/common.php');
require_once(APP_PATH.'/models/mail/Mail.php');
require_once(APP_PATH.'/models/contact/UserContact.php');
require_once(APP_PATH.'/models/Setting.php');
require_once(APP_PATH.'/models/Log.php');

class mailController extends Common
{
	protected $sinfo;
	protected $path;
	protected $mail;
	protected $set;
	protected $log;

	public function __construct(){
		parent::init();
		$this->sinfo = $this->getSession($_REQUEST['ajax']);
//		if($this->sinfo['lock']){
//			header("Location: ../auth/login");
//		}
		$this->path = $this->sinfo['maildir'];
		$this->mail = new WebMail_Model_Mail($this->path);
		$this->log = new WebMail_Model_Log();

		if(!empty($this->sinfo['privilege'])){
			$this->diskauth = $this->chkDiskRights($this->sinfo['privilege']);
		}

	}

	/*************************************************************************
	* 邮件相关
	*************************************************************************/

	/**
     * 邮件列表
     */
	public function mailboxAction(){
		if($_GET['sleep']){
			sleep(2);
		}
		$folder = $_GET['f'];
		$page = 1;
		$static_data_page = 1;
		if($_GET['page']){
			$page = $_GET['page'];
			$static_data_page = $_GET['page'];
		}

		$p1 = $this->getPageAmount(5);
		$p2 = $this->getPageAmount(6);

		
		//我的文件夹
		$strfolder = json_encode($this->listfolder());

		if(empty($_REQUEST['type'])){
			$_REQUEST['type'] = $_COOKIE['SET_MAILSHOW'];
		}

		//邮件列表静态数据
		$listdata = $this->staticmailbox($folder,$_REQUEST['type'],$static_data_page);

		if($_REQUEST['type']==1){
			$h1 = (floor((($_COOKIE['CLIENT_Y_SCREEN']-190)/2)/26)*26)+125;
			$h2 = $_COOKIE['CLIENT_Y_SCREEN']-$h1-72;
			if($_COOKIE['SET_MAILSHOW']!=1){
				$this->set = new WebMail_Model_Setting();
				$this->set->setFile($this->sinfo['maildir']."/");
				$this->set->update('mailshow',1);
			}
			require_once(APP_PATH.'/views/mail/'.$folder.'-iframe.html');
		}elseif($_REQUEST['type']==2){
			if($_COOKIE['SET_MAILSHOW']!=2){
				$this->set = new WebMail_Model_Setting();
				$this->set->setFile($this->sinfo['maildir']."/");
				$this->set->update('mailshow',2);
			}
			require_once(APP_PATH.'/views/mail/'.$folder.'.html');
		}
	}

	/**
     * 取得邮件列表静态数据
     */
	public function staticmailbox($mailbox,$type,$page){
		$pagesize = 5;
		if($type==1)$pagesize = 6;
		if($page){
			if ($_REQUEST['f'] == "_result_") {
require_once(APP_PATH. '/models/mail/MailExt.php');
				$search_engine = new WebMail_Model_MailExt($this->sinfo['userid'], $_COOKIE['SESSION_MARK'], $this->path);
				$result = $search_engine->resultDeserialize($this->path . '/tmp/search/' . $_COOKIE['SESSION_ID']);
				return $search_engine->resultListing($result,$page,$this->getPageAmount($pagesize));
			} else {
				return $this->mail->mailListing($mailbox,$page,$this->getPageAmount($pagesize),'RCV','DSC');
			}
		}
	}

	/**
     * 邮件列表记录集接口
     */
	public function maillistAction(){
		if(empty($_REQUEST['f'])){
			echo 0;
		}else{
			if ($_REQUEST['f'] == "_result_") {
require_once(APP_PATH. '/models/mail/MailExt.php');
				$search_engine = new WebMail_Model_MailExt($this->sinfo['userid'], $_COOKIE['SESSION_MARK'], $this->path);
				$result = $search_engine->resultDeserialize($this->path . '/tmp/search/' . $_COOKIE['SESSION_ID']);
				$pagesize = 5;
				if($_REQUEST['type'])$pagesize = 6;
				if($_REQUEST['page']){
					echo $search_engine->resultListing($result,$_REQUEST['page'],$this->getPageAmount($pagesize));
				}
			} else {
				$pagesize = 5;
				if($_REQUEST['type'])$pagesize = 6;
				if($_REQUEST['page']){
					echo $this->mail->mailListing($_REQUEST['f'],$_REQUEST['page'],$this->getPageAmount($pagesize),$_REQUEST['sort'],$_REQUEST['order']);
				}
			}
		}
	}
	
	/**
     * 邮件列表
     */
	public function searchAction(){
		if($_GET['sleep']){
			sleep(2);
		}
		$keyword = $_GET['k'];
		$page = 1;
		$static_data_page = 1;
		if($_GET['page']){
			$page = $_GET['page'];
			$static_data_page = $_GET['page'];
		}

		$p1 = $this->getPageAmount(5);
		$p2 = $this->getPageAmount(6);
		
		//我的文件夹
		$myfolder = $this->mail->listingFolder();
		
		$strfolder = json_encode($this->listfolder());

		if(empty($_REQUEST['type'])){
			$_REQUEST['type'] = $_COOKIE['SET_MAILSHOW'];
		}
		
		//搜索引擎
		require_once(APP_PATH. '/models/mail/MailExt.php');
		
		$search_engine = new WebMail_Model_MailExt($this->sinfo['userid'], $_COOKIE['SESSION_MARK'], $this->path);
		$result = $search_engine->mailSearch(urldecode($keyword));
		
		if(!file_exists($this->path."/tmp/search")){
			mkdir($this->path."/tmp/search");
		} else {
			//清理遗留数据
			if ($dh = opendir($this->path."/tmp/search")) {
				while (($file = readdir($dh)) !== false) {
					if ($file == "." || $file == "..") {
						continue;
					}
					if (time() - filectime($this->path."/tmp/search/" . $file) >= 24*60*60) {
						unlink($this->path."/tmp/search/" . $file);
					}
				}
				closedir($dh);
			}
		}
		
		$search_engine->resultSerialize($result, $this->path . '/tmp/search/' . $_COOKIE['SESSION_ID']);
		
		$pagesize = 5;
		if($_REQUEST['type']==1)$pagesize = 6;
		if($static_data_page){
			$listdata = $search_engine->resultListing($result,$static_data_page,$this->getPageAmount($pagesize));
		}
		
		
		if($_REQUEST['type']==1){
			$h1 = (floor((($_COOKIE['CLIENT_Y_SCREEN']-190)/2)/26)*26)+125;
			$h2 = $_COOKIE['CLIENT_Y_SCREEN']-$h1-72;
			if($_COOKIE['SET_MAILSHOW']!=1){
				$this->set = new WebMail_Model_Setting();
				$this->set->setFile($this->sinfo['maildir']."/");
				$this->set->update('mailshow',1);
			}
			require_once(APP_PATH.'/views/mail/_result_-iframe.html');
		}elseif($_REQUEST['type']==2){
			if($_COOKIE['SET_MAILSHOW']!=2){
				$this->set = new WebMail_Model_Setting();
				$this->set->setFile($this->sinfo['maildir']."/");
				$this->set->update('mailshow',2);
			}
			require_once(APP_PATH.'/views/mail/_result_.html');
		}
	}
	

	/**
     * 邮件撰写页面
     */
	public function writeAction(){
		$this->mail->clearTemp($this->path);
		$sendtype = 'normal';
		$omid = '';
		$replyinfo = '';
		$sign = '';
		$isdraft = 1;
		$disp_cc = "none";
		$disp_cc_desc = LANG_MAIL_M0070;
		$attach_mark = "none";
		$mailfolder = $_GET['f'];

		require_once(APP_PATH."/models/mail/MailBase.php");
		$mailbase = new WebMail_Model_MailBase($this->path);

		$this->set = new WebMail_Model_Setting();
		$this->set->setFile($this->sinfo['maildir']."/");
		//签名设置
		if($this->set->checkVal('sign')){
			$signcontent = $this->set->checkVal('signcontent');

			//处理签名内嵌图片文件
			$sign_pic_path = $this->path."/config/sign.pic";
			if(file_exists($sign_pic_path)){
				$spfiles = scandir($sign_pic_path);
				if(count($spfiles)>2){
					$sp = 0;
					foreach($spfiles as $spfile) {
						if(file_exists($sign_pic_path."/".$spfile) && $spfile != '.' && $spfile != '..') {
							$spfs[$sp] = $sign_pic_path."/".$spfile;
							$sp++;
						}
					}
				}

			}
			$sign = '<div><br></div><div><br></div><div><div style="color:#909090;font-family:Arial Narrow;font-size:12px">------------------</div>'.$signcontent;
		}

		if(!empty($_REQUEST['mid'])){
			$isdraft = 0;
			$mid = base64_decode($_REQUEST['mid']);
			$folder = $_REQUEST['f'];
			$offset = $_REQUEST['p'];
			$mailfile = $this->path."/eml/".$mid;
			
			$maildata = $this->mail->mailDetail($folder,$mid,$charset,'FULLADDRESS');

			//定时发送值
			$istimesend = $maildata['timesend'];

			//建立临时文件
			$tpath = $mid;
			if(($_REQUEST['oper']=='reply')||($_REQUEST['oper']=='replyall')||($_REQUEST['oper']=='transmit')||($_REQUEST['oper']=='attach')){
				$new_mid = time().".".rand(1,100).".".PANDORA_PATH_HOST;
				$tpath = $new_mid;
			}

			if(!file_exists($this->path."/tmp/attach")){
				mkdir($this->path."/tmp/attach");
			}
			if(!file_exists($this->path."/tmp/attach/".$tpath)){
				mkdir($this->path."/tmp/attach/".$tpath);
			}
			$temp_path = $this->path."/tmp/attach/".$tpath;

			//复制签名内嵌图片到临时目录
			if(count($spfs)){
				foreach ($spfs as $spf){
					$dst_spf = $mailbase->strToAscii(basename($spf));
					copy($spf,$temp_path."/".$dst_spf);
				}
			}

			if(!$maildata){
				$this->errorAction();
			}else{
				$addresstype = "STRING";
				if(!empty($_REQUEST['oper'])){
					$addresstype = "ADDRESS";
				}

				if ($_REQUEST['oper']=='attach'){
					$mid = $new_mid;
					$fcontent = file_get_contents($mailfile);
					$subject = $maildata['subject'];
					$attach_file = $mailbase->strToAscii($subject.".eml");
					file_put_contents($temp_path."/".$attach_file,$fcontent);
					$maildata['to'] = $maildata['cc'] = $maildata['subject'] = $maildata['content'] = '';
					$maildata['subject'] = LANG_COMMON_COM007.": ".$subject;
					$maildata['content'] = "<br><p>".LANG_MAIL_M0058."</p>";
					$attach_mark = "block";
					$attach_str = '<div class="oneAttach"> <span class="attachIcon"></span> <span>'.$subject.".eml".'</span> <span>('.filesize($temp_path."/".$attach_file).')</span> <span class="attachDele"><a href="####" onclick="delAttachFile(\''.$attach_file.'\');" style="font-family:Arial;font-weight:bold;text-decoration:none;"><font color="#FF0000">X</font></a></span> </div>';
				}else{
					//取出附件
					if(count($maildata['attach'])>0){
						for ($i=0;$i<count($maildata['attach']);$i++){
							if((($_REQUEST['oper']!='reply')&&($_REQUEST['oper']!='replyall'))||(!empty($maildata['attach'][$i]['cid']))){
								$fcontent = $this->mail->getContent($maildata['attach'][$i]['path'],$maildata['attach'][$i]['begin'],$maildata['attach'][$i]['length'],$maildata['attach'][$i]['encoding']);
								$fname = $mailbase->strToAscii($maildata['attach'][$i]['file']);
								if($maildata['attach'][$i]['cid']){
									$fname = $mailbase->strToAscii('innerimg-'.$maildata['attach'][$i]['file']);
								}
								file_put_contents($temp_path."/".$fname,$fcontent);
								if(empty($maildata['attach'][$i]['cid'])){
									$attach_str.='<div class="oneAttach"> <span class="attachIcon"></span> <span>'.$maildata['attach'][$i]['file'].'</span> <span>('.$maildata['attach'][$i]['size'].')</span> <span class="attachDele"><a href="####" onclick="delAttachFile(\''.$fname.'\');" style="font-family:Arial;font-weight:bold;text-decoration:none;"><font color="#FF0000">X</font></a></span> </div>';
								}
							}
						}
					}
					if(!empty($attach_str)){
						$attach_mark = "block";
					}
				}

				if($_REQUEST['oper']=='reply'){
					$to = $maildata['to'];
					$cc = $maildata['cc'];
					if(!empty($cc)){
						$r_desc_cc = '<strong>'.LANG_MAIL_M0072.':</strong> '.$cc.'<br>';
					}
					$cc = '';
					$maildata['to'] = $maildata['from'];
					$maildata['cc'] = '';
					$maildata['subject'] = LANG_COMMON_COM006.":".$maildata['subject'];
					$sendtype = 'replied';
					$mid = $new_mid;
					$omid = base64_decode($_REQUEST['mid']);

					$desc_from = $maildata['from'];
					$desc_from = str_replace("&lt;","[",$desc_from);
					$desc_from = str_replace("&gt;","]",$desc_from);
					$desc_to = $to;
					$desc_to = str_replace("&lt;","[",$desc_to);
					$desc_to = str_replace("&gt;","]",$desc_to);

					$replyinfo = '<BLOCKQUOTE dir=ltr style="PADDING-RIGHT: 0px; PADDING-LEFT: 5px; MARGIN-LEFT: 5px; BORDER-LEFT: #000000 2px solid; MARGIN-RIGHT: 0px"><div>-------------------- '.LANG_MAIL_M0128.' ---------------------</div><div><div style="background-color:#efefef;width:100%;"><strong>'.LANG_MAIL_M0033.':</strong> '.$desc_from.'</div><strong>'.LANG_MAIL_M0045.':</strong> '.date('l,F j Y H:i A',strtotime($maildata['date'])).'<br><strong>'.LANG_MAIL_M0050.':</strong> '.$desc_to.'<br>'.$r_desc_cc.'<strong>'.LANG_MAIL_M0034.':</strong> '.$maildata['subject'].'</div><br><br>'.$maildata['content']."</BLOCKQUOTE>";
					$maildata['content'] = '<br><br><br><br><br>'.$sign.'<br><br>'.$replyinfo;
				}elseif($_REQUEST['oper']=='replyall'){
					$to = $maildata['to'];
					$cc = $maildata['cc'];
					$from = $maildata['from'];
					$useradress = strtolower($_COOKIE['SESSION_MARK']);
					//合并收件人
					$tmp_to = str_replace("&lt;","<",$to);
					$tmp_to = str_replace("&gt;",">",$tmp_to);
					$arr_to = explode(";",$tmp_to);

					foreach ($arr_to as $ato){
						if(stripos($ato,$useradress)===false){
							$str_to.=trim($ato).";";
						}
					}

					$str_to = substr($str_to,0,-1);
					$str_to = str_replace("<","&lt;",$str_to);
					$str_to = str_replace(">","&gt;",$str_to);
					if(stripos($from,$useradress)===false){
						$str_to = $from.$str_to;
					}
					$maildata['to'] = $str_to;

					//合并抄送
					if(!empty($cc)){
						$disp_cc = "";
						$disp_cc_desc = LANG_MAIL_M0085;
						//合并收件人
						$tmp_cc = str_replace("&lt;","<",$cc);
						$tmp_cc = str_replace("&gt;",">",$tmp_cc);
						$arr_cc = explode(";",$tmp_cc);
						foreach ($arr_cc as $acc){
							if(strpos($acc,$useradress)===false){
								$str_cc.=trim($acc).";";
							}
						}
						$str_cc = substr($str_cc,0,-1);
						$str_cc = str_replace("<","&lt;",$str_cc);
						$str_cc = str_replace(">","&gt;",$str_cc);
						$maildata['cc'] = $str_cc;
						$desc_cc = LANG_MAIL_M0072.': '.$cc.'<br>';
					}
					$maildata['subject'] = LANG_COMMON_COM006.":".$maildata['subject'];
					$cc = $maildata['cc'];
					if(!empty($cc)){
						$r_desc_cc = '<strong>'.LANG_MAIL_M0072.':</strong> '.$cc.'<br>';
					}

					$desc_from = $maildata['from'];
					$desc_from = str_replace("&lt;","[",$desc_from);
					$desc_from = str_replace("&gt;","]",$desc_from);
					$desc_to = $to;
					$desc_to = str_replace("&lt;","[",$desc_to);
					$desc_to = str_replace("&gt;","]",$desc_to);

					$replyinfo = '<BLOCKQUOTE dir=ltr style="PADDING-RIGHT: 0px; PADDING-LEFT: 5px; MARGIN-LEFT: 5px; BORDER-LEFT: #000000 2px solid; MARGIN-RIGHT: 0px"><div>-------------------- '.LANG_MAIL_M0128.' ---------------------</div><div><div style="background-color:#efefef;width:100%;"><strong>'.LANG_MAIL_M0033.':</strong> '.$desc_from.'</div><strong>'.LANG_MAIL_M0045.':</strong> '.date('l,F j Y H:i A',strtotime($maildata['date'])).'<br><strong>'.LANG_MAIL_M0050.':</strong> '.$desc_to.'<br>'.$r_desc_cc.'<strong>'.LANG_MAIL_M0034.':</strong> '.$maildata['subject'].'</div><br><br>'.$maildata['content']."</BLOCKQUOTE>";
					$maildata['content'] = '<br><br><br><br><br>'.$sign.'<br><br>'.$replyinfo;
					$sendtype = 'replied';
					$mid = $new_mid;
					$omid = base64_decode($_REQUEST['mid']);
				}elseif($_REQUEST['oper']=='transmit'){
					$to = $maildata['to'];
					$cc = $maildata['cc'];
					if(!empty($cc)){
						$r_desc_cc = '<strong>'.LANG_MAIL_M0072.':</strong> '.$cc.'<br>';
					}
					$maildata['to'] = $maildata['cc'] = '';
					$maildata['subject'] = LANG_COMMON_COM007.":".$maildata['subject'];
					$sendtype = 'forwarded';
					$mid = $new_mid;
					$omid = base64_decode($_REQUEST['mid']);

					$desc_from = $maildata['from'];
					$desc_from = str_replace("&lt;","[",$desc_from);
					$desc_from = str_replace("&gt;","]",$desc_from);
					$desc_to = $to;
					$desc_to = str_replace("&lt;","[",$desc_to);
					$desc_to = str_replace("&gt;","]",$desc_to);

					$replyinfo = '<div>-------------------- '.LANG_MAIL_M0128.' ---------------------</div><div><div style="background-color:#efefef;width:100%;"><strong>'.LANG_MAIL_M0033.':</strong> '.$desc_from.'</div><strong>'.LANG_MAIL_M0045.':</strong> '.date('l,F j Y H:i A',strtotime($maildata['date'])).'<br><strong>'.LANG_MAIL_M0050.':</strong> '.$desc_to.'<br>'.$r_desc_cc.'<strong>'.LANG_MAIL_M0034.':</strong> '.$maildata['subject'].'</div><br><br>'.$maildata['content'];
					$maildata['content'] = '<br><br><br><br><br>'.$sign.'<br><br>'.$replyinfo;
				}
			}
		}else{
			$mid = time().".".rand(1,100).".".PANDORA_PATH_HOST;
			if(!file_exists($this->path."/tmp/attach")){
				mkdir($this->path."/tmp/attach");
			}
			if(!file_exists($this->path."/tmp/attach/".$mid)){
				mkdir($this->path."/tmp/attach/".$mid);
			}
			$temp_path = $this->path."/tmp/attach/".$mid;

			//复制签名内嵌图片到临时目录
			if(count($spfs)){
				foreach ($spfs as $spf){
					$dst_spf = $mailbase->strToAscii(basename($spf));
					copy($spf,$temp_path."/".$dst_spf);
				}
			}

			if ($_REQUEST['oper']=='attachs'){
				$attach_str = "";
				$mids = substr($_REQUEST['mids'],0,-1);
				$mids = explode(",",$mids);

				require_once(APP_PATH."/models/mail/MailBase.php");
				$mailbase = new WebMail_Model_MailBase($this->path);
				
				if ($_REQUEST['f'] == "_result_") {
require_once(APP_PATH. '/models/mail/MailExt.php');
					$search_engine = new WebMail_Model_MailExt($this->sinfo['userid'], $_COOKIE['SESSION_MARK'], $this->path);
					$result = $search_engine->resultDeserialize($this->path . "/tmp/search/" . $_COOKIE['SESSION_ID']);
					for($i=0;$i<count($mids);$i++){
						$tmid = base64_decode($mids[$i]);
						$folder = $result[$tmid];
						$mailfile = $this->path."/eml/".$tmid;
						$fcontent = file_get_contents($mailfile);
						$subject = $tmid;
						$attach_file = $mailbase->strToAscii($subject.".eml");
						if(file_put_contents($temp_path."/".$attach_file,$fcontent)){
							$attach_str.= '<div class="oneAttach"> <span class="attachIcon"></span> <span>'.$subject.".eml".'</span> <span>('.$mailbase->convertSize(filesize($temp_path."/".$attach_file)).')</span> <span class="attachDele"><a href="####" onclick="delAttachFile(\''.$attach_file.'\');" style="font-family:Arial;font-weight:bold;text-decoration:none;"><font color="#FF0000">X</font></a></span> </div>';
						}
					}
				} else {
					$folder = $_REQUEST['f'];
					for($i=0;$i<count($mids);$i++){
						$tmid = base64_decode($mids[$i]);
						$mailfile = $this->path."/eml/".$tmid;
						$fcontent = file_get_contents($mailfile);
						$subject = $tmid;
						$attach_file = $mailbase->strToAscii($subject.".eml");
						if(file_put_contents($temp_path."/".$attach_file,$fcontent)){
							$attach_str.= '<div class="oneAttach"> <span class="attachIcon"></span> <span>'.$subject.".eml".'</span> <span>('.$mailbase->convertSize(filesize($temp_path."/".$attach_file)).')</span> <span class="attachDele"><a href="####" onclick="delAttachFile(\''.$attach_file.'\');" style="font-family:Arial;font-weight:bold;text-decoration:none;"><font color="#FF0000">X</font></a></span> </div>';
						}
					}
				}
				$attach_mark = "block";
				$maildata['content'] = "<br><p>".LANG_MAIL_M0058."</p>";
			}else if ($_REQUEST['oper']=='disk') {
				$fcontent = file_get_contents($this->path."/disk/".$_REQUEST['file']);
				$attach_file = $mailbase->strToAscii($_REQUEST['name']);
				file_put_contents($temp_path."/".$attach_file,$fcontent);
				$maildata['content'] = "<br><p>".LANG_MAIL_M0058."</p>";
				$attach_mark = "block";
				$attach_str = '<div class="oneAttach"> <span class="attachIcon"></span> <span>'.$_REQUEST['name'].'</span> <span>('.$mailbase->convertSize(filesize($temp_path."/".$attach_file)).')</span> <span class="attachDele"><a href="####" onclick="delAttachFile(\''.$attach_file.'\');" style="font-family:Arial;font-weight:bold;text-decoration:none;"><font color="#FF0000">X</font></a></span> </div>';
			}else if ($_REQUEST['oper']=='contact'){
				if(!empty($_REQUEST['user'])){
					$_REQUEST['user'] = substr($_REQUEST['user'],0,-1);
					$tmp = explode(",",$_REQUEST['user']);
					for ($i=0;$i<count($tmp);$i++){
						$struser.=$mailbase->asciiToStr($tmp[$i]).";";
					}
					$maildata['to'] = $struser;
				}
			}else if ($_REQUEST['oper']=='service'){
				$maildata['to'] = "开发部 &lt;development@ee-post.com&gt;";
				$maildata['subject'] = '关于网页邮箱的改进建议';
			}else{
				if(!empty($sign)){
					$maildata['content'] = $sign;
				}
			}
		}

		//发件人信息
		$mailaddress = strtolower($_COOKIE['SESSION_MARK']);
		$sender_options = "<option value='".$mailaddress."'>".$mailaddress."</option>";
		if(!empty($this->sinfo['realname']))$sender_options.="<option value='".$this->sinfo['realname']."' selected>".$this->sinfo['realname']."</option>";
		if(!empty($this->sinfo['nickname']))$sender_options.="<option value='".$this->sinfo['nickname']."' selected>".$this->sinfo['nickname']."</option>";
		require_once(APP_PATH.'/views/mail/edit.html');
	}

	/**
     * 邮件保存
     */
	public function saveAction(){
		if($this->mail->isFull()==0){
			if (0 != strcasecmp($this->sinfo['realname'], $_POST['sender']) &&
				0 != strcasecmp($this->sinfo['nickname'], $_POST['sender'])) {
				$_POST['sender'] = strtolower($_COOKIE['SESSION_MARK']);
			}
			if($this->mail->mailSave($_POST,'draft',strtolower($_COOKIE['SESSION_MARK']))){
				$this->mail->clearAppointTemp($this->path,$_POST['mid']);
				$tip1 = LANG_MAIL_M0132;
				$tip2 = LANG_MAIL_M0133;
				if(!empty($_POST['istimesend'])){
					$tip2 = LANG_MAIL_M0133." ".LANG_MAIL_M0139;
				}
			}else{
				$tip1 = "<font color='red'>".LANG_MAIL_M0138."</font>";
				$tip2 = LANG_MAIL_M0136;
			}
		}else{
			$tip1 = "<font color='red'>".LANG_MAIL_M0138."</font>";
			$tip2 = LANG_MAIL_M0142;
		}
		require_once(APP_PATH.'/views/mail/save-done.html');
	}

	/**
     * 邮件发送
     */
	public function sendAction(){
		if($this->mail->isFull()==0){
			//合并收件人
			$to = explode(";",str_replace(" ","",trim($_POST['to'])));
			if(!empty($_POST['cc'])){
				$cc = explode(";",str_replace(" ","",trim($_POST['cc'])));
			}
			if(!empty($_POST['bcc'])){
				$bcc = explode(";",str_replace(" ","",trim($_POST['bcc'])));
			}
			$k = 0;
			for ($i=0;$i<count($to);$i++){
				if($to[$i]!=""){
					$address[$k] = $to[$i];
					$k++;
				}
			}
			for ($i=0;$i<count($cc);$i++){
				if($cc[$i]!=""){
					$address[$k] = $cc[$i];
					$k++;
				}
			}
			for ($i=0;$i<count($bcc);$i++){
				if($bcc[$i]!=""){
					$address[$k] = $bcc[$i];
					$k++;
				}
			}

			//延时发送时间设定
			$randsendtime = $_COOKIE['SET_DELAYTIME'];
			$_POST['randsendtime'] = $randsendtime;

			//取消定时发送
			if($_POST['istimesend']){
				$st = explode("|",$_POST['istimesend']);
				$this->mail->unsetTimeSend($st[0]);
			}

			//添加联系人
			$straddress = "";
			for ($i=0;$i<count($address);$i++){
				if($address[$i]!=''){
					$straddress.= $address[$i].";";
				}
			}
			$caddress = $this->mail->splitEmailAddress($straddress);
			$contact = new WebMail_Model_UserContact();
			$contact->setpath($this->path.'/contact.dat');
			$newcontct = $contact->compareContact($caddress,$this->sinfo['domainname']);
			$strnewcontact = '';
			$shownewcontact = 'none';

			if(count($newcontct)>0){
				$this->set = new WebMail_Model_Setting();
				$this->set->setFile($this->sinfo['maildir']."/");
				if(!$this->set->checkVal('autocontact')){
					//手动添加新联系人
					$shownewcontact = 'block';
					for ($i=0;$i<count($newcontct);$i++){
						if(empty($newcontct[$i]['name']))$newcontct[$i]['name'] = $newcontct[$i]['mail'];
						$param = base64_encode(json_encode($newcontct[$i]));
						$strnewcontact.='<li style="margin-left:-15px;">'.$newcontct[$i]['mail'].'&nbsp;&nbsp;<a href="javascript:void(1);" class="mailDoneLink" onclick="addnewcontact(\''.$newcontct[$i]['name'].'\',\''.$newcontct[$i]['mail'].'\')">'.LANG_CONTACT_C0049.'</a>&nbsp;&nbsp;<a href="../contact/edit?param='.$param.'" class="mailDoneLink">'.LANG_CONTACT_C0050.'</a></li>';
					}
				}else{
					//自动添加新联系人
					for ($i=0;$i<count($newcontct);$i++){
						$cdata = array('realname'=>$newcontct[$i]['mail'],'email'=>$newcontct[$i]['mail'],'cell'=>'','tel'=>'','nickname'=>'','birthday'=>'','address'=>'','company'=>'','memo'=>'','gruop'=>'','id'=>'','updatetime'=>time());
						$contact->addRecord($cdata,'email');
					}
				}
			}

			//发送邮件
			$mime = $this->mail->mailSave($_POST,'draft',strtolower($_COOKIE['SESSION_MARK']));

			foreach ($address as $add){$strto.=$add.";";}

			if($mime['mime']){
				$this->mail->clearAppointTemp($this->path,$_POST['mid']);
				if(!empty($_POST['timesend'])){
					/*定时发送*/
					//设定定时发送
					$sendtime = floor($_POST['timesend']/1000);
					$stmark = $this->mail->setTimeSend($sendtime, $this->path, "draft", $mime['mid']);
					if($stmark[0]){
						$sendtime_set = $stmark[1]."|".strtotime($_POST['timesenddesc']);
						//原邮件回复或转发标识设定
						if($_POST['sendtype']!='normal'){
							if((!empty($_POST['mailbox']))&&($_POST['mailbox']!="/")){
								$this->mail->changeProperty($_POST['omid'],$_POST['mailbox'],$_POST['sendtype'],1);
							}
						}
						$this->mail->changeProperty($mime['mid'],'draft','timesend','"'.$sendtime_set.'"');
						//设置日志
						$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),'mail timing send '.$strto,1);
						//设定定时发送成功信息
						$strres1 = LANG_MAIL_M0104;
						$strres2 = LANG_MAIL_M0116.date(LANG_MAIL_M0117,strtotime($_POST['timesenddesc'])).LANG_MAIL_M0054;
					}else{
						$strres1 = "<font color='red'>".LANG_MAIL_M0134."</font>";
						$strres2 = LANG_MAIL_M0137;
					}
				}elseif($randsendtime){
					/*延时发送*/
					//邮件移动
					$this->mail->changeProperty($mime['mid'], 'draft', 'unsent', 0);
					$this->mail->mailMove('draft','sent',array($mime['mid']));
					//设置延时发送
					$stmark = $this->mail->setTimeSend($randsendtime,$this->path, "sent", $mime['mid']);
					if($stmark[0]){
						$randsendtime_set = $stmark[1]."|".($randsendtime+time());
						//原邮件回复或转发标识设定
						if($_POST['sendtype']!='normal'){
							if((!empty($_POST['mailbox']))&&($_POST['mailbox']!="/")){
								$this->mail->changeProperty($_POST['omid'],$_POST['mailbox'],$_POST['sendtype'],1);
							}
						}
						$this->mail->changeProperty($mime['mid'],'sent','randsendtime',$randsendtime_set);
						//设定延时发送成功信息
						$strres1 = LANG_MAIL_M0104;
						if($randsendtime<60){
							$timedesc = $randsendtime.LANG_COMMON_COM033;
						}else{
							$timedesc = floor($randsendtime/60).LANG_COMMON_COM041;
						}
						$strres2 = LANG_MAIL_M0116.$timedesc.LANG_COMMON_COM042.LANG_MAIL_M0054;
						//设置日志
						$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),'mail delay send '.$strto,1);
					}else{
						$this->mail->changeProperty($mime['mid'], 'draft', 'unsent', 0);
						$this->mail->mailMove('sent','draft',array($mime['mid']));
						$strres1 = "<font color='red'>".LANG_MAIL_M0134."</font>";
						$strres2 = LANG_MAIL_M0137;
					}
				}else{
					/*直接发送*/
					$oper = 0;
					//邮件发送
					$oper = $this->mail->mailSend(strtolower($_COOKIE['SESSION_MARK']),$address,$mime['mime']);
					if(!$oper){
						//设定直接发送失败信息
						$strres1 = "<font color='red'>".LANG_MAIL_M0134."</font>";
						$strres2 = LANG_MAIL_M0135;
					}else{
						//设定直接发送成功信息
						$strres1 = LANG_MAIL_M0104;
						$strres2 = LANG_MAIL_M0105.$senttip.LANG_MAIL_M0106;
						//原邮件回复或转发标识设定
						if($_POST['sendtype']!='normal'){
							if((!empty($_POST['mailbox']))&&($_POST['mailbox']!="/")){
								$this->mail->changeProperty($_POST['omid'],$_POST['mailbox'],$_POST['sendtype'],1);
							}
						}
						//邮件移动
						
						$this->mail->changeProperty($mime['mid'], 'draft', 'unsent', 0);
						$this->mail->mailMove('draft','sent',array($mime['mid']));
						//设置日志
						$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),'mail sent '.$strto,1);
					}
				}
			}else{
				$strres2 = LANG_MAIL_M0136;
				$strres1 = "<font color='red'>".LANG_MAIL_M0134."</font>";
			}
		}else{
			$strres2 = LANG_MAIL_M0142;
			$strres1 = "<font color='red'>".LANG_MAIL_M0134."</font>";
		}

		require_once(APP_PATH.'/views/mail/sent-done.html');
	}

	/**
     * 发送可召回邮件
     */
	public function sendrecallAction(){
		if($this->mail->isFull()==0){
			//合并收件人
			$to = explode(";",str_replace(" ","",trim($_POST['to'])));
			if(!empty($_POST['cc'])){
				$cc = explode(";",str_replace(" ","",trim($_POST['cc'])));
			}
			if(!empty($_POST['bcc'])){
				$bcc = explode(";",str_replace(" ","",trim($_POST['bcc'])));
			}
			$k = 0;
			for ($i=0;$i<count($to);$i++){
				if($to[$i]!=""){
					$address[$k] = $to[$i];
					$k++;
				}
			}
			for ($i=0;$i<count($cc);$i++){
				if($cc[$i]!=""){
					$address[$k] = $cc[$i];
					$k++;
				}
			}
			for ($i=0;$i<count($bcc);$i++){
				if($bcc[$i]!=""){
					$address[$k] = $bcc[$i];
					$k++;
				}
			}

			//添加联系人
			$straddress = "";
			for ($i=0;$i<count($address);$i++){
				if($address[$i]!=''){
					$straddress.= $address[$i].";";
				}
			}
			$caddress = $this->mail->splitEmailAddress($straddress);
			$contact = new WebMail_Model_UserContact();
			$contact->setpath($this->path.'/contact.dat');
			$newcontct = $contact->compareContact($caddress,$this->sinfo['domainname']);
			$strnewcontact = '';
			$shownewcontact = 'none';

			if(count($newcontct)>0){
				$this->set = new WebMail_Model_Setting();
				$this->set->setFile($this->sinfo['maildir']."/");
				if(!$this->set->checkVal('autocontact')){
					//手动添加新联系人
					$shownewcontact = 'block';
					for ($i=0;$i<count($newcontct);$i++){
						if(empty($newcontct[$i]['name']))$newcontct[$i]['name'] = $newcontct[$i]['mail'];
						$param = base64_encode(json_encode($newcontct[$i]));
						$strnewcontact.='<li style="margin-left:-15px;">'.$newcontct[$i]['mail'].'&nbsp;&nbsp;<a href="javascript:void(1);" class="mailDoneLink" onclick="addnewcontact(\''.$newcontct[$i]['name'].'\',\''.$newcontct[$i]['mail'].'\')">'.LANG_CONTACT_C0049.'</a>&nbsp;&nbsp;<a href="../contact/edit?param='.$param.'" class="mailDoneLink">'.LANG_CONTACT_C0050.'</a></li>';
					}
				}else{
					//自动添加新联系人
					for ($i=0;$i<count($newcontct);$i++){
						$cdata = array('realname'=>$newcontct[$i]['mail'],'email'=>$newcontct[$i]['mail'],'cell'=>'','tel'=>'','nickname'=>'','birthday'=>'','address'=>'','company'=>'','memo'=>'','gruop'=>'','id'=>'','updatetime'=>time());
						$contact->addRecord($cdata,'email');
					}
				}
			}

			//发送邮件
			$mime = $this->mail->recallmailSave($_POST,'sent',strtolower($_COOKIE['SESSION_MARK']));

			for ($i=0;$i<count($address);$i++){
				$rmdata = $_POST;
				$toaddress = $this->mail->splitEmailAddress($address[$i]);
				$rmdata['content'] = '<br><div style="font-size:13px;">'.strtolower($_COOKIE['SESSION_MARK']).' 于'.date("Y-m-d H:i:s",time()).' 向你发送了一封机密邮件，此邮件内容已保存在服务器中。<br><br>请<a href="http://'.PANDORA_PATH_VHOST.'/index.php/recallmail/readmail?mid='.base64_encode($mime['mid']).'&address='.base64_encode(strtolower($_COOKIE['SESSION_MARK'])).'&key='.md5($mime['mid']).'&to='.base64_encode($toaddress[0]['mail']).'" style="text-decoration:none;color:blue;" target=_blank>点击此处</a>查看邮件内容</div>';
				$recallmail[$i]['mime'] = $this->mail->createRecallMime((object)$rmdata,strtolower($_COOKIE['SESSION_MARK']));
				$recallmail[$i]['to'] = $address[$i];
			}

			$smark = 0;
			for ($i=0;$i<count($recallmail);$i++){
				//邮件发送
				$oper = $this->mail->mailSend(strtolower($_COOKIE['SESSION_MARK']),array($recallmail[$i]['to']),$recallmail[$i]['mime']);
				if($oper){
					$smark++;
				}
			}

			if($smark==count($recallmail)){
				//设定直接发送成功信息
				$strres1 = LANG_MAIL_M0104;
				$strres2 = LANG_MAIL_M0105.$senttip.LANG_MAIL_M0154;
				//原邮件回复或转发标识设定
				if($_POST['sendtype']!='normal'){
					if((!empty($_POST['mailbox']))&&($_POST['mailbox']!="/")){
						$this->mail->changeProperty($_POST['omid'],$_POST['mailbox'],$_POST['sendtype'],1);
					}
				}
				//设置日志
				$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),'mail sent '.$strto,1);
			}else{
				$this->mail->mailMove('sent', 'draft', array($mime['mid']));
				$this->mail->changeProperty($mime['mid'], 'draft', 'unsent', 1);
				//设定直接发送失败信息
				$strres1 = "<font color='red'>".LANG_MAIL_M0134."</font>";
				$strres2 = LANG_MAIL_M0135;
			}

			require_once(APP_PATH.'/views/mail/sent-done.html');
		}
	}

	/**
     * 邮件内容页
     */
	public function readmailAction(){
		$mid = base64_decode($_REQUEST['mid']);
		
		if ($_REQUEST['f'] == "_result_") {
require_once(APP_PATH. '/models/mail/MailExt.php');

			$search_engine = new WebMail_Model_MailExt($this->sinfo['userid'], $_COOKIE['SESSION_MARK'], $this->path);
			$result = $search_engine->resultDeserialize($this->path . "/tmp/search/" . $_COOKIE['SESSION_ID']);
			$folder = $result[$mid];
		} else {
			$folder = $_REQUEST['f'];
		}
		$offset = $_REQUEST['p'];
		$charset = "";
		if($_REQUEST['charset']){
			$charset = $_REQUEST['charset'];
		}

		$order = 'RCV';
		$sort = 'DSC';
		if($_GET['order'])$order = $_GET['order'];
		if($_GET['sort'])$sort = $_GET['sort'];
		$maildata = $this->mail->mailDetail($folder,$mid,$charset,'EDITADDRESS');
		$pagesize = $this->getPageAmount(5);
		$curpage = $_REQUEST['curpage'];
		$mid_sque = $this->mail->getMailSequence($folder,$mid,$curpage,$pagesize,$order,$sort);
		//echo "<pre>";print_r($mid_sque);
		if($mid_sque){
			$maildata['nextmid'] = $mid_sque['nextmid'];
			$maildata['premid'] = $mid_sque['premid'];
		}

		if(!$maildata){
			$this->errorAction();
		}else{
			$attach_count = count($maildata['attach']);
			for ($i=0;$i<count($maildata['attach']);$i++){
				if(empty($maildata['attach'][$i]['file'])){
					$attach_count-=1;
				}
			}
			$showattach = "none";
			$attach_tip = '';
			if($attach_count>0){
				$showattach = "block";
				for ($i=0;$i<count($maildata['attach']);$i++){
					if(!empty($maildata['attach'][$i]['file'])){
						$param = base64_encode(json_encode($maildata['attach'][$i]));
						if($maildata['attach'][$i]['type']){
							$url = "download?type=1&param=".$param;
						}else{
							$url = "download?type=0&param=".$param;
						}
						$extend = "";

						$tmp = explode(".",$maildata['attach'][$i]['file']);
						$extname = $tmp[count($tmp)-1];
						if(array_search(strtolower($extname),array(1=>'jpeg',2=>'gif',3=>'bmp',4=>'png',5=>'jpg'))){
							$extend.="<span id='review'><a href='innerimg?param=".base64_encode(json_encode($maildata['attach'][$i]))."' target=_blank  title='".$maildata['attach'][$i]['file']."'><font class='orangelink'>".LANG_COMMON_COM014."</font></a></span>";
						}

						if(array_search(strtolower($extname),array(1=>'mp3'))&&$this->getbrowser()=='Internet Explorer'){
							$extend.="<span id='mswitch_".$i."'><a href='javascript:void(0);' onclick='musicplay(\"".base64_encode(json_encode($maildata['attach'][$i]))."\",".$i.");'><span id='sub_mswitch_".$i."' class='orangelink'>".LANG_COMMON_COM015."</span></a></span>";
						}

						if(strtolower($extname)=="eml"){
							$extend.="<a href='javascript:void(0);' onclick='openeml(\"".base64_encode(json_encode($maildata['attach'][$i]))."\");' class='bluelink'>".LANG_COMMON_COM016."</span></a></span>";
						}

						if((strtolower($extname)=="txt")||(strtolower($extname)=="htm")||(strtolower($extname)=="html")){
							$maildata['attach'][$i]['charset'] = $maildata['randcharset'];
							$extend.="<a href='javascript:void(0);' onclick='opencontent(\"".base64_encode(json_encode($maildata['attach'][$i]))."\");' class='bluelink'>".LANG_COMMON_COM016."</span></a></span>&nbsp;&nbsp;|&nbsp;";
						}

						switch (strtolower($extname)){
							case 'rar':$icon = 'FileClass05';break;
							case 'zip':$icon = 'FileClass05';break;
							case 'tar':$icon = 'FileClass05';break;
							case 'jpeg':$icon = 'FileClass02';break;
							case 'gif':$icon = 'FileClass02';break;
							case 'bmp':$icon = 'FileClass02';break;
							case 'png':$icon = 'FileClass02';break;
							case 'jpg':$icon = 'FileClass02';break;
							case 'mp3':$icon = 'FileClass03';break;
							case 'wma':$icon = 'FileClass03';break;
							case 'txt':$icon = 'FileClass04';break;
							case 'xls':$icon = 'FileClass06';break;
							case 'xlsx':$icon = 'FileClass06';break;
							case 'pdf':$icon = 'FileClass07';break;
							case 'doc':$icon = 'FileClass08';break;
							case 'docx':$icon = 'FileClass08';break;
							case 'html':$icon = 'FileClass09';break;
							case 'htm':$icon = 'FileClass09';break;
							case 'xml':$icon = 'FileClass09';break;
							case 'eml':$icon = 'FileClass10';break;
							default:$icon = 'FileClass01';
						}

						$attachlist.='<li><div class="'.$icon.'"></div>
  								  <div class="mailFileName">'.$maildata['attach'][$i]['file'].'&nbsp;&nbsp;&nbsp;('.$maildata['attach'][$i]['size'].')<br>
  								  '.$extend.'
    							  <a href="'.$url.'" class="bluelink">'.LANG_COMMON_COM043.'</a>&nbsp;&nbsp;|&nbsp;
    							  <a href="javascript:copyAttachFile(\''.$param.'\');" class="bluelink">'.LANG_MAIL_M0088.'</a><br>
    							  <span id="mplayer_'.$i.'"></span>
    							  </div></li>';
					}
				}
				$attach_tip = '<div id="div_L_Sendor"><span class="sendor">'.LANG_MAIL_M0053.'：</span><span style="float: left;" title="'.LANG_MAIL_M0119.'">'.$attach_count.'个</span><span class="icoFile"></span>&nbsp;'.LANG_MAIL_M0119.'</div>';
			}
			$isshowimg = 1;
			if(!isset($maildata['showimg'])||!$maildata['showimg']||($_REQUEST['showimg']&&($_REQUEST['type']))){
				$isshowimg = 0;
				preg_match_all("/<img(.[^<]*)src=\"\'?(.[^<\"\']*)\"?(.[^<]*)\/?>/is",$maildata['content'],$arrimg);
				preg_match_all("/background=\"\'?(.[^<\"\']*)\"?(.[^<]*)\/?>/is",$maildata['content'],$arrbg);
				preg_match_all("/<base(.[^<]*)\/?>/is",$maildata['content'],$arrbase);
				$mark = 0;
				$mark_type = 0;
				foreach ($arrimg[0] as $img){
					if(!strpos($img,'innerimg?param=')){
						$mark = 1;
						$mark_type = 1;
						break;
					}
				}
				foreach ($arrbg[0] as $bg){
					if(!strpos($bg,'innerimg?param=')){
						$mark = 1;
						$mark_type = 2;
						break;
					}
				}
				foreach ($arrbase[0] as $arrbase){
					if(!strpos($arrbase,'innerimg?param=')){
						$mark = 1;
						$mark_type = 2;
						break;
					}
				}
				if(!$mark){
					$isshowimg = 1;
				}
			}
			if(!$isshowimg){
				$marktip1 = LANG_MAIL_M0120;
				$marktip2 = LANG_MAIL_M0121;
				if($mark_type==2){
					$marktip1 = LANG_MAIL_M0122;
					$marktip2 = LANG_MAIL_M0123;
				}
				$showimg = '<span style="margin-left:10px;font-size:12px;font-weight:200;" id="showimgtip"><font color="red">'.$marktip1.'</font>&nbsp;<a href="javascript:void(0);" class="bluelink" onclick="showimg();">'.$marktip2.'</a></span>';
			}
		}

		if(!empty($maildata['cc'])){
			$mail_cc = '<div id="div_L_Sendor"><span class="sendor">'.LANG_MAIL_M0072.'：</span><span>'.$maildata['cc'].'</span></div>';
		}

		$pid = $_REQUEST['id'];
		$flag = '<span class="icoJobClear" onclick="setMail(\'flag.1\')" title="'.LANG_MAIL_M0093.'" style="cursor:hand;"></span>';
		if($maildata['flag']==1){
			$flag = '<span class="icoJob" onclick="setMail(\'flag.0\')" title="'.LANG_MAIL_M0092.'" style="cursor:hand;"></span>';
		}

		if($_REQUEST['edit']){
			if($maildata['timesend']){
				$sendtime = explode("|",$maildata['timesend']);
				$tid = $sendtime[0];
				$str_timesend = '<span style="padding:0px 5px 0px 5px;"></span><span style="padding-right:8px;"><font color="red"><span id="timemark">'.LANG_MAIL_M0096.' '.date("Y".LANG_COMMON_COM035." m".LANG_COMMON_COM036." d".LANG_COMMON_COM037." H".LANG_COMMON_COM038." i".LANG_COMMON_COM032." ".LANG_MAIL_M0097,$sendtime[1]).'</span></font></span><span style="padding-right:8px;"><a href="javascript:void(0)" onclick="cancelTimeSend()" class="bluelink">'.LANG_COMMON_COM005.'</a></span><span><a href="sendiframe?mid='.base64_encode($mid).'&tid='.$tid.'&f='.$folder.'&p='.$offset.'&type=timesend" onclick="sendatonce();" target="_blank" class="bluelink">'.LANG_MAIL_M0124.'</a></span><span style="padding:0px 5px 0px 5px;"></span>';
			}
		}else{
			if($maildata['randsendtime']){
				$sendtime = explode("|",$maildata['randsendtime']);
				$sendtime_date = date("D M d Y H:i:s O",$sendtime[1]);
				$sendtime_date = $sendtime[1]-time()-60;
				$tid = $sendtime[0];
				$str_timesend = '<span style="padding:0px 5px 0px 5px;"></span><span style="padding-right:8px;"><font color="red"><span id="timemark"></span></font></span><span style="padding-right:8px;"><a href="javascript:cancelTimeSend()" class="bluelink">'.LANG_COMMON_COM005.'</a></span><span><a href="sendiframe?mid='.base64_encode($mid).'&f='.$folder.'&tid='.$tid.'&p='.$offset.'&type=randsendtime" onclick="sendatonce();" target="_blank" class="bluelink">'.LANG_MAIL_M0124.'</a></span><span style="padding:0px 5px 0px 5px;"></span>';
			}
		}

		$content = $maildata['content'];
		$content = str_ireplace("<body","<br",$content);

		if(!$isshowimg){
			$content = $this->mail->removeHtmlImg($content);
		}

		if(empty($content))$content = "<p>&nbsp;</p>";

		if(!$maildata['read']){
			if($_GET['isusermail']){
				$this->mail->setSortByUserMail($folder,base64_encode($mid),'read',1);
			}else{
				$this->mail->changeProperty($mid,$folder,'read',1);
			}
		}

		//设置可召回邮件
		if($folder=='sent'){
			$opt_recall = "";
			if($maildata['recall']){
				$opt_recall = '<span id="recallbtn"><a href="javascript:void(0)" class="bluelink" id="btnCancelRecall" onclick="cancelRecallMail(\''.base64_encode($mid).'\')">'.LANG_MAIL_M0150.'</a></span><span style="padding:0px 5px 0px 5px;">|</span>';
			}

			if(isset($maildata['recall'])){
				$opt_recall.='<span><a href="javascript:void(0)" class="bluelink" id="btnViewMailLog" onclick="viewMailLog(\''.base64_encode($mid).'\')">'.LANG_MAIL_M0152.'</a></span><span style="padding:0px 5px 0px 5px;">|</span>';
			}
		}

		//邮件序列设置
		$str_sque_premid = $str_sque_nextmid = "";
		if($maildata['premid']){
			$str_sque_premid = '<a href="readmail?mid='.base64_encode($maildata['premid']).'&f='.$folder.'&id=0&back='.$_GET['back'].'&type='.$_GET['type'].'&curpage='.$mid_sque['retpage'].'&time='.time().'" class="bluelink">'.LANG_MAIL_M0157.'</a>';
		}

		if($maildata['nextmid']){
			$str_sque_nextmid = '<a href="readmail?mid='.base64_encode($maildata['nextmid']).'&f='.$folder.'&id=0&back='.$_GET['back'].'&type='.$_GET['type'].'&curpage='.$mid_sque['retpage'].'&time='.time().'" class="bluelink">'.LANG_MAIL_M0158.'</a>';
		}

		if(!empty($str_sque_premid)||!empty($str_sque_nextmid)){
			$str_sque = '<span>'.$str_sque_premid.'</span>&nbsp;&nbsp;<span>'.$str_sque_nextmid.'</span><span style="padding:0px 5px 0px 5px;">|</span>';
		}

		if($_REQUEST['edit']){
			require_once(APP_PATH.'/views/mail/readmail-draft-small.html');
		}elseif($_REQUEST['type']){
			if($_GET['back']){
				if(!empty($_GET['user'])){
					$strback = '<span style="margin-left:15px;"><a href="javascript:void(0);" onclick="backtouserlist(\''.$_GET['f'].'\',\''.$_GET['user'].'\',1)" style="text-decoration:none;color:#005590;"><< '.LANG_COMMON_COM025.'</a></span><input type="hidden" value="1" id="isback">';
				}else{
					if ($_GET['f'] == "_result_") {
						$strback = '<span style="margin-left:15px;"><a href="javascript:void(0);" onclick="backtolist(\''.$_GET['f'].'\','.$_GET['curpage'].')" style="text-decoration:none;color:#005590;"><< '.LANG_COMMON_COM025.'</a></span><input type="hidden" value="1" id="isback">';
					} else {
						$strback = '<span style="margin-left:15px;"><a href="javascript:void(0);" onclick="backtolist(\''.$_GET['f'].'\','.$mid_sque['retpage'].')" style="text-decoration:none;color:#005590;"><< '.LANG_COMMON_COM025.'</a></span><input type="hidden" value="1" id="isback">';
					}
				}
				$listfolder = $_GET['f'];
			}else{
				$str_sque = "";
			}
			require_once(APP_PATH.'/views/mail/readmail-small.html');
		}else{
			$arr_charset = array('utf-8','gb2312','gbk','big5','euc-jp','iso-2022-jp');
			$charset_option = "<option value=''>".LANG_COMMON_COM044."</option>";
			if(empty($_REQUEST['charset'])){
				$charset_option = "<option value='' selected>".LANG_COMMON_COM044."</option>";
			}
			foreach ($arr_charset as $ac){
				if($ac==$_REQUEST['charset']){
					$charset_option.="<option value='".$ac."' selected>".strtoupper($ac)."</option>";
				}else{
					$charset_option.="<option value='".$ac."'>".strtoupper($ac)."</option>";
				}
			}
			require_once(APP_PATH.'/views/mail/readmail.html');
		}
	}

	/**
     * 邮件打印页
     */
	public function printAction(){
		$mid = base64_decode($_REQUEST['mid']);
		$mailfile = $this->path."/".$_REQUEST['f']."/".$mid;

		$maildata = $this->mail->mailDetail($_REQUEST['f'],$mid);
		if(!$maildata){
			$this->errorAction();
		}else{
			$content = preg_replace("/<script.+script>/i","",$maildata['content']);
			if(!$_REQUEST['showimg']){
				$maildata['content'] = $this->mail->removeHtmlImg($maildata['content']);
			}
			require_once(APP_PATH.'/views/mail/print.html');
		}
	}

	/**
     * eml查看
     */
	public function reademlAction(){
		if($_REQUEST['t']){
			$data = array('path'=>$_REQUEST['param'],'type'=>1,'user'=>$_REQUEST['user'],'share'=>$_REQUEST['share'],'group'=>$this->sinfo['group'],'domain'=>$this->sinfo['domain']);
			$eml_param = base64_encode(json_encode($data));
			$emldata = $this->mail->getEmlDetail($data,1);
		}else{
			$eml_param = $_REQUEST['param'];
			$data = json_decode(base64_decode($eml_param),true);
			$emldata = $this->mail->getEmlDetail($data);
		}
		if(!$emldata){

		}else{
			$emldata = json_decode($emldata,true);
			$mailfile = $this->path."/tmp/eml/".$emldata['file'];
			$charset = "";
			if($_REQUEST['charset']){
				$charset = $_REQUEST['charset'];
			}
			$maildata = $this->mail->mailDetailContent($emldata,$mailfile,$charset);
			$attach_count = count($maildata['attach']);
			for ($i=0;$i<count($maildata['attach']);$i++){
				if(empty($maildata['attach'][$i]['file'])){
					$attach_count-=1;
				}
			}
			$showattach = "none";
			$attach_tip = '';
			if($attach_count>0){
				$showattach = "block";
				for ($i=0;$i<count($maildata['attach']);$i++){
					if(!empty($maildata['attach'][$i]['file'])){
						$param = base64_encode(json_encode($maildata['attach'][$i]));
						if($maildata['attach'][$i]['type']){
							$url = "download?type=1&param=".$param;
						}else{
							$url = "download?type=0&param=".$param;
						}
						$extend = "";

						$tmp = explode(".",$maildata['attach'][$i]['file']);
						$extname = $tmp[count($tmp)-1];
						if(array_search($extname,array(1=>'jpeg',2=>'gif',3=>'bmp',4=>'png',5=>'jpg'))){
							$extend.="<span id='review'><a href='innerimg?param=".base64_encode(json_encode($maildata['attach'][$i]))."' target=_blank  title='".$maildata['attach'][$i]['file']."'><font class='orangelink'>".LANG_COMMON_COM002."</font></a></span>";
						}

						if(array_search($extname,array(1=>'mp3'))&&$this->getbrowser()=='Internet Explorer'){
							$extend.="<span id='mswitch_".$i."'><a href='javascript:void(0);' onclick='musicplay(\"".base64_encode(json_encode($maildata['attach'][$i]))."\",".$i.");'><span id='sub_mswitch_".$i."' class='orangelink'>".LANG_COMMON_COM002."</span></a></span>";
						}

						if($extname=="eml"){
							$extend.="<a href='javascript:void(0);' onclick='openeml(\"".base64_encode(json_encode($maildata['attach'][$i]))."\");' class='bluelink'>".LANG_COMMON_COM016."</span></a></span>";
						}

						switch (strtolower($extname)){
							case 'rar':$icon = 'FileClass05';break;
							case 'zip':$icon = 'FileClass05';break;
							case 'tar':$icon = 'FileClass05';break;
							case 'jpeg':$icon = 'FileClass02';break;
							case 'gif':$icon = 'FileClass02';break;
							case 'bmp':$icon = 'FileClass02';break;
							case 'png':$icon = 'FileClass02';break;
							case 'jpg':$icon = 'FileClass02';break;
							case 'mp3':$icon = 'FileClass03';break;
							case 'wma':$icon = 'FileClass03';break;
							case 'txt':$icon = 'FileClass04';break;
							case 'xls':$icon = 'FileClass06';break;
							case 'xlsx':$icon = 'FileClass06';break;
							case 'pdf':$icon = 'FileClass07';break;
							case 'doc':$icon = 'FileClass08';break;
							case 'docx':$icon = 'FileClass08';break;
							case 'html':$icon = 'FileClass09';break;
							case 'htm':$icon = 'FileClass09';break;
							case 'xml':$icon = 'FileClass09';break;
							case 'eml':$icon = 'FileClass10';break;
							default:$icon = 'FileClass01';
						}

						$attachlist.='<li><div class="'.$icon.'"></div>
  								  <div class="mailFileName">'.$maildata['attach'][$i]['file'].'&nbsp;&nbsp;&nbsp;('.$maildata['attach'][$i]['size'].')<br>
  								  '.$extend.'
    							  <a href="'.$url.'" class="bluelink">'.LANG_COMMON_COM043.'</a>
    							  <a href="javascript:copyAttachFile(\''.$param.'\');" class="bluelink">'.LANG_MAIL_M0088.'</a><br>
    							  <span id="mplayer_'.$i.'"></span>
    							  </div></li>';
					}
				}
				$attach_tip = '<div id="div_L_Sendor"><span class="sendor">'.LANG_MAIL_M0053.'：</span><span style="float: left;"><a href="#attachlist">'.$attach_count.''.LANG_COMMON_COM034.'</a>'.LANG_MAIL_M0119.'</span><span class="icoFile"></span></div>';
			}

			$content = $maildata['content'];
			$content = str_ireplace("<body","<br",$content);

			$isshowimg = 1;
			if(!$_REQUEST['showimg']){
				$isshowimg = 0;
				preg_match_all("/<img(.[^<]*)src=\"\'?(.[^<\"\']*)\"?(.[^<]*)\/?>/is",$maildata['content'],$arrimg);
				preg_match_all("/background=\"\'?(.[^<\"\']*)\"?(.[^<]*)\/?>/is",$maildata['content'],$arrbg);
				preg_match_all("/<base(.[^<]*)\/?>/is",$maildata['content'],$arrbase);
				$mark = 0;
				$mark_type = 0;
				foreach ($arrimg[0] as $img){
					if(!strpos($img,'innerimg?param=')){
						$mark = 1;
						$mark_type = 1;
						break;
					}
				}
				foreach ($arrbg[0] as $bg){
					if(!strpos($bg,'innerimg?param=')){
						$mark = 1;
						$mark_type = 2;
						break;
					}
				}
				foreach ($arrbase[0] as $arrbase){
					if(!strpos($arrbase,'innerimg?param=')){
						$mark = 1;
						$mark_type = 2;
						break;
					}
				}
				if(!$mark){
					$isshowimg = 1;
				}
			}
			if(!$isshowimg){
				$marktip1 = LANG_MAIL_M0120;
				$marktip2 = LANG_MAIL_M0121;
				if($mark_type==2){
					$marktip1 = LANG_MAIL_M0122;
					$marktip2 = LANG_MAIL_M0123;
				}
				$showimg = '<span style="margin-left:10px;font-size:12px;font-weight:200;" id="showimgtip"><font color="red">'.$marktip1.'</font>&nbsp;<a href="javascript:void(0);" class="bluelink" onclick="showimg();">'.$marktip2.'</a></span>';
			}

			if(!$isshowimg){
				$content = $this->mail->removeHtmlImg($content);
			}

			if(!empty($maildata['cc'])){
				$mail_cc = '<div id="div_L_Sendor"><span class="sendor">抄　送：</span><span>'.$maildata['cc'].'</span></div>';
			}

			$arr_charset = array('utf-8','gb2312','gbk','big5','euc-jp','iso-2022-jp');
			$charset_option = "<option value=''>".LANG_COMMON_COM044."</option>";
			if(empty($_REQUEST['charset'])){
				$charset_option = "<option value='' selected>".LANG_COMMON_COM044."</option>";
			}
			foreach ($arr_charset as $ac){
				if($ac==$_REQUEST['charset']){
					$charset_option.="<option value='".$ac."' selected>".strtoupper($ac)."</option>";
				}else{
					$charset_option.="<option value='".$ac."'>".strtoupper($ac)."</option>";
				}
			}
			require_once(APP_PATH.'/views/mail/reademl.html');
		}
	}

	/**
     * 定时发送界面
     */
	public function sendtimeAction(){
		require_once(APP_PATH.'/views/mail/sendtime.html');
	}

	/**
     * 邮件列表记录集接口
     */
	public function mailboxdataAction(){
		if(empty($_REQUEST['oper'])){
			echo 0;
		}else{
			$pagesize = 5;
			if($_REQUEST['type'])$pagesize = 6;
			if($_REQUEST['page']){
				echo $this->mail->mailListing($_REQUEST['oper'],$_REQUEST['page'],$this->getPageAmount($pagesize),$_REQUEST['order'],$_REQUEST['sort']);
			}
		}
	}

	/**
     * 批量删除邮件
     */
	public function delmailsAction(){
		$oper = 0;
		if((!empty($_REQUEST['param']))&&(!empty($_REQUEST['f']))){
			$mid = explode(",",substr($_REQUEST['param'],0,-1));
			for($i=0;$i<count($mid);$i++){
				$mid[$i] = base64_decode($mid[$i]);
			}
			if ($_REQUEST['f'] == "_result_") {
				
require_once(APP_PATH. '/models/mail/MailExt.php');

				$search_engine = new WebMail_Model_MailExt($this->sinfo['userid'], $_COOKIE['SESSION_MARK'], $this->path);
				$result = $search_engine->resultDeserialize($this->path . "/tmp/search/" . $_COOKIE['SESSION_ID']);
				for ($i=0; $i<count($mid); $i++) {
					$this->mail->mailRemove(array($mid[$i]),$result[$mid[$i]]);
					unset($result[$mid[$i]]);
				}
				$search_engine->resultSerialize($result, $this->path . "/tmp/" . $_COOKIE['SESSION_ID']);
				$oper = 1;
			} else {
				if($this->mail->mailRemove($mid,$_REQUEST['f'])){
					$oper = 1;
				}
			}
		}
		echo $oper;
	}

	/**
     * 移动邮件
     */
	public function movemailsAction(){
		$oper = 0;
		$mids = explode(",",substr($_REQUEST['mid'],0,-1));
		for($i=0;$i<count($mids);$i++){
			$mids[$i] = base64_decode($mids[$i]);
		}
		if ($_REQUEST['cf'] == "_result_") {
				
require_once(APP_PATH. '/models/mail/MailExt.php');

				$search_engine = new WebMail_Model_MailExt($this->sinfo['userid'], $_COOKIE['SESSION_MARK'], $this->path);
				$result = $search_engine->resultDeserialize($this->path . "/tmp/search/" . $_COOKIE['SESSION_ID']);
				for ($i=0; $i<count($mids); $i++) {
					$this->mail->mailMove($result[$mids[$i]], $_REQUEST['tf'], array($mids[$i]));
					unset($result[$mids[$i]]);
				}
				$search_engine->resultSerialize($result, $this->path . "/tmp/" . $_COOKIE['SESSION_ID']);
				$oper = 1;
		} else {
			if($this->mail->mailMove($_REQUEST['cf'],$_REQUEST['tf'],$mids)){
				$oper = 1;
			}
		}
		echo $oper;
	}

	/**
     * 设置邮件标识
     */
	public function setmailAction(){
		if((empty($_REQUEST['f']))||(empty($_REQUEST['oper']))){
			echo 0;
		}else{
			$mark = 0;
			$mid = explode(",",substr($_REQUEST['mid'],0,-1));
			
			if ($_REQUEST['f'] == "_result_") {
				
require_once(APP_PATH. '/models/mail/MailExt.php');
				$search_engine = new WebMail_Model_MailExt($this->sinfo['userid'], $_COOKIE['SESSION_MARK'], $this->path);
				$result = $search_engine->resultDeserialize($this->path . "/tmp/search/" . $_COOKIE['SESSION_ID']);
				for ($i=0;$i<count($mid);$i++){
					$mid[$i] = base64_decode($mid[$i]);
					if($this->mail->changeProperty($mid[$i],$result[$mid[$i]],$_REQUEST['oper'],$_REQUEST['val'])){
						$mark++;
					}
				}
			} else {
				for ($i=0;$i<count($mid);$i++){
					$mid[$i] = base64_decode($mid[$i]);
					if($this->mail->changeProperty($mid[$i],$_REQUEST['f'],$_REQUEST['oper'],$_REQUEST['val'])){
						$mark++;
					}
				}
			}
			if($mark==count($mid)){
				echo 1;
			}else{
				echo 0;
			}
		}
	}

	/**
     * 空邮件界面
     */
	function blankAction(){
		require_once(APP_PATH.'/views/mail/blank.html');
	}

	/**
     * 设置显示邮件正文内容中的外链图片
     */
	function setshowimgAction(){
		if($this->mail->changeProperty(base64_decode($_REQUEST['mid']),$_REQUEST['f'],'showimg',$_REQUEST['val'])){
			echo 1;
		}else{
			echo 0;
		}
	}

	/**
     * 邮件下载
     */
	public function maildownloadAction(){
		$param = explode("|",$_REQUEST['param']);
		$mid = base64_decode($param[0]);
		$mailfile = $this->path."/eml/".$mid;
		$content = file_get_contents($mailfile);
		Header("Content-type: application/octet-stream");
		Header("Accept-Ranges: bytes");
		Header("Content-Length: ".strlen($content));
		Header("Content-Disposition: attachment; filename=".$mid.".eml");
		echo $content;
	}

	/**
     * 邮件附件下载
     */
	public function downloadAction(){
		if(!empty($_REQUEST['param'])){
			$ua = $_SERVER['HTTP_USER_AGENT'];
			$attach = json_decode(base64_decode($_REQUEST['param']));
			$filename = $attach->file;
			$tmp = explode(".",$filename);
			if($_REQUEST['type']){
				$content = file_get_contents($attach->path);
			}else{
				$content = $this->mail->getContent($attach->path,$attach->begin,$attach->length,$attach->encoding);
			}
			Header("Content-type: application/octet-stream");
			Header("Accept-Ranges: bytes");
			Header("Content-Length: ".strlen($content));
			if (preg_match("/Firefox/", $ua)) {
				Header('Content-Disposition: attachment; filename*="utf8\'\''.$filename.'"');
			} else if (preg_match("/Safari/", $ua)) {
				Header('Content-Disposition: attachment; filename="'.$filename.'"');
			} else {
				Header('Content-Disposition: attachment; filename="'.rawurlencode($filename).'"');
			}
			echo $content;
		}
	}

	/**
     * 发送回执
     */
	public function sendfeedbackAction(){
		$_REQUEST['param'] = str_replace(" ","+",$_REQUEST['param']);
		$param = json_decode(base64_decode($_REQUEST['param']),true);
		$content = LANG_MAIL_M0125.":".strtolower($_COOKIE['SESSION_MARK'])."<br>".LANG_MAIL_M0126.":".$param['subject']."<br>".LANG_MAIL_M0127.": ".date("Y-m-d H:i:s");
		$subject = LANG_MAIL_M0063.":".$param['subject'];
		$mail = array('from'=>strtolower($_COOKIE['SESSION_MARK']),'to'=>$param['to'],'subject'=>$subject,'content'=>$content,'charset'=>$param['charset']);
		$mail = (object)$mail;
		$mime = $this->mail->setFeedback($mail);
		if($mime){
			$to = array($param['to']);
			echo $this->mail->mailSend(strtolower($_COOKIE['SESSION_MARK']),$to,$mime);
		}
	}

	/**
     * 取消定时发送
     */
	function canceltimesendAction(){
		$oper = 0;
		if($this->mail->unsetTimeSend($_REQUEST['tid'])){
			if($this->mail->changeProperty(base64_decode($_REQUEST['mid']),$_REQUEST['f'],'timesend',0)){
				$oper = 1;
			}
		}
		echo $oper;
	}

	/**
     * 取消延时发送
     */
	function cancelrandsendtimeAction(){
		$oper = 0;
		if($this->mail->unsetTimeSend($_REQUEST['tid'])){
			if($this->mail->changeProperty(base64_decode($_REQUEST['mid']),$_REQUEST['f'],'randsendtime',0)){
				$this->mail->changeProperty(base64_decode($_REQUEST['mid']), 'sent', 'unsent', 1);
				if($this->mail->mailMove('sent','draft',array(base64_decode($_REQUEST['mid'])))){
					$oper = 1;
				}
			}
		}
		echo $oper;
	}

	/**
     * 清空邮箱
     */
	public function clearmailboxAction(){
		$oper = 0;
		if($this->mail->clearMailbox($_REQUEST['f'])){
			$oper = 1;
		}
		echo $oper;
	}

	/**
     * 复制到网盘界面
     */
	public function netdiskAction(){
		require_once(APP_PATH.'/views/mail/netdisk.html');
	}

	/**
     * 复制邮件到网盘
     */
	public function copytodiskAction(){
		require_once(APP_PATH.'/models/disk/File.php');
		$mailbox = $_REQUEST['f'];
		$disk = new WebMail_Model_File($this->path."/disk/");
		$used_disk = $disk->getDiskSpaceUsage();
		if($_REQUEST['type']){
			//附件复制到网盘
			$param = json_decode(base64_decode($_REQUEST['file']),true);
			echo $this->mail->copyAttachToDisk($param,$used_disk,$this->sinfo['maxsize'],$_REQUEST['t'],$disk,$_REQUEST['cover'],$this->path,$mailbox);
		}else{
			//邮件复制到网盘
			$files = explode(",",substr($_REQUEST['file'],0,-1));
			for($i=0;$i<count($files);$i++){
				$mids[$i] = base64_decode($files[$i]);
			}
			
			if ($_REQUEST['f'] == "_result_") {
require_once(APP_PATH. '/models/mail/MailExt.php');
				$search_engine = new WebMail_Model_MailExt($this->sinfo['userid'], $_COOKIE['SESSION_MARK'], $this->path);
				$result = $search_engine->resultDeserialize($this->path . "/tmp/search/" . $_COOKIE['SESSION_ID']);
				for ($i=0;$i<count($mids);$i++){
					$this->mail->copyMailsToDisk(array($mids[$i]),$used_disk,$this->sinfo['maxsize'],$_REQUEST['t'],$disk,$_REQUEST['cover'],$this->path,$result[$mids[$i]]);
				}
				return '{"code":"","tip":"'.LANG_TIP_M1002.'","state":1}';
			} else {
				echo $this->mail->copyMailsToDisk($mids,$used_disk,$this->sinfo['maxsize'],$_REQUEST['t'],$disk,$_REQUEST['cover'],$this->path,$mailbox);
			}
		}
	}

	/**
     * 邮箱信息
     */
	public function getinfoAction(){
		echo $this->mail->listingMailbox();
	}

	public function mailboxinfoAction(){
		$mailboxinfo = $this->mail->mailInfo();
		$folderinfo = json_decode($this->mail->listingMailbox(),true);
		$inbox = $folderinfo['inbox'];
		$mailboxinfo['unread'] = $inbox['unread'];
		echo json_encode($mailboxinfo);
	}

	/*************************************************************************
	* 自定义文件夹相关
	*************************************************************************/
	/**
     * 文件夹列表
     */
	public function myfolderAction(){
		$this->unsetmailtip();
		$page = 1;
		if($_REQUEST['page']){
			$page = $_REQUEST['page'];
		}
		$p1 = $this->getPageAmount(5);
		$p2 = $this->getPageAmount(6);

		$folder = $_REQUEST['folder'];
		$foldername = $_REQUEST['fname'];


		//我的文件夹
		$strfolder = json_encode($this->listfolder());

		if(empty($foldername)){
			$foldername = $this->asciiToStr($folder);
		}

		if(empty($_REQUEST['type'])){
			$_REQUEST['type'] = $_COOKIE['SET_MAILSHOW'];
		}

		//邮件列表静态数据
		$listdata = $this->staticmailbox($folder,$_REQUEST['type'],1);

		if($_REQUEST['type']==1){
			$h1 = (floor((($_COOKIE['CLIENT_Y_SCREEN']-190)/2)/26)*26)+125;
			$h2 = $_COOKIE['CLIENT_Y_SCREEN']-$h1-72;
			if($_COOKIE['SET_MAILSHOW']!=1){
				$this->set = new WebMail_Model_Setting();
				$this->set->setFile($this->sinfo['maildir']."/");
				$this->set->update('mailshow',1);
			}
			require_once(APP_PATH.'/views/mail/folder-iframe.html');
		}elseif($_REQUEST['type']==2){
			if($_COOKIE['SET_MAILSHOW']!=2){
				$this->set = new WebMail_Model_Setting();
				$this->set->setFile($this->sinfo['maildir']."/");
				$this->set->update('mailshow',2);
			}
			require_once(APP_PATH.'/views/mail/folder.html');
		}
	}

	/**
     * 新建文件夹
     */
	public function createfolderAction(){
		$path = "";
		$ppath = "";
		$fname = "";
		$strlist = json_encode($this->listfolder());
		
		require_once(APP_PATH.'/views/mail/editfolder.html');
	}

	/**
     * 新建文件夹
     */
	public function editfolderAction(){
		$usedname = array(1=>'inbox',2=>'draft',3=>'sent',4=>'trash',5=>'junk');
		
		$mailbase = new WebMail_Model_MailBase($this->path);
		$path = $_GET['path'];
		$tmp_name = $mailbase->asciitostr($path);
		$tmp_array = explode('/', $tmp_name);
		$ppath = '';
		for ($i=0; $i<count($tmp_array)-1; $i++) {
			if ($i > 0) {
				$ppath .= '/';
			}
			$ppath .= $tmp_array[$i];
		}
		$fname = $tmp_array[$i];
		
		if (!array_search($$ppath, $usedname)) {
			$ppath = $mailbase->strToAscii($ppath);
		}
		$strlist = json_encode($this->listfolder());
		
		require_once(APP_PATH.'/views/mail/editfolder.html');
	}

	/**
     * 保存文件夹
     */
	public function savefolderAction(){
		$usedname = array(1=>'inbox',2=>'draft',3=>'sent',4=>'trash',5=>'junk');
		$oper = 0;
		$mailbase = new WebMail_Model_MailBase($this->path);
		if(!empty($_REQUEST['fname'])){
			if ($_REQUEST['ppath'] != '') {
				if (array_search($_REQUEST['ppath'] ,$usedname)) {
					$ppath = $_REQUEST['ppath'];
				} else {
					$ppath = $mailbase->asciiToStr($_REQUEST['ppath']);
				}
				$tmp_name = $ppath . '/' . $_REQUEST['fname'];
			} else {
				$ppath = '';
				$tmp_name = $_REQUEST['fname'];
			}
			
			if(empty($_REQUEST['path'])){
				if($this->mail->addFolder($tmp_name)) {
					$oper = 1;
				}
			}else{
				
				$usedname1 = array(1=>LANG_MAIL_M0010,2=>LANG_MAIL_M0043,3=>LANG_MAIL_M0028,4=>LANG_MAIL_M0029,5=>LANG_MAIL_M0030);
                if (!array_search($tmp_name ,$usedname) && !array_search($tmp_name, $usedname1)){
					$allfolders = $this->listfolder();
					if($this->mail->renameFolder($_REQUEST['path'],$tmp_name)){
						$oper = 1;
						$tmp_array= $this->findSubfolder($allfolders, $mailbase->asciiToStr($_REQUEST['path']));
						if (!empty($tmp_array)) {
							$this->renameSubfolder($tmp_array, $tmp_name);
						}
					}
				}
			}
		}
		echo $oper;
	}
	
	private function findSubfolder($subfolders, $name) {
		$tmp_array = explode('/', $name);
		$ret_obj = &$subfolders;
		for ($i=0; $i<count($tmp_array); $i++) {
			$ret_obj = &$ret_obj[$tmp_array[$i]]['sub'];
			if (empty($ret_obj)) {
				return null;
			}
		}
		
		return $ret_obj;
	}
	
	private function renameSubfolder($subfolders, $dst_name) {
		foreach ($subfolders as $k => $v) {
			$this->mail->renameFolder($v['path'], $dst_name . '/' . $v['name']);
			if (!empty($v['sub'])) {
				$this->renameSubfolder($v['sub'], $dst_name . '/' . $v['name']);
			}
		}
	}
	

	/**
     * 删除文件夹
     */
	public function delfolderAction(){
		$oper = 0;
		$mailbase = new WebMail_Model_MailBase($this->path);
		if(!empty($_REQUEST['folder'])){
			$this->mail->deleteFolder($_REQUEST['folder']);
			$oper = 1;
		}
		echo $oper;
	}

	/**
     * 文件夹列表
     */
	public function listfolderAction(){
		echo json_encode($this->listfolder());
	}
	
	
	protected function listfolder() {
		$mailboxtitle = array('inbox'=>LANG_MAIL_M0010,'draft'=>LANG_MAIL_M0043,'sent'=>LANG_MAIL_M0028,'trash'=>LANG_MAIL_M0029,'junk'=>LANG_MAIL_M0030);
		
		require_once(APP_PATH."/models/mail/MailBase.php");
		$mailbase = new WebMail_Model_MailBase($this->path);
		$tmpfolders = $this->mail->listingFolder();
		if(!$tmpfolders){
			$tmpfolders = array();
		}
		for ($i=0;$i<count($tmpfolders);$i++){
			$tmpfolders[$i]['cname'] = $mailbase->asciiToStr($tmpfolders[$i]['name']);
		}
		
		
		$allfolders = array();
		
		$allfolders['inbox'] = array('title'=>$mailboxtitle['inbox'], 'path'=>'inbox', 'name'=>$mailboxtitle['inbox']);
		$allfolders['draft'] = array('title'=>$mailboxtitle['draft'], 'path'=>'draft', 'name'=>$mailboxtitle['draft']);
		$allfolders['sent'] = array('title'=>$mailboxtitle['sent'], 'path'=>'sent', 'name'=>$mailboxtitle['sent']);
		$allfolders['trash'] = array('title'=>$mailboxtitle['trash'], 'path'=>'trash', 'name'=>$mailboxtitle['trash']);
		$allfolders['junk'] = array('title'=>$mailboxtitle['junk'], 'path'=>'junk', 'name'=>$mailboxtitle['junk']);
		
		
		for ($i=0;$i<count($tmpfolders);$i++){
			$tmp_array = explode("/", $tmpfolders[$i]['cname']);
			for ($j=0; $j<count($tmp_array); $j++) {
				if ($j > 0) {
					$cur_folder .= '/' . $tmp_array[$j];
					$cur_title .= '/' . $tmp_array[$j];
					
					if (empty($parent_folder['sub'])) {
						$parent_folder['sub'] = array();
					}
					if (empty($parent_folder['sub'][$tmp_array[$j]])) {
						$parent_folder['sub'][$tmp_array[$j]] = array('title'=>$cur_title, 'path'=>$mailbase->strToAscii($cur_folder), 'name'=>$tmp_array[$j]);
					}
										
					$parent_folder = &$parent_folder['sub'][$tmp_array[$j]];
				} else {
					$cur_folder = $tmp_array[$j];
					if (!empty($mailboxtitle[$tmp_array[$j]])) {
						$cur_title = $mailboxtitle[$tmp_array[$j]];
					} else {
						$cur_title = $tmp_array[$j];
					}
					
					if (empty($allfolders[$tmp_array[$j]])) {
						$allfolders[$tmp_array[$j]] = array('title'=>$cur_title, 'path'=>$mailbase->strToAscii($cur_folder), 'name'=>$tmp_array[$j]);
					}
					
					$parent_folder = &$allfolders[$tmp_array[$j]];
				}
				
			}
		}
		
		return $allfolders;
	}

	public function newmailtipAction(){
		require_once(APP_PATH.'/models/mail/Sensor.php');
		$sensor = new WebMail_Model_Sensor();
		$res = $sensor->get($this->path);
		if($res[0]){
			echo json_encode(array('code'=>1,'cnt'=>$res[1]));
			$sensor->set($this->path, 0);
		}else{
			echo json_encode(array('code'=>0,'url'=>"/index.php/auth/login"));
		}
	}

	/**
     * 附件列表
     */
	public function attachlistAction(){
		$attach = $this->mail->getFileList($_REQUEST['mid']);
		echo json_encode($attach);
	}

	/**
     * 附件上传页面
     */
	public function uploadattachAction(){
		$mark = $_COOKIE['SESSION_MARK'];
		$key = $_COOKIE['SESSION_ID'];
		$mid = $_REQUEST['mid'];
		require_once(APP_PATH.'/views/mail/upload.html');
	}

	/**
     * 删除附件
     */
	public function delattachAction(){
		$file = str_replace(" ","+",$_REQUEST['file']);
		echo $this->mail->delAttachFile($this->path."/tmp/attach/".$_REQUEST['mid']."/".$file);
	}

	/**
     * 从网盘添加附件
     */
	public function diskattachAction(){
		$mid = $_REQUEST['mid'];
		require_once(APP_PATH.'/views/mail/diskattach.html');
	}

	/**
     * 执行从网盘添加附件
     */
	public function dodiskattachAction(){
		require_once(APP_PATH."/models/mail/MailBase.php");
		$mailbase = new WebMail_Model_MailBase($this->path);
		$rec = array('state'=>0,'tip'=>LANG_COMMON_COM008);
		$mid = $_REQUEST['mid'];
		if(!file_exists($this->path."/tmp/attach")){
			mkdir($this->path."/tmp/attach");
		}
		if(!file_exists($this->path."/tmp/attach/".$mid)){
			mkdir($this->path."/tmp/attach/".$mid);
		}
		$temp_path = $this->path."/tmp/attach/".$mid;
		$fname = $mailbase->strToAscii($_REQUEST['fname']);
		if(!file_exists($temp_path."/".$fname)){
			$fcontent = file_get_contents($this->path."/disk/".$_REQUEST['file']);
			file_put_contents($temp_path."/".$fname,$fcontent);
			$rec['state'] = 1;
		}else{
			$rec['tip'] = LANG_MAIL_M0060;
		}
		echo json_encode($rec);
	}

	/**
     * 显示内嵌图片
     */
	public function innerimgAction(){
		if(!empty($_REQUEST['param'])){
			$innrimg = json_decode(base64_decode($_REQUEST['param']));
			Header("Content-type: ".$innrimg->ctype);
			Header("Accept-Ranges: bytes");
			Header("Accept-Length: ".$attach->length);
			echo $this->mail->getContent($innrimg->path,$innrimg->begin,$innrimg->length,$innrimg->encoding);
		}
	}

	function showuploadinnerimgAction(){
		$mid = $_REQUEST['mid'];
		require_once(APP_PATH.'/views/mail/uploadinnerimg.html');
	}

	function douploadinnerimgAction(){
		require_once(APP_PATH."/models/mail/MailBase.php");
		$mailbase = new WebMail_Model_MailBase($this->path);

		$uptypes=array('image/jpg','image/jpeg','image/png','image/pjpeg','image/gif','image/bmp','image/x-png');

		$max_file_size=2000000; //上传文件大小限制, 单位BYTE

		$pathpre = $this->path."/tmp/attach/";
		$destination_folder=$_REQUEST['mid']."/"; //上传文件路径

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
			$destination = $pathpre.$destination_folder.$mailbase->strToAscii("innerimg-".date("YmdHis",time()).$authnum.".".$ftype);

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

			$picture_name = "http://".$_SERVER["SERVER_NAME"]."/index.php/mail/uploadinnerimg?param=".base64_encode($destination_folder.basename($destination));
			echo "<script language=javascript>\r\n";
			echo "window.parent.document.getElementById('content_picture').value='$picture_name';\r\n";
			echo "window.location.href='showuploadinnerimg?mid=".$_REQUEST['mid']."';\r\n";
			echo "</script>\r\n";
		}
	}

	//显示上传的内嵌图片
	public function uploadinnerimgAction(){
		if(!empty($_REQUEST['param'])){
			$innrimg = base64_decode($_REQUEST['param']);
			$file = $this->path."/tmp/attach/".$innrimg;
			$filename = basename($file);
			$tmp = explode(".",$filename);
			Header("Content-type: ".$tmp[count($tmp)-1]);
			Header("Accept-Ranges: bytes");
			Header("Accept-Length: ".filesize($file));
			echo file_get_contents($file);
		}
	}

	/**
     * 小页面邮件发送
     */
	public function sendiframeAction(){
		$mid = base64_decode($_REQUEST['mid']);
		$folder = $_REQUEST['f'];
		$offset = $_REQUEST['p'];
		$mailfile = $this->path."/".$_REQUEST['f']."/".$mid;
		$maildata = $this->mail->mailDetail($folder,$mid,'','ADDRESS');

		//合并收件人
		$to = explode(";",$maildata['to']);
		if(empty($cc)){
			$cc = explode(";",$maildata['cc']);
		}
		$k = 0;
		for ($i=0;$i<count($to);$i++){
			if(!empty($to[$i])){
				if($useradress!=trim($to[$i])){
					$address[$k] = trim($to[$i]);
					$k++;
				}
			}
		}
		for ($i=0;$i<count($cc);$i++){
			if(!empty($cc[$i])){
				if($useradress!=trim($cc[$i])){
					$address[$k] = trim($cc[$i]);
					$k++;
				}
			}
		}

		//添加联系人
		$straddress = "";
		for ($i=0;$i<count($address);$i++){
			if($address[$i]!=''){
				$straddress.= $address[$i].";";
			}
		}
		$caddress = $this->mail->splitEmailAddress($straddress);
		$contact = new WebMail_Model_UserContact();
		$contact->setpath($this->path.'/contact.dat');
		$newcontct = $contact->compareContact($caddress,$this->sinfo['domainname']);
		$strnewcontact = '';
		$shownewcontact = 'none';
		if(count($newcontct)>0){
			$shownewcontact = 'block';
			for ($i=0;$i<count($newcontct);$i++){
				if(empty($newcontct[$i]['name']))$newcontct[$i]['name'] = $newcontct[$i]['mail'];
				$param = base64_encode(json_encode($newcontct[$i]));
				$strnewcontact.='<li style="margin-left:-15px;">'.$newcontct[$i]['mail'].'&nbsp;&nbsp;<a href="javascript:void(1);" class="mailDoneLink" onclick="addnewcontact(\''.$newcontct[$i]['name'].'\',\''.$newcontct[$i]['mail'].'\')">'.LANG_CONTACT_C0049.'</a>&nbsp;&nbsp;<a href="../contact/edit?param='.$param.'" class="mailDoneLink">'.LANG_CONTACT_C0050.'</a></li>';
			}
		}

		if(count($address)>0){
			//发送邮件
			$mime = file_get_contents($mailfile);
			$oper = 0;
			if($mime){
				$oper = $this->mail->mailSend(strtolower($_COOKIE['SESSION_MARK']),$address,$mime);

				if($_REQUEST['tid']){
					if($this->mail->unsetTimeSend($_REQUEST['tid'])){
						$this->mail->changeProperty(base64_decode($_REQUEST['mid']),$folder,$_REQUEST['type'],0);
					}
				}

				$strres1 = LANG_MAIL_M0104;
				$strres2 = LANG_MAIL_M0105.$senttip.LANG_MAIL_M0106;
				if(!$oper){
					$strres1 = "<font color='red'>".LANG_MAIL_M0134."</font>";
					$strres2 = LANG_MAIL_M0135;
				}else{
					$this->mail->changeProperty($mid, 'draft', 'unsent', 0);
					$this->mail->mailMove('draft','sent',array($mid));
					foreach ($address as $add){
						$strto.=$add.";";
					}
					$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),'mail sent '.$strto,1);
					//设定邮件发送类型
					if($_POST['sendtype']!='normal'){
						$this->mail->changeProperty($_POST['omid'],$_REQUEST['mailbox'],$_POST['sendtype'],1);
					}
				}
			}
		}else{
			$strres1 = "<font color='red'>".LANG_MAIL_M0134."</font>";
			$strres2 = LANG_MAIL_M0136;
		}
		require_once(APP_PATH.'/views/mail/sent-done.html');
	}

	public function unsetmailtip(){
		require_once(APP_PATH.'/models/mail/Sensor.php');
		$sensor = new WebMail_Model_Sensor();
		$sensor->set($_COOKIE['SESSION_MARK'],0);
	}

	/*************************************************************************
	* 邮箱管理相关
	*************************************************************************/
	public function mailboxmanageAction(){
		
		$mailboxtitle = array('inbox'=>LANG_MAIL_M0010,'draft'=>LANG_MAIL_M0043,'sent'=>LANG_MAIL_M0028,'trash'=>LANG_MAIL_M0029,'junk'=>LANG_MAIL_M0030);
		
		$tmpfolders = $this->mail->allMailboxinfo();
		
		require_once(APP_PATH."/models/mail/MailBase.php");
		$mailbase = new WebMail_Model_MailBase($this->path);
		
		$allfolders = array();
		foreach ($tmpfolders as $k => $v){
			$tmp_array = explode("/", $v['title']);
			for ($i=0; $i<count($tmp_array); $i++) {
				if ($i > 0) {
					$cur_folder .= '/' . $tmp_array[$i];
					$cur_title .= '/' . $tmp_array[$i];
					
					if (empty($parent_folder['sub'])) {
						$parent_folder['sub'] = array();
					}
					if (empty($parent_folder['sub'][$tmp_array[$i]])) {
						$parent_folder['sub'][$tmp_array[$i]] = array('name'=>$tmp_array[$i],'title'=>$cur_title, 'path'=>$mailbase->strToAscii($cur_folder), 'unread'=>0, 'total'=>0, 'size'=>'0');
					}
					if ($i == count($tmp_array) - 1) {
						$parent_folder['sub'][$tmp_array[$i]]['unread'] = $v['unread'];
						$parent_folder['sub'][$tmp_array[$i]]['total'] = $v['total'];
						$parent_folder['sub'][$tmp_array[$i]]['size'] = $v['size'];
					}
										
					$parent_folder = &$parent_folder['sub'][$tmp_array[$i]];
				} else {
					$cur_folder = $tmp_array[$i];
					if (!empty($mailboxtitle[$tmp_array[$i]])) {
						$cur_title = $mailboxtitle[$tmp_array[$i]];
						$tmp_path = $cur_folder;
					} else {
						$cur_title = $tmp_array[$i];
						$tmp_path = $mailbase->strToAscii($cur_folder);
					}
					
					if (empty($allfolders[$tmp_array[$i]])) {
						$allfolders[$tmp_array[$i]] = array('name'=>$cur_title, 'title'=>$cur_title, 'path'=>$tmp_path, 'unread'=>0, 'total'=>0, 'size'=>'0');
					}
					
					
					if ($i == count($tmp_array) - 1) {
						$allfolders[$tmp_array[$i]]['unread'] = $v['unread'];
						$allfolders[$tmp_array[$i]]['total'] = $v['total'];
						$allfolders[$tmp_array[$i]]['size'] = $v['size'];
					}
					
					$parent_folder = &$allfolders[$tmp_array[$i]];
				}
				
			}
		}
		
		$strlist = json_encode($allfolders);
		
		require_once(APP_PATH.'/views/mail/mailboxmanage.html');
	}

	/**
     * eml邮件下载
     */
	public function emlmaildownloadAction(){
		$param = base64_decode($_REQUEST['param']);
		$mailfile = $this->path."/tmp/eml/".$param;
		$content = file_get_contents($mailfile);
		Header("Content-type: application/octet-stream");
		Header("Accept-Ranges: bytes");
		Header("Content-Length: ".strlen($content));
		Header("Content-Disposition: attachment; filename=".$param.".eml");
		echo $content;
	}

	/**
     * 邮件打印页
     */
	function emlprintAction(){
		$eml_param = $_REQUEST['param'];
		$data = json_decode(base64_decode($eml_param),true);
		if($data['type']){
			$emldata = $this->mail->getEmlDetail($data,1);
		}else{
			$emldata = $this->mail->getEmlDetail($data);
		}
		$emldata = json_decode($emldata,true);
		$mailfile = $this->path."/tmp/eml/".$emldata['file'];
		$charset = '';
		if(!$emldata){
			$this->errorAction();
		}else{
			$maildata = $this->mail->mailDetailContent($emldata,$mailfile,$charset);
			$content = preg_replace("/<script.+script>/i","",$maildata['content']);
			if(!$_REQUEST['showimg']){
				$maildata['content'] = $this->mail->removeHtmlImg($maildata['content']);
			}
			require_once(APP_PATH.'/views/mail/print.html');
		}
	}

	function asciiToStr($str){
		if(empty($str)){
			return $str;
		}else{
			$tmp = str_split($str);
			$newstr = '';
			for ($i=0;$i<count($tmp);$i+=2){
				$newstr.= chr(hexdec($tmp[$i].$tmp[$i+1]));
			}
			return $newstr;
		}
	}

	function opencontentAction(){
		if(!empty($_REQUEST['param'])){
			$data = json_decode(base64_decode($_REQUEST['param']));
			$fext = explode(".",$data->file);
			$type = $data->ctype;
			if(array_search(strtolower($fext[count($fext)-1]),array(1=>'txt',2=>'htm',3=>'html'))){
				$type = 'text/html';
			}
			Header("Content-type: ".$type.";charset=".$data->charset);
			Header("Accept-Ranges: bytes");
			Header("Accept-Length: ".$data->length);
			$content = $this->mail->getContent($data->path,$data->begin,$data->length,$data->encoding);
			echo $content;
		}
	}

	//取消召回
	function cancelrecallAction(){
		if(empty($_REQUEST['mid'])){
			echo 0;
		}else{
			$mid = base64_decode($_REQUEST['mid']);
			if($this->mail->changeProperty($mid,'sent','recall',0)){
				echo 1;
			}else{
				echo 0;
			}
		}
	}

	function viewmaillogAction(){
		$logfile = $this->sinfo['maildir']."/tmp/recall/".base64_decode($_REQUEST['mid']);
		if(!file_exists($logfile)){
			echo LANG_MAIL_M0153;
		}else{
			$strlist = "";
			$log = file($logfile);
			if(count($log)<=0){
				echo LANG_MAIL_M0153;
			}else{
				for ($i=0;$i<count($log);$i++){
					$row = explode("|",$log[$i]);
					$strlist.= "<tr><th>".$row[0]."</th><td>".$row[1]."</td><td>".date("Y-m-d H:i",$row[2])."</td></tr>";
				}
				require_once(APP_PATH.'/views/mail/viewmaillog.html');
			}
		}
	}

	
	
	function microtime_float()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
}
