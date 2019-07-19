<?php
/**
 * 定义 WebMail_Model_MailBase 类
 *
 * @copyright 
 * @author Rick Jin
 * @package pandora
 * @version 1.0
 */
class WebMail_Model_MailBase
{
	protected $path;
	/**
     * 构造函数
     *
     * @param string $path 索引文件根目录
     * @return void
     */
	function __construct($path){
		$this->path = $path;
	}

	/**
     * 转换文件大小显示
     *
     * @param int $size
     * @return string
     */
	function convertSize($size)
	{
		$formatsize = '';
		if($size<1024 && $size>0){
			$formatsize = "1K";
		}elseif (($size/1024)<1024){
			$formatsize = round(($size/1024),1)."K";
			if((floor($size/1024/100)<10)&&($size/1024/100>=1))$formatsize = round(($size/1024/1024),1)."M";
		}elseif ((($size/1024/1024)<1024)){
			$formatsize = round(($size/1024/1024),1)."M";
		}elseif ((($size/1024/1024/1024)<1024)){
			$formatsize = round(($size/1024/1024/1024),1)."G";
		}else{
			$formatsize = $size;
		}
		return $formatsize;
	}

	/**
     * 字符串特定编码编码
     *
     * @param string $str 字符串原文
     * @return string
     */
	function strToAscii($str){
		if(empty($str)){
			return $str;
		}else{
			$tmp = str_split($str);
			$newstr = '';
			for ($i=0;$i<count($tmp);$i++){
				$newstr.= dechex(ord($tmp[$i]));
			}
			return $newstr;
		}
	}

	/**
     * 字符串特定编码解码
     *
     * @param string $str 字符串密文
     * @return string
     */
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

	/**
     * 字符串处理
     *
     * @param string $str 需处理的字串
     * @param string $charset 字符编码
     * @return string
     */
	function strProce($str,$charset){
		$str = base64_decode($str);
		$tmpstr = '';
		if(($pos = strpos($str,"=?"))!==false){
			$tmpstr = substr($str,0,$pos);
			$str = substr($str,$pos,strlen($str));
		}
		$res = preg_split("/\?/",$str,-1);
		$n = $m = $b= 0;
		$bstr = $qstr = array();

		if(count($res)>5){
			for ($i=0;$i<count($res);$i++){
				if((strtolower($res[$i])=='q')||((strtolower($res[$i])=='b'))){
					$tstr[$n]['encoding'] = strtoupper($res[$i]);
					$tstr[$n]['charset'] = strtoupper($res[$i-1]);
					$tstr[$n]['value'] = $res[$i+1];

					if(strlen($res[$i+2])>3){
						$tstr[$n]['extstr'] = str_replace("=","",$res[$i+2]);
					}
					$n++;
				}
			}

			$str = "";
			for($i=0;$i<count($tstr);$i++){
				if($tstr[$i]['encoding'] == "Q"){
					if(($tstr[$i]['encoding'] == $tstr[$i+1]['encoding'])&&(!isset($tstr[$i]['extstr']))){
						array_push($qstr,$tstr[$i]);
						if(($tstr[$i+1]['encoding'] != $tstr[$i+2]['encoding'])){
							array_push($qstr,$tstr[$i+1]);
						}
					}else{
						if($tstr[$i]['encoding'] != $tstr[$i-1]['encoding']){
							$str.= $this->strDecode("=?".$tstr[$i]['charset']."?".$tstr[$i]['encoding']."?".$tstr[$i]['value']."?=",$charset);
							if(isset($tstr[$i]['extstr']))$str = $str.$tstr[$i]['extstr'];
						}
					}
				}elseif ($tstr[$i]['encoding'] == "B"){
					if(($tstr[$i]['encoding'] == $tstr[$i+1]['encoding'])&&(!isset($tstr[$i]['extstr']))){
						array_push($bstr,$tstr[$i]);
						if(($tstr[$i+1]['encoding'] != $tstr[$i+2]['encoding'])){
							array_push($bstr,$tstr[$i+1]);
						}
					}else{
						if($tstr[$i]['encoding'] != $tstr[$i-1]['encoding']){
							$str.= $this->strDecode("=?".$tstr[$i]['charset']."?".$tstr[$i]['encoding']."?".$tstr[$i]['value']."?=",$charset);
							if(isset($tstr[$i]['extstr']))$str = $str.$tstr[$i]['extstr'];
						}
					}
				}
			}

			//baseb4
			$tbtstr = "";
			for($i=0;$i<count($bstr);$i++){
				$s = base64_decode($bstr[$i]['value']);
				$c=unpack("H*",$s);
				$d=implode('',$c);
				$tbtstr.=$d;
				$tbcharset = $bstr[$i]['charset'];
			}

			if($tbtstr){
				$resstr = "";
				for($i=0;$i <strlen($tbtstr);$i+=4){
					$resstr.=pack("H4",substr($tbtstr,$i,4));
				}
				$str.= $this->strDecode("=?".$tbcharset."?B?".base64_encode($resstr)."?=",$charset);
			}

			//quotedprintable
			$tqtstr = "";
			for($i=0;$i<count($qstr);$i++){
				$s = quoted_printable_decode($qstr[$i]['value']);
				$c=unpack("H*",$s);
				$d=implode('',$c);
				$tqtstr.=$d;
				$tqcharset = $qstr[$i]['charset'];
			}
			if($tqtstr){
				$resstr = "";
				for($i=0;$i <strlen($tqtstr);$i+=4){
					$resstr.=pack("H4",substr($tqtstr,$i,4));
				}
				$str.= $this->strDecode("=?".$tqcharset."?B?".base64_encode($resstr)."?=",$charset);
			}

			return $tmpstr.$str;
		}else{
			$extstr = "";
			if(count($res)>1){
				if($res[(count($res)-1)]!="="){
					$extstr = str_replace("=","",$res[(count($res)-1)]);
					$str = str_replace($extstr,"",$str);
				}
			}
			return $tmpstr.$this->strDecode($str,$charset).$extstr;
		}
	}

	/**
     * 字符串解码
     *
     * @param string $str	已编码字符串
     * @return string
     */
	function strDecode($str,$charset=''){
		$this->_charset = $this->getCharset();
		$str = trim($str);
		$mark = preg_split("/[?]/",$str);
		preg_match("/[B|b|Q|q][?](.*)/",$str,$val);
		if(count($val)){
			$val[1] = str_replace("?=","",$val[1]);
			if(strtoupper($mark[2]) == 'B'){
				$decodestr = base64_decode($val[1]);
			}elseif (strtoupper($mark[2]) == 'Q'){
				$decodestr = quoted_printable_decode($val[1]);
			}else{
				if(empty($charset))$charset = 'gb2312';
				if(array_key_exists(strtolower($charset),$this->_charset)){
					$charset = $this->_charset[strtolower($charset)];
				}

				$str = iconv($charset,'utf-8//IGNORE',$str);
				return $str;
			}

			$encoding = $mark[1];
			if(array_key_exists(strtolower($encoding),$this->_charset)){
				$encoding = $this->_charset[strtolower($encoding)];
			}
		}else{
			$decodestr = $str;
		}

		$tmp = $decodestr;

		if(empty($encoding)){
			$decodestr = iconv("gb2312",'utf-8//IGNORE',$tmp);
		}else{
			$decodestr = iconv($encoding,'utf-8//IGNORE',$tmp);
			if(empty($decodestr)){
				$decodestr = iconv("gb2312",'utf-8//IGNORE',$tmp);
			}
		}

		if(!$decodestr){
			$decodestr = iconv("gb2312","utf-8//IGNORE",$tmp);
		}
		return $decodestr;
	}

	/**
     * 邮件附件数据集
     *
     * @param string $mid 邮件ID
     * @return array
     */
	function mailAttachlist($mid){
		$path = $this->path."/tmp/attach/".$mid;
		if(file_exists($path)){
			$files = scandir($path);
			if(count($files)>2){
				$i=0;
				foreach($files as $file) {
					if(file_exists($path."/".$file) && $file != '.' && $file != '..') {
						$filename = $this->asciiToStr($file);
						$data[$i]['show'] = 1;
						if((ereg("^innerimg-",$filename))||(ereg("^sign-",$filename))){$data[$i]['show'] = 0;}
						$data[$i]['filename'] = $filename;
						$data[$i]['size'] = $this->convertSize(filesize($path."/".$file));
						$data[$i]['file'] = $file;
						$i++;
					}
				}
			}
		}
		return $data;
	}

	/**
     * 取得邮件正文内容
     *
     * @param string $path	邮件物理地址
     * @param int $begin 起始地址
     * @param int $length 长度
     * @param string $encoding 编码类型
     * @param string $charset 字符编码
     * @param string $randcharset 默认字符编码
     * @param int $ishtml 是否为html
     * @return string
     */
	function getMailContent($path,$begin,$length,$encoding,$charset,$setcharset,$ishtml=1){
		if(empty($setcharset)){
			$randcharset = 'utf-8';
		}else{
			$randcharset = $setcharset;
		}
		//取得邮件正文内容
		$fpe = fopen($path,"r");
		fseek($fpe,$begin);
		$content = fread($fpe,$length);

		if(strtolower($encoding)=="base64"){
			$content = base64_decode($content);
		}elseif(strtolower($encoding)=="quoted-printable"){
			$content = quoted_printable_decode($content);
		}
		fclose($fpe);

		//html内容特殊处理
		if(!$ishtml){
			$content = str_replace("\r\n","<br>",$content);
		}else{
			$content = preg_replace("/<script.*>.*<\/script>/isU "," ",$content);
		}
		$tmp = $content;

		//取得html内容编码
		$content_charset = '';
		if($ishtml){
			preg_match_all("/<meta.+?charset=([-\w]+)/i",$tmp,$arrcharset);
			$content_charset = $arrcharset[1][0];
		}

		//设定转换前编码
		if(empty($setcharset)){
			if(empty($charset)){
				if(empty($content_charset)){
					$in_charset = 'gb2312';
				}else{
					$in_charset = $content_charset;
				}
			}else{
				$in_charset = $charset;
			}
		}else{
			if(empty($charset)){
				$in_charset = $setcharset;
			}else{
				$in_charset = $charset;
			}
		}

		//编码对照转换
		$charsets = $this->getCharset();
		if(array_key_exists(strtolower($in_charset),$charsets)){
			$in_charset = $charsets[strtolower($in_charset)];
		}
		$content = iconv($in_charset,$randcharset."//IGNORE",$tmp);
		if (FALSE == $content) {
			return $tmp;
		}
		return $content;
	}

	/**
     * 取得编码对照表
     *
     * @return array
     */
	function getCharset(){
		$data = parse_ini_file(APP_PATH.'/config/charset.ini',true);
		foreach ($data[$_COOKIE['SET_LANG']] as $name=>$value){
			$charset[$name] = $value;
		}
		return $charset;
	}

	/**
     * 清除邮件地址多余字符
     *
     * @param string $str 源字符
     * @return string
     */
	function clearAddress($str){
		return preg_replace("/[\|<|>|\'|\"]/","",$str);
	}

	/**
     * 处理邮件地址字串
     *
     * @param string $str	邮件地址字符串
     * @param string $type  返回结果类型 字串或数组
     * @return mixed
     */
	function getAddress($str,$type='STRING'){
		$str = base64_decode($str);
		//预处理
		preg_match_all('/[\"|\'](.*?)[\"|\']/',$str,$val);
		if(count($val)){
			for($i=0;$i<count($val);$i++){
				$repstr = str_replace(",","#comma#",$val[0][$i]);
				$str = str_replace($val[0][$i],$repstr,$str);
			}
		}
		$address = preg_split("/,|;/",$str,-1);

		for ($i=0;$i<(count($address));$i++){
			if(!empty($address[$i])){
				$address[$i] = str_replace("#comma#",",",$address[$i]);
				$address[$i] = str_replace(" ","",$address[$i]);
				$tmp = array();
				if(eregi("<",$address[$i])){
					$tmp = explode("<",$address[$i]);
				}else{
					$tmp[0] = $this->clearAddress($address[$i]);
				}


				if(count($tmp)>1){
					$data[$i]['name'] = $this->strDecode($this->clearAddress($tmp[0]));
					$data[$i]['address'] = $this->clearAddress($tmp[1]);
				}else{
					if (eregi("^[_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,4}$",$tmp[0])){
						$data[$i]['name'] = "";
						$data[$i]['address'] = $this->strDecode($this->clearAddress($tmp[0]));
					}else{
						$data[$i]['name'] = $this->strDecode($this->clearAddress($tmp[0]));
						if ($this->checkMailaddr($data[$i]['name'])) {
							$data[$i]['address'] = $data[$i]['name'];
						} else {
							$data[$i]['address'] = "无邮件地址";
						}
					}
				}
				$data[$i]['name'] = str_replace("<", "", $data[$i]['name']);
				$data[$i]['name'] = str_replace(">", "", $data[$i]['name']);
			}
		}

		if($type=='STRING'){
			for ($i=0;$i<count($data);$i++){
				$data[$i]['address'] = str_replace("?=","",$data[$i]['address']);
				if(!empty($data[$i]['name'])){
					$straddress.="<a href='###' class='bluelink' title='".$data[$i]['address']."'>".$data[$i]['name']."</a>";
				}else{
					$straddress.="<a href='###' class='bluelink' title='".$data[$i]['address']."'>".$data[$i]['address']."</a>";
				}
				if($i<count($data)-1)$straddress.="; ";
			}
			return $straddress;
		}elseif($type=='FULLADDRESS'){
			for ($i=0;$i<count($data);$i++){
				$data[$i]['address'] = str_replace("?=","",$data[$i]['address']);
				if(!empty($data[$i]['name'])){
					$straddress.=$data[$i]['name']."&lt;".$data[$i]['address']."&gt;";
				}else{
					$straddress.=$data[$i]['address'];
				}
				$straddress.="; ";
			}
			return $straddress;
		}elseif ($type=='ARRAY'){
			return $data;
		}elseif ($type=='ADDRESS'){
			for ($i=0;$i<count($data);$i++){
				$straddress.=$data[$i]['address'];
				if($i<count($data)-1)$straddress.="; ";
			}
			return $straddress;
		}elseif ($type=='EDITADDRESS'){
			$tmp = explode("@",$_COOKIE['SESSION_MARK']);
			$domain = strtolower($tmp[1]);
			for ($i=0;$i<count($data);$i++){
				$data[$i]['address'] = str_replace("?=","",$data[$i]['address']);
				if(empty($data[$i]['name'])){
					$data[$i]['name'] = $data[$i]['address'];
				}
				if(!strpos(strtolower($data[$i]['address']),$domain)){
					$param = base64_encode(json_encode(array('name'=>$data[$i]['name'],'mail'=>$data[$i]['address'])));
					$straddress.='<a href="javascript:addContact(\''.$param.'\')" title="'.$data[$i]['address'].','.LANG_CONTACT_C0035.'" class="bluelink">'.$data[$i]['name'].'</a></li>';
				}else{
					$straddress.="<a href='###' class='bluelink' title='".$data[$i]['address']."'>".$data[$i]['name']."</a>";
				}
				if($i<count($data)-1)$straddress.="; ";
			}
			return $straddress;
		}elseif ($type=='DESC'){
			for ($i=0;$i<count($data);$i++){
				$data[$i]['address'] = str_replace("?=","",$data[$i]['address']);
				if(!empty($data[$i]['name'])){
					$straddress.="\"".$data[$i]['name']."\" ".$data[$i]['address'];
				}else{
					$straddress.=$data[$i]['address'];
				}
				$straddress.="; ";
			}
			return $straddress;
		}
	}

	/**
     * 转换内嵌图片
     *
     * @param string $content 邮件正文内容
     * @param array 附件信息
     * @return string
     */
	function transferInnerimg($content,$attach){
		preg_match_all("/[C|c][I|i][D:d]:.*?[\'|\"|\>| ]/",$content,$res);
		$res = $res[0];
		for($i=0;$i<count($res);$i++){
			$res[$i] = preg_replace("/[\'|\"|\>| ].*/","",$res[$i]);
			$res[$i] = substr($res[$i],4,strlen($res[$i]));
			for($j=0;$j<count($attach);$j++){
				if($attach[$j]['cid']==$res[$i]){
					unset($attach[$j]['size']);
					$param = base64_encode(json_encode($attach[$j]));
					$url = "innerimg?param=".$param;
					$content = str_replace("cid:".$res[$i],$url,$content);
				}
			}
		}
		return $content;
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

	function removeHtmlImg($str){
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
     * 附件上传
     *
     * @param string $files 上传文件
     * @param string $mid   邮件编号
     * @return mixed
     */
	function uploadFile($files,$mid){
		if(!file_exists($this->path."/tmp/attach")){
			mkdir($this->path."/tmp/attach");
		}
		$path = $this->path."/tmp/attach/".$mid;
		if(!file_exists($path)){
			mkdir($path);
		}

		if (!empty($files)) {
			//去除文件名中的空格
			$tempFile = $files['Filedata']['tmp_name'];
			$targetPath = $path."/";
			$tmpfile = explode(".",$files['Filedata']['name']);
			$tmpfilename = $this->strToAscii($files['Filedata']['name']);
			if(strlen($tmpfilename)>200){
				$tmpfilename = substr($tmpfilename,0,200);
				$tmpfilename = $this->asciiToStr($tmpfilename);
				$tmpfilename.=".".$tmpfile[count($tmpfile)-1];
				$tmpfilename = $this->strToAscii($tmpfilename);
			}
			$targetFile = $targetPath.$tmpfilename;
			if(move_uploaded_file($tempFile,$targetFile)){
				return "1";
			}
		}
	}
	
	/**
     * 判断是否为邮件地址
     *
     * @param string $mailaddr 需要判断的字符串
     * @return bool
     */
	function checkMailaddr($mailaddr) {
		if (!eregi("^[_a-z0-9-]+(.[_a-z0-9-]+)*@[a-z0-9-]+(.[a-z0-9-]+)*$", $mailaddr)) {
			return false;
		}
		return true;
	}

}
