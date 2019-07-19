<?php
/**
 * Action.class.php(URL路由类) by Jessica(Skiyo.cn)
 * 
 * 转载请注明出处.使用请留下注释.
 * 
 * 网站动作入口类.类似ThinkPHP.框架的核心之一.
 * URL:index.php/test 对应 action/TestAction
 * URL:index.php/test/page/1/kind/2 对应action/TestAction 
 * 接受参数例子(TestAction):
 * function action($_get){  //此处的参数相当于$_GET
 *		echo $_get['page'];  //输出1
 *      echo $_get['kind'];  //输出2
 * }
 * 
 */
class Action{

	/**
	 * Action请求
	 *
	 * @var string
	 */
	var $action;

	/**
	 * Action的文件名
	 *
	 * @var string
	 */
	var $actionFile;

	/**
	 * Action的类名
	 *
	 * @var string
	 */
	var $actionClass;

	/**
	 * 程序的路径
	 *
	 * @var string
	 */
	var $path;

	/**
	 * URL参数
	 *
	 * @var array
	 */
	var $param;

	/**
	 * 构造函数.
	 * 
	 * @return void
	 * @access public
	 * 
	 */
	public function __construct() {

	}

	/**
	 * 设置程序目录
	 *
	 * @param string $path
	 * @return void
	 * @access public
	 */
	public function setPath($path) {
		$this->path = $path;
	}

	/**
	 * 通过解析URL获得Action类名,执行Action类名的方法.
	 * 
	 * @return void
	 * @access public
	 *
	 */
	public function run(){
		if (empty($this->path)) {
			$this->throwException("没有设置程序目录!");
		}
		$this->parsePath();
		$this->getActionFile();
		$this->getActionClass();
	}
	/**
	 * 解析URL路径
	 * 
	 * @return void
	 * @access private
	 *
	 */
	private function parsePath(){
		list($path, $param) = explode("?", $_SERVER["REQUEST_URI"]);
		$param = explode("/",str_replace($this->path, '', $path));
		$this->param['action'] = $param[1];
		$this->param['method'] = $param[2];
	}

	/**
	 * 根据解析的URL获取Action文件
	 * 
	 * @return void
	 * @access private
	 *
	 */
	private function getActionFile(){
		$this->actionFile = APP_PATH."/controllers/".$this->param['action']."Controller.php";
		if(!file_exists($this->actionFile)) {
			$this->throwException("错误的请求，找不到Action文件(".$this->actionFile.")");
		} else {
			include_once($this->actionFile);
		}
	}

	/**
	 * 根据Action文件名获取Action类名并且执行
	 * 
	 * @return void
	 * @access private
	 *
	 */
	private function getActionClass(){
		$this->actionClass = $this->param['action']."Controller";
		if(!class_exists($this->actionClass)) {
			$this->throwException("错误的请求，找不到Action类(".$this->actionClass.")");
		} else {
			$newAction = new $this->actionClass();
			$this->runMethod($newAction,$this->param['method'].'Action');
		}
	}
	
	private function runMethod($action,$point){
		$action->$point();
	}

	/**
     * 抛出一个错误信息
     *
     * @param string $message
     * @return void
     */
	private function throwException($message) {
		throw new Exception($message);
	}
	
}