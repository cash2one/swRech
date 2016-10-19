<?php
namespace 
{
/*
 * event dispatch 
 * must be start with 0 and continue count
 * auther zzd
 */

DEFINE('EVENT_DATA_LOG'		,	0	);	//resp log save
DEFINE('EVENT_SERVER_START'	,	1	);	//resp server start
DEFINE('EVENT_SERVER_STOP'	,	2	);	//resp server stop
DEFINE('EVENT_BASE_SIZE'	,	3	);	//EVENT`S SIZE

class event
{
private	static	$events = array();

public	static	function setup()
{
	//@初始化事件类
	//@EVENT_DATA_NAME 事件名称宏定义
	self::$events = array_fill(0,EVENT_BASE_SIZE, array());
}

// add observer
public	static	function attach( $event, $callback, $key=null )
{
	if(!isset(self::$events[$event]) || !is_callable($callback))
	{
		echo 'event:'.$event.'-register fail'.PHP_EOL;
		return false;
	}
	
	if(is_null($key))
		self::$events[$event][]		= $callback;
	else
		self::$events[$event][$key]	= $callback;
	
	return true;
}

// remove observer
public	static	function detach( $event, $key )
{
	unset(self::$events[$event][$key]);
}

/* dispatch
* 派发事件 此函数不容错，所以参数必须传正确
* params event 派发的事件ID
* params contents 派发的数据 类型 array(arg1,arg2) 直接是回调函数的参数
* auther zzd
*/
public static	function dispatch( $event, $content )
{
	foreach (self::$events[$event] as $callback)
	{
		call_user_func_array( $callback, $content );
	}

	return true;
}


}
}

?>
