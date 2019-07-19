<?php
ini_set('memory_limit','128M');

//定义根目录
define("APP_PATH",realpath(dirname(__FILE__)."/../"));

$handle = fopen(APP_PATH."/config/allow_hosts.txt", 'r');
if ($handle) {
	$matched = FALSE;
	while (($buffer = fgets($handle, 1024)) !== false) {
		$buffer = trim($buffer);
		if ($buffer[0] == '#') {
			continue;
		}
		if (0 == strcasecmp($buffer, $_SERVER['SERVER_NAME'])) {
			$matched = TRUE;
			break;
		}
	}
	fclose($handle);
	if (FALSE == $matched) {
		header('HTTP/1.1 404 Not Found');
		die();
	}
}

require_once(APP_PATH."/libs/Action.class.php");
require_once(APP_PATH."/libs/Mysql.class.php");


//初始化常量
if(CONFIG_SET!=1){
	$config = parse_ini_file(APP_PATH."/config/config.ini",true);
	define(strtoupper("config_set"),1);
	foreach ($config as $k=>$v){
		foreach ($v as $sk=>$sv){
			define(strtoupper("pandora_".$k."_".$sk),$sv);
		}
	}
}

if ($_COOKIE['SET_TIMEZONE']) {
	date_default_timezone_set($_COOKIE['SET_TIMEZONE']);
} else {
	date_default_timezone_set(PANDORA_SYSTEM_TIMEZONE);
}

//初始化语言
if(empty($_COOKIE['SET_LANG'])){
	$langs = array();  
	preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)s*(;s*qs*=s*(1|0.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);
	if (count($lang_parse[1])) {
		$langs = array_combine($lang_parse[1], $lang_parse[4]);
		foreach ($langs as $lang => $val) {  
			if ($val === '') $langs[$lang] = 1;  
		}
		arsort($langs, SORT_NUMERIC);
		$tlang = key($langs);
	} else {
		$tlang = "zh-cn";
	}
   
	if (preg_match("/zh-c/i",$tlang) || preg_match("/zh-h/i")){
		$setlang = 'zh';
	}elseif (preg_match("/zh/i", $tlang)){
		$setlang = 'cn';
	}elseif (preg_match("/en/i", $tlang)){
		$setlang = 'en';
	}elseif (preg_match("/jp/i", $tlang)){
		$setlang = 'jp';
	}elseif (preg_match("/ja/i", $tlang)){
		$setlang = 'jp';
	}else{
		$setlang = 'zh';
	}
}else{
	$setlang = $_COOKIE['SET_LANG'];
}

//登录页语言设置
if(rtrim($_SERVER["REQUEST_URI"])=='/index.php/auth/login'){
	$langs = array();  
	preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)s*(;s*qs*=s*(1|0.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);
	if (count($lang_parse[1])) {
		$langs = array_combine($lang_parse[1], $lang_parse[4]);
		foreach ($langs as $lang => $val) {  
			if ($val === '') $langs[$lang] = 1;  
		}
		arsort($langs, SORT_NUMERIC);
		$tlang = key($langs);
	} else {
		$tlang = "zh-cn";
	}
   
	if (preg_match("/zh-c/i",$tlang) || preg_match("/zh-h/i")){
		$setlang = 'zh';
	}elseif (preg_match("/zh/i", $tlang)){
		$setlang = 'cn';
	}elseif (preg_match("/en/i", $tlang)){
		$setlang = 'en';
	}elseif (preg_match("/jp/i", $tlang)){
		$setlang = 'jp';
	}elseif (preg_match("/ja/i", $tlang)){
		$setlang = 'jp';
	}else{
		$setlang = 'zh';
	}
}

//设置默认语言
$mlang = array(1=>'cn',2=>'zh',3=>'jp',4=>'en');
if(!array_search($setlang,$mlang)){
	$setlang = 'en';
}

$php_version = explode('-', phpversion());
$php_version = $php_version[0];
$php_version_ge530 = strnatcasecmp($php_version, '5.3.0') >= 0 ? true : false;

if (false == $php_version_ge530) {
        $lang = parse_ini_file(APP_PATH."/lang/".$setlang.".ini",true);
} else {
        if (0 == strcmp('en', $setlang)) {
                $lang = parse_ini_file(APP_PATH."/lang/".$setlang.".ini",true, INI_SCANNER_RAW);
        } else {
                $lang = parse_ini_file(APP_PATH."/lang/".$setlang.".ini",true);
        }
}

define(strtoupper("lang_set"),1);
foreach ($lang as $k=>$v){
	foreach ($v as $sk=>$sv){
		define(strtoupper("lang_".$k."_".$sk),$sv);
	}
}

//$http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
$http_type = "https://";

define('PANDORA_PATH_WWWROOT', $http_type . $_SERVER['SERVER_NAME']);

//定义url路由
if($_SERVER['REQUEST_URI']=='/'||$_SERVER['REQUEST_URI']=='/index.php'){
	header("Location: ". $http_type . PANDORA_PATH_HOST. "/index.php/index/index?domain=" . $_SERVER['SERVER_NAME']);
}else{
	$action = new Action();
	$action->setPath(rtrim($_SERVER["SCRIPT_NAME"],'/'));
	$action->run();
}
