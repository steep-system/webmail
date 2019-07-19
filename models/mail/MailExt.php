<?php

require_once(APP_PATH."/libs/sphinxapi.php");

/**
 * 定义 WebMail_Model_MailExt 类
 *
 * @copyright 
 * @author YangSongyi
 * @package pandora
 * @version 2.0
 */
class WebMail_Model_MailExt
{
	protected $cl;
	protected $idbmail;
	protected $mailbase;
	protected $username;
	protected $path;
	protected $sphinx;

	/**
     * 构造函数
     *
	 * @param string $username 用户名
     * @param string $path 用户目录，用于定位sphinx服务器
     * @return void
     */
	function __construct($userid, $username, $path){
		@ini_set('memory_limit', '1024M');
		$this->cl = new SphinxClient();
		$this->idbmail = new IDB_Mail($path);
		$this->mailbase = new WebMail_Model_MailBase($path);
		$this->userid = $userid;
		$this->username = $username;
		$this->path = $path;
		
		$array = parse_ini_file(APP_PATH."/config/config.ini", true);
		foreach ($array['sphinx'] as $k=>$v){
			$len = strlen($k) + 1;
			if (0 == strncmp($path, $k . "/", $len)) {
				$tmp = explode(':', $v);
				$this->sphinx['host'] = $tmp[0];
				if (count($tmp) < 2) {
					$this->sphinx['port'] = 9312;
				} else {
					$this->sphinx['port'] = $tmp[1];
				}
				break;
			}
		}
	}


	
	/**
	 * 搜索邮件
	 *
	 * @param string $keyword 检索关键字
	 * @return string
	 */
	function mailSearch($keyword){
		
		$mbobj = array();
		
		$index_time = (int)0;
		if ($this->sphinx) {
			$this->cl->SetServer($this->sphinx['host'], $this->sphinx['port']);
			$this->cl->SetArrayResult(true);
			$this->cl->SetMatchMode(SPH_MATCH_ALL);
			$this->cl->SetLimits(0, 200, 200);
			$this->cl->SetFilter("userid", array($this->userid));
			$res = $this->cl->Query($this->cl->EscapeString($keyword), "main");
			
			for ($i=0; $i<count($res['matches']); $i++) {
				$tmpobj = json_decode($res['matches'][$i]['attrs']['location'], true);
				if (0 == strcasecmp($tmpobj['username'], $this->username)) {
					$retobj[$tmpobj['file']] = $tmpobj['folder'];
				}
			}
			$content = file_get_contents($this->path . "/config/sphinx.cfg");
			if (isset($content)) {
				$cfg = json_decode($content, true);
				$index_time = (int)$cfg['last_time'];
			}
		}
		
		$opt = $this->idbmail->listfolder();
		if (!$opt['state']) {
			return null;
		}
		$folders = array('inbox', 'sent', 'draft', 'junk', 'trash');
		if (!empty($opt['data'])) {
			$folders = array_merge($folders, $opt['data']);
		}
		for ($i=0; $i<count($folders); $i++) {
			$opt = $this->idbmail->uidl($folders[$i]);
			if ($opt['state']) {
				$mrows = $opt['data'];
				for ($j=0; $j<count($mrows); $j++) {
					$mbobj[$mrows[$j][0]] = $folders[$i];
				}
			}
		}
		
		/* 传统方法检索未索引的邮件 */
		foreach ($mbobj as $k=>$v){
			if (intval($k) >= $index_time) {
				if (true == $this->mailMatch($v, $k, $keyword)) {
					$retobj["$k"] = $v;
				}
			}
		}
		
		/* 检测搜索结果中的项目是否还存在邮箱中 */
		foreach ($retobj as $k=>$v){
			if (!isset($mbobj["$k"])) {
				unset($retobj["$k"]);
			}
		}
		
		return $retobj;
	}


	/**
	 * 传统方式匹配邮件
	 *
	 * @param string $folder 邮件夹
	 * @param string $file 邮件文件名
	 * @param string $keyword 检索关键字
	 * @return bool
	 */
	function mailMatch($folder, $file, $keyword){
		
		$opt = $this->idbmail->match($folder, $file);
		
		if ($opt['state'] == 0) {
			return false;
		}
		
		$tmp = json_decode($opt['data'], true);
		$path = $this->path . '/eml/' . $tmp['file'];
		$retobj['file'] = $tmp['file'];
		for ($i=0;$i<count($tmp['mimes']);$i++){
			if((strtolower($tmp['mimes'][$i]['ctype'])=='text/plain')) {
				if(!empty($tmp['mimes'][$i]['charset'])){
					$charset = $tmp['mimes'][$i]['charset'];
				}else{
					$charset = 'gb2312';
				}
				if (empty($retobj['content'])) {
					if (stristr($this->mailbase->getMailContent($path,$tmp['mimes'][$i]['begin'],$tmp['mimes'][$i]['length'],$tmp['mimes'][$i]['encoding'],$charset), $keyword)) {
						return true;
					}
				}
			} else if (strtolower($tmp['mimes'][$i]['ctype'])=='text/html'){
				if(!empty($tmp['mimes'][$i]['charset'])){
					$charset = $tmp['mimes'][$i]['charset'];
				}else{
					$charset = 'gb2312';
				}
				if (stristr($this->mailbase->getMailContent($path,$tmp['mimes'][$i]['begin'],$tmp['mimes'][$i]['length'],$tmp['mimes'][$i]['encoding'],$charset), $keyword)) {
					return true;
				}
			}
			
			if ($tmp['mimes'][$i]['filename']) {
				if ($retobj['attachment']) {
					$retobj['attachment'] .= "; ";
				}
				if (stristr($this->mailbase->clearAddress($this->mailbase->strProce($tmp['mimes'][$i]['filename'],$charset)), $keyword)) {
					return true;
				}
			}
		}
		
		

		if($tmp['from']){
			$from = $this->mailbase->getAddress($tmp['from'],"ARRAY");
			if ($from[0]['name']) {
				if (stristr($from[0]['name'], $keyword)) {
					return true;
				}
			} else {
				if (stristr($from[0]['address'], $keyword)) {
					return true;
				}
			}
		}
		
		if($tmp['to']){
			$to = $this->mailbase->getAddress($tmp['to'],"ARRAY");
			for ($i=0;$i<count($to);$i++) {
				if ($to[$i]['name']) {
					if (stristr($to[$i]['name'], $keyword)) {
						return true;
					}
				}
				if (stristr($to[$i]['address'], $keyword)) {
					return true;
				}
				
			}
		}
		
		if($tmp['cc']){
			$cc = $this->mailbase->getAddress($tmp['cc'],"ARRAY");
			for ($i=0;$i<count($cc);$i++) {
				if ($cc[$i]['name']) {
					if (stristr($cc[$i]['name'], $keyword)) {
						return true;
					}
				}
				if (stristr($cc[$i]['address'], $keyword)) {
					return true;
				}
				
			}
		}
		
		if($tmp['subject']){
			if (stristr($this->mailbase->clearAddress($this->mailbase->strProce($tmp['subject'],$charset)), $keyword)) {
				return true;
			}
		}
		
		return false;	 
	}
	
	
	/**
     * 邮件列表
     *
     * @param string $result 搜索结果集
     * @param int $start 记录起始位置
     * @param int $amount 显示记录条数
     * @return string
     */
	function resultListing($result,$start,$amount){
		if($start<=1){
			$row_start = 0;
		}else{
			$row_start = ($start-1)*$amount;
		}
		
		$maxpage = ceil(count($result)/$amount);
		
		$cnt = 0;
		foreach ($result as $k=>$v) {
			if ($cnt >= $row_start) {
				$opt = $this->idbmail->match($v, $k);
				if ($opt['state'] == 0) {
					continue;
				}
				$tmp = json_decode($opt['data'], true);
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
					$tmp['received'] = date("Y-m-d H:i",filemtime($this->path."/".$v."/".$tmp['file']));
				}else{
					$tmp['received'] = date("Y-m-d H:i",($tmp['received']));
				}
				$date = strtotime(base64_decode($tmp['received']));
				if($date){
					$tmp['date'] = date("Y-m-d",$date);
				}else{
					$tmp['date'] = $tmp['received'];
				}
				$tmp['file'] = base64_encode($tmp['file']);
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
     * 序列化搜索结果
     *
     * @param string $result 搜索结果集
     * @param string $path 文件路径
     */
	function resultSerialize($result, $path) {
		file_put_contents($path, json_encode($result));
	}
	
	/**
     * 反序列化搜索结果
     *
     * @param string $path 文件路径
	 * @return string 搜索结果集
     */
	function resultDeserialize($path) {
		$content = file_get_contents($path);
		return json_decode($content, true);
	}
}

 
