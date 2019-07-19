<?php
require_once(APP_PATH.'/controllers/common.php');
require_once(APP_PATH.'/models/disk/File.php');
require_once(APP_PATH.'/models/Setting.php');

class diskController extends Common
{
	protected $file;
	protected $sinfo;
	protected $infomenu;
	protected $set;

	public function __construct(){
		parent::init();
		$this->sinfo = $this->getSession($_REQUEST['ajax']);
		$this->file = new WebMail_Model_File($this->sinfo['maildir']."/disk/");
		if(!empty($this->sinfo['privilege'])){
			$this->chkRights($this->sinfo['privilege'],'disk');
		}
		$this->createInfo();
		$this->set = new WebMail_Model_Setting();
		$this->set->setFile($this->sinfo['maildir']."/");
	}
	
	public function createInfo(){
		$info = array(1=>array('ico'=>'ico ico-webdiscs-mine','title'=>LANG_DISK_D0008,'link'=>'list'),
		              2=>array('ico'=>'ico ico-webdiscs-teamshare','title'=>LANG_DISK_D0014,'link'=>'group'),
		              3=>array('ico'=>'ico ico-webdiscs-area','title'=>LANG_DISK_D0015,'link'=>'domain'));
		for ($i=1;$i<=count($info);$i++){
			$tmpinfomenu='<li><b class="'.$info[$i]['ico'].'"></b><a href="'.$info[$i]['link'].'" class="on" style="text-decoration:none;"><span>'.$info[$i]['title'].'</span></a><div class="ln-thin ln-c-mid"><b class="ext1"></b></div></li>';
			if(($i==2)&&($this->sinfo['group']==0)){
				$tmpinfomenu = "";
			}
			$this->infomenu.=$tmpinfomenu;
		}
	}

	//文件列表
	public function getlistAction(){
		echo $this->file->listIndex($_REQUEST['folder'],$_REQUEST['page'],$_REQUEST['str'],$this->getPageAmount(1));
	}
	
	//文件列表
	public function getblocklistAction(){
		echo $this->file->listIndex($_REQUEST['folder'],$_REQUEST['page'],$_REQUEST['str'],$this->getPageAmount(2,$_REQUEST['folder']));
	}
	
	public function getpagesAction(){
		$num = $this->file->getIndexNum($this->sinfo['maildir']."/disk/".$_REQUEST['f']."index");
		if($_REQUEST['type']){
			echo ceil($num/$this->getPageAmount(2,$_REQUEST['f']));
		}else{
			echo ceil($num/$this->getPageAmount(1));
		}
	}

	//添加文件夹
	public function addfolderAction(){
		$oper = $this->file->createFolder($_REQUEST['dir'],$_REQUEST['fname']);
		if(!empty($oper['code']))$oper['tip'] = $oper['code'];
		echo json_encode($oper);
	}

	//删除文件
	public function delfileAction(){
		if(!empty($_REQUEST['files'])){
			echo $this->file->delFile($_REQUEST['files'],$_REQUEST['folder']);
		}
	}

	//文件重命名
	public function editfilenameAction(){
		if(!empty($_REQUEST['file'])&&!empty($_REQUEST['newfilename'])){
			if($this->file->checkFileExist($_REQUEST['newfilename'],$_REQUEST['folder'])){
				if($this->file->editFileInfo($_REQUEST['file'],array('tag'=>'name','val'=>$_REQUEST['newfilename']),$_REQUEST['folder'])){
					$this->file->editShareFile('group',$_REQUEST['file'],array('tag'=>'name','val'=>$_REQUEST['newfilename']));
					$this->file->editShareFile('domain',$_REQUEST['file'],array('tag'=>'name','val'=>$_REQUEST['newfilename']));
					echo '{"code":"","tip":"","state":1}';
				}
			}else{
				echo '{"code":"D1000","tip":"'.LANG_TIP_D1004.'","state":0}';;
			}
		}
	}

	//文件树
	public function filetreeAction(){
		$tree = $this->file->getFileTree($_REQUEST['f']);
		$tree = '{"data":"'.LANG_DISK_D0008.'","attributes":{"path":"/","folder":"/"},"state":"open",children:['.$tree.']}';
		echo "[".$tree."]";
	}
	
	//包含文件的文件树
	public function filestreeAction(){
		$tree = $this->file->getFileTree($_REQUEST['f'],1);
		$tree = '{"data":"'.LANG_DISK_D0008.'","attributes":{"path":"/","folder":"/","rel":"root"},"state":"open",children:['.$tree.']}';
		echo "[".$tree."]";
	}

	//部门共享列表（用户）
	public function getgroupAction(){
		echo $this->file->groupFolderList($this->sinfo['group'],$_REQUEST['page'],$this->getPageAmount(3));
	}
	
	//部门共享列表（用户）界面
	public function groupAction(){
		$maxsize = $this->sinfo['maxsize'];
		if($this->sinfo['group']){
			require_once(APP_PATH.'/views/disk/group_user_list.html');
		}else{
			$this->redirect("/index.php/error/auth");
		}
	}

	//域共享列表（用户）
	public function getdomainAction(){
		echo $this->file->domainFolderList($this->sinfo['domain'],$_REQUEST['page'],$this->getPageAmount(3));
	}
	
	//域共享列表（用户）界面
	public function domainAction(){
		$maxsize = $this->sinfo['maxsize'];
		require_once(APP_PATH.'/views/disk/domain_user_list.html');
	}

	//部门共享文件列表
	public function getgrouplistAction(){
		$type = $_REQUEST['type'];
		if($_REQUEST['type']){
			echo $this->file->listShareFile("group",$_REQUEST['user'],$_REQUEST['page'],$this->getPageAmount(4),$_REQUEST['str']);
		}else{
			echo $this->file->listShareFile("group",$_REQUEST['user'],$_REQUEST['page'],$this->getPageAmount(1),$_REQUEST['str']);
		}
	}
	
	//部门共享文件列表界面
	public function grouplistAction(){
		$user = $_REQUEST['user'];
		$title = urldecode($_REQUEST['title']);
		$this->file->chkUserRights($user);
		$maxsize = $this->sinfo['maxsize'];
		
		if(empty($_REQUEST['type'])){
			$_REQUEST['type'] = $_COOKIE['SET_DISKSHOW'];
		}
		if($_REQUEST['type']==1){
			$this->set->update('diskshow',1);
			require_once(APP_PATH.'/views/disk/group_block.html');
		}elseif($_REQUEST['type']==2){
			$this->set->update('diskshow',2);
			require_once(APP_PATH.'/views/disk/group_list.html');
		}
	}

	//域共享文件列表
	public function getdomainlistAction(){
		if($_REQUEST['type']){
			echo $this->file->listShareFile("domain",$_REQUEST['user'],$_REQUEST['page'],$this->getPageAmount(4),$_REQUEST['str']);
		}else{
			echo $this->file->listShareFile("domain",$_REQUEST['user'],$_REQUEST['page'],$this->getPageAmount(1),$_REQUEST['str']);
		}
	}
	
	//域共享文件列表界面
	public function domainlistAction(){
		$user = $_REQUEST['user'];
		$title = urldecode($_REQUEST['title']);
		$this->file->chkUserRights($user);
		$maxsize = $this->sinfo['maxsize'];
		
		if(empty($_REQUEST['type'])){
			$_REQUEST['type'] = $_COOKIE['SET_DISKSHOW'];
		}
		if($_REQUEST['type']==1){
			$this->set->update('diskshow',1);
			require_once(APP_PATH.'/views/disk/domain_block.html');
		}elseif($_REQUEST['type']==2){
			$this->set->update('diskshow',2);
			require_once(APP_PATH.'/views/disk/domain_list.html');
		}
	}

	//添加共享文件
	public function addsharefileAction(){
		echo $this->file->addShareFile($_REQUEST['type'],$_REQUEST['file'],$_REQUEST['f']);
	}

	//移除共享文件
	public function removesharefileAction(){
		if($this->file->delShareFile($_REQUEST['type'],$_REQUEST['file'],$_REQUEST['f'])){
			echo '{"code":"1","tip":"","state":1}'; 
		}else{
			echo '{"code":"E1000","tip":"'.LANG_TIP_E1000.'","state":0}';
		}
	}

	//部门共享目录文件列表
	public function groupsharelistAction(){
		$dir = $this->file->getShareFolder($_REQUEST['user']);
		$sharefile = new WebMail_Model_File($dir."/disk/");
		if($sharefile->checkShareFolder($_REQUEST['f'],"group")){
			if($_REQUEST['type']){
				echo $sharefile->listIndex($_REQUEST['f'],$_REQUEST['page'],$_REQUEST['str'],$this->getPageAmount(4),$_REQUEST['user']);
			}else{
				echo $sharefile->listIndex($_REQUEST['f'],$_REQUEST['page'],$_REQUEST['str'],$this->getPageAmount(7),$_REQUEST['user']);
			}
		}else{
			echo '{"code":"D1000","tip":"'.LANG_TIP_D1000.'","state":0}';
		}
	}

	//域共享文件列表
	public function domainsharelistAction(){
		$dir = $this->file->getShareFolder($_REQUEST['user']);
		$sharefile = new WebMail_Model_File($dir."/disk/");
		if($sharefile->checkShareFolder($_REQUEST['f'],"domain")){
			if($_REQUEST['type']){
				echo $sharefile->listIndex($_REQUEST['f'],$_REQUEST['page'],$_REQUEST['str'],$this->getPageAmount(4),$_REQUEST['user']);
			}else{
				echo $sharefile->listIndex($_REQUEST['f'],$_REQUEST['page'],$_REQUEST['str'],$this->getPageAmount(7),$_REQUEST['user']);
			}
		}else{
			echo '{"code":"D1000","tip":"'.LANG_TIP_D1000.'","state":0}';
		}
	}
	
	//文件列表界面
	public function listAction(){
		$maxsize = $this->sinfo['maxsize'];
		$type = $_REQUEST['type'];
		
		if(empty($_REQUEST['type'])){
			if(empty($_COOKIE['SET_DISKSHOW'])){
				$_REQUEST['type'] = 1;
			}else{
				$_REQUEST['type'] = $_COOKIE['SET_DISKSHOW'];
			}
		}
		
		if($_REQUEST['type']==1){
			$this->set->update('diskshow',1);
			require_once(APP_PATH.'/views/disk/main_block.html');
		}elseif($_REQUEST['type']==2){
			$this->set->update('diskshow',2);
			require_once(APP_PATH.'/views/disk/main_list.html');
		}
	}
	
	//文件上传界面
	public function fileuploadAction(){
		$mark = $_COOKIE['SESSION_MARK'];
		$key = $_COOKIE['SESSION_ID'];
		$type = $_REQUEST['type'];
		$folder = $_REQUEST['folder'];
		require_once(APP_PATH.'/views/disk/fileupload.html');
	}
	
	//文件下载
	public function downloadAction(){
		if(!empty($_REQUEST['param'])){
			$ua = $_SERVER['HTTP_USER_AGENT'];
			$tmp = explode("|",$_REQUEST['param']);
			$param = $tmp[0];
			$folder = $tmp[1];
			$info = json_decode(base64_decode($param),true);
			$filename = $info['name'];
			Header("Content-type: application/octet-stream");
			Header("Accept-Ranges: bytes");
			Header("Content-Length: ".$info['size']);
			if (preg_match("/Firefox/", $ua)) {
				Header('Content-Disposition: attachment; filename*="utf8\'\''.$filename.'"');
			} else if (preg_match("/Safari/", $ua)) {
				Header('Content-Disposition: attachment; filename="'.$filename.'"');
			} else {
				Header('Content-Disposition: attachment; filename="'.rawurlencode($filename).'"');
			}
			if(!empty($info['dir']))$folder.=$info['dir'];
			$this->file->getDownloadFile($folder.$info['path'],'',$info['isshare']);
		}
	}
	
	//声音文件下载
	public function audioAction(){
		if(!empty($_REQUEST['param'])){
			$tmp = explode("|",$_REQUEST['param']);
			$param = $tmp[0];
			$folder = $tmp[1];
			$info = json_decode(base64_decode($param),true);
			Header("Content-type: audio/mpeg");
			Header("Accept-Ranges: bytes");
			Header("Content-Length: ".$info['size']);
			Header("Content-Disposition: attachment; filename=".iconv("utf-8","gb2312",$info['name']));
			if(!empty($info['dir']))$folder.=$info['dir'];
			$this->file->getDownloadFile($folder.$info['path'],'',$info['isshare']);
		}
	}
	
	//图片预览
	public function reviewAction(){
		if(!empty($_REQUEST['param'])){
			$info = json_decode(base64_decode($_REQUEST['param']),true);
			Header("Content-Type:image/jpeg");
			$folder = $_REQUEST['f'];
			if(!empty($info['dir']))$folder.=$info['dir'];
			$this->file->getDownloadFile($folder.$info['path'],'',$info['isshare']);
		}
	}
	
	//文件重命名界面
	public function renameAction(){
		$name = $_REQUEST['name'];
		$file = $_REQUEST['file'];
		$folder = $_REQUEST['f'];
		require_once(APP_PATH.'/views/disk/rename.html');
	}
	
	//文件目录树界面
	public function folderAction(){
		$btn = 'btnMoveFiles';
		if($_REQUEST['type']){
			$btn = 'btnCopyFile';
			$param = $_REQUEST['param'];
		}
		require_once(APP_PATH.'/views/disk/folder.html');
	}
	
	//文件打包下载
	public function packagedownloadAction(){
		$files = explode(",",$_REQUEST['files']);
		$zipfile = $this->file->compressFile($files,$_REQUEST['folder'],$_REQUEST['isshare']);
		Header("Content-type: application/octet-stream");
		Header("Accept-Ranges: bytes");
		Header("Content-Length: ".filesize($zipfile));
		Header("Content-Disposition: attachment; filename=".basename($zipfile));
		$this->file->getDownloadFile($zipfile,1);
	}
	
	//移动文件
	public function movefilesAction(){
		$files = explode(",",$_REQUEST['files']);
		$folder = $_REQUEST['f'];
		if($folder=="/")$folder = "";
		$target = $_REQUEST['t'];
		if($target=="//")$target = "";
		$move = $this->file->movFiles($files,$folder,$target);
		if($move){
			if($move==1){
				$this->file->delFile($_REQUEST['files'],$folder,1);
			}
		}
		
		switch ($move){
			case 1:$info ='{"code":"","tip":"","state":1}';break;
			case 2:$info ='{"code":"D1005","tip":"'.LANG_TIP_D1005.'","state":0}';break;
			case 0:$info ='{"code":"","tip":"'.LANG_TIP_E1000.'","state":0}';break;
		}
		echo $info;
	}
	
	//复制文件
	public function copyfileAction(){
		echo $this->file->copyMailAttachFile($_REQUEST['file'],$_REQUEST['t']);
	}
	
	
	public function showgroupAction(){
		
	}
	
	public function showgrouplistAction(){
		if(!$this->file->checkShareAuth($_REQUEST['user'],"group",$this->_sinfo['group'])){
			//$this->_redirect(PANDORA_PATH_WWWROOT."/index.php/error/auth");
		}
		$this->view->user = $_REQUEST['user'];
	}
	
	public function showdomainAction(){
		
	}
	
	public function showdomainlistAction(){
		if(!$this->file->checkShareAuth($_REQUEST['user'],"domain",$this->_sinfo['domain'])){
			$this->_redirect(PANDORA_PATH_WWWROOT."/index.php/error/auth");
		}
		$this->view->user = $_REQUEST['user'];
	}
	
	//新建文件夹界面
	public function newfolderAction(){
		$folder = $_REQUEST['f'];
		require_once(APP_PATH.'/views/disk/newfolder.html');
	}
	
	//附件发送
	public function sendatachAction(){
		$mid = time().".".rand(1,100).".".PANDORA_PATH_HOST;
		if($this->file->sendAttachFile($mid,$_REQUEST['files'],$_REQUEST['folder'])){
			header("Location: ../mail/write?t=1&mid=$mid");
		}
	}
	
	public function getdiskinfoAction(){
		$ihead = $this->file->getIndexHead();
		echo '{"size":"'.$this->file->convertSize($ihead['size']).'","osize":"'.$ihead['size'].'","files":'.$ihead['files'].',"maxsize":'.$this->sinfo['maxsize'].'}';
	}
}