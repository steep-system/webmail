<?php
/**
 * 定义 WebMail_Model_Base 类
 *
 * @copyright 
 * @author Rick Jin
 * @package webmail
 * @version 1.0
 */
class WebMail_Model_Base
{
	public $_sysconfig;

	function init(){
		//$this->_sysconfig = $this->getSysconfig();
	}

	public function sysSortArray($ArrayData,$KeyName1,$SortOrder1 = "SORT_ASC",$SortType1 = "SORT_REGULAR")
	{
		if(!is_array($ArrayData))
		{
			return $ArrayData;
		}

		// Get args number.
		$ArgCount = func_num_args();

		// Get keys to sort by and put them to SortRule array.
		for($I = 1;$I < $ArgCount;$I ++)
		{
			$Arg = func_get_arg($I);
			if(!eregi("SORT",$Arg))
			{
				$KeyNameList[] = $Arg;
				$SortRule[]    = '$'.$Arg;
			}
			else
			{
				$SortRule[]    = $Arg;
			}
		}

		// Get the values according to the keys and put them to array.
		foreach($ArrayData AS $Key => $Info)
		{
			foreach($KeyNameList AS $KeyName)
			{
				${$KeyName}[$Key] = $Info[$KeyName];
			}
		}

		// Create the eval string and eval it.
		$EvalString = 'array_multisort('.join(",",$SortRule).',$ArrayData);';
		eval ($EvalString);
		return $ArrayData;
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
	public function cutString($string, $sublen, $start = 0, $code = 'UTF-8')
	{
		if($code == 'UTF-8')
		{
			$pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
			preg_match_all($pa, $string, $t_string);

			if(count($t_string[0]) - $start > $sublen) return join('', array_slice($t_string[0], $start, $sublen))."...";
			return join('', array_slice($t_string[0], $start, $sublen));
		}
		else
		{
			$start = $start*2;
			$sublen = $sublen*2;
			$strlen = strlen($string);
			$tmpstr = '';

			for($i=0; $i< $strlen; $i++)
			{
				if($i>=$start && $i< ($start+$sublen))
				{
					if(ord(substr($string, $i, 1))>129)
					{
						$tmpstr.= substr($string, $i, 2);
					}
					else
					{
						$tmpstr.= substr($string, $i, 1);
					}
				}
				if(ord(substr($string, $i, 1))>129) $i++;
			}
			if(strlen($tmpstr)< $strlen ) $tmpstr.= "...";
			return $tmpstr;
		}
	}

	public function getUserIP(){
		if($_SERVER['HTTP_X_FORWARDED_FOR']){
			$onlineip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			$c_agentip=1;
		} elseif($_SERVER['HTTP_CLIENT_IP']){
			$onlineip = $_SERVER['HTTP_CLIENT_IP'];
			$c_agentip=1;
		} else{
			$onlineip = $_SERVER['REMOTE_ADDR'];
			$c_agentip=0;
		}
		$onlineip = preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/',$onlineip) ? $onlineip : 'Unknown';
		return $onlineip;
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
		}else{
			$formatsize = $size;
		}
		return $formatsize;
	}

	function microtime_float()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}

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
}