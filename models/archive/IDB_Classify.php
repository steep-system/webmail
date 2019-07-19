<?php

define ("SOCKET_TIMEOUT",     60);

class IDB_Classify
{
	protected $archiver;

	
	function __construct($ini_array){
		$this->archiver = array();
		foreach ($ini_array as $k=>$v){
			$tmp_archiver['path'] = $k;
			$tmp = explode(':', $v);
			$tmp_archiver['host'] = $tmp[0];
			if (count($tmp) < 2) {
				$tmp_archiver['port'] = 5556;
			} else {
				$tmp_archiver['port'] = $tmp[1];
			}
			array_push($this->archiver, $tmp_archiver);
		}
	}
	
	function getprefix($server_id) {
		if ($server_id >= count($this->archiver)) {
			return NULL;
		}
		return $this->archiver[$server_id]['path']; 
	}
	
	
	function execute($host, $port, $cmd,$blist){
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

		$length = 1024*4096;
		
		$timeout = SOCKET_TIMEOUT;
		
		if (!@socket_connect($socket, $host, $port)) {
			return false;
		}
		
		if(@socket_select($read,$write=NULL,$except=NULL,$timeout)<0){
			socket_close($socket);
			return false;
		}
		
		$out = socket_read($socket, $length);
		if (0 != strcasecmp($out, "OK\r\n")) {
			socket_close($socket);
			return false;
		}
		
		
		$cmd .= "\r\n";
		if (false == socket_write($socket, $cmd)) {
			socket_close($socket);
			return false;
		}
			
		$read = array($socket);
				
		if(@socket_select($read,$write=NULL,$except=NULL,$timeout)<0){
			socket_close($socket);
			return false;
		}
		
		if (false == $blist) {
			$out = socket_read($socket, $length);
			if (0 == strncasecmp($out, "FALSE", 5)) {
				socket_close($socket);
				return false;
			}
		} else {
			$cnt = 0;
			$tmp_out = socket_read($socket, $length);
			if(substr($tmp_out,0,4)=="TRUE"){
				$lines = substr($tmp_out,5,strpos(substr($tmp_out,5,strlen($tmp_out)),"\r\n"));
				$cnt = count(explode("\r\n",$tmp_out))-2;

				$out = $tmp_out;
				while($cnt<$lines){
					$tmp_out = socket_read($socket, $length);
					$cnt = (count(explode("\r\n",$tmp_out))-1) + $cnt;
					if((substr($out,-1,1)=="\r")&&(substr($tmp_out,0,1)=="\n")){
						$cnt+=1;
					}
					$out.= $tmp_out;
				}
			}else{
				socket_close($socket);
				return false;
			}
		}
		socket_close($socket);
		return $out;
	}

	
	function search($username, $sender, $rcpt, $from, $to, $cc, $rcv1, $rcv2, $priority, $subject, $content, $attachment){
		$data = array();
		$cmd = "A-SRCH gb2312 UNIT " . base64_encode($username);
		if ($sender) {
			$cmd .= " SENDER " . base64_encode($sender);
		}
		if ($rcpt) {
			$cmd .= " RCPT " . base64_encode($rcpt);
		}
		if ($from) {
			$cmd .= " FROM " . base64_encode($from);
		}
		if ($to) {
			$cmd .= " TO " . base64_encode($to);
		}
		if ($cc) {
			$cmd .= " CC " . base64_encode($cc);
		}
		
		if ($rcv1 && $rcv2) {
			$cmd .= " RTIME " . $rcv1 . " " . $rcv2;
		} else if ($rcv1 || $rcv2) {
			if ($rcv1) {
				$cmd .= " RTIME GE " . $rcv1;
			} else {
				$cmd .= " RTIME LE " . $rcv2;
			}
		}
		
		if ($priority) {
			$cmd .= " PRIORITY " . $priority;
		}
		if ($subject) {
			$cmd .= " SUBJECT " . base64_encode($subject);
		}
		if ($content) {
			$cmd .= " CONTENT " . base64_encode($content);
		}
		if ($attachment) {
			$cmd .= " FILENAME " . base64_encode($attachment);
		}
		
		$cmd .= "\r\n";
		
		for ($i=0; $i<count($this->archiver); $i++) {
			$res = $this->execute($this->archiver[$i]['host'], $this->archiver[$i]['port'], $cmd, true);
			if (false == $res) {
				continue;
			}
					
			$tmp = explode("\r\n",$res);
			for($j=1;$j<(count($tmp)-1);$j++){
				$tmp_item[0] = $i;
				$tmp_item[1] = $tmp[$j];
				array_push($data, $tmp_item);
			}
		}
		return array('state'=>1,'counts'=>count($data),'data'=>$data);
			
	}

	
	function match($server_id,$mail_id){
		if ($server_id >= count($this->archiver)) {
			return array('state'=>0,'error'=>1);
		}
		$cmd = "A-MTCH " . $mail_id . "\r\n";
		$res = $this->execute($this->archiver[$server_id]['host'], $this->archiver[$server_id]['port'], $cmd, false);
		if (false == $res) {
			return array('state'=>0,'error'=>2);
		}else{
			return array('state'=>1,'data'=>substr($res, 5));
		}
	}

}
