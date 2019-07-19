<?php
require_once(APP_PATH.'/controllers/common.php');
require_once(APP_PATH.'/models/mail/Mail.php');
require_once(APP_PATH.'/models/contact/UserContact.php');
require_once(APP_PATH.'/models/Setting.php');
require_once(APP_PATH.'/models/Log.php');

class recallmailController
{
	protected $mail;
	protected $path;

	public function __construct(){
		$this->path = $this->getMaildir(base64_decode($_REQUEST['address']));
		$this->mail = new WebMail_Model_Mail($this->path);
	}

	function getMaildir($address){
		$param = array('user'=>PANDORA_DATABASE_USER,'password'=>PANDORA_DATABASE_PASSWORD,'host'=>PANDORA_DATABASE_HOST,'dbname'=>PANDORA_DATABASE_DBNAME);
		$db = new Mysql($param);
		$res = $db->select("select maildir from users where username='".$address."'");
		return $res[0]['maildir'];
	}

	function setLog($mid,$user){
		$logdir = $this->path."/tmp/recall";
		if(!file_exists($logdir)){
			mkdir($logdir);
		}
		$logfile = $logdir."/".$mid;
		$fpe = fopen($logfile,'a+');
		fwrite($fpe,base64_decode($user)."|".$_SERVER["REMOTE_ADDR"]."|".time()."\r\n");
		fclose($fpe);
	}

	function vaildMail(){
		$mid = base64_decode($_REQUEST['mid']);
		$maildata = $this->mail->mailDetail('sent',$mid,'','EDITADDRESS');

		if(!$maildata){
			header("Content-Type: text/plain; charset=utf-8");
			echo "邮件已被召回！";
			exit();
		}elseif (!$maildata['recall']){
			header("Content-Type: text/plain; charset=utf-8");
			echo "邮件已被召回！";
			exit();
		}elseif ($maildata['recall']!=$_REQUEST['key']){
			header("Content-Type: text/plain; charset=utf-8");
			echo "参数错误！";
			exit();
		}
	}

	/**
     * 邮件内容页
     */
	public function readmailAction(){
		$this->vaildMail();
		$mid = base64_decode($_REQUEST['mid']);
		$folder = 'sent';
		$offset = $_REQUEST['p'];
		$charset = "utf-8";
		if($_REQUEST['charset']){
			$charset = $_REQUEST['charset'];
		}

		$maildata = $this->mail->mailDetail($folder,$mid,$charset,'EDITADDRESS');

		$this->setLog($mid,$_REQUEST['to']);
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
				//if(empty($maildata['attach'][$i]['cid'])||(strstr($maildata['attach'][$i]['ctype'],"application/"))){
				if(!empty($maildata['attach'][$i]['file'])){
					$param = base64_encode(json_encode($maildata['attach'][$i]));
					if($maildata['attach'][$i]['type']){
						$url = "download?type=1&param=".$param."&key=".$_REQUEST['key']."&mid=".$_REQUEST['mid']."&to=".$_REQUEST['to']."&address=".$_REQUEST['address'];
					}else{
						$url = "download?type=0&param=".$param."&key=".$_REQUEST['key']."&mid=".$_REQUEST['mid']."&to=".$_REQUEST['to']."&address=".$_REQUEST['address'];
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
    							  <a href="'.$url.'" class="bluelink">'.LANG_COMMON_COM043.'</a>
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
			$this->mail->changeProperty($mid,$folder,'read',1);
		}

		if($_REQUEST['edit']){
			require_once(APP_PATH.'/views/mail/readmail-draft-small.html');
		}elseif($_REQUEST['type']){
			if($_GET['back']){
				$strback = '<span style="margin-left:15px;"><a href="javascript:void(0);" onclick="backtolist(\''.$_GET['f'].'\','.$_GET['page'].')" style="text-decoration:none;color:#005590;"><< '.LANG_COMMON_COM025.'</a></span>';
				$listfolder = $_GET['f'];
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
			require_once(APP_PATH.'/views/mail/readrecallmail.html');
		}
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

	/**
     * 邮件附件下载
     */
	public function downloadAction(){
		$this->vaildMail();
		if(!empty($_REQUEST['param'])){
			$ua = $_SERVER['HTTP_USER_AGENT'];
			$attach = json_decode(base64_decode($_REQUEST['param']));
			$filename = $attach->file;
			$encoded_filename = urlencode($filename);
			$encoded_filename = str_replace("+","%20",$encoded_filename);
			$tmp = explode(".",$filename);
			if($_REQUEST['type']){
				$content = file_get_contents($attach->path);
			}else{
				$content = $this->mail->getContent($attach->path,$attach->begin,$attach->length,$attach->encoding);
			}
			Header("Content-type: application/octet-stream");
			Header("Accept-Ranges: bytes");
			Header("Content-Length: ".strlen($content));
			if (preg_match("/MSIE/", $ua)) {
				Header('Content-Disposition: attachment; filename="'.iconv("utf-8","gb2312",$filename).'"');
			} else if (preg_match("/Firefox/", $ua)) {
				Header('Content-Disposition: attachment; filename*="utf8\'\''.$filename.'"');
			} else {
				Header('Content-Disposition: attachment; filename="'. $filename.'"');
			}
			echo $content;
		}
	}
}