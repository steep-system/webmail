<?php
class WebMail_Model_MailEncode extends WebMail_Model_Base
{
	protected $charset = "utf-8";
	public $from;
	public $to;
	public $cc;
	public $sender;
	public $subject;
	public $content;
	public $attache;
	public $innerimg;
	public $header;
	public $body;
	public $mailcontent;
	public $extra;
	public $plain;
	public $xpriority;
	public $notification;

	function __construct(){
		 @ini_set('memory_limit', '1024M');
	}

	public function setCharset($charset){
		$this->charset = $charset;
	}

	public function setFrom($from){
		$this->from = $from;
	}

	public function setTo($to){
		$this->to = $this->formatEmailAddress($to);
	}

	public function setCc($cc){
		$this->cc = $this->formatEmailAddress($cc);
	}

	public function setSender($sender){
		$this->sender = $sender;
	}

	public function setSubject($subject){
		$this->subject = $subject;
	}

	public function setContent($content){
		$this->content = $content;
	}

	public function setAttache($attache){
		$this->attache = $attache;
	}

	public function setInnerimg($innerimg){
		$this->innerimg = $innerimg;
	}

	public function setExtra($extra){
		$this->extra = $extra;
	}

	public function setXpriority($xpriority){
		$this->xpriority = $xpriority;
	}

	public function setNotification($notification){
		$this->notification = $notification;
	}

	public function save($mail){
		$this->setFrom($mail->from);
		$this->setTo($mail->to);
		$this->setCc($mail->cc);
		$this->setContent($mail->content);
		$this->setSender($mail->from);
		$this->setSubject($mail->subject);
		$this->setAttache($mail->attachments);
		$this->setInnerimg($mail->htmlimage);
		$this->setXpriority($mail->xpriority);
		$this->setNotification($mail->notification);
		//$this->setExtra($extra);
		$this->plain = 0;
		if($mail->istext){
			$this->plain = 1;
		}
		$mime = $this->setMail();
		return $mime;
	}

	public function setMail(){
		$mimeheader = $this->setHeader();
		$boundary = $mime = "";
		if(!empty($this->attache)){
			$mimetype = "Content-Type: multipart/mixed;"."\r\n";
			$mixed_boundary = $this->getBoundary();
			$mimetype.="\t".'boundary="'.$mixed_boundary.'"'."\r\n"."\r\n";
			$mime = $mimetype;
			$boundary = $mixed_boundary;
		}

		if(!empty($this->innerimg)){
			$this->plain = 0;
			if(!empty($mixed_boundary)){
				$mime.="--".$mixed_boundary."\r\n";
			}
			$mimetype = "Content-Type: multipart/related;"."\r\n";
			$relate_boundary = $this->getBoundary();
			$mimetype.="\t".'boundary="'.$relate_boundary.'"'."\r\n"."\r\n";
			$mime = $mime.$mimetype;
			$boundary = $relate_boundary;
		}
		
		$mimebody = $this->setBody();
		if(!empty($boundary)){
			$mime.="--".$boundary."\r\n";
		}
		$mime.=$mimebody;
		
		if(!empty($this->innerimg)){
			for ($i=0;$i<count($this->innerimg);$i++){
				$mime.="\r\n";
				$mime.="--".$relate_boundary."\r\n";
				$mime.=$this->putInnerImg($this->innerimg[$i]);
			}
			if(!empty($relate_boundary)){
				$mime.="\r\n";
				$mime.="--".$relate_boundary."--\r\n";
			}
		}

		if(!empty($this->attache)){
			for ($i=0;$i<count($this->attache);$i++){
				$mime.="\r\n";
				$mime.="--".$mixed_boundary."\r\n";
				$mime.=$this->putAttache($this->attache[$i]);
			}
			if(!empty($mixed_boundary)){
				$mime.="\r\n";
				$mime.="--".$mixed_boundary."--\r\n";
			}
		}
		
		$mail = $mimeheader."\r\n".$mime;
		return $mail;
	}

	public function setHeader(){
		list($usec, $sec) = explode(" ", microtime());
		$msgid = (float)$usec + (float)$sec;
		$header.=$this->processMailAddress("From",$this->from)."\r\n";
		if(!empty($this->to)){
			$header.=$this->processMailAddress("To",$this->to)."\r\n";
		}
		if(!empty($this->cc)){
			$header.=$this->processMailAddress("Cc",$this->cc)."\r\n";
		}
		$header.="Subject: ".$this->setEncode($this->subject)."\r\n";
		$header.="Message-ID: <".$msgid.".".$this->from[0]['mail'].">\r\n";
		$header.="X-Mailer: Pandora ".PANDORA_SYSTEM_VERSION."\r\n";
		if($this->xpriority){
			$header.="X-Priority: ".$this->xpriority."\r\n";
		}
		if($this->notification){
			$header.=$this->processMailAddress("Disposition-Notification-To",$this->from)."\r\n";
		}
		$header.="Mime-Version: 1.0\r\n";
		$header.="Date: " . date(DATE_RFC2822, time());
		return $header;
	}

	public function setBody(){
		if($this->plain){
			$mimestr.=$this->setMailContent($this->content,"text/plain");
			$mimestr.="\r\n";
		}else{
			$content_boundary = $this->getBoundary();
			$mimestr.='Content-Type: multipart/alternative;'."\r\n\t".'boundary="'.$content_boundary.'";'."\r\n";
			$mimestr.="\r\n";
			$mimestr.="--".$content_boundary."\r\n";
			$mimestr.=$this->setMailContent($this->content,"text/plain");
			$mimestr.="\r\n";
			$mimestr.="--".$content_boundary."\r\n";
			$mimestr.=$this->setMailContent($this->content,"text/html");
			$mimestr.="\r\n";
			$mimestr.="--".$content_boundary."--\r\n";
		}
		return $mimestr;
	}

	/**
     * 设置邮件正文
     *
     * @param string $content 
     * @param string $type
     * @return string
     */
	public function setMailContent($content,$type="text/html"){
		$content = iconv('utf-8',$this->charset."//TRANSLIT",$content);
		if($type=="text/plain"){
			$content = str_replace("<BR>","\r\n",$content);
			$content = str_replace("&nbsp;"," ",$content);
			$content = str_replace("\t","    ",$content);
			$content = strip_tags($content);
		}
		$mimestr.='Content-Type: '.$type.';'."\r\n\t".'charset="'.$this->charset.'"'."\r\n";
		$mimestr.="Content-Transfer-Encoding: base64\r\n";
		$mimestr.="\r\n";
		$mimestr.=$this->divideString(base64_encode($content),"\r\n");
		$mimestr.="\r\n";
		return $mimestr;
	}

	/**
     * 设置邮件附件
     *
     * @param string $filepath 
     * @return string
     */
	public function putAttache($file){
		$mimestr.='Content-Type: '.$this->getMimeType($file['filename']).';'."\r\n\t".'name="'.$this->setEncode($file['filename']).'"'."\r\n";
		$mimestr.='Content-Disposition: attachment;'."\r\n\t".'filename="'.$this->setEncode($file['filename']).'"'."\r\n";
		$mimestr.='Content-Transfer-Encoding: base64'."\r\n";
		$mimestr.="\r\n";
		$mimestr.=$this->divideString(base64_encode(file_get_contents($file['filepath'])),"\r\n");
		return $mimestr;
	}

	/**
     * 设置邮件内嵌图片
     *
     * @param string $filepath 
     * @return string
     */
	public function putInnerImg($file){
		$ext = preg_replace('/^.*\./', '', basename($file['filename']));
		$mimestr.='Content-Type: '.$this->getMimeType($file['filename']).';'."\r\n\t".'name="'.$this->setEncode($file['filename']).'"'."\r\n";
		$mimestr.='Content-Transfer-Encoding: base64'."\r\n";
		$mimestr.='Content-ID: <'.$file['cid'].'>'."\r\n";
		$mimestr.="\r\n";
		$mimestr.=$this->divideString(base64_encode(file_get_contents($file['filepath'])),"\r\n");
		return $mimestr;
	}

	/**
     * 字符串编码
     *
     * @param string $val 
     * @param string $charset
     * @param string $codetype
     * @return string
     */
	public function setEncode($val,$codetype='B'){
		$encodestr = "";
		if(!empty($val)){
			//转换编码
			$val = iconv('utf-8',$this->charset,$val);
			if($codetype=='B'){
				$encodestr = "=?".$this->charset."?B?".base64_encode($val)."?=";
			}elseif ($codetype=="Q"){
				$encodestr = "=?".$this->charset."?Q?".quoted_printable_encode($val)."?=";
			}
		}
		return $encodestr;
	}

	/**
     * 邮件地址字符串处理
     *
     * @param string $field 
     * @param string $val
     * @return string
     */
	public function processMailAddress($field,$val){
		$mailaddress = $field.": ";
		$lastlen = strlen($mailaddress);
		for ($i=0;$i<count($val);$i++){
			if(!empty($val[$i]['mail'])){
				if(empty($val[$i]['name'])){
					$tmp = $val[$i]['mail'];
				}else{
					$tmp = $this->setEncode($val[$i]['name'])." <".$val[$i]['mail'].">";
				}
				if($i<(count($val)-1)){
					$tmp.=";";
				}
				$mailaddress.=$tmp;
				if($lastlen>76&&($i<(count($val)-1))){
					$lastlen = 0;
					$mailaddress.="\r\n\t";
				}else{
					$lastlen+=strlen($tmp);
				}
			}
		}
		return $mailaddress;
	}

	function formatEmailAddress($str){
		$str = trim($str);
		if(substr($str,-1,1)==";")$str = substr($str,0,-1);
		$tmp = explode(";",$str);
		for ($i=0;$i<count($tmp);$i++){
			if(!empty($tmp[$i])){
				$t = explode("<",$tmp[$i]);
				if(count($t)>1){
					$address[$i]['mail'] = trim(str_replace(">","",$t[1]));
					$address[$i]['name'] = trim($t[0]);
				}else{
					$address[$i]['mail'] = trim($tmp[$i]);
					$address[$i]['name'] = '';
				}
			}
		}
		return $address;
	}

	/**
     * 字符串分割处理
     *
     * @param string $str
     * @return string
     */
	public function divideString($str,$mark="\r\n\t"){
		if(strlen($str)>76){
			for ($i=0;$i<strlen($str);$i+=76){
				$dividedstr.=substr($str,$i,76).$mark;
			}
			return $dividedstr;
		}else{
			return $str;
		}
	}

	public function getBoundary(){
		$str = "0123456789ABCDEF";
		$str = str_split($str);
		$boundary = "----=_NextPart_";
		for ($i=1;$i<=3;$i++){
			for ($j=1;$j<=8;$j++){
				$boundary.=$str[rand(0,count($str))];
			}
			if($i<=2){
				$boundary.="_";
			}
		}
		return $boundary;
	}

	/**
     * 取得附件文件mime类型
     *
     * @param string $filename	文件名	
     * @return void
     */
	function getMimeType($filename) {
		$mime_types = array(
		'txt' => 'application/plain',
		'htm' => 'application/html',
		'html' => 'application/html',
		'php' => 'application/html',
		'css' => 'text/css',
		'js' => 'application/javascript',
		'json' => 'application/json',
		'xml' => 'application/xml',
		'swf' => 'application/x-shockwave-flash',
		'flv' => 'video/x-flv',

		// images
		'png' => 'image/png',
		'jpe' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'jpg' => 'image/jpeg',
		'gif' => 'image/gif',
		'bmp' => 'image/bmp',
		'ico' => 'image/vnd.microsoft.icon',
		'tiff' => 'image/tiff',
		'tif' => 'image/tiff',
		'svg' => 'image/svg+xml',
		'svgz' => 'image/svg+xml',

		// archives
		'zip' => 'application/zip',
		'rar' => 'application/x-rar-compressed',
		'exe' => 'application/x-msdownload',
		'msi' => 'application/x-msdownload',
		'cab' => 'application/vnd.ms-cab-compressed',

		// audio/video
		'mp3' => 'audio/mpeg',
		'qt' => 'video/quicktime',
		'mov' => 'video/quicktime',

		// adobe
		'pdf' => 'application/pdf',
		'psd' => 'image/vnd.adobe.photoshop',
		'ai' => 'application/postscript',
		'eps' => 'application/postscript',
		'ps' => 'application/postscript',

		// ms office
		'doc' => 'application/msword',
		'rtf' => 'application/rtf',
		'xls' => 'application/vnd.ms-excel',
		'ppt' => 'application/vnd.ms-powerpoint',

		// open office
		'odt' => 'application/vnd.oasis.opendocument.text',
		'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
		);

		$ext = strtolower(array_pop(explode('.',$filename)));
		if (array_key_exists($ext, $mime_types)) {
			return $mime_types[$ext];
		}
		elseif (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME);
			$mimetype = finfo_file($finfo, $filename);
			finfo_close($finfo);
			return $mimetype;
		}
		else {
			return 'application/octet-stream';
		}
	}

	/**
     * 邮件地址格式化
     *
     * @param string $address	邮件地址
     * @return array
     */
	public function addressFormat($address){
		if(strpos($address,";")){
			$address = substr($address,0,-1);
			$address = split(";",$address);
			for ($i=0;$i<count($address);$i++){
				if(strpos($address[$i],"<")){
					$tmp = split("<",$address[$i]);
					$formated[$i]['name'] = $tmp[0];
					$formated[$i]['mail'] = str_replace(">","",$tmp[1]);
				}else{
					$tmp = split("@",$address[$i]);
					$formated[$i]['name'] = $tmp[0];
					$formated[$i]['mail'] = $address[$i];
				}
			}
		}else{
			$tmp = split("@",$address);
			$formated[0]['name'] = $tmp[0];
			$formated[0]['mail'] = $address;
		}
		return $formated;
	}
}
