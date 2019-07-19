<?php
require_once(APP_PATH."/models/Base.php");

class WebMail_Model_TrackLog extends WebMail_Model_Base
{
	protected $logfile;
	
	function __construct(){
		if(!file_exists(APP_PATH."/log")){
			mkdir(APP_PATH."/log",0777);
		}
		$file = "track.log";
		if(!file_exists(APP_PATH."/log/".$file)){
			file_put_contents(APP_PATH."/log/".$file,"");
		}
		$this->logfile = APP_PATH."/log/".$file;
	}
	
	public function sendLog($user,$action,$state){
		if(empty($user)){
			$user = "unknow";
		}
		
		$message = date("Y-m-d H:i:s")."\tuser: ".$user.", IP: ".$this->getUserIP()." ".$action." ".$state."\r\n";
		$fpe = fopen($this->logfile,'a+');
		fwrite($fpe,$message);
		fclose($fpe);
	}
}
?>