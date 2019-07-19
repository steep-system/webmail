<?php

require_once(APP_PATH."/models/mail/MailBase.php");

/**
 * 定义 WebMail_Model_MailArchive 类
 *
 * @copyright 
 * @author YangSongyi
 * @package pandora
 * @version 2.0
 */
class WebMail_Model_MailArchive
{
	protected $mailbase;
	protected $username;
	protected $path;

	/**
     * 构造函数
     *
	 * @param string $username 用户名
     * @param string $path 用户目录，用于定位sphinx服务器
     * @return void
     */
	function __construct($username, $path){
		$this->mailbase = new WebMail_Model_MailBase($path);
		$this->username = $username;
		$this->path = $path;
	}


		
	
	function resultListing($result,$start,$amount){
		require_once(APP_PATH.'/models/archive/IDB_Classify.php');
		
		$config = parse_ini_file(APP_PATH . "/config/config.ini", true);
		$cidb = new IDB_Classify($config['archiver']);
		
		if($start<=1){
			$row_start = 0;
		}else{
			$row_start = ($start-1)*$amount;
		}
		
		$maxpage = ceil(count($result)/$amount);
		
		$cnt = 0;
		for ($k=0; $k<count($result); $k++) { 
			if ($cnt >= $row_start) {
				$opt = $cidb->match($result[$k][0], $result[$k][1]);
				if ($opt['state'] == 0) {
					continue;
				}
				$tmp_pos = strpos($opt['data'], ' ');
				$tmp_path = substr($opt['data'], 0, $tmp_pos);
				$tmp_string = substr($opt['data'], $tmp_pos + 1);
				$tmp = json_decode($tmp_string, true);
				if (!$tmp) {
					$tmp = json_decode(iconv('utf-8','utf-8//IGNORE',$opt['data']),true);
				}
				
				$tmp['received'] = strtotime(base64_decode($tmp['received']));
				$tmp['attach'] = 0;
				for ($i=0;$i<count($tmp['mimes']);$i++){
					if((strtolower($tmp['mimes'][$i]['ctype'])=='text/plain')||(strtolower($tmp['mimes'][$i]['ctype'])=='text/html')){
						if(!empty($tmp['mimes'][$i]['charset'])){
							$tmp['randcharset'] = $tmp['mimes'][$i]['charset'];
						}else{
							$tmp['randcharset'] = 'gb2312';
						}
					}elseif ((!empty($tmp['mimes'][$i]['filename']))&&((empty($tmp['mimes'][$i]['cid'])||(strstr($tmp['mimes'][$i]['ctype'],"application/"))))&&(empty($tmp['mimes'][$i]['cntl']))){
						$tmp['attach'] = 1;
					}elseif ($tmp['mimes'][$i]['ctype']=='message/rfc822'){
						$tmp['attach'] = 1;
					}
				}

				if(empty($tmp['subject'])){
					$tmp['subject'] = '无主题';
				}else{
					$tmp['subject'] = $this->mailbase->clearAddress($this->mailbase->strProce($tmp['subject'],$tmp['randcharset']));
					if(empty($tmp['subject']))$tmp['subject'] = "无法识别的编码！";
				}

				$$tmp['subject'] = str_replace("&", "&amp;", $tmp['subject']);

				if(empty($tmp['from'])){
					$tmp['from'] = '无发件人';
					$tmp['fromtip'] = '无发件人';
				}else{
					$from = $tmp['from'] = $this->mailbase->getAddress($tmp['from'],"ARRAY");
					$tmp['from'] = $from[0]['name'];
					if(empty($from[0]['name']))$tmp['from'] = $from[0]['address'];
					$tmp['fromtip'] = $from[0]['address'];
				}

				$tmp['from'] = str_replace("&", "&amp;", $tmp['from']);
				$tmp['fromtip'] = str_replace("&", "&amp;", $tmp['fromtip']);

				if('sent' == $v && !empty($tmp['to'])){
					$to = $tmp['to'] = $this->mailbase->getAddress($tmp['to'],"ARRAY");
					$tmp['to'] = $to[0]['name'];
					if(empty($to[0]['name']))$tmp['to'] = $to[0]['address'];
					$tmp['totip'] = $to[0]['address'];

					$tmp['to'] = str_replace("&", "&amp;", $tmp['to']);
					$tmp['totip'] = str_replace("&", "&amp;", $tmp['totip']);

				}

				$tmp['size'] = $this->mailbase->convertSize($tmp['size']);
				if(empty($tmp['received'])){
					$tmp['received'] = date("Y-m-d H:i",filemtime($cidb->getprefix($result[$k][0]) . "/". $tmp_path . "/".$result[$k][1]));
				}else{
					$tmp['received'] = date("Y-m-d H:i",($tmp['received']));
				}
				$date = strtotime(base64_decode($tmp['received']));
				if($date){
					$tmp['date'] = date("Y-m-d",$date);
				}else{
					$tmp['date'] = $tmp['received'];
				}
				$tmp['file'] = base64_encode($k);
				$tmp['read'] = 1;
				unset($tmp['mimes']);
				
				if ($strdata) {
					$strdata.=",";
				}
				$strdata.=json_encode($tmp);
			}
			$cnt ++;
			if ($cnt >= $start*$amount) {
				break;
			}
		}
		$strdata = '{"maxpage":'.$maxpage.',"curpage":'.$start.',"data":['.$strdata.'],"total":'.count($result).'}';
		return $strdata;
	}
	
	
	/**
     * 邮件明细
     */
	function mailDetail($server_id,$mail_id,$charset='',$addresstype='STRING'){
		require_once(APP_PATH."/models/mail/Mail.php");
		require_once(APP_PATH."/models/archive/IDB_Classify.php");
		
		$config = parse_ini_file(APP_PATH . "/config/config.ini", true);
		$cidb = new IDB_Classify($config['archiver']);
		
		
		$opt = $cidb->match($server_id, $mail_id);
		if(!$opt['state']){
			return false;
		}
		
		$tmp_pos = strpos($opt['data'], ' ');
		$tmp_path = substr($opt['data'], 0, $tmp_pos);
		$tmp_string = substr($opt['data'], $tmp_pos + 1);
		
		$path = $cidb->getprefix($server_id) . "/". $tmp_path . "/". $mail_id;
		
		$data = json_decode($tmp_string,true);
		if(!empty($data)){
			$mailobj = new WebMail_Model_Mail($this->path);
			$mail =  $mailobj->mailDetailContent($data,$path,$charset,$addresstype);
			return $mail;
		}else{
			return false;
		}
	}
	
	/**
     * 邮件明细
     */
	function mailPath($server_id,$mail_id){
		require_once(APP_PATH."/models/archive/IDB_Classify.php");
		
		$config = parse_ini_file(APP_PATH . "/config/config.ini", true);
		$cidb = new IDB_Classify($config['archiver']);
		
		
		$opt = $cidb->match($server_id, $mail_id);
		if(!$opt['state']){
			return false;
		}
		
		$tmp_pos = strpos($opt['data'], ' ');
		$tmp_path = substr($opt['data'], 0, $tmp_pos);
		
		$path = $cidb->getprefix($server_id) . "/". $tmp_path . "/". $mail_id;
		
		return $path;
	}
	
}

?>