<?php
Class Mysql
{
	private $conn = "";
	private $user;
	private $password;
	private $host;
	private $dbname;
	
	//构造函数
	function Mysql($param)
	{
		$this->user = $param['user'];
		$this->password = $param['password'];
		$this->host = $param['host'];
		$this->dbname = $param['dbname'];
		$this->conn = mysql_connect($this->host,$this->user,$this->password);
		mysql_select_db($this->dbname,$this->conn);
		return true;
	}

	//汇总数据记录
	function total($strSQL = "")
	{
		if(empty($strSQL))
		return false;
		if(empty($this->conn))
		return false;

		$conn = $this->conn;
		$results = mysql_query($strSQL,$conn);
		if(empty($results))
		return false;
		$totals = mysql_num_rows($results);
		return $totals;
	}

	//创建数据表
	function create_table($strSQL = "")
	{
		if(empty($strSQL)) return false;
		if(empty($this->conn)) return false;
		$conn = $this->conn;
		$results = mysql_query($strSQL,$conn);
		return $results;
	}

	//查询数据记录
	function select($strSQL = "")
	{
		if(empty($strSQL))
		return false;
		if(empty($this->conn))
		return false;

		$conn = $this->conn;
		$results = mysql_query($strSQL,$conn);
		if((!$results) or (empty($results)))
		{
			return false;
		}
		$num = 0;
		$data = array();
		while($row = mysql_fetch_array($results,MYSQL_ASSOC))
		{
			$data[$num] = $row;
			$num++;
		}
		mysql_free_result($results);
		return $data;
	}

	//插入数据记录
	function insert($strSQL = "")
	{
		if(empty($strSQL))
		return false;
		if(empty($this->conn))
		return false;
		$conn = $this->conn;
		$results = mysql_query($strSQL,$conn);
		if(!$results)
		return false;
		$results = mysql_insert_id();  //得到id记录号
		return $results;
	}

	//更新数据记录
	function update($strSQL = "")
	{
		if(empty($strSQL))
		return false;
		if(empty($this->conn))
		return false;
		$conn = $this->conn;
		$results = mysql_query($strSQL,$conn);
		return $results;

	}

	//删除数据记录
	function delete($strSQL = "")
	{
		if(empty($strSQL))
		return false;
		if(empty($this->conn))
		return false;
		$conn = $this->conn;
		$results = mysql_query($strSQL,$conn);
		return $results;
	}
	
	//关闭连接
	function close(){
		return mysql_close($this->conn);
	}
}
