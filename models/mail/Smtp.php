<?php
require_once(APP_PATH.'/models/Log.php');

class WebMail_Model_Smtp{
	protected $host;
	protected $port = 25;
	protected $timeout = 30;
	public $connection = 0;
	protected $log;

	function __construct($server,$port=25){
		$this->host = $server;
		$this->port = $port;
		$this->log = new WebMail_Model_Log();
	}

	function open(){
		if (!$this->connection=fsockopen($this->host,$this->port,$err_no, $err_str, $this->timeout)){
			$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),'fail to connect '.$this->host.':'.$this->port,0);
			return false;
		}else{
			$resp=fgets($this->connection,256);
			if (substr($resp,0,1) != '2'){
				$resp = str_replace("\r\n",'',$resp);
				$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"smtp server response:\"".$resp."\" after connection",0);
			}else{
				return true;
			}
		}
	}

	function command($command,$return_code='2',&$err_str=''){
		if($this->connection==0){
			$err_str = "no connection alive";
			return false;
		}

		if (!fputs($this->connection,"$command \r\n"))
		{
			$err_str = "fail to send command \"".$command."\"";
			return false;
		}
		else
		{
			$resp=fgets($this->connection,256);
			if (substr($resp,0,1) == $return_code) {
				return true;
			}else{
				$resp = str_replace("\r\n",'',$resp);
				$err_str = $resp;
				return false;
			}
		}
	}

	function close()
	{
		if($this->connection!=0)
		{
			fclose($this->connection);
			$this->connection=0;
		}
	}

	function send($from,$to,$mime){
		if(!$this->open())return false;
		if(!$this->command("HELO $this->host",'2',$error_str)){
			$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"smtp server response:\"".$error_str."\" after command "."HELO $this->host",0);
			fputs($this->connection,"QUIT\r\n");
			return false;
		}
		if(!$this->command("MAIL FROM:<".$from.">",'2',$error_str)){
			$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"smtp server response:\"".$error_str."\" after command "."MAIL FROM:<".$from.">",0);
			fputs($this->connection,"QUIT\r\n");
			return false;
		}
		for ($i=0;$i<count($to);$i++){
			if(!$this->command("RCPT TO:<".$to[$i].">",'2',$error_str)){
				$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"smtp server response:\"".$error_str."\" after command "."RCPT TO:<".$to[$i].">",0);
				fputs($this->connection,"QUIT\r\n");
				return false;
			};
		}
		if(!$this->command("DATA",'3',$error_str)){
			$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"smtp server response:\"".$error_str."\" after command "."DATA",0);
			fputs($this->connection,"QUIT\r\n");
			return false;
		}
		if(!fputs($this->connection,$mime)||!fputs($this->connection,"\r\n.\r\n")){
			$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"timeout when sending mail content",0);
			fputs($this->connection,"QUIT\r\n");
			return false;
		};

		$resp=fgets($this->connection,65536);
		if (substr($resp,0,1)!="2"){
			$resp = str_replace("\r\n",'',$resp);
			$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),"smtp server response:\"".$resp."\" after sending mail content",0);
			fputs($this->connection,"QUIT\r\n");
			return false;
		}
		
		fputs($this->connection,"QUIT\r\n");
		$this->close();
		return true;
	}
}
?>
