<?php
/*
* 执行worker派发的任务
*/
namespace
{
class task
{
private	static	$worker_id = -1;
private	static	$commands = array();
private	static	$services = array();


//@处理任务
public	static	function deal_task($serv, $task_id, $from_id, $data)
{
	if(isset(self::$commands[$data[0]]))
	{
		$cmd = self::$commands[$data[0]];
		$result = $cmd::handler($serv, $task_id, $from_id, $data[1]);
		return $result;
	}
	else
	{
		var_dump(array("未定义task",$data[0]));
		return false;
	}
}

//@task start
public	static	function start($serv, $worker_id)
{
	//\dbmysql::setup($serv);
	self::$worker_id = $worker_id;

	//@选择加载service
	
	if($worker_id == $serv->setting["worker_num"])
	{
		ksort(self::$commands);
		write_log("init_task_command", self::$commands);
		write_log('init_task_service', self::$services);
	}

	return true;
}

public  static  function stop($serv, $worker_id)
{
	self::$commands = null;
	//回收资源

	foreach(self::$services as $service)
	{
		if(!$service::crash())
			write_warn('service', 'worker:'.$worker_id.' close fail :'.$service);
	}
}

//*注册task指令
public	static	function register_command(array $c_data)
{
	if(!isset($c_data[0]) || !isset($c_data[1]))
	{
		var_dump("reg_comd err",$c_data);
		return false;
	}
	
	if(isset(self::$commands[$c_data[0]]))
	{
		var_dump(array("协议号重复啦",$c_data));
		return false;
	}
	
	if(array_search($c_data[1], self::$commands) !== false)
	{
		var_dump(array("协议的功能重复啦",$c_data));
		return false;
	}

	self::$commands[$c_data[0]] = $c_data[1];

	return true;
}

public	static	function register_service($service)
{
	self::$services[] = $service;
	return true;
}


}
}
?>
