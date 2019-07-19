<?php
require_once(APP_PATH."/models/Base.php");
require_once(APP_PATH."/libs/Mysql.class.php");
require_once(APP_PATH.'/models/Log.php');

/**
 * 定义 WebMail_Model_File 类
 *
 * @copyright 
 * @author Rick Jin
 * @package pandora
 * @version 1.1
 */
class WebMail_Model_File extends WebMail_Model_Base
{
	protected $_root;
	protected $_size = 512;
	protected $_sharesize = 512;
	protected $_tag = PANDORA_PATH_HOST;
	protected $_dbparams = array('user'=>PANDORA_DATABASE_USER,'password'=>PANDORA_DATABASE_PASSWORD,'host'=>PANDORA_DATABASE_HOST,'dbname'=>PANDORA_DATABASE_DBNAME);
	protected $_lock;
	protected $_res;
	protected $log;

	/**
     * 构造函数
     * @return WebMail_Model_File
     */
	function __construct($dirroot){
		//parent::init();
		$this->_root = $dirroot;
		$this->_lock = new WebMail_Model_Socket(PANDORA_SOCKET_LOCK);
		$this->_res = $_COOKIE['SESSION_MARK']."-MAILBOX";
		$this->log = new WebMail_Model_Log();
	}
	
	function show(){
		return $this->_root;
	}

	function checkIndex($folder=""){
		if(!file_exists($this->_root.$folder."index")){
			return $this->createIndex($folder);
		}
	}

	function setFileName(){
		return time().".".rand(0,9).".".$this->_tag;
	}

	/**
     * 取得文件名
     *
     * @param string $filename 文件名
     * @return string
     */
	function getFileName($filename){
		$filename = explode(".",$filename);
		return urldecode(base64_decode($filename[4]));
	}

	/**
     * 生成索引文件
     *
     * @param string $folder
     * @return bool
     */
	function createIndex($folder=""){
		$path = $this->_root.$folder;
		$head = array('size'=>0,'files'=>0,'updir'=>$folder);
		$head = sprintf("%-".$this->_size."s",json_encode($head));
		if($this->_lock->lock($this->_res)){
			$oper = file_put_contents($path."index",$head);
			$this->_lock->unlock();
			return $oper;
		}
	}

	/**
     * 添加索引记录
     *
     * @param string $folder	目录地址
     * @param string $filename	文件名
     * @param string $realname	文件实际名称
     * @return bool
     */
	function addIndex($folder,$filename,$realname){
		if($this->_lock->lock($this->_res)){
			$index = $folder."index";
			$finfo = $this->getFileInfo($folder.$filename,$realname);
			$offset = filesize($index);
			$fpe = fopen($index,"r+");

			//更新当前目录占用空间信息
			$head = $this->getIndexHead($index);
			$head['size']+=filesize($folder.$filename);
			$head['files']+=1;
			$this->setIndexHead($head,$index);

			
			//更新根目录索引头信息
			if($index != $this->_root."index"){
				$ihead = $this->getIndexHead();
				$ihead['size']+=filesize($folder.$filename);
				$ihead['files']+=1;
				$this->setIndexHead($ihead);
			}

			//添加新增文件信息
			$fpe = fopen($index,"a+");
			$str = sprintf("%-".$this->_size."s",json_encode($finfo));
			$oper = fwrite($fpe,$str);
			fclose($fpe);
			$this->_lock->unlock();
			return $oper;
		}
	}

	/**
     * 文件列表
     *
     * @param string $folder	目录地址
     * @param int $start	起始位置
     * @param int $amount	每页显示记录数
     * @param int $isshare	受否为共享
     * @param string $sharetype	共享类型
     * @return string
     */
	function listIndex($folder="",$start=1,$substr=0,$amount=10,$isshare=0,$sharetype=''){
		$this->checkIndex($folder);
		$index = $this->_root.$folder."index";
		//echo $index;
		$p = $this->_size;
		$fpe = fopen($index,"r");
		$num = $this->getIndexNum($index);
		$maxpage = ceil($num/$amount);

		$s = ($start-1)*$amount+1;
		$e = $s+$amount-1;
		$p = $p+($s-1)*$this->_size;

		$head = $this->getIndexHead($index);

		$n = 0;
		for ($i=$s;$i<=$e;$i++){
			$offset = fseek($fpe,$p);
			$t = trim(fgets($fpe,$this->_size));
			if(!empty($t)){
				$data[$n] = json_decode($t,true);
				$data[$n]['offset'] = $p;
				$data[$n]['dir'] = $head['updir'];
				$n++;
				$p+=$this->_size;
			}
		}
		fclose($fpe);
		//$data = sysSortArray($data,"id");
		for ($i=0;$i<count($data);$i++){
			$data[$i]['info'] = base64_encode(json_encode(array('name'=>$data[$i]['name'],'path'=>$data[$i]['path'],'size'=>$data[$i]['osize'],'isshare'=>$isshare)));
			if($substr){
				$data[$i]['shortname'] = $this->cutString($data[$i]['name'],$substr);
			}
			$data[$i]['isshare'] = 0;
			$strdata.=json_encode($data[$i]);
			if($i<(count($data)-1))$strdata.=",";
		}
		$ihead = $this->getIndexHead();
		$strdata = '{"code":1,"maxpage":'.$maxpage.',"curpage":'.$start.',"size":"'.$this->convertSize($ihead['size']).'","osize":"'.$ihead['size'].'","files":'.$ihead['files'].',"updir":"'.$head['updir'].'","data":['.$strdata.']}';
		return $strdata;
	}

	/**
     * 取得索引记录数
     *
     * @param string $index	索引地址
     * @return int
     */
	function getIndexNum($index){
		$num = (filesize($index)-$this->_size)/$this->_size;
		return $num;
	}

	/**
     * 取得全部已用空间
     *
     * @param string $path	目录物理地址
     * @return int
     */
	function getDiskSpaceUsage($path=''){
		$ihead = $this->getIndexHead();
		return $ihead['size'];
	}

	/**
     * 取得指定目录已用空间
     *
     * @param string $path	目录物理地址
     * @return int
     */
	function getFolderSpaceUsage($path){
		$dir = $this->_root.$path;
		@$dh = opendir($dir);
		$size = 0;
		while($file = @readdir($dh)){
			if($file != "." and $file != ".."){
				if(is_file($dir.$file)){
					if($file!='index'){
						$size+=filesize($dir.$file);
					}
				}
			}
		}
		@closedir($dh);
		return $size;
	}
	
	function formatDisk(){
		if(filesize($this->_root."index")<=$this->_size){
			$ihead = $this->getIndexHead();
			$ihead['size'] = 0;
			$ihead['files'] = 0;
			$this->setIndexHead($ihead);
		}
	}

	/**
     * 文件上传
     *
     * @param string $upfile	$_FILES数组
     * @param string $filepath	目的路径
     * @param int $allowtype 	
     * @return void
     */
	function uploadFile($path="",$files,$maxspace = 100){
		$filepath = $this->_root.$path;
		$used = $this->getDiskSpaceUsage();
		//file_put_contents('/var/www/html/test/log.txt',$filepath."\n".$files['Filedata']["tmp_name"]."\n".$files['Filedata']["name"]);
		if (!isset($files['Filedata']) || !is_uploaded_file($files['Filedata']["tmp_name"]) || $files['Filedata']["error"] != 0 || (($used+$files['Filedata']["size"]))>($maxspace*1024*1024)) {
			if ((($used+$files['Filedata']["size"]))>($maxspace*1024*1024)){
				header("HTTP/1.1 500");
				exit(0);
			}
			exit(0);
		}elseif(!$this->checkFileExist($files['Filedata']["name"],$path)){
			header("HTTP/1.1 501");
			exit(0);
		}else{
			$newfile = $this->setFileName();
			if(move_uploaded_file($files['Filedata']["tmp_name"],$filepath.$newfile)){
				echo $this->addIndex($filepath,$newfile,$files['Filedata']["name"]);
				$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),'file uploaded '.$files['Filedata']["name"],1);
			}
		}
	}

	/**
     * 检查文件是否存在
     *
     * @param string $filename	文件名
     * @param string $folder	文件保存目录
     * @return bool
     */
	function checkFileExist($filename,$folder){
		$row = $this->getAllRecords($this->_root.$folder."index");
		$mark = 1;
		for ($i=1;$i<=count($row['data']);$i++){
			if($row['data'][$i]['name']==$filename){
				$mark = 0;
				break;
			}
		}
		return $mark;
	}
	
	/* 取得已存在文件信息
     *
     * @param string $filename	文件名
     * @param string $folder	文件保存目录
     * @return bool
     */
	function getExistFile($filename,$folder){
		$data = 0;
		$row = $this->getAllRecords($this->_root.$folder."index");
		for ($i=1;$i<=count($row['data']);$i++){
			if($row['data'][$i]['name']==$filename){
				$data = $row['data'][$i];
			}
		}
		return $data;
	}

	/**
     * 取得文件信息
     *
     * @param string $filepath	文件物理地址
     * @return array
     */
	function getFileInfo($filepath,$realname){
		$temp = explode(".",$realname);
		$ext = $temp[count($temp)-1];
		$fileinfo = array(
		'size'=>$this->convertSize(filesize($filepath)),
		'osize'=>filesize($filepath),
		'mtime'=>date("Y-m-d H:i:s",filemtime($filepath)),
		'fileinode'=>fileinode($filepath),
		'name'=>$realname,
		'ext'=>strtolower($ext),
		'path'=>basename($filepath),
		'isdir'=>0,
		'group'=>0,
		'domain'=>0);
		if (is_dir($filepath)) {
			$fileinfo['isdir'] = 1;
			$fileinfo['path'].= "/";
		}
		return $fileinfo;
	}

	/**
     * 新建文件夹
     *
     * @param string $path	路径地址
     * @param string $foldername  文件夹名称
     * @return bool 1:新建成功；0:新建失败；2:已存在相同文件夹
     */
	function createFolder($path,$foldername){
		$info = array('state'=>0,'code'=>'','tip'=>'');
		$newfolder = $this->setFileName();
		if(file_exists($this->_root.$path.$newfolder)){
			$info['code'] = LANG_TIP_D1003;
		}else{
			if($this->_lock->lock($this->_res)){
				if(!$this->checkDirDepth($path)){
					$info['code'] = LANG_TIP_D1001;
				}elseif(!$this->checkFileExist($foldername,$path)){
					$info['code'] = LANG_TIP_D1002;
				}else{
					if(mkdir($this->_root.$path.$newfolder)){
						$this->checkIndex($path.$newfolder."/");
						if($this->addIndex($this->_root.$path,$newfolder,$foldername)){
							$info['state'] = 1;
						}
					}
				}
				$this->_lock->unlock();
			}
		}
		return $info;
	}

	/**
     * 生成下载文件
     *
     * @param string $filepath	文件物理地址
     * @return array
     */
	function getDownloadFile($path,$type='',$isshare=''){
		$filepath = $this->_root.$path;
		if(!empty($type)){
			$filepath = $path;
		}elseif(!empty($isshare)){
			$share = $this->getShareFolder($isshare);
			$filepath = $share."/disk/".$path;
		}

		$fpe = fopen($filepath,"r");
		$buffer_size = 1024*1024*2;
		$cur_pos = 0;
		$filesize = filesize($filepath);
		$buffer = "";
		while(!feof($fpe)&&$filesize-$cur_pos>$buffer_size)
		{
			$buffer = fread($fpe,$buffer_size);
			echo $buffer;
			$cur_pos += $buffer_size;
		}

		$buffer = fread($fpe,$filesize-$cur_pos);
		echo $buffer;
		fclose($fpe);
		if(!empty($type))unlink($filepath);
	}

	/**
     * 删除文件
     *
     * @param string $filepath	文件物理地址
     * @return bool
     */
	function delFile($filepath,$folder="",$ismove=0){
		if($this->_lock->lock($this->_res)){
			$files = explode(",",$filepath);
			$row = $this->getAllRecords($this->_root.$folder."index");
			$data = $row['data'];
			$key = $row['key'];
			$head = $row['head'];
			$num = count($data);
			$thead = array('size'=>0,'files'=>0);
			for ($i=0;$i<(count($files)-1);$i++){
				$k = array_search($files[$i],$key);
				if($head['size']>0)$head['size']-=$data[$k]['osize'];
				if($head['files']>0)$head['files']-=1;
				$thead['size']+=$data[$k]['osize'];
				$thead['files']+=1;
				unset($data[$k]);
				if($k){
					if(is_dir($this->_root.$folder.$files[$i])){
						$dir = $this->clearFolder($folder.$files[$i]);
						if($head['size']>0)$head['size']-=$dir['size'];
						if($head['files']>0)$head['files']-=$dir['files'];
					}else{
						unlink($this->_root.$folder.$files[$i]);
						$this->log->sendLog(strtolower($_COOKIE['SESSION_MARK']),'file deleted '.$folder.$files[$i],1);
					}
				}
				if(!$ismove){
					$this->delLinkShareFile("group",$files[$i]);
					$this->delLinkShareFile("domain",$files[$i]);
				}
			}
			for($i=1;$i<=$num;$i++){
				if(is_array($data[$i])){
					$tmp = sprintf("%-".$this->_size."s",json_encode($data[$i]));
					$strdata.=$tmp;
				}
			}
			if($head['size']<0)$head['size'] = 0;
			$strdata = sprintf("%-".$this->_size."s",json_encode($head)).$strdata;
			$oper = file_put_contents($this->_root.$folder."index",$strdata);

			if($this->_root.$folder."index"!=$this->_root."index"){
				$ihead = $this->getIndexHead();
				if($ihead['size']>0)$ihead['size']-=$dir['size'];
				if($ihead['files']>0)$ihead['files']-=$dir['files'];
				if($ihead['size']>0)$ihead['size']-=$thead['size'];
				if($ihead['files']>0)$ihead['files']-=$thead['files'];
				$this->setIndexHead($ihead);
			}
			$this->formatDisk();
			$this->_lock->unlock();
			$code = 0;
			if($oper)$code = 1;
			return json_encode(array('code'=>$code));
		}
	}

	/**
     * 清理目录
     *
     * @param string $path	目录地址
     * @param array $head	索引文件头信息
     * @return array
     */
	function clearFolder($folder,$head = array('size'=>0,'files'=>0)){
		$path = $this->_root.$folder;
		if($handle = opendir($path)){
			while(false !== ($item = readdir($handle))){
				if($item != "." && $item != ".."){
					if(is_dir($path.$item)){
						$head['size']+=filesize($path.$item);
						$head['files']+=1;
						$this->clearFolder($folder.$item."/",$head);
					}else{
						if($item!='index'){
							$head['size']+=filesize($path.$item);
							$head['files']+=1;
						}
						$this->delLinkShareFile("group",$item);
						$this->delLinkShareFile("domain",$item);
						unlink($path.$item);
					}
				}
			}
			closedir($handle);
			rmdir($path);
		}
		return $head;
	}

	/**
     * 取得索引文件头信息
     *
     * @param string $index	索引文件地址
     * @return array
     */
	function getIndexHead($index=''){
		if(empty($index))$index=$this->_root."index";
		$fpe = fopen($index,"r");
		$head = json_decode(trim(fread($fpe,$this->_size)),true);
		fclose($fpe);
		return $head;
	}

	/**
     * 设置索引文件头信息
     *
     * @param array $head	索引文件头信息
     * @param string $index	索引文件地址
     * @return int
     */
	function setIndexHead($head,$index=''){
		if(empty($index))$index=$this->_root."index";
		$fpe = fopen($index,"r+");
		$oper = fwrite($fpe,sprintf("%-".$this->_size."s",json_encode($head)));
		fclose($fpe);
		return $oper;
	}

	/**
     * 取得所有信息记录
     *
     * @param string $file 索引文件路径
     * @param int $size 块大小
     * @return array
     */
	function getAllRecords($file){
		$p = $this->_size;
		$num = $this->getNum($file);
		$fpe = fopen($file,"r");
		$head = json_decode(trim(fread($fpe,$this->_size)),true);
		for ($i=1;$i<=$num;$i++){
			$offset = fseek($fpe,$p);
			$t = trim(fread($fpe,$this->_size));
			$data[$i] = json_decode($t,true);
			$data[$i]['p'] = $p;
			$key[$i] = $data[$i]['path'];
			$p+=$this->_size;
		}
		fclose($fpe);
		$row = array('key'=>$key,'data'=>$data,'head'=>$head);
		return $row;
	}

	/**
     * 取得索引信息条数
     *
     * @param string $file 索引文件路径
     * @param int $size 块大小
     * @return int
     */
	function getNum($file){
		$num = (filesize($file)-$this->_size)/$this->_size;
		return $num;
	}

	/**
     * 编辑文件信息
     *
     * @param string $file 索引文件路径
     * @param array $editinfo 编辑信息
     * @param string $folder 目录名称
     * @return int
     */
	function editFileInfo($file,$editinfo,$folder){
		if($this->_lock->lock($this->_res)){
			$mark = 0;
			$index = $this->_root.$folder."index";
			$data = $this->getAllRecords($index);
			$k = array_search($file,$data['key']);
			if($k){
				$data['data'][$k][$editinfo['tag']] = $editinfo['val'];
			}
			for($i=1;$i<=count($data['data']);$i++){
				$tmp = sprintf("%-".$this->_size."s",json_encode($data['data'][$i]));
				$strdata.=$tmp;
			}
			$strdata = sprintf("%-".$this->_size."s",json_encode($data['head'])).$strdata;
			if(file_put_contents($index,$strdata)){
				$mark = 1;
			}
			$this->_lock->unlock();
			return $mark;
		}
	}

	/**
     * 取得文件夹树
     *
     * @param string $folder 目录名称
     * @return string
     */
	function getFileTree($folder='',$showfile=0){
		$path = $this->_root.$folder;
		$index = $path."index";
		$row = $this->getAllRecords($index);
		$data = $row['data'];
		if(count($data)>0){
			for ($i=1;$i<=count($data);$i++){
				if($data[$i]['isdir']==1){
					$strdata.='{"data":"'.$data[$i]['name'].'","attributes":{"path":"'.$data[$i]['path'].'","folder":"'.$folder.'","rel":"folder"}';
					$sub = $this->getFileTree($folder.$data[$i]['path'],$showfile);
					if(!empty($sub)){
						$strdata.=",children:[";
						$strdata.=$sub;
						$strdata.="]";
					}
					$strdata.='},';
				}else{
					if($showfile){
						$strdata.='{"data":"'.$data[$i]['name'].'","attributes":{"path":"'.$data[$i]['path'].'","folder":"'.$folder.'","isfile":"1","name":"'.$data[$i]['name'].'","rel":"page"}},';
					}
				}
			}
			$strdata = substr($strdata,0,-1);
		}
		return $strdata;
	}

	/**
     * 压缩文件
     *
     * @param array $path	原文件地址
     * @param string $temp  临时文件存放地址
     * @return string
     */
	public function compressFile($files,$folder='',$isshare=''){
		$zip = new ZipArchive();
		$zipfile = str_replace("disk/","",$this->_root."tmp/".time().".zip");

		$path = $this->_root.$folder;
		if(!empty($isshare)){
			$share = $this->getShareFolder($isshare);
			$path = $share."/disk/".$folder;
		}
		$index = $path."index";
		$row = $this->getAllRecords($index);

		if ($zip->open($zipfile, ZIPARCHIVE::CREATE)== TRUE){
			for ($i=0;$i<(count($files)-1);$i++){
				$k = array_search($files[$i],$row['key']);
				if($k){
					if($row['data'][$k]['isdir']){
						//$dirname = iconv("utf-8","gb2312",$row['data'][$k]['name']);
						//$zip->addEmptyDir($dirname);
						//$this->addCompressFile($zipfile,$folder.$row['data'][$k]['path'],$dirname);
					}else{
						$zip->addFile($path.$row['data'][$k]['path'],iconv("utf-8","gb2312",$row['data'][$k]['name']));
					}
				}
			}
			$zip->close();
		}
		return $zipfile;
	}

	public function addCompressFile($zipfile,$folder,$dir){
		$zip = new ZipArchive();
		$path = $this->_root.$folder;
		$index = $path."index";
		$row = $this->getAllRecords($index);

		if ($zip->open($zipfile)== TRUE){
			for ($i=1;$i<=(count($row['data']));$i++){
				if($row['data'][$i]['isdir']){
					$dirname = iconv("utf-8","gb2312",$row['data'][$i]['name']);
					$subdir = $dir."/".$dirname;echo "<Br>";
					echo $subdir;
					$zip->addEmptyDir($subdir);
					$this->addCompressFile($zipfile,$row['data'][$i]['path'],$subdir);
				}else{
					$zip->addFile($path.$row['data'][$i]['path'],$dir."/".iconv("utf-8","gb2312",$row['data'][$i]['name']));
				}
			}
			$zip->close();
		}
	}

	/**
     * 移动文件
     *
     * @param array $files  需要移动的文件路径集合
     * @param string $folder  所在目录
     * @param string $target 目标路径
     * @return int
     */
	public function movFiles($files,$folder='',$target){
		if($folder!=$target){
			$path = $this->_root.$folder;
			$index = $path."index";

			$row = $this->getAllRecords($index);
			$data = $row['data'];

			for ($i=0;$i<count($files);$i++){
				$k = array_search($files[$i],$row['key']);
				if($k){
					if(!$this->checkFileExist($data[$k]['name'],$target)){
						return 2;
						exit(0);
					}elseif ($folder.$data[$k]['path']==$target){
						return 3;
						exit(0);
					}else{
						$this->copyFiles($data[$k],$folder,$target);
						$this->editShareFile('group',$files[$i],array('tag'=>'dir','val'=>$target));
						$this->editShareFile('domain',$files[$i],array('tag'=>'dir','val'=>$target));
					}
				}
			}
			$this->_lock->unlock();
			return 1;
		}
	}

	/**
     * 复制邮件附件
     *
     * @param string $files  需要复制的附件信息
     * @param string $target 目标路径
     * @return int
     */
	public function copyMailAttachFile($file,$target){
		if($this->_lock->lock($this->_res)){
			$file = json_decode(base64_decode($file));
			$fpe = fopen($file->path,"r");
			fseek($fpe,$file->begin);
			$content = fread($fpe,$file->length);
			$content = base64_decode($content);
			fclose($fpe);
			$fname = $this->setFileName();
			if(file_put_contents($this->_root.$target.$fname,$content)){
				$oper = $this->addIndex($this->_root.$target,$fname,$file->file);
			}
			$this->_lock->unlock();
			return $oper;
		}
	}

	/**
     * 复制文件
     *
     * @param string $files  需要复制文件
     * @param string $folder 文件所在目录
     * @param string $target 目标路径
     * @return int
     */
	public function copyFiles($file,$folder,$target){
		if($file['isdir']){
			mkdir($this->_root.$target.$file['path']);
			$this->addIndex($this->_root.$target,$file['path'],$file['name']);

			$this->checkIndex($target.$file['path']);
			$index = $this->_root.$folder.$file['path']."index";
			$row = $this->getAllRecords($index);
			for ($i=1;$i<=count($row['data']);$i++){
				$this->copyFiles($row['data'][$i],$folder.$file['path'],$target.$file['path']);
			}
		}else{
			if(copy($this->_root.$folder.$file['path'],$this->_root.$target.$file['path'])){
				$this->addIndex($this->_root.$target,$file['path'],$file['name']);
			}
		}

		if($file['group']){
			$this->editFileInfo($file['path'],array('tag'=>'group','val'=>1),$target);
		}

		if($file['domain']){
			$this->editFileInfo($file['path'],array('tag'=>'domain','val'=>1),$target);
		}
	}

	/**
     * 部门共享目录列表
     *
     * @param int $groupid	部门代号
     * @param int $start  起始页
     * @param int $amount 每页记录数
     * @return string
     */
	public function groupFolderList($groupid,$start=1,$amount=10){
		if(!$groupid){
			$strdata = '{"maxpage":0,"curpage":0,"data":""}';
		}else{
			$db = new Mysql($this->_dbparams);
			$num = $db->total("select * from (select username,address_type from users where group_id=$groupid) as a where a.address_type=0");
			$maxpage = ceil($num/$amount);

			$s = ($start-1)*$amount;
			$e = $amount;
			
			$groupuser = $db->select("select a.real_name as name,group_id as gid,username,privilege_bits as privilege from (select username,address_type,real_name,group_id,privilege_bits from users where group_id=$groupid limit $s,$e) as a where a.address_type=0"); 
			for ($i=0;$i<count($groupuser);$i++){
				$groupuser[$i]['urights'] = $this->chkRights($groupuser[$i]['privilege'],'user');
				$groupuser[$i]['arights'] = $this->chkRights($groupuser[$i]['privilege'],'pubaddr');
				$groupuser[$i]['realname'] = urlencode($groupuser[$i]['name']);
				if(empty($groupuser[$i]['name'])) $groupuser[$i]['realname'] = urlencode($groupuser[$i]['username']);
			}
			$strdata = '{"code":1,"maxpage":'.$maxpage.',"curpage":'.$start.',data:'.json_encode($groupuser).'}';
			$db->close();
		}
		return $strdata;
	}

	/**
     * 域共享目录列表
     *
     * @param int $domainid	部门代号
     * @param int $start  起始页
     * @param int $amount 每页记录数
     * @return string
     */
	public function domainFolderList($domainid,$start=1,$amount=10){
		if(!$domainid){
			$strdata = '{"maxpage":0,"curpage":0,"data":""}';
		}else{
			$db = new Mysql($this->_dbparams);

			$num = $db->total("select * from (select username,address_type from users where domain_id=$domainid) as a where a.address_type=0");
			$maxpage = ceil($num/$amount);

			$s = ($start-1)*$amount;
			$e = $amount;

			$domainuser = $db->select("select a.real_name as name,domain_id as did,username,privilege_bits as privilege from (select username,address_type,real_name,domain_id,privilege_bits from users where domain_id=$domainid) as a where a.address_type=0 limit $s,$e");
			for ($i=0;$i<count($domainuser);$i++){
				$domainuser[$i]['urights'] = $this->chkRights($domainuser[$i]['privilege'],'user');
				$domainuser[$i]['arights'] = $this->chkRights($domainuser[$i]['privilege'],'pubaddr');
				$domainuser[$i]['realname'] = urlencode($domainuser[$i]['name']);
				if(empty($domainuser[$i]['name'])) $domainuser[$i]['realname'] = urlencode($domainuser[$i]['username']);
			}
			
			$strdata = '{"code":1,"maxpage":'.$maxpage.',"curpage":'.$start.',data:'.json_encode($domainuser).'}';
			$db->close();
		}
		return $strdata;
	}

	/**
     * 取得共享目录路径
     *
     * @param string $user	用户名
     * @return string
     */
	public function getShareFolder($user){
		$db = new Mysql($this->_dbparams);
		$user = $db->select("select maildir from users where username='".$user."'");
		return $user[0]['maildir'];
	}

	/**
     * 添加共享文件
     *
     * @param string $type	类型
     * @param string $file  需要添加文件
     * @param string $folder 文件所在目录地址
     * @return int
     */
	public function addShareFile($type,$file,$folder=''){
		if($this->_lock->lock($this->_res)){
			$path = str_replace("disk","config",$this->_root);
			$share = $path.$type.".acl";
			if(!file_exists($share)){
				$fpe = fopen($share,"w+");
				//chmod($share, 0777);
			}else{
				$fpe = fopen($share,"a+");
			}

			$row = $this->getAllRecords($this->_root.$folder."index");
			$sharerow = $this->getShareFile($type);
			$tmp = explode("/",$row['head']['updir']);
			$updir = $tmp[count($tmp)-2]."/";

			$files = explode(",",$file);
			for ($i=0;$i<(count($files)-1);$i++){
				$k = array_search($files[$i],$row['key']);
				$sk = array_search($updir,$sharerow['key']);
				if(!$row['data'][$k][$type]){
					$data = array('path'=>$row['data'][$k]['path'],
					'isdir'=>$row['data'][$k]['isdir'],
					'name'=>$row['data'][$k]['name'],
					'size'=>$row['data'][$k]['size'],
					'osize'=>$row['data'][$k]['osize'],
					'mtime'=>$row['data'][$k]['mtime'],
					'ext'=>$row['data'][$k]['ext'],
					'group'=>$row['data'][$k]['group'],
					'domain'=>$row['data'][$k]['domain'],
					'dir'=>$folder);
					$str.= sprintf("%-".$this->_sharesize."s",json_encode($data));
					$this->editFileInfo($files[$i],array('tag'=>$type,'val'=>1),$folder);
				}
			}
			$oper = fwrite($fpe,$str);
			fclose($fpe);
			$this->_lock->unlock();
			$code = 0;
			if($oper)$code = 1;
			return json_encode(array('code'=>$code));
		}
	}

	/**
     * 设置共享文件参数
     *
     * @param string $type	类型
     * @param int $val  参数值
     * @param string $folder 文件所在目录地址
     * @return int
     */
	public function setShareDirFiles($type,$val,$folder=''){
		$row = $this->getAllRecords($this->_root.$folder."index");
		for ($i=1;$i<=count($row['data']);$i++){
			//$this->editFileInfo($row['data'][$i]['path'],array('tag'=>$type,'val'=>$val),$folder);
			if(!$row['data'][$i]['isdir']){
				//$this->setShareDirFiles($type,$val,$folder.$row['data'][$i]['path']);
				$this->editFileInfo($row['data'][$i]['path'],array('tag'=>$type,'val'=>$val),$folder);
			}
		}
	}

	/**
     * 共享文件参数编辑
     *
     * @param string $type	类型
     * @param int $val  参数值
     * @param string $file 文件路径
     * @return int
     */
	public function editShareFile($type,$file,$val){
		$row = $this->getShareFile($type);
		$k = array_search($file,$row['key']);
		if($k){
			$row['data'][$k][$val['tag']] = $val['val'];
			$path = str_replace("disk","config",$this->_root);
			$share = $path.$type.".acl";
			if($this->_lock->lock($this->_res)){
				$fpe = fopen($share,'r+');
				fseek($fpe,$row['data'][$k]['p']);
				$oper = fwrite($fpe,sprintf("%-".$this->_sharesize."s",json_encode($row['data'][$k])));
				fclose($fpe);
				$this->_lock->unlock();
				return $oper;
			}
		}
	}

	/**
     * 取得共享文件数据集
     *
     * @param string $type	类型
     * @return array
     */
	public function getShareFile($type){
		$path = str_replace("disk","config",$this->_root);
		$share = $path.$type.".acl";
		$num = filesize($share)/$this->_sharesize;
		$fpe = fopen($share,"r");
		$p = 0;
		for ($i=1;$i<=$num;$i++){
			$offset = fseek($fpe,$p);
			$t = trim(fread($fpe,$this->_sharesize));
			$data[$i] = json_decode($t,true);
			$data[$i]['p'] = $p;
			$key[$i] = $data[$i]['path'];
			$p+=$this->_sharesize;
		}
		fclose($fpe);
		$row = array('key'=>$key,'data'=>$data);
		return $row;
	}

	/**
     * 删除共享文件
     *
     * @param string $type	类型
     * @param string $file  要删除的文件
     * @param string $folder 文件所在目录地址
     * @return int
     */
	public function delShareFile($type,$file,$folder=''){
		if($this->_lock->lock($this->_res)){
			$row = $this->getShareFile($type);
			$k = array_search($file,$row['key']);
			unset($row['data'][$k]);
			foreach ($row['data'] as $v){
				if(is_array($v)){
					$strdata.= sprintf("%-".$this->_sharesize."s",json_encode($v));
				}
			}
			
			$path = str_replace("disk","config",$this->_root);
			$share = $path.$type.".acl";
			
			$indexrow = $this->getAllRecords($this->_root.$folder."index");
			$ik = array_search($file,$indexrow['key']);
			if($indexrow['data'][$ik]['isdir']){
				$this->setShareDirFiles($type,0,$folder.$indexrow['data'][$ik]['path']);
			}
			$oper = $this->editFileInfo($file,array('tag'=>$type,'val'=>0),$folder);
			file_put_contents($share,$strdata);
			$this->_lock->unlock();
			return $oper;
		}
	}

	/**
     * 删除共享文件关联
     *
     * @param string $type	类型
     * @param string $file  要删除关联的文件
     * @return int
     */
	public function delLinkShareFile($type,$file){
		$row = $this->getShareFile($type);
		$k = array_search($file,$row['key']);
		unset($row['data'][$k]);
		for($i=1;$i<=count($row['data']);$i++){
			if(is_array($row['data'][$i])){
				$strdata.= sprintf("%-".$this->_sharesize."s",json_encode($row['data'][$i]));
			}
		}
		$path = str_replace("disk","config",$this->_root);
		$share = $path.$type.".acl";
		$oper = file_put_contents($share,$strdata);
		return $oper;
	}

	/**
     * 共享文件列表
     *
     * @param string $type	类型
     * @param string $user  用户名
     * @param int $start  开始页
     * @param int $amount  每页显示数
     * @return int
     */
	public function listShareFile($type,$user,$start=1,$amount=10,$substr=0){
		$folder = $this->getShareFolder($user);
		$share = $folder."/config/".$type.".acl";
		$fpe = fopen($share,"r");
		$num = filesize($share)/$this->_sharesize;
		$maxpage = ceil($num/$amount);

		$p = 0;
		$s = ($start-1)*$amount+1;
		$e = $s+$amount-1;
		$p = $p+($s-1)*$this->_sharesize;

		$n = 0;
		for ($i=$s;$i<=$e;$i++){
			$offset = fseek($fpe,$p);
			$t = trim(fgets($fpe,$this->_sharesize));
			if(!empty($t)){
				$data[$n] = (array)json_decode($t);
				$n++;
			}
			$p+=$this->_sharesize;
		}
		fclose($fpe);

		for ($i=0;$i<count($data);$i++){
			$data[$i]['info'] = base64_encode(json_encode(array('name'=>$data[$i]['name'],'path'=>$data[$i]['path'],'size'=>$data[$i]['osize'],'isshare'=>$user,'dir'=>$data[$i]['dir'])));
			$data[$i]['isshare'] = $user;
			if($substr){
				$data[$i]['shortname'] = $this->cutString($data[$i]['name'],$substr);
			}
			$strdata.=json_encode($data[$i]);
			if($i<(count($data)-1))$strdata.=",";
		}

		$ihead = $this->getIndexHead();
		$strdata = '{"code":1,"maxpage":'.$maxpage.',"curpage":'.$start.',"size":"'.$this->convertSize($ihead['size']).'","osize":"'.$ihead['size'].'","files":'.$ihead['files'].',data:['.$strdata.']}';
		return $strdata;
	}

	public function sendAttachFile($mid,$files,$folder){
		$path = str_replace("disk/","tmp/attach/",$this->_root).$mid."/";
		if(!file_exists($path)){
			mkdir($path);
		}
		$file = explode(",",$files);
		$row = $this->getAllRecords($this->_root.$folder."index");
		for ($i=0;$i<(count($file)-1);$i++){
			$k = array_search($file[$i],$row['key']);
			$info = $row['data'][$k];
			copy($this->_root.$folder.$file[$i],$path.base64_encode($info['name']));
		}
		return true;
	}

	public function checkShareFolder($folder,$type){
		$share = $this->getShareFile($type);
		$mark = 0;
		for ($i=1;$i<=count($share['key']);$i++){
			if(strstr($folder,$share['key'][$i])){
				$mark = 1;
				break;
			}
		}
		return $mark;
	}

	/**
     * 检查是否同一部门或同一域
     *
     * @param string $user	用户名
     * @return string
     */
	public function checkShareAuth($user,$type,$auth){
		$db = new Mysql($this->_dbparams);
		$user = $db->select("select ".$type."_id as id from users where username='".$user."'");
		if($auth == $user[0]['id']){
			return 1;
		}else{
			return 0;
		}
	}
	
	/**
     * 检查目录深度
     *
     * @param string $folder	当前目录路径
     * @return bool
     */
	public function checkDirDepth($folder){
		$head = $this->getIndexHead($this->_root.$folder."index");
		$updir = explode("/",$head['updir']);
		if((count($updir)-1)<PANDORA_DISK_DIRDEPTH){
			return true;
		}else{
			return false;
		}
	}
	
	function chkRights($privilege,$type){
		$privilege = decbin($privilege);
		$privilege = sprintf("%+024s",$privilege);
		$privilege = str_split($privilege);
		switch ($type){
			case 'user':return $privilege[19];break;
			case 'domain':return $privilege[3];break;
			case 'pubaddr':return $privilege[20];break;
		}
	}
	
	function chkUserRights($user){
		$db = new Mysql($this->_dbparams);
		$user = $db->select("select privilege_bits as privilege from users where username='".$user."'");
		if((!$this->chkRights($user[0]['privilege'],'user'))||(!$this->chkRights($user[0]['privilege'],'pubaddr'))){
			header("Location:".PANDORA_PATH_WWWROOT."/index.php/error/auth");
		}
	}
}