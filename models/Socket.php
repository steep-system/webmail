<?php
require_once(APP_PATH.'/models/Log.php');

/**
 * 定义 WebMail_Model_Socket 类
 *
 * @copyright 
 * @author Rick Jin
 * @package webmail
 * @version 1.1
 */
class WebMail_Model_Socket
{
	protected $_socket;
	protected $_path;
	protected $_timeout = 60;	//超时，默认60秒
	protected $log;

	/**
     * 构造函数
     * @return WebMail_Model_Socket
     */
	function __construct($path){
		$this->_path = $path;
		$this->log = new WebMail_Model_Log();
	}

	/**
     * 创建连接
     *
     * @return bool
     */
	function create(){
		if(($socket = socket_create(AF_UNIX,SOCK_STREAM,0)) >= 0)
		{
			if (socket_connect($socket,$this->_path)) {
				return $socket;
			}
		}
	}

	/**
     * 发送指令
     *
     * @param string $cmd 指令
     * @param int $type 读取类型(0:普通读取;1:拼接读取)
     * @return string
     */
	function send($cmd,$type=0,$q=0){
		$length = 1024*1025;
		$socket = $this->create();
		if($socket){
			socket_write($socket, $cmd, strlen($cmd));
			$read = array($socket);
			$timeout = $this->_timeout;
			if($q)$timeout = 0;
			if(socket_select($read,$write=NULL,$except=NULL,$timeout)>0){
				if($type){
					$cnt = 0;
					$tmp_out = socket_read($socket, $length);
					if(substr($tmp_out,0,4)=="TRUE"){
						$lines = substr($tmp_out,5,strpos(substr($tmp_out,5,strlen($tmp_out)),"\r\n"));
						$cnt = count(explode("\r\n",$tmp_out))-2;

						$out = $tmp_out;
						while($cnt<$lines){
							if(socket_select($read,$write=NULL,$except=NULL,$timeout)<=0){
								socket_close($socket);
								//设置日志
								$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),$cmd ." socket time out",0);
								return 'FALSE';
							}
							$tmp_out = socket_read($socket, $length);
							if(empty($tmp_out)){
								$lasterror = socket_strerror(socket_last_error($socket));
								$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),$cmd ." socket Error: ".$lasterror,0);
								return 'FALSE';
							}
							$cnt = (count(explode("\r\n",$tmp_out))-1) + $cnt;
							if((substr($out,-1,1)=="\r")&&(substr($tmp_out,0,1)=="\n")){
								$cnt+=1;
							}
							$out.= $tmp_out;
						}
					}else{
						$out = $tmp_out;
					}
				}else{
					$out = socket_read($socket, $length);
					if(empty($out)){
						$lasterror = socket_strerror(socket_last_error($socket));
						$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),$cmd ." socket Error: ".$lasterror,0);
					}
				}
				$qcmd = "QUIT\r\n";
				socket_write($socket,$qcmd,strlen($qcmd));
				socket_close($socket);
				return $out;
			}else{
				socket_close($socket);
				//设置日志
				$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),$cmd ." socket time out",0);
				return 'FALSE';
			}
		}else{
			//设置日志
			$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),$cmd ." socket create or connect failed",0);
			return 'FALSE';
		}
	}

	/**
     * 锁定资源
     *
     * @param string $string  资源
     * @return string
     */
	function lock($rs){
		$cmd = "LOCK ".$rs."\r\n";
		$res = $this->send($cmd);
		return $res;
	}

	/**
     * 解除锁定
     *	
     * @return string
     */
	function unlock(){
		$res = $this->send("UNLOCK\r\n");
		return $res;
	}

	/**
     * 跳出
     *
     * @return string
     */
	function quit(){
		$res = $this->send("QUIT\r\n",0,1);
		return $res;
	}

	/**
     * 请求session
     *
     * @param string $mark	资源标识符
     * @return array
     */
	function getSession($mark){
		$error = array(0=>"通讯错误，代理失去后端session服务器连接",1=>"session服务器中hash表容量已满",2=>"单个用户分配session超过限制",3=>"session服务器内存耗尽");
		$res = $this->send("ALLOC ".$mark."\r\n");
		if($res){
			$res = explode(" ",$res);
			for ($i=0;$i<count($res);$i++){
				$res[$i] = trim($res[$i]);
			}
			if($res[0]=='FALSE'){
				$res[0] = 0;
				if(!($res[1])){
					$res[1] = $error[0];
				}else{
					$res[1] = $error[$res[1]];
				}
			}else{
				$res[0] = 1;
			}
		}else{
			$res = array(0=>0,1=>1);
		}
		return $res;

	}

	function setSession($mark,$key,$val){
		$res = $this->send("SET ".$mark." ".$key." ".json_encode($val)."\r\n");
		return $res;
	}

	/**
     * 检查session
     *
     * @param string $mark	资源标识符
     * @param string $key	session值
     * @return bool
     */
	function checkSession($mark,$key){
		$res = $this->send("CHECK ".$mark." ".$key."\r\n");
		if(trim($res) == "TRUE"){
			return 1;
		}else{
			return 0;
		}
	}

	function querySession($mark,$key){
		$res = $this->send("QUERY ".$mark." ".$key."\r\n");
		if($res=='FALSE'){
			return 0;
		}else{
			$tmp = substr($res,5,strlen($res));
			return json_decode(trim($tmp),true);
		}
	}

	/**
     * 释放session
     *
     * @param string $mark	资源标识符
     * @param string $key	session值
     * @return bool
     */
	function freeSession($mark,$key){
		$res = $this->send("FREE ".$mark." ".$key."\r\n");
		return $res;
	}

	function microtime(){
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
}