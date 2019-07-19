<?php
/**
 * 定义 JsonOper 类
 *
 * @copyright 
 * @author Rick Jin
 * @package pandora
 * @version 1.0
 */
class JsonOper
{
	protected $datafile;
	protected $data;

	/**
     * 构造函数
     *
     * @param string $file json文件路径
     * @return void
     */
	function __construct($file){
		$this->datafile = $file;
		$this->data = json_decode(file_get_contents($this->datafile),true);
	}

	/**
     * 添加新字段及对应值
     *
     * @param string $key 字段名
     * @param string $val 字段值
     * @param string $type 字段值类型
     * @return bool
     */
	function add($key,$val,$type=""){
		if(!array_key_exists($key,$this->data)){
			if($type=="base64"){
				$val = $this->encodeVal($val,$type);
			}
			$this->data[$key] = $val;
		}

		if(array_key_exists($key,$this->data)){
			return true;
		}else{
			return false;
		}
	}

	/**
     * 清空字段值
     *
     * @param string $key 字段名
     * @return bool
     */
	function delete($key){
		if(array_key_exists($key,$this->data)){
			$this->data[$key] = "";
		}

		if((array_key_exists($key,$this->data))&&($this->data[$key]=="")){
			return true;
		}else{
			return false;
		}
	}

	/**
     * 更新字段值
     *
     * @param string $key 字段名
     * @param string $val 字段值
     * @param string $type 字段值类型
     * @return bool
     */
	function update($key,$val,$type=""){
		if(array_key_exists($key,$this->data)){
			if($type=="base64"){
				$val = $this->encodeVal($val,$type);
			}
			$this->data[$key] = $val;
		}

		if(array_key_exists($key,$this->data)){
			return true;
		}else{
			return false;
		}
	}

	/**
     * 移除字段
     *
     * @param string $key 字段名
     * @return bool
     */
	function remove($key){
		if(array_key_exists($key,$this->data)){
			unset($this->data[$key]);
		}

		if(!array_key_exists($key,$this->data)){
			return true;
		}else{
			return false;
		}
	}

	/**
     * 取得一个字段值
     *
     * @param string $key 字段名
     * @param string $type 字段值类型
     * @return string
     */
	function getOneRecord($key,$type=""){
		$val = $this->data[$key];
		if($type=="base64"){
			$val = $this->decodeVal($val,$type);
		}else{
			if($this->isBase64($val)){
				$val = $this->decodeVal($val,'base64');
			}
		}
		return $val;
	}

	/**
     * 取得所有字段值
     *
     * @return array
     */
	function getAllRecords(){
		foreach ($this->data as $k=>$v){
			if($this->isBase64($v)){
				$this->data[$k] = $this->decodeVal($v,'base64');
			}
		}
		return $this->data;
	}

	/**
     * 保存字段更新
     *
     * @return bool
     */
	function save($basic){
		$mark = 1;
		foreach ($basic as $k=>$v){
			if(!array_key_exists($k,$this->data)){
				$mark = 0;
				break;
			}
		}

		if($mark){
			$data = json_encode($this->data);
			if($data){
				return file_put_contents($this->datafile,$data);
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	/**
     * 检查字段完整
     *
     * @param array $basic 参照字段数据集
     * @return void
     */
	function check($basic){
		$mark = 0;
		foreach ($basic as $k=>$v){
			if(!array_key_exists($k,$this->data)){
				$this->data[$k] = $v;
				$mark++;
			}
		}
		if($mark>0){
			$this->save();
		}
	}

	/**
     * 对字段值进行编码
     *
     * @param string $val 字段值
     * @param string $type 字段值类型
     * @return bool
     */
	function encodeVal($val,$type){
		if($type=="base64"){
			return base64_encode($val);
		}
	}

	/**
     * 对字段值进行解码
     *
     * @param string $val 字段值
     * @param string $type 字段值类型
     * @return bool
     */
	function decodeVal($val,$type){
		if($type=="base64"){
			return base64_decode($val);
		}
	}

	/**
     * 判断变量是否为base64不编码
     *
     * @param string $string 字符串
     * @return bool
     */
	function isBase64($string) {
		$strlen=strlen($string);
		if($strlen%4==0){
			$strarray=unpack("C*",$string);
			$base64str="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
			$base64array=unpack("C*",$base64str);
			$array=array_diff($strarray,$base64array);
			if(count($array)){return false;}else{return true;}
		}else{
			return false;
		}
	}
}