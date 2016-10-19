<?php
namespace task
{
/*
* task timer exec
* auther zzd
*/
class schedule extends \task_class
{

private	static $modules = array();
//@communication protocol with worker must set it and can`t repeat with other task
public	static	$protocol = WT_PROTOCOL_SCHEDULE;


//@模块注册
public	static	function reg_schedule($key, $callable, $params=array())
{
	if(!is_callable($callable) || !is_array($params) )
		return false;

	$modules[$key] = array( $callable, $params );
	
	return true;
}

//@模块注销
public	static	function unreg_schedule($key)
{
	unset($modules[$key]);	
	return true;
}

/*
*	must be covering parent simple method 
*/
public	static	function handler($serv, $fd, $pid=null, $args=null)
{
	if(empty(self::$modules))
	{
		return;
	}
		var_dump("响应定时器");
	foreach(self::$modules as $mdata)
	{
		//call_user_func_array($mdata[0],$params);
	}
	return;
}

}
}
?>
