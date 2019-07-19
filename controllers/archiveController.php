<?php
require_once(APP_PATH.'/controllers/common.php');
require_once(APP_PATH.'/models/Setting.php');


class archiveController extends Common
{
	protected $sinfo;
	protected $path;
	protected $set;
	protected $log;

	public function __construct(){
		parent::init();
		$this->sinfo = $this->getSession($_REQUEST['ajax']);
		$this->path = $this->sinfo['maildir'];
		$this->set = new WebMail_Model_Setting();
		$this->set->setFile($this->sinfo['maildir']."/");
	}

	
	/*
     * 归档邮件搜索条件框显示
     */
	public function conditionAction(){
		if (!$this->chkArchiveRights($this->sinfo['privilege'])) {
			$this->redirect("/index.php/error/auth");
		} else {
			require_once(APP_PATH.'/views/archive/condition.html');
		}
	}
	
	/*
	 * 根据搜索条件并显示邮件列表
	 */
	public function searchAction(){
		
		$page = 1;
		$static_data_page = 1;
		if($_GET['page']){
			$page = $_GET['page'];
			$static_data_page = $_GET['page'];
		}

		$p1 = $this->getPageAmount(5);
		$p2 = $this->getPageAmount(6);
		
		
		if(empty($_REQUEST['type'])){
			$_REQUEST['type'] = $_COOKIE['SET_MAILSHOW'];
		}
		
		
		require_once(APP_PATH.'/models/archive/IDB_Classify.php');
		
		$config = parse_ini_file(APP_PATH . "/config/config.ini", true);
		$cidb = new IDB_Classify($config['archiver']);
		
		
		if ('NULL' != $_REQUEST['received1']) {
			$tm1 = mktime(0, 0, 0) - 86400*$_REQUEST['received1'];
		}
		
		if ('NULL' != $_REQUEST['received2']) {
			$tm2 = mktime(0, 0, 0) - 86400*$_REQUEST['received2'];
		}
		
		if ('NULL' != $_REQUEST['received1'] && 'NULL' != $_REQUEST['received2']) {
			if ($tm1 > $tm2) {
				$tm1 += 86400;
			} else {
				$tm2 += 86400;
			}
		}
		
		
		if(!file_exists($this->path."/tmp/archive")){
			mkdir($this->path."/tmp/archive");
		} else {
			//清理遗留数据
			if ($dh = opendir($this->path."/tmp/archive")) {
				while (($file = readdir($dh)) !== false) {
					if ($file == "." || $file == "..") {
						continue;
					}
					if (time() - filectime($this->path."/tmp/archive/" . $file) >= 24*60*60) {
						unlink($this->path."/tmp/archive/" . $file);
					}
				}
				closedir($dh);
			}
		}
		
		$res = $cidb->search($_COOKIE['SESSION_MARK'], $_REQUEST['sender'],
				$_REQUEST['rcpt'], $_REQUEST['from'], $_REQUEST['to'], $_REQUEST['cc'],
				$tm1, $tm2, $_REQUEST['priority'], $_REQUEST['subject'],
				$_REQUEST['content'], $_REQUEST['attachment']);
		
		
		if (false == $res) {
			$result = array();
		} else {
			$result = $res['data'];
		}
		
		file_put_contents($this->path . "/tmp/archive/" . $_COOKIE['SESSION_ID'], serialize($result));
		
		require_once(APP_PATH.'/models/archive/MailArchive.php');
		$archive_engine = new WebMail_Model_MailArchive($_COOKIE['SESSION_MARK'], $this->path);
		
		$pagesize = 5;
		if($_REQUEST['type']==1)$pagesize = 6;
		if($static_data_page){
			$listdata = $archive_engine->resultListing($result,$static_data_page,$this->getPageAmount($pagesize));
		}
		
		require_once(APP_PATH."/models/mail/IDB_Mail.php");
		require_once(APP_PATH."/models/mail/MailBase.php");
		$idbmail = new IDB_Mail($this->path);
		$mailbase = new WebMail_Model_MailBase($this->path);
		$opt = $idbmail->listfolder();
		if($opt['state']){
			for($i=0;$i<count($opt['data']);$i++){
				$folder[$i]['title'] = $mailbase->asciiToStr($opt['data'][$i]);
				$folder[$i]['name'] = $opt['data'][$i];
				//我的文件夹
				$strfolder.='<option value="'.$folder[$i]['name'].'"><div style="cursor:pointer;width:60px;white-space:nowrap;text-overflow:ellipsis;overflow:hidden;">'.$folder[$i]['title'].'</div></option>';
			}
		}
					
		if($_REQUEST['type']==1){
			$h1 = (floor((($_COOKIE['CLIENT_Y_SCREEN']-190)/2)/26)*26)+125;
			$h2 = $_COOKIE['CLIENT_Y_SCREEN']-$h1-72;
			if($_COOKIE['SET_MAILSHOW']!=1){
				$this->set->update('mailshow',1);
			}
			require_once(APP_PATH.'/views/archive/list-iframe.html');
		}elseif($_REQUEST['type']==2){
			if($_COOKIE['SET_MAILSHOW']!=2){
				$this->set->update('mailshow',2);
			}
			require_once(APP_PATH.'/views/archive/list.html');
		}
	}
	
	public function mailboxAction(){
		$page = 1;
		$static_data_page = 1;
		if($_GET['page']){
			$page = $_GET['page'];
			$static_data_page = $_GET['page'];
		}

		$p1 = $this->getPageAmount(5);
		$p2 = $this->getPageAmount(6);
		
		require_once(APP_PATH."/models/mail/IDB_Mail.php");
		require_once(APP_PATH."/models/mail/MailBase.php");
		$idbmail = new IDB_Mail($this->path);
		$mailbase = new WebMail_Model_MailBase($this->path);
		$opt = $idbmail->listfolder();
		if($opt['state']){
			for($i=0;$i<count($opt['data']);$i++){
				$folder[$i]['title'] = $mailbase->asciiToStr($opt['data'][$i]);
				$folder[$i]['name'] = $opt['data'][$i];
				//我的文件夹
				$strfolder.='<option value="'.$folder[$i]['name'].'"><div style="cursor:pointer;width:60px;white-space:nowrap;text-overflow:ellipsis;overflow:hidden;">'.$folder[$i]['title'].'</div></option>';
			}
		}
		

		$result = file_get_contents($this->path . "/tmp/archive/" . $_COOKIE['SESSION_ID']);
		$result = unserialize($result);
		
		require_once(APP_PATH.'/models/archive/MailArchive.php');
		$archive_engine = new WebMail_Model_MailArchive($_COOKIE['SESSION_MARK'], $this->path);
		
		$pagesize = 5;
		if($_REQUEST['type']==1)$pagesize = 6;
		$listdata = $archive_engine->resultListing($result,$static_data_page,$this->getPageAmount($pagesize));

		if($_REQUEST['type']==1){
			$h1 = (floor((($_COOKIE['CLIENT_Y_SCREEN']-190)/2)/26)*26)+125;
			$h2 = $_COOKIE['CLIENT_Y_SCREEN']-$h1-72;
			if($_COOKIE['SET_MAILSHOW']!=1){
				$this->set->update('mailshow',1);
			}
			require_once(APP_PATH.'/views/archive/list-iframe.html');
		}elseif($_REQUEST['type']==2){
			if($_COOKIE['SET_MAILSHOW']!=2){
				$this->set->update('mailshow',2);
			}
			require_once(APP_PATH.'/views/archive/list.html');
		}
	}
	
	/**
     * 邮件列表记录集接口
     */
	public function maillistAction(){
		
		$result = file_get_contents($this->path . "/tmp/archive/" . $_COOKIE['SESSION_ID']);
		$result = unserialize($result);
		
		require_once(APP_PATH.'/models/archive/MailArchive.php');
		$archive_engine = new WebMail_Model_MailArchive($_COOKIE['SESSION_MARK'], $this->path);
		
		$pagesize = 5;
		if($_REQUEST['type'])$pagesize = 6;
		if($_REQUEST['page']){
			echo $archive_engine->resultListing($result,$_REQUEST['page'],$this->getPageAmount($pagesize));
		}
		
	}
	
	/**
     * 邮件内容页
     */
	public function readmailAction(){
		$mid = base64_decode($_REQUEST['mid']);
		
		$offset = $_REQUEST['p'];
		$charset = "";
		if($_REQUEST['charset']){
			$charset = $_REQUEST['charset'];
		}

		require_once(APP_PATH.'/models/archive/MailArchive.php');
		$archive_engine = new WebMail_Model_MailArchive($_COOKIE['SESSION_MARK'], $this->path);
		
				
		$pagesize = $this->getPageAmount(5);
		$curpage = $_REQUEST['curpage'];
		
		$result = file_get_contents($this->path . "/tmp/archive/" . $_COOKIE['SESSION_ID']);
		$result = unserialize($result);
		
		if ($mid >= count($result)) {
			$this->errorAction();
		}
		
		$mid_sque = array('nextmid'=>$mid<count($result)-1?$mid+1:-1,"premid"=>$mid>0?$mid-1:-1,"retpage"=>floor((count($result)-1)/$pagesize)+1,"curpage"=>$curpage);
		
		$maildata = $archive_engine->mailDetail($result[$mid][0], $result[$mid][1], $charset,'EDITADDRESS');
		
		if(!$maildata){
			$this->errorAction();
		}
		
		$maildata['nextmid'] = $mid_sque['nextmid'];
		$maildata['premid'] = $mid_sque['premid'];
		
		
		
		
		
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
							  <a href="'.$url.'" class="bluelink">'.LANG_COMMON_COM043.'</a>&nbsp;&nbsp;<br>
							  <span id="mplayer_'.$i.'"></span>
							  </div></li>';
				}
			}
			$attach_tip = '<div id="div_L_Sendor"><span class="sendor">'.LANG_MAIL_M0053.'：</span><span style="float: left;" title="'.LANG_MAIL_M0119.'">'.$attach_count.'个</span><span class="icoFile"></span>&nbsp;'.LANG_MAIL_M0119.'</div>';
		}
		$isshowimg = 1;
		if(!isset($result[$mid][2])){
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
		
		
		$content = $maildata['content'];
		$content = str_ireplace("<body","<br",$content);

		if(!$isshowimg){
			$content = $this->removeHtmlImg($content);
		}

		if(empty($content))$content = "<p>&nbsp;</p>";

		
		
		//邮件序列设置
		$str_sque_premid = $str_sque_nextmid = "";
		if($maildata['premid'] >= 0){
			$str_sque_premid = '<a href="readmail?mid='.base64_encode($maildata['premid']).'&f='.$folder.'&id=0&back='.$_GET['back'].'&type='.$_GET['type'].'&curpage='.$mid_sque['retpage'].'&time='.time().'" class="bluelink">'.LANG_MAIL_M0157.'</a>';
		}

		if($maildata['nextmid'] >= 0){
			$str_sque_nextmid = '<a href="readmail?mid='.base64_encode($maildata['nextmid']).'&f='.$folder.'&id=0&back='.$_GET['back'].'&type='.$_GET['type'].'&curpage='.$mid_sque['retpage'].'&time='.time().'" class="bluelink">'.LANG_MAIL_M0158.'</a>';
		}

		if(!empty($str_sque_premid)||!empty($str_sque_nextmid)){
			$str_sque = '<span>'.$str_sque_premid.'</span>&nbsp;&nbsp;<span>'.$str_sque_nextmid.'</span><span style="padding:0px 5px 0px 5px;">|</span>';
		}

		if($_REQUEST['type']){
			if($_GET['back']){
				
				$strback = '<span style="margin-left:15px;"><a href="javascript:void(0);" onclick="backtolist(\'_archive_\','.$_GET['curpage'].')" style="text-decoration:none;color:#005590;"><< '.LANG_COMMON_COM025.'</a></span><input type="hidden" value="1" id="isback">';
					
				$listfolder = $_GET['f'];
			}else{
				$str_sque = "";
			}
			require_once(APP_PATH.'/views/archive/readmail-small.html');
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
			require_once(APP_PATH.'/views/archive/readmail.html');
		}
	}
	
	
	function setshowimgAction(){
		
		$mid = base64_decode($_REQUEST['mid']);
		$result = file_get_contents($this->path . "/tmp/archive/" . $_COOKIE['SESSION_ID']);
		$result = unserialize($result);
		if ($mid >= count($result) || $mid < 0) {
			echo 0;
		}
		
		$result[$mid][2] = 1;
		file_put_contents($this->path . "/tmp/archive/" . $_COOKIE['SESSION_ID'], serialize($result));		
		echo 1;
	}
	
	function removeHtmlImg($str){
		//preg_match_all("/<img(.*)src=\"([^\"]+)\"[^>]+>/isU",$str,$arrimg);
		preg_match_all("/<img(.[^<]*)src=\"?(.[^<\"]*)\"?(.[^<]*)\/?>/is",$str,$arrimg);
		preg_match_all("/background=\"?(.[^<\"]*)\"?(.[^<]*)\/?>/is",$str,$arrbg);
		preg_match_all("/<base(.[^<]*)\/?>/is",$str,$arrbase);
		foreach ($arrimg[2] as $img){
			if(!strpos($img,'innerimg?param=')){
				$str = str_replace($img,"",$str);
			}
		}

		foreach ($arrbg[1] as $bg){
			$bg = str_replace(">","",$bg);
			if(!strpos($bg,'innerimg?param=')){
				$str = preg_replace("'/".$bg."/'"," ",$str);
			}
		}
		foreach ($arrbase[0] as $ba){
			if(!strpos($ba,'innerimg?param=')){
				$str = str_replace($ba,"",$str);
			}
		}
		return $str;
	}
	
	public function innerimgAction(){
		if(!empty($_REQUEST['param'])){
			$innrimg = json_decode(base64_decode($_REQUEST['param']));
			Header("Content-type: ".$innrimg->ctype);
			Header("Accept-Ranges: bytes");
			Header("Accept-Length: ".$attach->length);
			echo $this->getContent($innrimg->path,$innrimg->begin,$innrimg->length,$innrimg->encoding);
		}
	}
	
	public function downloadAction(){
		if(!empty($_REQUEST['param'])){
			$ua = $_SERVER['HTTP_USER_AGENT'];
			$attach = json_decode(base64_decode($_REQUEST['param']));
			$filename = $attach->file;
			$tmp = explode(".",$filename);
			if($_REQUEST['type']){
				$content = file_get_contents($attach->path);
			}else{
				$content = $this->getContent($attach->path,$attach->begin,$attach->length,$attach->encoding);
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
	
	function getContent($path,$begin,$length,$encoding){
		$fpe = fopen($path,"r");
		fseek($fpe,$begin, SEEK_SET);
		$content = fread($fpe,$length);
		if(strtolower($encoding)=="base64"){
			$content = base64_decode($content);
		}elseif(strtolower($encoding)=="quoted-printable"){
			$content = quoted_printable_decode($content);
		}
		fclose($fpe);
		return $content;
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
			$content = $this->getContent($data->path,$data->begin,$data->length,$data->encoding);
			echo $content;
		}
	}
	
	/**
     * 邮件下载
     */
	public function maildownloadAction(){
		require_once(APP_PATH.'/models/archive/MailArchive.php');
		$archive_engine = new WebMail_Model_MailArchive($_COOKIE['SESSION_MARK'], $this->path);
		
		$result = file_get_contents($this->path . "/tmp/archive/" . $_COOKIE['SESSION_ID']);
		$result = unserialize($result);
		
		$mid = json_decode(base64_decode($_REQUEST['param']));
		
		if ($mid >= count($result)) {
			return;
		}
		$path = $archive_engine->mailPath($result[$mid][0], $result[$mid][1]);
		if (false == $path) {
			return;
		}
		$content = file_get_contents($path);
		Header("Content-type: application/octet-stream");
		Header("Accept-Ranges: bytes");
		Header("Content-Length: ".strlen($content));
		Header("Content-Disposition: attachment; filename=".$mid.".eml");
		echo $content;
	}
	
	/**
     * 复制邮件到
     */
	public function movemailsAction(){
		
		$oper = 0;
		$mids = explode(",",substr($_REQUEST['mid'],0,-1));
		for($i=0;$i<count($mids);$i++){
			$mids[$i] = base64_decode($mids[$i]);
		}
		
		$result = file_get_contents($this->path . "/tmp/archive/" . $_COOKIE['SESSION_ID']);
		$result = unserialize($result);
		
		require_once(APP_PATH."/models/archive/IDB_Classify.php");
		require_once(APP_PATH."/models/mail/IDB_Mail.php");		
		
		$config = parse_ini_file(APP_PATH . "/config/config.ini", true);
		$cidb = new IDB_Classify($config['archiver']);
		
		$idbmail = new IDB_Mail($this->path);
		
		for ($i=0; $i<count($mids); $i++) {
			$mid = $mids[$i];
			if ($mid >= count($result) || $mid < 0) {
				continue;	
			}
			
			$server_id = $result[$mid][0];
			$mail_id = $result[$mid][1];
			
			$opt = $cidb->match($server_id, $mail_id);
			if(!$opt['state']){
				continue;
			}
		
			$tmp_pos = strpos($opt['data'], ' ');
			$tmp_path = substr($opt['data'], 0, $tmp_pos);
			
			$path = $cidb->getprefix($server_id) . $tmp_path . "/". $mail_id;
			
			$filename = time() . "." . $i . ".archive";
			
			if (!copy($path, $this->path . "/eml/" . $filename)) {
				continue;
			}
			$opt = $idbmail->insert($_REQUEST['tf'], $filename, "(S)");
			if (!$opt['state']) {
				unlink($dstfolder . "/" . $filename);
			}
		}
		
		echo 1;
	}
	
	/**
     * 空邮件界面
     */
	function blankAction(){
		require_once(APP_PATH.'/views/archive/blank.html');
	}
}

?>
