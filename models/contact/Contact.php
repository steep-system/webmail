<?php
require_once(APP_PATH."/models/Base.php");
require_once(APP_PATH."/models/Socket.php");

/**
 * 定义 WebMail_Model_Contact 类
 *
 * @copyright 
 * @author Rick Jin
 * @package pandora
 * @version 1.0
 */
class WebMail_Model_Contact extends WebMail_Model_Base
{
	protected $_lock;
	protected $_res;
	protected $_size = 2048;
	protected $_path;

	/**
     * 构造函数
     * @return WebMail_Model_Contact
     */
	function __construct(){
		parent::init();
		$this->_lock = new WebMail_Model_Socket(PANDORA_SOCKET_LOCK);
		$this->_res = strtoupper($_COOKIE['SESSION_MARK']."-mailbox");
	}

	/**
     * 设置索引文件路径
     *
     * @param string $path 用户根目录路径
     * @return void
     */
	function setpath($path){
		$this->_path = $path;
		if(!file_exists($this->_path)){
			$fpe = fopen($this->_path,'w');
			fclose($fpe);

			//设置默认联系人组
			if(strpos($path,'group.dat')&&(filesize($this->_path)<=0)){
				$randgroup = array(LANG_CONTACT_C0006,LANG_CONTACT_C0007,LANG_CONTACT_C0008);
				if($this->_lock->lock($this->_res)){
					$fpe = fopen($this->_path,'a');
					foreach ($randgroup as $rg){
						fwrite($fpe,sprintf("%-".$this->_size."s",json_encode(array('name'=>base64_encode($rg),'id'=>$this->setID()))));
					}
					fclose($fpe);
					$this->_lock->unlock();
				}
			}
		}
	}

	/**
     * 设置记录信息ID
     *
     * @return int
     */
	function setID(){
		list($usec, $sec) = explode(" ", microtime());
		return $sec."-".str_replace("0.","",$usec);
	}

	/**
     * 添加信息
     *
     * @param array $data 用户信息数据
     * @return bool
     */
	function addRecord($data,$key='id'){
		$oper = 0;
		if(!$this->checkRecord($data[$key],$key)){
			$data['id'] = $this->setID();
			foreach ($data as $k=>$v){
				if($k!='id'&&$k!='group'&&$k!='updatetime'){
					$data[$k] = base64_encode($v);
				}
			}
			$data = sprintf("%-".$this->_size."s",json_encode($data));
			if($this->_lock->lock($this->_res)){
				$fpe = fopen($this->_path,'a');
				if(fwrite($fpe,$data))$oper = 1;
				fclose($fpe);
				$this->_lock->unlock();
			}
		}else{
			$oper = 2;
		}
		return $oper;
	}

	/**
     * 删除信息记录
     *
     * @param array $arrcid 搜索数据集
     * @param string $key 关键字段
     * @return bool
     */
	function delRecords($arrcid,$key='id'){
		$oper = 0;
		$row = $this->getAllRecords($key);
		$num = count($row['data']);
		for ($i=0;$i<count($arrcid);$i++){
			$k = array_search($arrcid[$i],$row['key']);
			if($k){
				unset($row['data'][$k]);
			}
		}
		$strdata = '';
		for($i=1;$i<=$num;$i++){
			if(is_array($row['data'][$i])){
				$strdata.= sprintf("%-".$this->_size."s",json_encode($this->formatRecord($row['data'][$i])));
			}
		}
		if($this->_lock->lock($this->_res)){
			$oper = file_put_contents($this->_path,$strdata);
			$this->_lock->unlock();
		}
		return $oper;
	}

	/**
     * 取得所有信息记录
     *
     * @param string $key 关键字段
     * @return array
     */
	function getAllRecords($key='email'){
		$p = 0;
		$num = $this->getNum();
		$fpe = fopen($this->_path,"r");
		for ($i=1;$i<=$num;$i++){
			$offset = fseek($fpe,$p);
			$t = trim(fread($fpe,$this->_size));
			$data[$i] = json_decode($t,true);
			foreach ($data[$i] as $k=>$v){
				if($k!='id'&&$k!='group'&&$k!='updatetime'){
					$data[$i][$k] = base64_decode($v);
				}
			}
			$data[$i]['p'] = $p;
			$arrkey[$i] = $data[$i][$key];
			$p+=$this->_size;
		}
		fclose($fpe);
		$row = array('key'=>$arrkey,'data'=>$data);
		return $row;
	}

	/**
     * 取得单条信息记录
     *
     * @param int $p 偏移位置
     * @param string $search 查询数据
     * @param string $key 关键字段
     * @return array
     */
	function getOneRecord($p='',$search='',$key=''){
		if(!empty($p)){
			$fpe = fopen($this->_path,"r");
			$offset = fseek($fpe,$p);
			$t = trim(fread($fpe,$this->_size));
			$retdata = json_decode($t,true);
		}else{
			$row = $this->getAllRecords($key);
			$k = array_search($search,$row['key']);
			$retdata = $row['data'][$k];
		}
		return $retdata;
	}

	/**
     * 更新记录信息
     * 
     * @param array $data 需要更新的值
     * @param int $cid 目标记录id
     * @return int
     */
	function updateRecord($data,$cid){
		$oper = 0;
		$row = $this->getAllRecords('id');
		$k = array_search($cid,$row['key']);
		foreach ($data as $key=>$val){
			$row['data'][$k][$key] = $val;
		}
		$row['data'][$k]['updatetime']  = time();
		if($this->_lock->lock($this->_res)){
			$fpe = fopen($this->_path,'r+');
			$offset = fseek($fpe,$row['data'][$k]['p']);
			if(fwrite($fpe,sprintf("%-".$this->_size."s",json_encode($this->formatRecord($row['data'][$k])))))$oper = 1;
			fclose($fpe);
			$this->_lock->unlock();
		}
		return $oper;
	}

	/**
     * 取得信息记录条数
     *
     * @return int
     */
	function getNum(){
		return (filesize($this->_path)/$this->_size);
	}

	/**
     * 检查指定信息记录是否存在
     *
     * @param string $search 搜索数据
     * @param string $key 关键字段
     * @return bool
     */
	function checkRecord($search,$key){
		$row = $this->getAllRecords($key);
		return array_search($search,$row['key']);
	}

	function formatRecord($row){
		unset($row['p']);
		foreach ($row as $k=>$v){
			if($k!='id'&&$k!='group'&&$k!='updatetime'){
				$row[$k] = base64_encode($v);
			}
		}
		return $row;
	}
}