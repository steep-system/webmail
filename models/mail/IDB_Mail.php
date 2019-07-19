<?php
require_once(APP_PATH."/models/Socket.php");

/**
 * 定义 IDB_Mail 类
 *
 * @copyright 
 * @author Rick Jin
 * @package pandorac
 * @version 1.0
 */
class IDB_Mail
{
	protected $path;
	protected $socket;

	/**
     * 构造函数
     *
     * @param string $path 索引文件根目录
     * @return void
     */
	function __construct($path){
		$this->path = $path;
		$this->socket = new WebMail_Model_Socket(PANDORA_SOCKET_AMIDB);
	}

	/**
     * 执行查询
     *
     * @param string $cmd 查询命令
     * @param int $split 是否预处理，默认为否
     * @return array $res
     */
	function execute($cmd,$split=0){
		if($split==1){
			$res = $this->socket->send($cmd,1);
			return $res;
		}elseif($split==2){
			$tres = $this->socket->send($cmd);
			$tres = explode(" ",$tres);
			if(count($tres)>2){
				$res[0] = $tres[0];
				for ($i=1;$i<count($tres);$i++){
					$res[1].=$tres[$i];
				}
			}else{
				$res = $tres;
			}
			return $res;
		}else{
			$res = trim($this->socket->send($cmd));
			return explode(" ",$res);
		}
	}

	/**
     * 插入信息
     *
     * @param string $folder 索引文件目录
     * @param string $record 摘要信息
     * @return string
     */
	function insert($folder, $mid, $flags){
		$time = time();
		$cmd = "M-INST ".$this->path." ".$folder." ".$mid." ".$flags." ".$time."\r\n";
		$res = $this->execute($cmd);
		if($res[0]=='FALSE'){
			return array('state'=>0,'error'=>$res[1]);
		}else{
			return array('state'=>1);
		}
	}

	/**
     * 删除信息
     *
     * @param string $folder 索引文件目录
     * @param array $mids 记录信息ID集合
     * @return string
     */
	function delete($folder,$mids){
		$subcmd = "";
		for($i=0;$i<count($mids);$i++){
			$subcmd.=$mids[$i];
			if($i<(count($mids)-1))$subcmd.=" ";
		}
		$cmd = "M-DELE ".$this->path." ".$folder." ".$subcmd."\r\n";
		$res = $this->execute($cmd);
		if($res[0]=='FALSE'){
			return array('state'=>0,'error'=>$res[1]);
		}else{
			return array('state'=>1);
		}
	}

	/**
     * 更新信息
     *
     * @param string $folder 索引文件目录
     * @param string $id 记录信息ID
     * @param string $record 摘要信息
     * @return string
     */
	function update($folder,$id,$tag,$value){
		$cmd = "M-UPDT ".$this->path." ".$folder." ".$id." ".$tag." ".$value."\r\n";
		$res = $this->execute($cmd);
		if($res[0]=='FALSE'){
			return array('state'=>0,'error'=>$res[1]);
		}else{
			return array('state'=>1);
		}
	}

	/**
     * 移动记录信息
     *
     * @param string $srcfolder 移动源索引文件目录
     * @param string $id 记录信息ID
     * @param string $dstfolder 移动目标索引文件目录
     * @return string
     */
	function move($srcfolder,$dstfolder,$id){
		$cmd = "M-MOVE ".$this->path." ".$srcfolder." ".$id." ".$dstfolder."\r\n";
		$res = $this->execute($cmd);
		if(trim($res[0])=='TRUE'){
			return array('state'=>1);
		}else{
			return array('state'=>0,'error'=>$res[1]);
		}
	}

	/**
     * 复制记录信息
     *
     * @param string $srcfolder 复制源索引文件目录
     * @param string $id 记录信息ID
     * @param string $dstfolder 复制目标索引文件目录
     * @return string
     */
	function copy($srcfolder,$dstfolder,$id){
		$cmd = "M-COPY ".$this->path." ".$srcfolder." ".$id." ".$dstfolder."\r\n";
		$res = $this->execute($cmd);
		if($res[0]=='FALSE'){
			return array('state'=>0,'error'=>$res[1]);
		}else{
			return array('state'=>1,'mid'=>$res[1]);
		}
	}

	/**
     * 取出记录信息集
     *
     * @param string $folder 索引文件目录
     * @param string $order 排序字段
     * @param string $sort 排序类型 asc dsc
     * @param int $offset 起始位置
     * @param int $length 取得记录数
     * @return string
     */
	function listing($folder,$order,$sort,$offset="",$length=""){
		if($length){
			$cmd = "M-LIST ".$this->path." ".$folder." ".$order." ".$sort." ".$offset." ".$length."\r\n";
		}else{
			$cmd = "M-LIST ".$this->path." ".$folder." ".$order." ".$sort."\r\n";
		}
		$res = $this->execute($cmd,1);
		$tmp = explode("\r\n",$res);
		$rct = explode(" ",$tmp[0]);
		if($rct[0]=='TRUE'){
			for($i=1;$i<(count($tmp)-1);$i++){
				$data[$i-1] = $tmp[$i];
			}
			return array('state'=>1,'counts'=>$rct[1],'data'=>$data);
		}else{
			return array('state'=>0,'error'=>trim($rct[1]));
		}
	}
	
	
	/**
     * 取出记录信息集
     *
     * @param string $folder 索引文件目录
     * @return string
     */
	function uidl($folder){
		$data = array();
		$cmd = "M-UIDL ".$this->path." ".$folder."\r\n";
		$res = $this->execute($cmd,1);
		$tmp = explode("\r\n",$res);
		$rct = explode(" ",$tmp[0]);
		if($rct[0]=='TRUE'){
			for($i=1;$i<(count($tmp)-1);$i++){
				$data[$i-1] = explode(" ", $tmp[$i]);
			}
			return array('state'=>1,'counts'=>$rct[1],'data'=>$data);
		}else{
			return array('state'=>0,'error'=>trim($rct[1]));
		}
	}

	/**
     * 取出记录信息集
     *
     * @param string $folder 索引文件目录
     * @param string $order 排序字段
     * @param string $sort 排序类型 asc dsc
     * @param int $offset 起始位置
     * @param int $length 取得记录数
     * @return string
     */
	function simplelisting($folder,$order,$sort,$offset="",$length=""){
		if($length){
			$cmd = "P-SIML ".$this->path." ".$folder." ".$order." ".$sort." ".$offset." ".$length."\r\n";
		}else{
			$cmd = "P-SIML ".$this->path." ".$folder." ".$order." ".$sort."\r\n";
		}
		$res = $this->execute($cmd,1);
		$tmp = explode("\r\n",$res);
		$rct = explode(" ",$tmp[0]);
		if($rct[0]=='TRUE'){
			for($i=1;$i<(count($tmp)-1);$i++){
				$data[$i-1] = $tmp[$i];
			}
			return array('state'=>1,'counts'=>$rct[1],'data'=>$data);
		}else{
			return array('state'=>0,'error'=>trim($rct[1]));
		}
	}

	function offset($folder,$id,$order,$sort){
		$cmd = "P-OFST ".$this->path." ".$folder." ".$id." ".$order." ".$sort."\r\n";
		$res = $this->execute($cmd,2);
		if($res[0]=='TRUE'){
            return array('state'=>1,'data'=>trim($res[1]));
        }else{
            return array('state'=>0,'error'=>trim($res[1]));
        }
	} 


	/**
     * 取出单条记录信息
     *
     * @param string $folder 索引文件目录
     * @param string $id 记录信息ID
     * @return string
     */
	function match($folder,$id){
		$cmd = "M-MTCH ".$this->path." ".$folder." ".$id."\r\n";
		$res = $this->execute($cmd,2);
		if($res[0]=='TRUE'){
			return array('state'=>1,'data'=>$res[1]);
		}else{
			return array('state'=>0,'error'=>trim($res[1]));
		}
	}

	/**
     * 取出指定目录邮件摘要信息
     *
     * @param string $folder 索引文件目录
     * @return string
     */
	function sum($folder){
		$cmd = "M-SUMY ".$this->path." ".$folder."\r\n";
		$res = $this->execute($cmd);
		if($res[0]=='FALSE'){
			return array('state'=>0,'error'=>$res[1]);
		}else{
			return array('state'=>1,'unread'=>$res[1],'total'=>$res[2]);
		}
	}

	/**
     * 取出邮箱容量信息
     *
     * @return string
     */
	function quta(){
		$cmd = "M-QUTA ".$this->path."\r\n";
		$res = $this->execute($cmd);
		if($res[0]=='FALSE'){
			return array('state'=>0,'error'=>$res[1]);
		}else{
			return array('state'=>1,'maxmails'=>trim($res[4]),'maxcapacity'=>trim($res[3]),'mails'=>trim($res[2]),'capacity'=>trim($res[1]));
		}
	}

	/**
     * 创建自定义文件夹
     *
     * @param string $folder 自定义文件夹名称(encoded)
     * @return string
     */
	function createfolder($folder){
		$cmd = "M-MAKF ".$this->path." ".$folder."\r\n";
		$res = $this->execute($cmd);
		if($res[0]=='FALSE'){
			return array('state'=>0,'error'=>$res[1]);
		}else{
			return array('state'=>1);
		}
	}

	/**
     * 删除自定义文件夹
     *
     * @param string $folder 自定义文件夹名称(encoded)
     * @return string
     */
	function delfolder($folder){
		$cmd = "M-REMF ".$this->path." ".$folder."\r\n";
		$res = $this->execute($cmd);
		if($res[0]=='FALSE'){
			return array('state'=>0,'error'=>$res[1]);
		}else{
			return array('state'=>1);
		}
	}

	/**
     * 重名名自定义文件夹
     *
     * @param string $oldfolder 原自定义文件夹名称(encoded)
     * @param string $newfolder 新自定义文件夹名称(encoded)
     * @return string
     */
	function renamefolder($oldfolder,$newfolder){
		$cmd = "M-RENF ".$this->path." ".$oldfolder." ".$newfolder."\r\n";
		$res = $this->execute($cmd);
		if($res[0]=='FALSE'){
			return array('state'=>0,'error'=>$res[1]);
		}else{
			return array('state'=>1);
		}
	}

	/**
     * 列出自定义文件夹信息
     *
     * @return string
     */
	function listfolder(){
		$cmd = "M-ENUM ".$this->path."\r\n";
		$res = $this->execute($cmd,1);
		$tmp = explode("\r\n",$res);
		$rct = explode(" ",$tmp[0]);
		if($rct[0]=='FALSE'){
			return array('state'=>0,'error'=>trim($rct[1]));
		}else{
			for($i=1;$i<(count($tmp)-1);$i++){
				$data[$i-1] = $tmp[$i];
			}
			return array('state'=>1,'counts'=>$rct[1],'data'=>$data);
		}
	}

	/**
     * 列出邮件文件夹信息
     *
     * @return string
     */
	function listmailbox(){
		$cmd = "M-INFO ".$this->path."\r\n";
		$res = $this->execute($cmd,1);
		$tmp = explode("\r\n",$res);
		$rct = explode(" ",$tmp[0]);
		if($rct[0]=='FALSE'){
			return array('state'=>0,'error'=>trim($rct[1]));
		}else{
			for($i=1;$i<(count($tmp)-1);$i++){
				$subtmp = explode(" ",$tmp[$i]);
				$data[$i-1]['folder'] = $subtmp[0];
				$data[$i-1]['unread'] = $subtmp[1];
				$data[$i-1]['total'] = $subtmp[2];
			}
			return array('state'=>1,'counts'=>$rct[1],'data'=>$data);
		}
	}

	/**
     * 列出指定目录中所有邮件ID
     *
     * @param string $folder 文件夹名称
     * @return array
     */
	function allmails($folder){
		$cmd = "M-UIDL ".$this->path." ".$folder."\r\n";
		$res = $this->execute($cmd,1);
		$tmp = explode("\r\n",$res);
		$rct = explode(" ",$tmp[0]);
		if($rct[0]=='FALSE'){
			return array('state'=>0,'error'=>trim($rct[1]));
		}else{
			for($i=1;$i<(count($tmp)-1);$i++){
				$subdata = explode(" ",$tmp[$i]);
				$data[$i-1]['mid'] = $subdata[0];
				$data[$i-1]['size'] = $subdata[1];
			}
			return array('state'=>1,'counts'=>$rct[1],'data'=>$data);
		}
	}

	/**
     * 检查邮箱是否已满
     *
     * @return array
     */
	function chkfull(){
		$cmd = "M-CKFL ".$this->path."\r\n";
		$res = $this->execute($cmd);
		if($res[0]=='FALSE'){
			return array('state'=>0,'error'=>$res[1]);
		}else{
			return array('state'=>1,'full'=>$res[1]);
		}
	}
}
