<?php
require_once(APP_PATH."/models/contact/Contact.php");

/**
 * 定义 WebMail_Model_UserContact 类
 *
 * @copyright 
 * @author Rick Jin
 * @package pandora
 * @version 1.0
 */
class WebMail_Model_UserContact extends WebMail_Model_Contact
{
	function getData($gid,$order='id',$sort='SORT_DESC'){
		$row = $this->getAllRecords('group');
		$data = $row['data'];
		for ($i=1;$i<=count($data);$i++){
			if(empty($data[$i]['realname'])){
				$data[$i]['fulladdress'] = $this->strToAscii($data[$i]['email']."&lt;".$data[$i]['email']."&gt;");
			}else{
				$data[$i]['fulladdress'] = $this->strToAscii($data[$i]['realname']."&lt;".$data[$i]['email']."&gt;");
			}
		}
		if($gid!=''){
			$n = 1;
			for ($i=1;$i<=count($data);$i++){
				if($gid==$row['key'][$i]){
					$ndata[$n] = $data[$i];
					$n++;
				}
			}
			$data = $ndata;
		}

		if(empty($data[1]['id'])){
			$num = 0;
		}else{
			$num = count($data);
		}
		fclose($fpe);
		$data = $this->sysSortArray($data,$order,$sort);
		$strdata = '';
		for ($i=0;$i<count($data);$i++){
			$strdata.=json_encode($data[$i]);
			if($i<(count($data)-1))$strdata.=",";
		}

		$strdata = '{"data":['.$strdata.'],"total":'.$num.'}';
		return $strdata;
	}

	function getTree($showuser,$obj){
		$strdata = '';
		$row = $this->getAllRecords('id');
		$records = $row['data'];
		for ($i=1;$i<=count($records);$i++){
			$strdata.='{"data":"'.$records[$i]['name'].'","attributes":{"ctype":"class","id":"'.$records[$i]['id'].'","name":"'.$records[$i]['name'].'","list":"2","rel":"class"}';
			if($showuser){
				if($this->isHaveUsers($obj,$records[$i]['id'])){
					$strsub = $this->getUserTree($obj,$records[$i]['id']);
					if(!empty($strsub)){
						$strdata.=",children:[";
						$strdata.=$strsub;
						$strdata.="]";
					}
				}
			}
			$strdata.='},';
		}
		$strdata.='{"data":"'.LANG_CONTACT_C0037.'","attributes":{"ctype":"class","id":"0","name":"'.LANG_CONTACT_C0037.'","list":"2","rel":"class"}';
		if($showuser){
			$strdata.=",children:[";
			$strdata.=$this->getAllUser($obj);
			$strdata.="]";
		}
		$strdata.='}';
		//$strdata = substr($strdata,0,-1);
		return $strdata;
	}

	function getUserTree($obj,$gid){
		$strdata = '';
		$row = $obj->getAllRecords('group');
		for ($i=1;$i<=count($row['data']);$i++){
			if($gid==$row['data'][$i]['group']){
				if(!$this->isValidUtf8($row['data'][$i]['realname'])){
					$row['data'][$i]['realname'] = "";
				}
				$strdata.='{"data":"'.addslashes($row['data'][$i]['realname']).'","attributes":{"ctype":"user","id":"'.$row['data'][$i]['id'].'","name":"'.addslashes($row['data'][$i]['realname']).'","email":"'.addslashes($row['data'][$i]['email']).'","list":"2","rel":"user"}},';
			}
		}
		$strdata = substr($strdata,0,-1);
		return $strdata;
	}

	function getAllUser($obj){
		$strdata = '';
		$row = $obj->getAllRecords('group');
		for ($i=1;$i<=count($row['data']);$i++){
			if(!$row['data'][$i]['group']){
				if(!$this->isValidUtf8($row['data'][$i]['realname'])){
					$row['data'][$i]['realname'] = "";
				}
				$strdata.='{"data":"'.addslashes($row['data'][$i]['realname']).'","attributes":{"ctype":"user","id":"'.$row['data'][$i]['id'].'","name":"'.addslashes($row['data'][$i]['realname']).'","email":"'.addslashes($row['data'][$i]['email']).'","list":"2","rel":"user"}},';
			}
		}
		return $strdata;
	}

	function isHaveUsers($obj,$gid){
		$row = $obj->getAllRecords('group');
		return array_search($gid,$row['key']);
	}

	function clearUserGroup($gid){
		$row = $this->getAllRecords('group');
		for ($i=1;$i<=count($row['data']);$i++){
			if($row['key'][$i]==$gid){
				$row['data'][$i]['group'] = 0;
			}
			$row['data'][$i] = $this->formatRecord($row['data'][$i]);
		}

		$strdata = '';
		for($i=1;$i<=count($row['data']);$i++){
			if(is_array($row['data'][$i])){
				$strdata.= sprintf("%-".$this->_size."s",json_encode($row['data'][$i]));
			}
		}

		if(!empty($strdata)){
			$oper = 0;
			if($this->_lock->lock($this->_res)){
				$oper = file_put_contents($this->_path,$strdata);
				$this->_lock->unlock();
			}
		}else{
			$oper = 1;
		}
		return $oper;
	}

	function getAllCacheRecords(){
		$strdata = '';
		$records = $this->getAllRecords();
		for ($i=1;$i<=count($records['data']);$i++){
			$records['data'][$i]['realname'] = str_replace("\r\n", "", $records['data'][$i]['realname']);
                        $records['data'][$i]['email'] = str_replace("\r\n", "", $records['data'][$i]['email']);
			$tmp = $records['data'][$i]['realname'].'<'.$records['data'][$i]['email'].'>';
			//$tmp = $records['data'][$i]['realname'].'&lt;'.$records['data'][$i]['email'].'&gt;';
			//$tmp = '{name:"'.addslashes($records['data'][$i]['realname']).'", to:"'.addslashes($records['data'][$i]['email']).'"}';
			$tmp = addslashes($tmp);
			$strdata.='"'.$tmp.'"';
			//$strdata.=$tmp.',';
			if($i<count($records['data']))$strdata.=",";
		}
		return $strdata;
	}

	/**
     * 导入vcard至私有联系人信息
     *
     * @param array $xls 上传文件数组
     * @param array $filepath 临时文件路径
     * @return array
     */
	function importContact($xls,$filepath){
		$newfile = $filepath."tmp/import.csv";
		if(move_uploaded_file($xls['tmp_name'],$newfile)){
			if(file_exists($newfile)){
				$data = file($newfile);
				$mark = array('realname','email','cell','tel','nickname','birthday','address','company','memo');
				for ($i=1;$i<=count($data);$i++){
					$subdata = explode(",",trim($data[$i]));
					for ($j=0;$j<count($subdata);$j++){
						$row[$i][$mark[$j]] = iconv('gb2312','utf-8',$subdata[$j]);
					}
					$row[$i]['updatetime'] = time();
				}

				$cnt = 0;
				for ($i=0;$i<count($row);$i++){
					$add = $this->addRecord($row[$i],'email');
					if($add==1)$cnt++;
				}
				return $cnt;
			}
		}
	}

	function compareContact($search,$domain){
		$row = $this->getAllRecords();
		$n = 0;
		for ($i=0;$i<count($search);$i++){
			$k = array_search($search[$i]['mail'],$row['key']);
			if(!$k){
				if(!strpos($search[$i]['mail'],$domain)){
					$crow[$n]['mail'] = $search[$i]['mail'];
					$crow[$n]['name'] = $search[$i]['name'];
					$n++;
				}
			}
		}
		return $crow;
	}

	function isValidUtf8($string)
	{
		$str_len = strlen($string);
		for($i=0;$i<$str_len;)
		{
			$str = ord($string[$i]);
			if($str>=0 && $str < 0x7f)
			{
				$i++;
				continue;
			}
			if($str< 0xc0 || $str>0xfd) return false;
			$count = $str>0xfc?5:$str>0xf8?4:$str>0xf0?3:$str>0xe0?2:1;
			if($i+$count > $str_len) return false;
			$i++;
			for($m=0;$m<$count;$m++)
			{
				if(ord($string[$i])<0x80 || ord($string[$i])>0xbf) return false;
				$i++;
			}
		}
		return true;
	}
}
