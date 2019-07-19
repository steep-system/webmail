<?php
require_once(APP_PATH."/models/mail/IDB_Mail.php");
require_once(APP_PATH."/models/mail/MailBase.php");
require_once(APP_PATH.'/models/Log.php');
require_once(APP_PATH."/models/Socket.php");

/**
 * 定义 WebMail_Model_Mail 类
 *
 * @copyright 
 * @author Rick Jin
 * @package pandora
 * @version 1.0
 */
class WebMail_Model_Mail
{
	protected $idbmail;
	protected $mailbase;
	protected $log;
	protected $path;
	protected $errordesc;

	/**
     * 构造函数
     *
     * @param string $path 索引文件根目录
     * @return void
     */
	function __construct($path){
		@ini_set('memory_limit', '1024M');
		$this->idbmail = new IDB_Mail($path);
		$this->mailbase = new WebMail_Model_MailBase($path);
		$this->log = new WebMail_Model_Log();
		$this->path = $path;
		$this->errordesc = parse_ini_file(APP_PATH."/config/error.ini",true);
	}

	function createMid(){
		return time().".".rand(1,100).".".PANDORA_PATH_HOST;
	}

	/**
     * 生成邮件mime
     *
     * @param object $mail 邮件内容对象
     * @param string $sender 发件人
     * @param string $folder 邮件所在目录
     * @return string
     */
	function createMime($mail,$sender,$folder){
		$mail->from = array(0=>array('name'=>$mail->sender,'mail'=>$sender));
		$tmp_cid = explode("@",$sender);

		//附件处理
		$attachfiles = $this->mailbase->mailAttachlist($mail->mid);
		$m1 = $m2 = 0;
		for ($i=0;$i<count($attachfiles);$i++){
			if($attachfiles[$i]['show']){
				$attachname = str_replace(" ","",$attachfiles[$i]['filename']);
				$attachfiles[$i]['filename'] = $attachname;
				$attach[$m1] = $attachfiles[$i];
				$attach[$m1]['filepath'] = $this->path."/tmp/attach/".$mail->mid."/".$attachfiles[$i]['file'];
				$m1++;
			}else{
				//内嵌图片处理
				$attachname = str_replace(" ","",$attachfiles[$i]['filename']);

				$spf = $this->mailbase->asciiToStr($attachfiles[$i]['file']);
				$signpic = "";
				if(substr($spf,0,5)=="sign-"){
					$signpic = base64_encode($spf);
					$fname = str_replace('sign-','',$attachfiles[$i]['filename']);
					$fname = str_replace('innerimg-','',$fname);
					$fname = 'sign-'.$fname;
				}else{
					$fname = str_replace('sign-','',$attachfiles[$i]['filename']);
					if(substr_count($fname,'innerimg-')<=1){
						$fname = str_replace('innerimg-','',$fname);
					}else{
						$fname = str_replace('innerimg-','',$fname);
						$fname = 'innerimg-'.$fname;
					}
				}

				$attachfiles[$i]['filename'] = $attachname;
				$htmlimage[$m2] = $attachfiles[$i];
				$htmlimage[$m2]['filepath'] = $this->path."/tmp/attach/".$mail->mid."/".$attachfiles[$i]['file'];
				$htmlimage[$m2]['cid'] = md5(uniqid(time()))."@".$tmp_cid[1];
				$htmlimage[$m2]['signpic'] = $signpic;

				if($mail->sendtype=='normal'){
					//普通邮件
					$chk_folder = $folder;
					$chk_mid = $mail->mid;
				}else{
					//回复或转发邮件
					$chk_folder = $mail->mailbox;
					$chk_mid = $mail->omid;
				}

				//检查该邮件是否存在
				$editmail = $this->idbmail->match($chk_folder,$chk_mid);
				if($editmail['state']){
					$editmail['data'] = json_decode($editmail['data'],true);
				}

				if($editmail['state']){
					for ($n=0;$n<count($editmail['data']['mimes']);$n++){
						$editmail['data']['mimes'][$n]['filename'] = $this->mailbase->strDecode(base64_decode($editmail['data']['mimes'][$n]['filename']));
						if (!empty($editmail['data']['mimes'][$n]['cid']))
							$editmail['data']['mimes'][$n]['cid'] = trim($this->mailbase->strDecode(base64_decode($editmail['data']['mimes'][$n]['cid'])), "<>");
						//echo $editmail['data']['mimes'][$n]['filename']."|".$fname."<br>";
						if($editmail['data']['mimes'][$n]['filename']==$fname){
							$htmlimage[$m2]['filename'] = $fname;
							$tmp = array('file'=>$fname,
							'begin'=>$editmail['data']['mimes'][$n]['begin'],
							'length'=>$editmail['data']['mimes'][$n]['length'],
							'encoding'=>$editmail['data']['mimes'][$n]['encoding'],
							'path'=>$this->path."/eml/".$editmail['data']['file'],
							'ctype'=>$editmail['data']['mimes'][$n]['ctype'],
							'cid'=>$editmail['data']['mimes'][$n]['cid']);

							if(empty($tmp['cid']))$tmp['cid'] = base64_decode($editmail['data']['mimes'][$n]['cntl']);
							$tmp = base64_encode(json_encode($tmp));
							$mail->content = str_replace('http://'.$_SERVER['SERVER_NAME'].'/index.php/mail/innerimg?param='.$tmp,"cid:".$htmlimage[$m2]['cid'],$mail->content);
							$mail->content = str_replace('"innerimg?param='.$tmp,"\"cid:".$htmlimage[$m2]['cid'],$mail->content);
						}else{
							//内嵌图片替换
							if(!empty($htmlimage[$m2]['signpic'])){
								$mail->content = str_replace('http://'.$_SERVER['SERVER_NAME'].'/index.php/set/uploadsingpic?param='.$htmlimage[$m2]['signpic'],"cid:".$htmlimage[$m2]['cid'],$mail->content);
							}else{
								$mail->content = str_replace('http://'.$_SERVER['SERVER_NAME'].'/index.php/mail/uploadinnerimg?param='.base64_encode($mail->mid."/".$attachfiles[$i]['file']),"cid:".$htmlimage[$m2]['cid'],$mail->content);
							}
						}
					}
				}else{
					//内嵌图片替换
					if(!empty($htmlimage[$m2]['signpic'])){
						$mail->content = str_replace('http://'.$_SERVER['SERVER_NAME'].'/index.php/set/uploadsingpic?param='.$htmlimage[$m2]['signpic'],"cid:".$htmlimage[$m2]['cid'],$mail->content);
					}else{
						$mail->content = str_replace('http://'.$_SERVER['SERVER_NAME'].'/index.php/mail/uploadinnerimg?param='.base64_encode($mail->mid."/".$attachfiles[$i]['file']),"cid:".$htmlimage[$m2]['cid'],$mail->content);
					}
				}
				$m2++;
			}
		}

		$n = 0;
		for ($i=0;$i<count($htmlimage);$i++){
			if(strpos($mail->content,$htmlimage[$i]['cid'])){
				$nhtmlimage[$n] = $htmlimage[$i];
				$n++;
			}
		}

		$mail->attachments = $attach;
		$mail->htmlimage = $nhtmlimage;

		//邮件编码
		require_once(APP_PATH."/models/mail/MailEncode.php");
		$mailencode = new WebMail_Model_MailEncode();

		//指定编码
		$charset = $_COOKIE['SET_MAILCODE'];
		$mailencode->setCharset($charset);

		$mime = $mailencode->save($mail);
		return $mime;
	}

	/**
     * 生成可召回邮件邮件mime
     *
     * @param object $mail 邮件内容对象
     * @param string $sender 发件人
     * @param string $folder 邮件所在目录
     * @return string
     */
	function createRecallMime($mail,$sender){
		$mail->from = array(0=>array('name'=>$mail->sender,'mail'=>$sender));
		$tmp_cid = explode("@",$sender);

		//邮件编码
		require_once(APP_PATH."/models/mail/MailEncode.php");
		$mailencode = new WebMail_Model_MailEncode();

		//指定编码
		$charset = $_COOKIE['SET_MAILCODE'];
		$mailencode->setCharset($charset);

		$mime = $mailencode->save($mail);
		return $mime;
	}

	function mailSave($data,$folder,$sender){
		$mail = (object)$data;

		$mark = 0;
		$oldmid = $mail->mid;
		
		//重新分配mid
		$mid = time().".".rand(1,100).".".PANDORA_PATH_HOST;
		
		//取得邮件mime
		$mime = $this->createMime($mail,$sender,$folder);

		if (file_exists($this->path."/eml/".$oldmid)) {
			$this->idbmail->delete($folder,array($oldmid));
			unlink($this->path."/eml/".$oldmid);
		}
		
		if(file_put_contents($this->path."/eml/".$mid,$mime)){
			//保存邮件
			$opt = $this->idbmail->insert($folder, $mid, "(S)");
			if($opt['state']){
				$mark = array('mid'=>$mid,'mime'=>$mime);
			}
		}

		return $mark;
	}

	function recallmailSave($data,$folder,$sender){
		$mail = (object)$data;

		$mark = 0;
		$oldmid = $mail->mid;
		
		//重新分配mid
		$mid = time().".".rand(1,100).".".PANDORA_PATH_HOST;
		
		//取得邮件mime
		$mime = $this->createMime($mail,$sender,$folder);

		if (file_exists($this->path."/eml/".$oldmid)) {
			$this->idbmail->delete($folder,array($oldmid));
			unlink($this->path."/eml/".$oldmid);
		}

		if(file_put_contents($this->path."/eml/".$mid,$mime)){
			//保存邮件
			$opt = $this->idbmail->insert($folder, $mid, "(S)");
			if($opt['state']){
				$this->changeProperty($mid, $folder, 'recall', '"'.md5($mid).'"');
				$mark = array('mid'=>$mid,'mime'=>$mime);
			}
		}

		return $mark;
	}

	/**
     * 发送邮件
     *
     * @param string $from	发件人
     * @param string $to 收件人
     * @param string $mime 邮件mime
     * @return bool
     */
	function mailSend($from,$to,$mime){
		require_once(APP_PATH."/models/mail/Smtp.php");
		for ($i=0;$i<count($to);$i++){
			preg_match_all("/<.*>/",$to[$i],$out);
			if(!empty($out[0][0])){
				$to[$i] = preg_replace("/[<|>]/","",$out[0][0]);
			}
		}
		$smtp = new WebMail_Model_Smtp('127.0.0.1');
		return $smtp->send($from,$to,$mime);
	}

	/**
     * 删除邮件
     *
     * @param string $folder 索引文件目录
     * @param array $mids 邮件ID数组
     * @return bool
     */
	function mailRemove($mids,$folder){
		//取消定时或延时发送
		$strmids = "";
		for($i=0;$i<count($mids);$i++){
			$mail = $this->idbmail->match($folder,$mids[$i]);
			if($mail['state']){
				//取消延时发送
				$mdata = json_decode($mail['data'],true);
				if($mdata['randsendtime']){
					$rst = explode("|",$mdata['randsendtime']);
					$this->unsetTimeSend($rst[0]);
				}

				//取消定时发送
				if($mdata['timesend']){
					$st = explode("|",$mdata['timesend']);
					$this->unsetTimeSend($st[0]);
				}

				//删除邮件查看日志
				if(isset($mdata['recall'])){
					unlink($this->path."/tmp/recall/".$mids[$i]);
				}
			}
			$strmids.=$mids[$i];
			if($i<(count($mids)-1))$strmids.=",";
		}

		$opt = $this->idbmail->delete($folder,$mids);
		if($opt['state']){
			foreach ($mids as $mid){
				unlink($this->path."/eml/".$mid);
			}
			$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Mail deleted [".$this->path."/eml"."][".$strmids."]",1);
		}else{
			$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Mail deleted [".$this->path."/eml"."][".$strmids."][Error:".$this->errordesc['ERR'.$opt['error']]."]",0);
			return false;
		}
		return true;
	}

	/**
     * 移动邮件
     *
     * @param string $srcfolder 移动源索引文件目录
     * @param array $mids 邮件ID数组
     * @param string $dstfolder 移动目标索引文件目录
     * @return bool
     */
	function mailMove($srcfolder,$dstfolder,$mids){
		foreach ($mids as $mid){
			$mail = $this->idbmail->match($srcfolder,$mid);
			if($mail['state']){
				//取消延时发送
				$mdata = json_decode($mail['data'],true);
				if($mdata['randsendtime']){
					$rst = explode("|",$mdata['randsendtime']);
					$this->unsetTimeSend($rst[0]);
					$this->changeProperty($mid,$srcfolder,'randsendtime',0);
				}

				//取消定时发送
				if($mdata['timesend']){
					$st = explode("|",$mdata['timesend']);
					$this->unsetTimeSend($st[0]);
					$this->changeProperty($mid,$srcfolder,'timesend',0);
				}

				//取消可召回
				if($srcfolder=="sent"){
					if((isset($mdata['recall']))&&($mdata['recall']!='')){
						$this->changeProperty($mid,$srcfolder,'recall',0);
						unlink($this->path."/tmp/recall/".$mid);
					}
				}
			}

			//检查目标目录中文件是否存在
			$chk = $this->idbmail->match($dstfolder,$mid);

			if((count($chk['data'])<=0)){
				$opt = $this->idbmail->move($srcfolder,$dstfolder,$mid);
				if($opt['state']){
					$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Mail moved [".$this->path."/".$srcfolder."/".$mid." To ".$this->path."/".$dstfolder."/".$mid."]",1);
				}else{
					$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Mail moved [".$this->path."/".$srcfolder."/".$mid." To ".$this->path."/".$dstfolder."/".$mid."] [Error:".$this->errordesc['ERR'.$opt['error']]."(".$opt['error'].")]",0);
					return false;
				}
			}else{
				$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Mail moved [".$this->path."/".$srcfolder."/".$mid." To ".$this->path."/".$dstfolder."/".$mid."] [Error:Target directory exists identical file]",0);
				return false;
			}
		}
		return true;
	}

	/**
     * 邮件列表
     *
     * @param string $srcfolder 邮箱目录
     * @param int $start 记录起始位置
     * @param int $amount 显示记录条数
     * @param string $order 排序字段
     * @param string $sort 排序方式
     * @return string
     */
	function mailListing($folder,$start,$amount,$order,$sort='DSC'){
		if($start<=1){
			$row_start = 0;
		}else{
			$row_start = ($start-1)*$amount;
		}

		$opt = $this->idbmail->listing($folder,$order,$sort,$row_start,$amount);
		$mailboxinfo = $this->idbmail->sum($folder);
		$maxpage = ceil($mailboxinfo['total']/$amount);
		if(!$opt['state']){
			$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Mail listing [Folder:".$folder."] [Error:".$this->errordesc['ERR'.$opt['error']]."]",0);
			return 0;
		}else{
			$rows = $opt['data'];
			for ($i=0;$i<count($rows);$i++){
				$tmp = $rows[$i];
				$rows[$i] = json_decode($rows[$i],true);
				unset($rows[$i]['charset']);
				if(!$rows[$i]){
					$rows[$i] = json_decode(iconv('utf-8','utf-8//IGNORE',$tmp),true);
				}
				$rows[$i]['received'] = strtotime(base64_decode($rows[$i]['received']));
				$rows[$i]['attach'] = 0;
				for ($j=0;$j<count($rows[$i]['mimes']);$j++){
					if((strtolower($rows[$i]['mimes'][$j]['ctype'])=='text/plain')||(strtolower($rows[$i]['mimes'][$j]['ctype'])=='text/html')){
						if(!empty($rows[$i]['mimes'][$j]['charset'])){
							$rows[$i]['randcharset'] = $rows[$i]['mimes'][$j]['charset'];
						}else{
							$rows[$i]['randcharset'] = 'gb2312';
						}
					}elseif ((!empty($rows[$i]['mimes'][$j]['filename']))&&((empty($rows[$i]['mimes'][$j]['cid'])||(strstr($rows[$i]['mimes'][$j]['ctype'],"application/"))))&&(empty($rows[$i]['mimes'][$j]['cntl']))){
						$rows[$i]['attach'] = 1;
					}elseif ($rows[$i]['mimes'][$j]['ctype']=='message/rfc822'){
						$rows[$i]['attach'] = 1;
					}
				}

				if(empty($rows[$i]['subject'])){
					$rows[$i]['subject'] = LANG_MAIL_M0172;
				}else{
					$rows[$i]['subject'] = $this->mailbase->clearAddress($this->mailbase->strProce($rows[$i]['subject'],$rows[$i]['randcharset']));
					if(empty($rows[$i]['subject']))$rows[$i]['subject'] = LANG_MAIL_M0171;
				}

				$rows[$i]['subject'] = str_replace("&", "&amp;", $rows[$i]['subject']);

				if(empty($rows[$i]['from'])){
					$rows[$i]['from'] = LANG_MAIL_M0173;
					$rows[$i]['fromtip'] = LANG_MAIL_M0173;
				}else{
					$from = $rows[$i]['from'] = $this->mailbase->getAddress($rows[$i]['from'],"ARRAY");
					$rows[$i]['from'] = $from[0]['name'];
					if(empty($from[0]['name']))$rows[$i]['from'] = $from[0]['address'];
					$rows[$i]['fromtip'] = $from[0]['address'];
				}

				$rows[$i]['from'] = str_replace("&", "&amp;", $rows[$i]['from']);
				$rows[$i]['fromtip'] = str_replace("&", "&amp;", $rows[$i]['fromtip']);

				if(('sent' == $folder || 0 == strncmp($folder, '73656e74', 8)) && !empty($rows[$i]['to'])){
					$to = $rows[$i]['to'] = $this->mailbase->getAddress($rows[$i]['to'],"ARRAY");
					$rows[$i]['to'] = $to[0]['name'];
					if(empty($to[0]['name']))$rows[$i]['to'] = $to[0]['address'];
					$rows[$i]['totip'] = $to[0]['address'];

					$rows[$i]['to'] = str_replace("&", "&amp;", $rows[$i]['to']);
					$rows[$i]['totip'] = str_replace("&", "&amp;", $rows[$i]['totip']);

				}

				$rows[$i]['size'] = $this->mailbase->convertSize($rows[$i]['size']);
				if(empty($rows[$i]['received'])){
					$rows[$i]['received'] = date("Y-m-d H:i",filemtime($this->path."/".$folder."/".$rows[$i]['file']));
				}else{
					$rows[$i]['received'] = date("Y-m-d H:i",($rows[$i]['received']));
				}
				$date = strtotime(base64_decode($rows[$i]['received']));
				if($date){
					$rows[$i]['date'] = date("Y-m-d",$date);
				}else{
					$rows[$i]['date'] = $rows[$i]['received'];
				}
				$rows[$i]['file'] = base64_encode($rows[$i]['file']);
				unset($rows[$i]['mimes']);

				$strdata.=json_encode($rows[$i]);
				if($i<(count($rows)-1))$strdata.=",";
			}

			if($this->_folder=='inbox'){
				$strdata = '{"maxpage":'.$maxpage.',"curpage":'.$start.',"data":['.$strdata.'],"total":"'.$mailboxinfo['total'].'","unread":'.$head['unread'].'}';
			}else{
				$strdata = '{"maxpage":'.$maxpage.',"curpage":'.$start.',"data":['.$strdata.'],"unread":'.$mailboxinfo['unread'].',"total":'.$mailboxinfo['total'].'}';
			}
			return $strdata;
		}
	}

	/**
     * 邮件明细
     *
     * @param string $folder 邮件所在文件目录
     * @param int $mid 邮件ID
     * @param string $charset 邮件指定编码
     * @return mixed
     */
	function mailDetail($folder,$mid,$charset='',$addresstype='STRING'){
		$opt = $this->idbmail->match($folder,$mid);
		$path = $this->path."/eml/".$mid;
		if(!$opt['state']){
			$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Mail read [".$this->path."/eml/".$mid."] [Error:".$this->errordesc['ERR'.$opt['error']]."]",0);
			return false;
		}else{
			$tmp = $opt['data'];
			$data = json_decode($opt['data'],true);
			if(!empty($data)){
				$mail =  $this->mailDetailContent($data,$path,$charset,$addresstype);
				return $mail;
			}else{
				return false;
			}
		}
	}

	/**
     * 邮件明细内容
     *
     * @param array $data 邮件mime信息
     * @param string $path 邮件文件路径
     * @param string $charset 邮件字符集
     * @return array
     */
	function mailDetailContent($data,$path,$charset,$addresstype='STRING'){
		$n = 0;
		$fmark = 1;
		for ($i=0;$i<count($data['mimes']);$i++){
 			if (!empty($data['mimes'][$i]['cid']))
				$data['mimes'][$i]['cid'] = trim(base64_decode($data['mimes'][$i]['cid']), "<>");
		}
		for ($i=0;$i<count($data['mimes']);$i++){
			//邮件正文
			$tmp_ctype = explode(";",strtolower($data['mimes'][$i]['ctype']));
			$ctype = $tmp_ctype[0];
			switch (strtolower($ctype)){
				case 'text/plain':{
					if(empty($mail['content_text'])){
						$mail['content_text'] = "<pre>".$this->mailbase->getMailContent($path,$data['mimes'][$i]['begin'],$data['mimes'][$i]['length'],$data['mimes'][$i]['encoding'],$data['mimes'][$i]['charset'],$charset,0);
						$mail['content_text'] = str_replace("\t","&nbsp;&nbsp;&nbsp;&nbsp;",$mail['content_text']);
						$mail['content_text'] = str_replace(" ","&nbsp;",$mail['content_text']);
						$winmaildat_charset = $data['mimes'][$i]['charset'];
					}
					break;
				}
				case 'text/html':{
					if(empty($mail['content_html'])){
						$mail['content_html'] = $this->mailbase->getMailContent($path,$data['mimes'][$i]['begin'],$data['mimes'][$i]['length'],$data['mimes'][$i]['encoding'],$data['mimes'][$i]['charset'],$charset,1);
						$winmaildat_charset = $data['mimes'][$i]['charset'];
					}
					break;
				}
				case 'text/calendar':{
					if(empty($mail['content_html'])){
						$mail['content_html'] = $this->mailbase->getMailContent($path,$data['mimes'][$i]['begin'],$data['mimes'][$i]['length'],$data['mimes'][$i]['encoding'],$data['mimes'][$i]['charset'],$charset,1);
						$winmaildat_charset = $data['mimes'][$i]['charset'];
					}
					break;
				}
			}

			//邮件默认字符集
			if((strtolower($data['mimes'][$i]['ctype'])=='text/plain')||(strtolower($data['mimes'][$i]['ctype'])=='text/html')){
				if(!empty($data['mimes'][$i]['charset'])){
					$mail['randcharset'] = $data['mimes'][$i]['charset'];
				}else{
					$mail['randcharset'] = 'gb2312';
				}
			}

			//设置附件
			if(!empty($data['mimes'][$i]['filename'])||!empty($data['mimes'][$i]['cid'])||!empty($data['mimes'][$i]['cntl'])||(strtolower($data['mimes'][$i]['ctype'])=='message/rfc822')){
				//winmaildat处理
				if(base64_decode($data['mimes'][$i]['filename']) == "winmail.dat"){
					$tmpfolder = $this->path."/tmp/winmaildat/";
					if(file_exists($tmpfolder.$data['file'])){
						$fpe = fopen($tmpfolder.$data['file'],"r");
						$t = trim(fgets($fpe,1024));
						$fdata = json_decode($t,true);
						foreach ($fdata as $f){
							$mail['attach'][$n] = $f;
							$n++;
						}
					}else{
						$TNEF = "/usr/bin/tnef --overwrite --number-backups";
						if(!file_exists($tmpfolder)){
							mkdir($tmpfolder);
						}
						if(!file_exists($tmpfolder."temp/")){
							mkdir($tmpfolder."temp/");
						}
						$content = $this->getContent($path,$data['mimes'][$i]['begin'],$data['mimes'][$i]['length'],$data['mimes'][$i]['encoding']);
						file_put_contents($tmpfolder."temp/winmail.dat",$content);
						chdir($tmpfolder.'temp');
						exec("$TNEF ".$tmpfolder."temp/winmail.dat");
						$dir = opendir($tmpfolder.'temp');
						while (false!==($file = readdir($dir))) {
							if ($file != "." && $file != ".." && is_file($file)) {
								if(strlen($file)>1){
									$files[] = $file;
								}
							}
						}
						closedir($dir);
						$k = 0;
						$f_offset = 1024;
						$tmpstr = "";
						foreach ($files as $f){
							if($f!='winmail.dat'){
								$filepath = $tmpfolder."temp/".$f;
								$fcontent = base64_encode(file_get_contents($filepath));
								$flength = strlen($fcontent);
								$tmpstr.=$fcontent;
								$fdata[$k]['file'] = iconv($winmaildat_charset,'utf-8//IGNORE',$f);
								$fdata[$k]['begin'] = $f_offset;
								$fdata[$k]['length'] = $flength;
								$fdata[$k]['size'] = $this->mailbase->convertSize(filesize($filepath));
								$fdata[$k]['encoding'] = 'base64';
								$fdata[$k]['path'] = $tmpfolder.$data['file'];
								$f_offset = $f_offset + $flength;
								$mail['attach'][$n] = $fdata[$k];
								$k++;
								$n++;
							}
						}
						$fhead = sprintf("%-1024s",json_encode($fdata));
						$tmpstr = $fhead.$tmpstr;
						$fpe = fopen($tmpfolder.$data['file'],'w+');
						fwrite($fpe,$tmpstr);
						fclose($fpe);
					}
					//删除临时文件
					$tmpfiles = scandir($tmpfolder."temp/");
					if(count($tmpfiles)>2){
						foreach($tmpfiles as $tfile) {
							if(file_exists($tmpfolder."temp/".$tfile) && $tfile != '.' && $tfile != '..') {
								unlink($tmpfolder."temp/".$tfile);
							}
						}
					}
				}else{
					//设置eml附件
					if(strtolower($data['mimes'][$i]['ctype'])=='message/rfc822'){
						if(!empty($data['mimes'][$i]['filename'])){
							$mail['attach'][$n]['file'] = $this->mailbase->clearAddress($this->mailbase->strProce($data['mimes'][$i]['filename'],$mail['randcharset']));
						}else{
							$mail['attach'][$n]['file'] = "未知邮件-".$fmark.".eml";
							$fmark+=1;
						}
						if(substr($mail['attach'][$n]['file'],-4,4)!='.eml')$mail['attach'][$n]['file'].=".eml";
						$mail['attach'][$n]['begin'] = $data['mimes'][$i]['begin'];
						$mail['attach'][$n]['length'] = $data['mimes'][$i]['length'];
						$mail['attach'][$n]['size'] = $this->mailbase->convertSize($data['mimes'][$i]['length']);
						$mail['attach'][$n]['encoding'] = $data['mimes'][$i]['encoding'];
						$mail['attach'][$n]['path'] = $path;
						$mail['attach'][$n]['ctype'] = $data['mimes'][$i]['ctype'];
						$n++;
					}else{
						//普通附件
						$mail['attach'][$n]['file'] = $this->mailbase->clearAddress($this->mailbase->strProce($data['mimes'][$i]['filename'],$mail['randcharset']));
						$mail['attach'][$n]['begin'] = $data['mimes'][$i]['begin'];
						$mail['attach'][$n]['length'] = $data['mimes'][$i]['length'];
						$mail['attach'][$n]['size'] = $this->mailbase->convertSize($data['mimes'][$i]['length']*3/4);
						$mail['attach'][$n]['encoding'] = $data['mimes'][$i]['encoding'];
						$mail['attach'][$n]['path'] = $path;
						$mail['attach'][$n]['ctype'] = $data['mimes'][$i]['ctype'];
						//						$ext_tmp = explode(".",$mail['attach'][$n]['file']);
						//						$ext_name = strtolower($ext_tmp[count($ext_tmp)-1]);
						//						$ext_pic = array(1=>'jpg',2=>'bmp',3=>'gif',4=>'png');
						//						if($data['mimes'][$i]['cid']&&array_search($ext_name,$ext_pic)){
						//							//$mail['attach'][$n]['file'] = $data['mimes'][$i]['filename'];
						//							$mail['attach'][$n]['cid'] = $data['mimes'][$i]['cid'];
						//							$isinnerimg = 1;
						//						}

						if($data['mimes'][$i]['cid']){
							$mail['attach'][$n]['cid'] = $data['mimes'][$i]['cid'];
							$isinnerimg = 1;
						}
						$n++;
					}
				}
			}
		}

		//邮件头信息
		if(empty($data['subject'])){
			$mail['subject'] = '';
		}else{
			$mail['subject'] = $this->mailbase->clearAddress($this->mailbase->strProce($data['subject'],$mail['randcharset']));
			if(empty($mail['subject']))$mail['subject'] = LANG_MAIL_M0171;
		}

		if(empty($data['from'])){
			$mail['from'] = LANG_MAIL_M0173;
		}else{
			$mail['from'] = $this->mailbase->getAddress($data['from'],$addresstype);
		}

		if(empty($mail['received'])){
			$mail['received'] = date("Y-m-d H:i:s",filemtime($path));
		}else{
			$mail['received'] = date("Y-m-d H:i:s",strtotime(base64_decode($data['received'])));
		}

		if(empty($data['date'])){
			$mail['date'] = $mail['received'];
		}else{
			$date = strtotime(base64_decode($data['date']));
			if($date){
				$mail['date'] = date("Y-m-d H:i:s",$date);
			}else{
				$mail['date'] = $mail['received'];
			}
		}

		$mail['to'] = $this->mailbase->getAddress($data['to'],$addresstype);
		$mail['cc'] = $this->mailbase->getAddress($data['cc'],$addresstype);

		//其他参数
		$mail['flag'] = $data['flag'];
		$mail['showimg'] = $data['showimg'];
		$mail['timesend'] = $data['timesend'];
		$mail['randsendtime'] = $data['randsendtime'];
		$mail['read'] = $data['read'];
		$mail['recall'] = $data['recall'];

		//设置回执
		if(!empty($data['notification'])){
			$mail['feedback'] = base64_decode($data['notification']);
		}

		$mail['content'] = $mail['content_html'];

		//转换内嵌图片
		if($isinnerimg){
			$mail['content'] = $this->mailbase->transferInnerimg($mail['content'],$mail['attach']);
		}
		if(empty($mail['content_html'])){
			$mail['content'] = $mail['content_text'];
		}
		$mail['content'] = preg_replace("/<[L|l][I|i][N|n][K|k][\s\S]*?>/","",$mail['content']);
		if(isset($data['showimg'])){
			$mail['showimg'] = $data['showimg'];
		}

		return $mail;
	}

	/**
     * 邮箱容量信息
     *
     * @return array
     */
	function mailInfo(){
		$opt = $this->idbmail->quta();
		if(!$opt['state']){
			$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Get mailbox info [Error:".$this->errordesc['ERR'.$opt['error']]."]",0);
		}else{
			$opt['perused'] = round(($opt['capacity']/($opt['maxcapacity']))*100, 1);
			$opt['maxcapacity'] = $this->mailbase->convertSize($opt['maxcapacity']*1024*1024);
			$opt['capacity'] = $this->mailbase->convertSize($opt['capacity']);
			return $opt;
		}
	}

	/**
     * 添加自定义文件夹
     *
     * @param string $folder 文件夹名称
     * @return string
     */
	function addFolder($folder){
		$usedname = array(1=>'inbox',2=>'draft',3=>'sent',4=>'trash',5=>'junk');
		if(array_search($folder,$usedname)){
			$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Create folder [Error:0]",0);
			return false;
		}else{
			$encode_folder = $this->mailbase->strToAscii($folder);
			if(strlen($encode_folder)>150){
				$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Create folder [Error:0]",0);
				return false;
			}else{
				$opt = $this->idbmail->createfolder($encode_folder);
				if(!$opt['state']){
					$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Create folder [Error:".$this->errordesc['ERR'.$opt['error']]."]",0);
					return false;
				}else{
					$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Create folder [name:$folder]",1);
					return true;
				}
			}
		}
	}

	/**
     * 删除自定义文件夹
     *
     * @param string $folder 文件夹名称 (encoded)
     * @return string
     */
	function deleteFolder($folder){
		$opt = $this->idbmail->delfolder($folder);
		if(!$opt['state']){
			$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Delete folder [Error:".$this->errordesc['ERR'.$opt['error']]."]",0);
			return false;
		}else{
			$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Delete folder [name:".$this->mailbase->asciiToStr($folder)."]",1);
			return true;
		}
	}

	/**
     * 重命名自定义文件夹
     *
     * @param string $oldfolder 原文件夹名称 (encoded)
     * @param string $newfolder 新文件夹名称 
     * @return string
     */
	function renameFolder($oldfolder,$newfolder){
		$opt = $this->idbmail->renamefolder($oldfolder,$this->mailbase->strtoascii($newfolder));
		if(!$opt['state']){
			$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Rename folder [Error:".$this->errordesc['ERR'.$opt['error']]."]",0);
			return false;
		}else{
			$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Rename folder [oldname:".$this->mailbase->asciiToStr($oldfolder)."][newname:".$newfolder."]",1);
			return true;
		}
	}

	/**
     * 自定义文件夹列表
     *
     * @return string
     */
	function listingFolder(){
		$opt = $this->idbmail->listfolder();
		if(!$opt['state']){
			$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Folder listing [Error:".$this->errordesc['ERR'.$opt['error']]."]",0);
			return false;
		}else{
			for($i=0;$i<count($opt['data']);$i++){
				$folder[$i]['title'] = $this->mailbase->asciiToStr($opt['data'][$i]);
				$folder[$i]['name'] = $opt['data'][$i];
			}
			return $folder;
		}
	}

	/**
     * 邮箱列表
     *
     * @return string
     */
	function listingMailbox(){
		$opt = $this->idbmail->listmailbox();
		if(!$opt['state']){
			$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Mailbox listing [Error:".$this->errordesc['ERR'.$opt['error']]."]",0);
			return false;
		}else{
			for ($i=0;$i<count($opt['data']);$i++){
				$rows[$opt['data'][$i]['folder']]['unread'] = $opt['data'][$i]['unread'];
				$rows[$opt['data'][$i]['folder']]['total'] = $opt['data'][$i]['total'];
			}
			return json_encode($rows);
		}
	}

	/**
     * 清空临时文件
     *
     * @param string $path	文件夹路径
     * @return void
     */
	function clearTemp($path){
		$path = $path."/tmp/attach/";
		$files = scandir($path);
		if(count($files)>2){
			foreach($files as $file) {
				if(file_exists($path.$file) && $file != '.' && $file != '..') {
					if((time()-filemtime($path.$file))>3600){
						$subfiles = scandir($path.$file."/");
						if(count($subfiles)>2){
							foreach($subfiles as $sfile) {
								if(file_exists($path.$file."/".$sfile) && $sfile != '.' && $sfile != '..') {
									unlink($path.$file."/".$sfile);
								}
							}
						}
						rmdir($path.$file);
					}
				}
			}
		}
	}

	/**
     * 清空指定临时文件
     *
     * @param string $path	文件夹路径
     * @return void
     */
	function clearAppointTemp($path,$mid){
		$path = $path."/tmp/attach/".$mid."/";
		$files = scandir($path);
		foreach($files as $file) {
			if($file != '.' && $file != '..') {
				unlink($path.$file);
			}
		}
		return rmdir($path);
	}

	/**
     * 邮件索引信息属性值
     *
     * @param string $mid 邮件编号
     * @param string $folder 文件目录
     * @param string $tag 属性参数
     * @param mixed $val 属性值
     * @return int
     */
	function changeProperty($mid,$folder,$tag,$val){
		$opt = $this->idbmail->update($folder,$mid,$tag,$val);
		if(!$opt['state']){
			$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"change property [Error:".$this->errordesc['ERR'.$opt['error']]."]",0);
			return false;
		}else{
			return true;
		}
	}

	/**
     * 设置邮件回执
     *
     * @param object $mail 邮件内容对象
     * @return bool
     */
	function setFeedback($mail){
		require_once(APP_PATH."/models/mail/MailEncode.php");
		$mail->to = $this->mailbase->getAddress(base64_encode($mail->to),"ADDRESS");
		$mail->from = $this->mailbase->getAddress(base64_encode($mail->from),"ARRAY");
		$mail->from[0]['mail'] = $mail->from[0]['address'];
		$mailencode = new WebMail_Model_MailEncode();
		//指定编码
		$charset = $_COOKIE['SET_MAILCODE'];
		$mailencode->setCharset($charset);
		return $mime = $mailencode->save($mail);
	}

	/**
     * 取消定时发送
     *
     * @param int $id 发送队列ID
     * @return bool
     */
	function unsetTimeSend($id){
		$connection = fsockopen(PANDORA_TIMEEXCUTE_HOST,PANDORA_TIMEEXCUTE_PORT,$err_no, $err_str, 5);
		if ($connection){
			$resp=fgets($connection,256);
			if($resp){
				$command = 'CANCEL '.$id;
				if(fputs($connection,"$command\r\n")){
					$resp=fgets($connection,256);
					$resp = str_replace("\r\n","",$resp);
					$resp = explode(' ',$resp);
					fputs($connection,"QUIT\r\n");
					fclose($connection);
					return $resp[0];
				}
			}
		}
	}

	/**
     * 设置定时发送
     *
     * @param int $mid 邮件ID
     * @param int $time 定时时间
     * @return bool
     */
	function setTimeSend($time, $path, $folder, $mid){
		$connection = fsockopen(PANDORA_TIMEEXCUTE_HOST, PANDORA_TIMEEXCUTE_PORT, $err_no, $err_str, 5);
		if ($connection){
			$resp=fgets($connection,256);
			if($resp){
				$command = 'ADD '.$time.' sendmail '.$_COOKIE['SESSION_MARK'].' '.$path.' '.$folder.' '.$mid.' 127.0.0.1:25';
				if(fputs($connection,"$command\r\n")){
					$resp=fgets($connection,256);
					$resp = str_replace("\r\n","",$resp);
					$resp = explode(' ',$resp);
					fputs($connection,"QUIT\r\n");
					fclose($connection);
					return $resp;
				}
			}
		}
	}

	/**
     * 清空指定邮箱
     *
     * @param string $folder 文件夹名称
     * @return void
     */
	function clearMailbox($folder){
		$rct = $this->idbmail->uidl($folder);
		if($rct['state']){
			$row = $rct['data'];
			$n = 0;
			for($i=0;$i<count($row);$i++){
				unlink($this->path."/eml/".$row[$i][0]);
				$mids[$n] = $row[$i][0];
				$n++;
				if ($n >= 1000) {
					$delrct = $this->idbmail->delete($folder,$mids);
					if(!$delrct['state']){
						return false;
					}
					$n = 0;
				}
			}
			if ($n > 0) {
				$delrct = $this->idbmail->delete($folder,$mids);
				if(!$delrct['state']){
					return false;
				}
			}
			return true;
		}else{
			return false;
		}
	}

	/**
     * 复制附件到网盘
     *
     * @param array $file	文件信息数组
     * @param int $diskused 网盘已用空间大小
     * @param int $maxsize 网盘最大空间大小
     * @param int $folder 网盘对应目录
     * @param object $disk 网盘对象
     * @param int $cover 同名文件是否覆盖标记
     * @param string $path 用户目录路径
     * @param string $mailbox 邮箱目录
     * @return string
     */
	function copyAttachToDisk($file,$diskused,$maxsize,$folder,$disk,$cover,$path,$mailbox){
		$fpe = fopen($file['path'],"r");
		fseek($fpe,$file['begin']);
		$fcontent = fread($fpe,$file['length']);
		if(strtolower($file['encoding'])=="base64"){
			$fcontent = base64_decode($fcontent);
		}elseif(strtolower($file['encoding'])=="quoted-printable"){
			$fcontent = quoted_printable_decode($fcontent);
		}
		fclose($fpe);
		$mailsize = strlen($fcontent);

		//检查网盘空间
		if(($diskused+$mailsize)>=($maxsize*1024*1024)){
			return '{"code":"M1000","tip":"'.LANG_TIP_M1000.'","state":2}';
			exit();
		}

		//检查是否存在同名文件
		if(!$cover){
			$mark = $disk->checkFileExist($file['file'],$folder);
			if(!$mark){
				return '{"code":"M1001","tip":"'.LANG_TIP_M1001.'","state":3}';
				exit();
			}
		}else{
			$tmp = $disk->getExistFile($mails[$i]['file'],$folder);
			if($tmp){
				$file['filepath'] = $tmp['path'];
			}
		}

		//复制文件
		if($file['filepath']){
			$newfile = $file['filepath'];
			if(file_put_contents($path."/disk/".$folder.$newfile,$fcontent)){
				$finfo = $disk->getFileInfo($path."/".$mailbox."/".$file['filepath'],$file['file']);
				$disk->editFileInfo($file['filepath'],$finfo,$mailbox);
			}
		}else{
			$newfile = $disk->setFileName();
			if(file_put_contents($path."/disk/".$folder.$newfile,$fcontent)){
				$disk->addIndex($path."/disk/".$folder,$newfile,$file['file']);
			}
		}
		return '{"code":"","tip":"'.LANG_TIP_M1002.'","state":1}';
	}

	function copyMailsToDisk($mids,$diskused,$maxsize,$folder,$disk,$cover,$path,$mailbox){
		$mailsize = 0;
		for ($i=0;$i<count($mids);$i++){
			$mails[$i] = $this->mailDetail($mailbox,$mids[$i]);
			//$mails[$i]['subject'] = $this->mailbase->clearAddress($this->mailbase->strProce($mails[$i]['subject'],$mails[$i]['randcharset']));

			$fname = $mails[$i]['subject'].".eml";
			if(empty($mails[$i]['subject'])){
				$mails[$i]['subject'] = LANG_MAIL_M0171;
				$fname = "[".date("Y-m-d H:i:s",strtotime($mails[$i]['received']))."] no subject.eml";
			}
			$mails[$i]['fname'] = $fname;
			$mails[$i]['file'] = $mids[$i];
			$mailsize+=$mails[$i]['size'];
		}
		//检查网盘空间
		if(($diskused+$mailsize)>=($maxsize*1024*1024)){
			return '{"code":"M1000","tip":"'.LANG_TIP_M1000.'","state":2}';
			exit();
		}

		//检查是否存在同名文件
		for ($i=0;$i<count($mails);$i++){
			if(!$cover){
				$mark = $disk->checkFileExist($mails[$i]['fname'],$folder);
				if(!$mark){
					return '{"code":"M1001","tip":"'.LANG_TIP_M1001.'","state":3}';
					exit();
				}
			}else{
				$tmp = $disk->getExistFile($mails[$i]['fname'],$folder);
				if($tmp){
					$mails[$i]['filepath'] = $tmp['path'];
				}
			}
		}

		//复制文件
		for ($i=0;$i<count($mails);$i++){
			if($mails[$i]['filepath']){
				$newfile = $mails[$i]['filepath'];
				$fcontent = file_get_contents($path."/".$mailbox."/".$mails[$i]['file']);
				if(file_put_contents($path."/disk/".$folder.$newfile,$fcontent)){
					$finfo = $disk->getFileInfo($path."/".$mailbox."/".$mails[$i]['filepath'],$mails[$i]['fname']);
					$disk->editFileInfo($mails[$i]['filepath'],$finfo,$this->_folder);
				}
			}else{
				$newfile = $disk->setFileName();
				$fcontent = file_get_contents($path."/".$mailbox."/".$mails[$i]['file']);
				if(file_put_contents($path."/disk/".$folder.$newfile,$fcontent)){
					$disk->addIndex($path."/disk/".$folder,$newfile,$mails[$i]['fname']);
				}
			}
		}
		return '{"code":"","tip":"'.LANG_TIP_M1002.'","state":1}';
	}

	/**
     * 移除邮件中外链图片
     *
     * @param string $str	邮件内容
     * @return string
     */
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

	/**
     * 取得邮件内容
     *
     * @param string $path	邮件物理地址
     * @param int $begin 起始地址
     * @param int $length 长度
     * @param string $encoding 编码类型
     * @return string
     */
	function getContent($path,$begin,$length,$encoding){
		$fpe = fopen($path,"r");
		fseek($fpe,$begin);
		$content = fread($fpe,$length);
		if(strtolower($encoding)=="base64"){
			$content = base64_decode($content);
		}elseif(strtolower($encoding)=="quoted-printable"){
			$content = quoted_printable_decode($content);
		}
		fclose($fpe);
		return $content;
	}

	/**
     * 取得邮件附件
     *
     * @param int $mid 邮件ID
     * @return string
     */
	public function getFileList($mid){
		return $this->mailbase->mailAttachlist($mid);
	}

	function getEmlDetail($eml,$type=0){
		//取得暂存路径
		$dir = $this->path."/tmp/eml/";
		if(!file_exists($dir)){
			mkdir($dir);
		}

		//清除过期文件
		$expired = 3600;
		$files = scandir($dir);
		if(count($files)>2){
			foreach($files as $file) {
				if(file_exists($dir.$file) && $file != '.' && $file != '..') {
					if(filemtime($dir.$file)+$expired<time()){
						unlink($dir.$file);
					}
				}
			}
		}

		//取得eml内容
		if(!$type){
			$fpe = fopen($eml['path'],'r');
			fseek($fpe,$eml['begin']);
			$mime = fread($fpe,$eml['length']);
			if($eml['encoding']=='base64'){
				$mime = base64_decode($mime);
			}
			fclose($fpe);
		}else{
			if($eml['user']){
				require_once(APP_PATH."/models/disk/File.php");
				$disk = new  WebMail_Model_File('');
				if($disk->checkShareAuth($eml['user'],$eml['share'],$eml[$eml['share']])){
					$share_dir = $disk->getShareFolder($eml['user']);
					if($share_dir){
						$mime = file_get_contents($share_dir."/disk/".$eml['path']);
					}else{
						echo "路径错误";
					}
				}else{
					echo "没有权限";
				}
			}else{
				$mime = file_get_contents($this->path."/disk/".$eml['path']);
			}
		}

		$res = 0;
		$mid = $this->createMid();
		if(file_put_contents($dir.$mid,$mime)){
			chdir ("/var/pandora/tools");
			$cmd = "./digest ".$dir.$mid;
			$res = exec($cmd);
		}
		return $res;
	}

	/**
     * 删除附件
     *
     * @param string $path	附件路径
     * @return mixed
     */
	function delAttachFile($path){
		return unlink($path);
	}

	function splitEmailAddress($str){
		$str = trim($str);
		if(substr($str,-1,1)==";")$str = substr($str,0,-1);
		$tmp = explode(";",$str);
		for ($i=0;$i<count($tmp);$i++){
			if(!empty($tmp[$i])){
				$t = explode("<",$tmp[$i]);
				if(count($t)>1){
					$address[$i]['mail'] = str_replace(">","",$t[1]);
					$address[$i]['name'] = $t[0];
				}else{
					$address[$i]['mail'] = $tmp[$i];
					$address[$i]['name'] = '';
				}
			}
		}
		return $address;
	}

	/**
     * 检查邮箱是否已满
     *
     * @return bool
     */
	function isFull(){
		$opt = $this->idbmail->chkfull();
		return $opt['full'];
	}

	/**
     * POP邮箱功能
     * @param object $account 帐号对象信息
     * @return bool
     */
	function popMail($account){
		include_once 'Net/POP3.php';
		$pop3 =& new Net_POP3();
		$folder = $this->path."/inbox/";

		foreach ($account as $act){
			if(!$ret= $pop3->connect($act->host,$act->port)){
				exit();
			}

			if(!$ret= $pop3->login(trim($act->address),trim($act->pass),'USER')){
				exit();
			}

			$msg = $pop3->getListing();

			for ($i=0;$i<count($msg);$i++){
				$mime = $pop3->getMsg($msg[$i]['msg_id']);
				$this->mailSend('',array(strtolower($_COOKIE['SESSION_MARK'])),$mime);
				$pop3->deleteMsg($msg[$i]['msg_id']);
			}
			$pop3->disconnect();
		}
	}

	/**
     * 取得指定目录邮件所占用的空间大小
     * @param string $folder
     * @param int $custom
     * @return bool
     */
	function totalMailSize($folder){
		$res = $this->idbmail->uidl($folder);
		if (0 == $res['state']) {
			return 0;
		}
		$size = 0;
		for ($i; $i<$res['counts']; $i++) {
			$size += $res['data'][$i][1];	
		}
		return $this->mailbase->convertSize($size);
	}

	/**
     * 邮箱所有文件夹信息
     * @return array
     **/
	function allMailboxinfo(){
		$folder = array('inbox','sent','draft','junk','trash');
		$mailboxinfo = array();

		//取得普通文件夹信息
		foreach ($folder as $f){
			$res = $this->idbmail->sum($f);
			if(!$res['state']){
				$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Mailbox Info [Error:".$this->errordesc['ERR'.$res['error']]."]",0);
				return false;
			}else{
				$mailboxinfo[$f]['unread'] = $res['unread'];
				$mailboxinfo[$f]['total'] = trim($res['total']);
				//取得邮件占用空间
				$mailboxinfo[$f]['size'] = $this->totalMailSize($f);
				$mailboxinfo[$f]['name'] = $f;
				$mailboxinfo[$f]['title'] = $f;
				$mailboxinfo[$f]['custom'] = 0;
			}
		}

		//取得自定义文件夹信息
		$opt = $this->idbmail->listfolder();
		if(!$opt['state']){
			$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Folder listing [Error:".$this->errordesc['ERR'.$opt['error']]."]",0);
			return false;
		}else{
			for($i=0;$i<count($opt['data']);$i++){
				$title = $this->mailbase->asciiToStr($opt['data'][$i]);
				$name = $opt['data'][$i];

				$cinfo = $this->idbmail->sum($name);

				if(!$cinfo['state']){
					$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Mailbox Info [Error:".$this->errordesc['ERR'.$cinfo['error']]."]",0);
					return false;
				}else{
					$mailboxinfo[$name]['unread'] = $cinfo['unread'];
					$mailboxinfo[$name]['total'] = trim($cinfo['total']);
					//取得邮件占用空间
					$mailboxinfo[$name]['size'] = $this->totalMailSize($name);
					$mailboxinfo[$name]['name'] = $name;
					$mailboxinfo[$name]['title'] = $title;
					$mailboxinfo[$name]['custom'] = 1;
				}
			}
		}

		return $mailboxinfo;
	}

	/**
     * 创建按邮件地址分类邮件数据库
     * @param string $folder
     * @return bool
     */
	function createSortByUserMail($folder){
		$socket = new WebMail_Model_Socket(PANDORA_SOCKET_LOCK);
		$socket->lock($this->path);
		$dir = $this->path."/tmp/classify/".$folder."/";
		if(!file_exists($this->path."/tmp/classify"))mkdir($this->path."/tmp/classify");
		if(!file_exists($this->path."/tmp/classify/".$folder))mkdir($this->path."/tmp/classify/".$folder);

		//清除目录文件
		if(is_dir($dir) && is_readable($dir)){
			$handle = opendir($dir);
			while (false != ($filename = readdir($handle))) {
				if($filename!="."&&$filename!=".."){
					unlink($dir."/".$filename);
				}
			}
			closedir($handle);
		}

		$rows = $this->idbmail->listing($folder,'RCV','dsc');
		$tempuser = $userlist = $mindex = array();

		for($i=0;$i<count($rows['data']);$i++){
			//for($i=0;$i<100;$i++){
			$temp = json_decode($rows['data'][$i],true);
			$mid = $temp['file'];
			if($folder=="sent"){
				$mailaddress = $this->mailbase->getAddressLite($temp['to']);
				$straddress = base64_decode($temp['to']);
			}else{
				$mailaddress = $this->mailbase->getAddressLite($temp['from']);
				$straddress = base64_decode($temp['from']);
			}

			$address = "";
			for($k=0;$k<count($mailaddress);$k++){
				if(empty($mailaddress[$k]['name'])){
					$address.= $mailaddress[$k]['address'].";";
					$mailaddress[$k]['name'] = $mailaddress[$k]['address'];
				}else{
					$address.= '"'.$mailaddress[$k]['name'].'" '.$mailaddress[$k]['address'].";";
				}

				if((array_search($mailaddress[$k]['address'],$tempuser)===false)&&(!empty($mailaddress[$k]['address']))){
					array_push($tempuser,$mailaddress[$k]['address']);
					$userlist[$mailaddress[$k]['address']] = $mailaddress[$k]['name'];
				}

				if(strpos($straddress,$mailaddress[$k]['address'])){
					if(!isset($mindex[$mailaddress[$k]['address']])){
						$mindex[$mailaddress[$k]['address']] = array($mid);
					}else{
						array_push($mindex[$mailaddress[$k]['address']],$mid);
					}
				}
			}
		}

		foreach ($mindex as $k=>$v){
			$row['name'] = $userlist[$k];
			$row['mid'] = $v;
			$rec = json_encode($row);
			if($rec){
				file_put_contents($dir.$k,$rec);
			}
		}
		$socket->unlock();
		return true;
	}

	/**
     * 更新按邮件地址分类邮件数据库
     * @param string $folder
     * @return bool
     */
	function updateSortByUserMail($folder){
		$socket = new WebMail_Model_Socket(PANDORA_SOCKET_LOCK);
		$socket->lock($this->path);
		$dir = $this->path."/tmp/classify/".$folder."/";

		if(!file_exists($this->path."/tmp/classify"))mkdir($this->path."/tmp/classify");
		if(!file_exists($this->path."/tmp/classify/".$folder))mkdir($this->path."/tmp/classify/".$folder);

		$rows = $this->idbmail->listing($folder,'RCV','dsc');
		for($i=0;$i<count($rows['data']);$i++){
			$temp = json_decode($rows['data'][$i],true);
			$maillist[$temp['file']] = $i;
		}
		$currows = array();
		if(is_dir($dir) && is_readable($dir)){
			$handle = opendir($dir);
			while (false != ($filename = readdir($handle))) {
				if($filename!="."&&$filename!=".."){
					$tmp = json_decode(file_get_contents($dir.$filename),true);
					$userlist[$filename] = $tmp['name'];
					foreach ($tmp['mid'] as $mid){
						if(isset($maillist[$mid])){
							unset($maillist[$mid]);
							$currows[$mid] = $filename;
							if(!isset($mindex[$filename])){
								$mindex[$filename] = array($mid);
							}else{
								array_push($mindex[$filename],$mid);
							}
						}
					}
				}
			}
		}

		foreach ($maillist as $k=>$v){
			$temp = json_decode($rows['data'][$v],true);
			$mid = $temp['file'];
			if($folder=="sent"){
				$mailaddress = $this->mailbase->getAddress($temp['to'],'ARRAY');
				$straddress = base64_decode($temp['to']);
			}else{
				$mailaddress = $this->mailbase->getAddress($temp['from'],'ARRAY');
				$straddress = base64_decode($temp['from']);
			}

			$address = "";
			for($k=0;$k<count($mailaddress);$k++){
				$tmpaddress = explode("@",$mailaddress[$k]['address']);
				$tmpaddress = $tmpaddress[1];
				if($tmpaddress==$_COOKIE['USED_DOMAIN']){
					$mailaddress[$k]['name'] = $this->getUserNameFromContact($mailaddress[$k]['address']);
				}

				if(empty($mailaddress[$k]['name'])){
					$address.= $mailaddress[$k]['address'].";";
					$mailaddress[$k]['name'] = $mailaddress[$k]['address'];
				}else{
					$address.= '"'.$mailaddress[$k]['name'].'" '.$mailaddress[$k]['address'].";";
				}

				if((array_search($mailaddress[$k]['address'],$tempuser)===false)&&(!empty($mailaddress[$k]['address']))){
					array_push($tempuser,$mailaddress[$k]['address']);
					$userlist[$mailaddress[$k]['address']] = $mailaddress[$k]['name'];
				}

				if(strpos($straddress,$mailaddress[$k]['address'])!==false){
					if(!isset($mindex[$mailaddress[$k]['address']])){
						$mindex[$mailaddress[$k]['address']] = array($mid);
					}else{
						array_push($mindex[$mailaddress[$k]['address']],$mid);
					}
				}
			}

		}

		//清除目录文件
		if(is_dir($dir) && is_readable($dir)){
			$handle = opendir($dir);
			while (false != ($filename = readdir($handle))) {
				if($filename!="."&&$filename!=".."){
					unlink($dir."/".$filename);
				}
			}
			closedir($handle);
		}

		foreach ($mindex as $k=>$v){
			$row['name'] = $userlist[$k];
			$row['mid'] = $v;
			$rec = json_encode($row);
			if($rec){
				file_put_contents($dir.$k,$rec);
			}
		}
		$socket->unlock();
		return true;
	}

	/**
     * 取得按用户分类邮件列表
     * @param string $folder
     * @param string $user
     * @param int $start
     * @param int $amount
     * @param string $order
     * @param string $sort
     * @return string $strdata
     */
	function getSortByUserMail($folder,$user,$start,$amount,$order,$sort='DSC'){
		$dir = $this->path."/tmp/classify/".$folder."/";

		$rows = json_decode(file_get_contents($dir.$user),true);
		if($rows){
			$mids = $rows['mid'];
			$detail = array();
			$unread = 0;
			foreach ($mids as $mid){
				$res = $this->idbmail->match($folder,$mid);
				if($res['state']){
					$row = json_decode(trim($res['data']),true);
					if($row){
						if($row['read']==0)$unread++;
						array_push($detail,$row);
					}
				}
			}
		}
		$total = count($detail);

		if($start<=1){
			$row_start = 0;
		}else{
			$row_start = ($start-1)*$amount;
		}

		$row_end = $row_start+$amount;
		if($row_end>count($detail))$row_end = count($detail);

		for ($i=$row_start;$i<$row_end;$i++){
			$tmp = $detail[$i];
			//			if(!$detail[$i]){
			//				$detail[$i] = json_decode(iconv('utf-8','utf-8//IGNORE',$tmp),true);
			//			}
			$detail[$i]['received'] = strtotime(base64_decode($detail[$i]['received']));
			$detail[$i]['attach'] = 0;
			for ($j=0;$j<count($detail[$i]['mimes']);$j++){
				if((strtolower($detail[$i]['mimes'][$j]['ctype'])=='text/plain')||(strtolower($detail[$i]['mimes'][$j]['ctype'])=='text/html')){
					if(!empty($detail[$i]['mimes'][$j]['charset'])){
						$detail[$i]['randcharset'] = $detail[$i]['mimes'][$j]['charset'];
					}else{
						$detail[$i]['randcharset'] = 'gb2312';
					}
				}elseif ((!empty($detail[$i]['mimes'][$j]['filename']))&&((empty($detail[$i]['mimes'][$j]['cid'])||(strstr($detail[$i]['mimes'][$j]['ctype'],"application/"))))&&(empty($detail[$i]['mimes'][$j]['cntl']))){
					$detail[$i]['attach'] = 1;
				}elseif ($detail[$i]['mimes'][$j]['ctype']=='message/rfc822'){
					$detail[$i]['attach'] = 1;
				}
			}

			if(empty($detail[$i]['subject'])){
				$detail[$i]['subject'] = LANG_MAIL_M0172;
			}else{
				$detail[$i]['subject'] = $this->mailbase->clearAddress($this->mailbase->strProce($detail[$i]['subject'],$detail[$i]['randcharset']));
				if(empty($detail[$i]['subject']))$detail[$i]['subject'] = LANG_MAIL_M0171;
			}

			if(empty($detail[$i]['from'])){
				$detail[$i]['from'] = LANG_MAIL_M0173;
				$detail[$i]['fromtip'] = LANG_MAIL_M0173;
			}else{
				$from = $detail[$i]['from'] = $this->mailbase->getAddress($detail[$i]['from'],"ARRAY");
				$detail[$i]['from'] = $from[0]['name'];
				if(empty($from[0]['name']))$detail[$i]['from'] = $from[0]['address'];
				$detail[$i]['fromtip'] = $from[0]['address'];
			}

			if(!empty($detail[$i]['to'])){
				$to = $detail[$i]['to'] = $this->mailbase->getAddress($detail[$i]['to'],"ARRAY");
				$detail[$i]['to'] = $to[0]['name'];
				if(empty($to[0]['name']))$detail[$i]['to'] = $to[0]['address'];
				$detail[$i]['totip'] = $to[0]['address'];
			}

			$detail[$i]['size'] = $this->mailbase->convertSize($detail[$i]['size']);
			if(empty($detail[$i]['received'])){
				$detail[$i]['received'] = date("Y-m-d H:i",filemtime($this->path."/".$folder."/".$detail[$i]['file']));
			}else{
				$detail[$i]['received'] = date("Y-m-d H:i",($detail[$i]['received']));
			}
			$date = strtotime(base64_decode($detail[$i]['received']));
			if($date){
				$detail[$i]['date'] = date("Y-m-d",$date);
			}else{
				$detail[$i]['date'] = $detail[$i]['received'];
			}
			$detail[$i]['file'] = base64_encode($detail[$i]['file']);
			unset($detail[$i]['mimes']);
			$strdata.=json_encode($detail[$i]);
			if($i<($row_end-1))$strdata.=",";
		}

		//基本信息
		$maxpage = ceil($total/$amount);
		$strdata = '{"maxpage":'.$maxpage.',"curpage":'.$start.',"data":['.$strdata.'],"unread":'.$unread.',"total":'.$total.'}';
		return $strdata;
	}

	/**
     * 获取按用户分类邮件用户列表
     * @param string $folder
     * @return string $strdata
     */
	function getUserList($folder,$start,$amount=50){
		$dir = $this->path."/tmp/classify/".$folder."/";

		if($start<=1){
			$row_start = 0;
		}else{
			$row_start = ($start-1)*$amount;
		}

		$rows = array();
		if(is_dir($dir) && is_readable($dir)){
			$handle = opendir($dir);
			while (false != ($filename = readdir($handle))) {
				if($filename!="."&&$filename!=".."){
					array_push($rows,$filename);
				}
			}
		}

		$maxpage = ceil(count($rows)/$amount);
		$row_end = $row_start+$amount;
		if($row_end>count($rows))$row_end = count($rows);

		$userlist = array();
		for ($i=$row_start;$i<$row_end;$i++){
			$tmp = file_get_contents($dir.$rows[$i]);
			$tmp = json_decode($tmp,true);
			if($tmp){
				array_push($userlist,array('username'=>$this->cutString($tmp['name'],16),'tip'=>$tmp['name'],'address'=>$rows[$i]));
			}
		}

		return json_encode(array('maxpage'=>$maxpage,'curpage'=>$start,'detail'=>$userlist));
	}

	/**
     * 移动按用户分类邮件
     * @param string $srcfolder
     * @param string $dstfolder
     * @param array $mids
     * @return 
     */
	function moveSortByUserMail($srcfolder,$dstfolder,$mids){
		$this->mailMove($srcfolder,$dstfolder,$mids);
	}

	/**
     * 删除按用户分类邮件
     * @param string $folder 索引文件目录
     * @param array $mids 邮件ID数组
     * @return 
     */
	function removeSortByUserMail($mids,$folder){
		//取消定时或延时发送
		$strmids = "";
		for($i=0;$i<count($mids);$i++){
			$mail = $this->idbmail->match($folder,$mids[$i]);
			if($mail['state']){
				//取消延时发送
				$mdata = json_decode($mail['data'],true);
				if($mdata['randsendtime']){
					$rst = explode("|",$mdata['randsendtime']);
					$this->unsetTimeSend($rst[0]);
				}

				//取消定时发送
				if($mdata['timesend']){
					$st = explode("|",$mdata['timesend']);
					$this->unsetTimeSend($st[0]);
				}

				//删除邮件查看日志
				if(isset($mdata['recall'])){
					unlink($this->path."/tmp/recall/".$mids[$i]);
				}
			}
			$strmids.=$mids[$i];
			if($i<(count($mids)-1))$strmids.=",";
		}

		$opt = $this->idbmail->delete($folder,$mids);
		$pfolder = $folder;
		if($opt['state']){
			foreach ($mids as $mid){
				unlink($this->path."/eml/".$mid);
			}
			$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Mail deleted [".$this->path."/eml][".$strmids."]",1);
		}else{
			$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"Mail deleted [".$this->path."/eml][".$strmids."][Error:".$this->errordesc['ERR'.$opt['error']]."]",0);
			return false;
		}
		return true;
	}

	/**
     * 更新按用户分类邮件属性
     * @param string $folder
     * @param string $mid
     * @param string $tag
     * @param string $val
     * @return 
     */
	function setSortByUserMail($folder,$mid,$tag,$val){
		$mid = base64_decode($mid);
		$ret = $this->idbmail->update($folder,$mid,$tag,$val);
		return $ret['state'];
	}


    function getMailSequence($folder,$mid,$curpage,$pagesize,$order,$sort){
        $res = $this->idbmail->offset($folder,$mid,$order,$sort);
        if($res['state']){
            $offset = $res['data'];
            if ($offset > 0) {
                $res = $this->idbmail->simplelisting($folder,$order,$sort,$offset-1,3);
                $pre_rec = explode(" ", $res['data'][0]);
                $nxt_rec = explode(" ", $res['data'][2]);
                $pre_mid = $pre_rec[0];
                $nxt_mid = $nxt_rec[0];
            } else {    
                $res = $this->idbmail->simplelisting($folder,$order,$sort,$offset,2);
                $nxt_rec = explode(" ", $res['data'][1]);
                $nxt_mid = $nxt_rec[0];
            }           

            $retpage = (int)(($offset+1)/$pagesize) + 1;
        }       

        return array('nextmid'=>$nxt_mid,"premid"=>$pre_mid,"retpage"=>$retpage,"curpage"=>$curpage);
    }   

	function getSortByUserMailUsername($folder,$address){
		$dir = $this->path."/tmp/classify/".$folder."/";
		$row = json_decode(file_get_contents($dir.$address),true);
		return $row['name'];
	}

	function getUserNameFromContact($address){
		$param = array('user'=>PANDORA_DATABASE_USER,'password'=>PANDORA_DATABASE_PASSWORD,'host'=>PANDORA_DATABASE_HOST,'dbname'=>PANDORA_DATABASE_DBNAME);
		$this->_db = new Mysql($param);
		$row = $this->_db->select("select real_name from users where username='$address'");
		return $row[0]['real_name'];
	}

	/**
     * 字符串截取
     *
     * @param string $string	输入字符串
     * @param int $sublen	长度
     * @param int $start	起始位置 	
     * @param int $code		编码
     * @return string
     */
	public function cutString($string, $length, $dot = '..',$charset='utf-8') {
		if(strlen($string) <= $length) {
			return $string;
		}
		$string = str_replace(array('　',' ', '&', '"', '<', '>'), array('','','&', '"', '<', '>'), $string);
		$strcut = '';
		if(strtolower($charset) == 'utf-8') {
			$n = $tn = $noc = 0;
			while($n < strlen($string)) {
				$t = ord($string[$n]);
				if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
					$tn = 1; $n++; $noc++;
				} elseif(194 <= $t && $t <= 223) {
					$tn = 2; $n += 2; $noc += 2;
				} elseif(224 <= $t && $t < 239) {
					$tn = 3; $n += 3; $noc += 2;
				} elseif(240 <= $t && $t <= 247) {
					$tn = 4; $n += 4; $noc += 2;
				} elseif(248 <= $t && $t <= 251) {
					$tn = 5; $n += 5; $noc += 2;
				} elseif($t == 252 || $t == 253) {
					$tn = 6; $n += 6; $noc += 2;
				} else {
					$n++;
				}
				if($noc >= $length) {
					break;
				}
			}
			if($noc > $length) {
				$n -= $tn;
			}
			$strcut = substr($string, 0, $n);
		} else {
			for($i = 0; $i < $length; $i++) {
				$strcut .= ord($string[$i]) > 127 ? $string[$i].$string[++$i] : $string[$i];
			}
		}
		return $strcut.$dot;
	}

	function microtime_float()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
}
?>
