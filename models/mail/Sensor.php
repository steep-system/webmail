<?php
require_once(APP_PATH."/models/Socket.php");
/**
 * 定义 WebMail_Model_Sensor 类
 *
 * @copyright 
 * @author Rick Jin
 * @package webmail
 * @version 1.1
 */
class WebMail_Model_Sensor
{
	protected $_socket;
	
	function __construct()
	{
		$this->_socket = new WebMail_Model_Socket(PANDORA_SOCKET_SENSOR);
	}
	
	function set($username,$num){
		$cmd = "SET ".$username." ".$num."\r\n";
		$res = $this->_socket->send($cmd);
		$res = explode(" ",$res);
		return $res;
	}
	
	function get($username){
		$cmd = "GET ".$username."\r\n";
		$res = $this->_socket->send($cmd);
		$res = explode(" ",$res);
		return $res;
	}
	
	function add($username,$num){
		$cmd = "ADD ".$username." ".$num."\r\n";
		$res = $this->_socket->send($cmd);
		$res = explode(" ",$res);
		return $res;
	}
	
	function rem($username){
		$cmd = "REM ".$username."\r\n";
		$res = $this->_socket->send($cmd);
		return $res;
	}
	
	function close(){
		$this->_socket->close();
	}
}