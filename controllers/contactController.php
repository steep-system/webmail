<?php
require_once(APP_PATH.'/controllers/common.php');
require_once(APP_PATH.'/models/contact/UserContact.php');
require_once(APP_PATH.'/models/contact/PublicContact.php');
require_once(APP_PATH.'/models/Setting.php');

class contactController extends Common
{
	protected $sinfo;
	protected $ucontact;
	protected $pcontact;
	protected $path;
	protected $set;

	public function __construct(){
		parent::init();
		$this->sinfo = $this->getSession($_REQUEST['ajax']);
		$this->path = $this->sinfo['maildir']."/";
		$this->ucontact = new WebMail_Model_UserContact();
		$this->ucontact->setpath($this->path.'contact.dat');
		$this->pcontact = new WebMail_Model_PublicContact();

		$this->ugroup = new WebMail_Model_UserContact();
		$this->ugroup->setpath($this->path.'group.dat');
		$this->set = new WebMail_Model_Setting();
		$this->set->setFile($this->sinfo['maildir']."/");
	}

	//编辑联系人信息页面
	public function editAction(){
		$group = $this->ugroup->getAllRecords('id');
		$optgroup = '';
		$disable = '';

		if(!empty($_REQUEST['id'])){
			$udata = $this->ucontact->getOneRecord('',$_REQUEST['id'],'id');
			$disable = 'disabled';
		}elseif (!empty($_REQUEST['param'])){
			$param = json_decode(base64_decode($_REQUEST['param']),true);
			$udata['realname'] = $param['name'];
			$udata['email'] = $param['mail'];
		}

		for ($i=1;$i<=count($group['data']);$i++){
			$selected = '';
			if((!empty($_REQUEST['id'])&&($udata['group']==$group['data'][$i]['id'])))$selected = 'selected';
			$optgroup.='<option value="'.$group['data'][$i]['id'].'" '.$selected.'>'.$group['data'][$i]['name'].'</option>';
		}
		require_once(APP_PATH.'/views/contact/useredit.html');
	}

	//查看联系人信息
	public function showcontactAction(){
		if(!empty($_REQUEST['id'])){
			if($_REQUEST['type']){
				$udata = $this->pcontact->getOneRecord($_REQUEST['id']);
			}else{
				$udata = $this->ucontact->getOneRecord('',$_REQUEST['id'],'id');
			}
		}
		require_once(APP_PATH.'/views/contact/showuser.html');
	}

	//联系人列表页
	public function listAction(){
		$tableHeight = ($_COOKIE['CLIENT_Y_SCREEN']-70);
		$tdHeight = ($_COOKIE['CLIENT_Y_SCREEN']-178);
		$divTop = ($_COOKIE['CLIENT_Y_SCREEN']-109);

		$page = 1;
		if($_REQUEST['page']){
			$page = $_REQUEST['page'];
		}
		$groups = $this->ugroup->getAllRecords('id');
		$groups = $groups['data'];
		$optgroup = '';
		for ($i=1;$i<=count($groups);$i++){
			$optgroup.="<option value='".$groups[$i]['id']."'>".$groups[$i]['name']."</option>";
		}

		if(empty($_REQUEST['type'])){
			if(empty($_COOKIE['SET_CONTACTSHOW'])){
				$_REQUEST['type'] = 1;
			}else{
				$_REQUEST['type'] = $_COOKIE['SET_CONTACTSHOW'];
			}
		}

		if($_REQUEST['type']==2){
			require_once(APP_PATH.'/views/contact/userblock.html');
			$this->set->update('contactshow',2);
		}elseif($_REQUEST['type']==1){
			$this->set->update('contactshow',1);
			require_once(APP_PATH.'/views/contact/userlist.html');
		}
	}

	//保存联系人信息
	public function saveuserAction(){
		$data = json_decode($_REQUEST['data'],true);
		$data['updatetime'] = time();
		if($data['id']){
			$id = $data['id'];
			$oper = $this->ucontact->updateRecord($data,$id);
		}else{
			$oper = $this->ucontact->addRecord($data,'email');
		}
		echo $oper;
	}

	//编辑联系人组信息页面
	public function editgroupAction(){
		require_once(APP_PATH.'/views/contact/groupedit.html');
	}

	//保存联系人组信息
	public function savegroupAction(){
		$oper = 0;
		if(!empty($_REQUEST['gname'])){
			if(!empty($_REQUEST['gid'])){
				$oper = $this->ugroup->updateRecord(array('name'=>$_REQUEST['gname'],'id'=>$_REQUEST['gid']),$id);
			}else{
				$oper = $this->ugroup->addRecord(array('name'=>$_REQUEST['gname']),'name');
			}
		}
		echo $oper;
	}

	//取得联系人列表数据
	public function getuserlistAction(){
		$ctype = $_REQUEST['ctype'];
		if($_REQUEST['class']==1){
			//公共联系人
			if($ctype=='root'){
				$data = $this->pcontact->getData($this->sinfo['domain']);
			}else{
				$data = $this->pcontact->getData($this->sinfo['domain'],array('key'=>$ctype,'val'=>$_REQUEST['id']));
			}
		}else{
			//私有联系人
			if($ctype=='root'){
				$data = $this->ucontact->getData();
			}else{
				$data = $this->ucontact->getData($_REQUEST['id']);
			}
		}
		echo $data;
	}

	//将联系人添加到组
	public function addtogroupAction(){
		if(!empty($_REQUEST['cid'])){
			$mark = 1;
			$cid = substr($_REQUEST['cid'],0,-1);
			$cid = explode(",",$cid);
			for ($i=0;$i<count($cid);$i++){
				if(!$this->ucontact->updateRecord(array('group'=>$_REQUEST['gid']),$cid[$i])){
					$mark = 0;
					break;
				}
			}
			echo $mark;
		}
	}

	//删除联系人
	public function delcontactAction(){
		if(!empty($_REQUEST['cid'])){
			$cid = substr($_REQUEST['cid'],0,-1);
			$cid = explode(",",$cid);
			echo $this->ucontact->delRecords($cid);
		}
	}

	//删除组
	public function delgroupAction(){
		if(!empty($_REQUEST['gid'])){
			$gid = array($_REQUEST['gid']);
			if($this->ucontact->clearUserGroup($_REQUEST['gid'])){
				echo $this->ugroup->delRecords($gid);
			}
		}
	}

	//导出联系人
	public function exportAction(){
		$row = $this->ucontact->getAllRecords();
		$prow = $this->pcontact->getAllRecords($this->sinfo['domain']);

		$outcode = array('zh'=>'gb2312','cn'=>'big5','jp'=>'iso-2022-jp','en'=>'us-ascii');
		if(($_COOKIE['SET_LANG']=='cn')||($_COOKIE['SET_LANG']=='zh')||$_COOKIE['SET_LANG']=='jp'){
			$tarcode = $outcode[$_COOKIE['SET_LANG']];
			$fname = iconv("utf-8",$tarcode,LANG_CONTACT_C0012).".csv";
		}else{
			$tarcode = "us-ascii";
			$fname = LANG_CONTACT_C0012.".csv";
		}

		header ("Content-Type: text/plain; charset=");
		header ("Content-Disposition: attachment; filename=$fname" );

		$csv_content = LANG_CONTACT_C0017.",".LANG_CONTACT_C0018.",".LANG_CONTACT_C0019.",".LANG_CONTACT_C0020.",".LANG_CONTACT_C0021.",".LANG_CONTACT_C0022.",".LANG_CONTACT_C0023.",".LANG_CONTACT_C0024.",".LANG_CONTACT_C0025."\r\n";
		$csv_content = iconv("utf-8", $tarcode."//IGNORE", $csv_content);
		for ($i=1;$i<=count($row['data']);$i++){
			$line_content = $row['data'][$i]['realname'].",".$row['data'][$i]['email'].",".$row['data'][$i]['cell'].",".$row['data'][$i]['tel'].",".$row['data'][$i]['nickname'].",".$row['data'][$i]['birthday'].",".$row['data'][$i]['address'].",".$row['data'][$i]['company'].",".$row['data'][$i]['memo']."\r\n";
			$line_content = iconv("utf-8", $tarcode."//IGNORE", $line_content);
			$csv_content .= $line_content;
		}
		for ($i=0;$i<=count($prow);$i++){
			$line_content = $prow[$i]['realname'].",".$prow[$i]['email'].",".$prow[$i]['cell'].",".$prow[$i]['tel'].",".$prow[$i]['nickname'].",".$prow[$i]['birthday'].",".$prow[$i]['address'].",".$prow[$i]['company'].",".$prow[$i]['memo']."\r\n";
			$line_content = iconv("utf-8", $tarcode."//IGNORE", $line_content);
			$csv_content .= $line_content;
		}
		echo $csv_content;
	}

	//导入联系人显示页
	public function importAction(){
		require_once(APP_PATH.'/views/contact/import.html');
	}

	public function doimportAction(){
		$csv = $_FILES['importfile'];
		echo $this->ucontact->importContact($csv,$this->path);
	}

	//联系人缓存
	public function contactcacheAction(){
		$puser = $this->pcontact->getAllCacheRecords($this->sinfo['domain']);
		$cuser = $this->ucontact->getAllCacheRecords();
		if(strlen($cuser)){
			if(strlen($puser)){
				$cache = "var emails=[".$puser.",".$cuser."];";
			}else{
				$cache =  "var emails=[".$cuser."];";
			}
		}else{
			$cache =  "var emails=[".$puser."];";
		}
		echo $cache;
	}

	public function treeAction(){
		if($_REQUEST['showuser']){
			$ptree = $this->pcontact->getTree($this->sinfo['domain'],0);
			$utree = $this->ugroup->getTree(0,$this->ucontact);
		}else{
			if($this->pcontact->getPcontactCnt($this->sinfo['domain'])<=1000){
				$ptree = $this->pcontact->getTree($this->sinfo['domain']);
			}
			$utree = $this->ugroup->getTree(1,$this->ucontact);
		}
		if(empty($ptree)){
			$strtree = '[{"data":"'.LANG_CONTACT_C0012.'","attributes":{"ctype":"root","list":"2","rel":"root","name":"'.LANG_CONTACT_C0012.'"},children:['.$utree.']}]';
		}else{
			//$strtree = str_replace(" ","",$strtree);
			$strtree = '[{"data":"'.LANG_CONTACT_C0011.'","attributes":{"ctype":"root","list":"1","rel":"root","name":"'.LANG_CONTACT_C0011.'"},children:'.$ptree.'},{"data":"'.LANG_CONTACT_C0012.'","attributes":{"ctype":"root","list":"2","rel":"root","name":"'.LANG_CONTACT_C0012.'"},children:['.$utree.']}]';
		}
		echo $strtree;
	}
}
