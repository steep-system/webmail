<?php
require_once(APP_PATH."/models/Base.php");
require_once(APP_PATH."/models/Socket.php");

/**
 * 定义 WebMail_Model_PublicContact 类
 *
 * @copyright 
 * @author Rick Jin
 * @package pandora
 * @version 1.0
 */
class WebMail_Model_PublicContact extends WebMail_Model_Base
{
	protected $_db;
	protected $_path;

	function __construct(){
		$param = array('user'=>PANDORA_DATABASE_USER,'password'=>PANDORA_DATABASE_PASSWORD,'host'=>PANDORA_DATABASE_HOST,'dbname'=>PANDORA_DATABASE_DBNAME);
		$this->_db = new Mysql($param);
	}
	
	function getPcontactCnt($domain){
		return $this->_db->total("select * from users where domain_id=$domain");
	}

	function getTree($domain,$showuser=1){
		$strdata.='[';
		$groups = $this->_db->select("select * from groups where domain_id=$domain");
		for($i=0;$i<count($groups);$i++){
			$classes = $this->_db->select("select c.* from classes as c left join hierarchy as h on c.id=h.child_id where c.group_id=".$groups[$i]['id']." and h.class_id=0");
			$strdata.='{"data":"'.$groups[$i]['title'].'","attributes":{"ctype":"group","id":"'.$groups[$i]['id'].'","name":"'.$groups[$i]['title'].'","list":"1","rel":"group"}';
			if(count($classes)>0){
				$strdata.=",children:[";
				for ($j=0;$j<count($classes);$j++){
					$strdata.='{"data":"'.$classes[$j]['classname'].'","attributes":{"ctype":"class","id":"'.$classes[$j]['id'].'","name":"'.$classes[$j]['classname'].'","list":"1","rel":"class"}';
					$classnum = $this->_db->total("select child_id from hierarchy where class_id=".$classes[$j]['id']);
					if($classnum>0){
						$strdata.=",children:[";
						$strdata.=$this->getClassTree($classes[$j]['id'],$showuser);
						if($showuser){
							$strdata.=$this->getClassUserTree($classes[$j]['id']);
						}
						$strdata.="]";
					}else{
						if($showuser){
							$strdata.=",children:[";
							$strdata.=$this->getClassUserTree($classes[$j]['id']);
							$strdata.="]";
						}
					}
					$strdata.='},';
				}
				if($showuser){
					$strdata.='{"data":"'.LANG_CONTACT_C0009.'","attributes":{"ctype":"class","id":"0","name":"'.LANG_CONTACT_C0009.'","list":"1","rel":"class"}';
					$strdata.=",children:[";
					$strdata.=$this->getGroupUserTree($groups[$i]['id']);
					$strdata.="]}";
				}
				$strdata.="]";
			}else{
				if($showuser){
					$strdata.=",children:[";
					$strdata.=$this->getGroupUserTree($groups[$i]['id']);
					$strdata.="]";
				}
			}
			$strdata.='},';
		}
		if($showuser){
			$strdata.='{"data":"'.LANG_CONTACT_C0010.'","attributes":{"ctype":"group","id":"0","name":"'.LANG_CONTACT_C0010.'","list":"1","rel":"group"}';
			$strdata.=",children:[";
			$strdata.=$this->getDomainUserTree($domain);
			$strdata.="]}";
		}
		$strdata.=']';

		return $strdata;
	}

	function getClassTree($cid,$showuser){
		$subclasses = $this->_db->select("select c.* from hierarchy as h left join classes as c on c.id=h.child_id where h.class_id=$cid");
		for ($i=0;$i<count($subclasses);$i++){
			$strdata.='{"data":"'.$subclasses[$i]['classname'].'","attributes":{"ctype":"class","id":"'.$subclasses[$i]['id'].'","name":"'.$subclasses[$i]['classname'].'","list":"1","rel":"class"}';
			$classnum = $this->_db->total("select * from hierarchy where class_id=".$subclasses[$i]['id']);
			if($classnum>0){
				$strdata.=",children:[";
				$strdata.=$this->getClassTree($subclasses[$i]['id'],$showuser);
				if($showuser){
					$strdata.=$this->getClassUserTree($subclasses[$i]['id']);
				}
				$strdata.= "]";
			}else{
				if($showuser){
					$strdata.=",children:[";
					$strdata.=$this->getClassUserTree($subclasses[$i]['id']);
					$strdata.="]";
				}
			}
			$strdata.='},';
		}
		return $strdata;
	}

	function getGroupUserTree($gid){
		$strdata = '';
		$user = $this->_db->select("select u.id,u.username as email,u.real_name as realname,u.privilege_bits as privilege from users as u where u.group_id=".$gid." and address_type=0");
		if(count($user)>0){
			for ($k=0;$k<count($user);$k++){
				if($this->chkRights($user[$k]['privilege'])){
					$strdata.='{"data":"'.addslashes($user[$k]['realname']).'","attributes":{"ctype":"user","id":"'.$user[$k]['id'].'","name":"'.addslashes($user[$k]['realname']).'","email":"'.addslashes($user[$k]['email']).'","rel":"user"}}';
					if($k<(count($user)-1))$strdata.=",";
				}
			}
		}
		return $strdata;
	}

	function getDomainUserTree($id){
		$strdata = '';
		$user = $this->_db->select("select u.id,u.username as email,u.real_name as realname,u.privilege_bits as privilege from users as u where u.group_id=0 and u.domain_id=".$id." and address_type=0");
		if(count($user)>0){
			for ($k=0;$k<count($user);$k++){
				if($this->chkRights($user[$k]['privilege'])){
					$strdata.='{"data":"'.addslashes($user[$k]['realname']).'","attributes":{"ctype":"user","id":"'.$user[$k]['id'].'","name":"'.addslashes($user[$k]['realname']).'","email":"'.addslashes($user[$k]['email']).'","rel":"user"}}';
					if($k<(count($user)-1))$strdata.=",";
				}
			}
		}
		return $strdata;
	}

	function getClassUserTree($cid){
		$strdata = '';
		$subuser = $this->_db->select("select u.id,u.username as email,u.real_name as realname,u.privilege_bits as privilege from members as m left join users as u on u.username=m.username where m.class_id=".$cid." and address_type=0");
		if(count($subuser)>0){
			for ($i=0;$i<count($subuser);$i++){
				if($this->chkRights($subuser[$i]['privilege'])){
					$strdata.='{"data":"'.addslashes($subuser[$i]['realname']).'","attributes":{"ctype":"user","id":"'.$subuser[$i]['id'].'","name":"'.addslashes($subuser[$i]['realname']).'","email":"'.addslashes($subuser[$i]['email']).'","rel":"user"}}';
					if($k<(count($subuser)-1))$strdata.=",";
				}
			}
		}
		return $strdata;
	}

	function getData($domain,$condition='',$order='id',$sort='SORT_DESC'){
		if($sort=='SORT_DESC'){
			$sort = "desc";
		}else{
			$sort = "asc";
		}
		if(empty($condition)){
			$records = $this->_db->select("select u.id,u.username as email,u.real_name as realname,u.cell,u.tel,u.nickname,u.homeaddress as address,UNIX_TIMESTAMP(u.create_day) as updatetime,u.memo,u.privilege_bits as privilege,g.title as company from users as u left join groups as g on u.group_id=g.id where u.domain_id=".$domain." and u.address_type=0 order by ".$order);
		}else{
			if($condition['key']=='group'){
				$records = $this->_db->select("select u.id,u.username as email,u.real_name as realname,u.cell,u.tel,u.nickname,u.homeaddress as address,UNIX_TIMESTAMP(u.create_day) as updatetime,u.memo,u.privilege_bits as privilege,g.title as company from users as u left join groups as g on u.group_id=g.id where u.group_id=".$condition['val']." and u.address_type=0 order by ".$order);
			}elseif($condition['key']=='class'){
				//取得所有用户组
				$strclass = $this->getClassid($condition['val']);
				$strclass = substr($strclass,0,-1);
				$arrclass = explode(",",$strclass);
				$arrclass = array_unique($arrclass);
				$strclass = $condition['val'];
				foreach ($arrclass as $v){
					$strclass.=",".$v;
				}
				if ("," == substr($strclass,-1,1)) {
					$strclass = substr($strclass,0,-1);
				}
				$records = $this->_db->select("select u.id,u.username as email,u.real_name as realname,u.cell,u.tel,u.nickname,u.homeaddress as address,UNIX_TIMESTAMP(u.create_day) as updatetime,u.memo,u.privilege_bits as privilege,g.title as company from members as m left join users as u on m.username=u.username left join groups as g on u.group_id=g.id where u.address_type=0 and m.class_id in ($strclass) group by u.id order by ".$order);
			}
		}

		for ($i=0;$i<count($records);$i++){
			if(empty($records[$i]['realname'])){
				$records[$i]['fulladdress'] = $this->strToAscii($records[$i]['email']."&lt;".$records[$i]['email']."&gt;");
			}else{
				$records[$i]['fulladdress'] = $this->strToAscii($records[$i]['realname']."&lt;".$records[$i]['email']."&gt;");
			}
		}

		//不显示隐藏用户
		$n = 0;
		for ($i=0;$i<=count($records);$i++){
			if($this->chkRights($records[$i]['privilege'])){
				$recusers[$n] = $records[$i];
				$n++;
			}
		}

		if(is_array($records)){
			$strdata = '{"data":'.json_encode($recusers).',"total":'.count($recusers).'}';
		}else{
			$strdata = '{"data":"","total":'.$num.'}';
		}
		return $strdata;
	}

	function getClassid($cid,$strclass=""){
		$class = $this->_db->select("select child_id from hierarchy where class_id=$cid");
		for ($i=0;$i<count($class);$i++){
			$strclass.=$class[$i]['child_id'].",";
			if($this->_db->total("select * from hierarchy where class_id=".$class[$i]['child_id'])){
				$strclass.=$this->getClassid($class[$i]['child_id'],$strclass);
			}
		}
		return $strclass;
	}

	function chkRights($privilege){
		$privilege = decbin($privilege);
		$privilege = sprintf("%+024s",$privilege);
		$privilege = str_split($privilege);
		return $privilege[20];
	}

	function getOneRecord($id){
		$record = $this->_db->select("select u.id,u.username as email,u.real_name as realname,u.cell,u.tel,u.nickname,u.homeaddress as address,UNIX_TIMESTAMP(u.create_day) as updatetime,u.memo,u.privilege_bits as privilege from users as u where u.id=".$id );
		return $record[0];
	}

	function getAllCacheRecords($domain){
		$records = $this->_db->select("select username as email,real_name as name,privilege_bits as privilege from users where domain_id=".$domain." and address_type=0");
		$strdata = '';
		if(count($records)<=1000){
			for ($i=0;$i<count($records);$i++){
				//不显示隐藏用户
				if($this->chkRights($records[$i]['privilege'])){
					$tmp = $records[$i]['name'].'<'.$records[$i]['email'].'>';
					//$tmp = $records[$i]['name'].'&lt;'.$records[$i]['email'].'&gt;';
					//$tmp = '{name:"'.addslashes($records[$i]['name']).'", to:"'.addslashes($records[$i]['email']).'"}';
					$tmp = addslashes($tmp);
					$strdata.='"'.$tmp.'",';
					//$strdata.=$tmp.',';
				}
			}
			$strdata = substr($strdata,0,-1);
		}
		return $strdata;
	}
	
	function getAllRecords($domain){
		$records = $this->_db->select("select u.username as email,u.real_name as realname,u.cell,u.tel,u.nickname,u.homeaddress as address,g.title as memo from users as u left join groups as g on u.group_id=g.id where u.domain_id=".$domain." and u.address_type=0");
		return $records;
	}
}
