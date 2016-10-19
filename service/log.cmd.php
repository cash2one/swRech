<?php
/**
 * 异步回存LOG
 * 限定worker使用
 * @author zzd
 */
namespace service 
{
class log_cmd extends \service_class
{
//@定义内部变量存储一些数据
//@private	static	$args;
private	static	$worker;
private	static	$logs;
private	static	$timeLimit;
private	static	$logStatus;
private	static	$logCache;


/***************************************************************************
** 系统自启动方法
*/

//@类系统方法-服务初始化时调用
public	static	function setup()
{
	self::$timeLimit	= intval(LOG_SAVE_MIN_LIMIT_TIME)/1000+5;
	self::$logStatus	= array();
	self::$logCache		= array();

	if( !$logs = getLogDefine() )
		return 0;

	if(!\event::attach(EVENT_DATA_LOG, __CLASS__.'::make_log'))
		return 0;
	
	parent::register(__CLASS__);

	foreach($logs as $name => $logDefine)
	{
		if ( !$logDefine )
			return 0;

		$column	= $logDefine['column'];
		sort($column);

		$logs[$name]['column'] = $column;
		self::$logStatus[$name]	= time();
		self::$logCache[$name]	= array();
	}

	self::$logs = $logs;

	global $serv;

	$serv->after(1000+500*$serv->worker_id, function()use($serv){$serv->tick(LOG_SAVE_MIN_LIMIT_TIME,array(__CLASS__,'resp_time_save'));});

	return 1;
}

//@类系统方法-当crash时被自动调用
public	static	function crash()
{
	if(empty(self::$logStatus))
		return 1;
	
	$names	= array_keys(self::$logStatus);

	foreach($names as $name)
	{
		self::send_log($name, 1);
	}

	return 1;
}

/***************************************************************************
** 类公共方法
*/

/*类方法-生成Log
* 生成存储LOG
* param $name tablename index
* param $value array(value1,value2....value3); 必须和数据库的字段对应，无序的需先按可以升序排列
* date	2016-04-27
* auther zzd
*/
public	static   function  make_log( $name, $value )
{
	if(!isset(self::$logs[$name]))
	{
		write_log('cmd_log_err', '未定义name:'.$name);
		return false;
	}

	$logDefine       = self::$logs[$name];

	if ( count($value) != count($logDefine["column"]) )
	{
		write_log('cmd_log_err', json_encode(array($name, $value)));
		return false;
	}
	
	ksort($value);
	$value	= array_values($value);
		
	self::$logCache[$name][]	= $value;

	if ( count(self::$logCache[$name]) >= $logDefine["cacheCount"])
	{
		self::send_log($name, 0);
	}

	return true;
}

public	static	function resp_time_save()
{
	$key	= 0;
	$nowf	= time()-self::$timeLimit;

	asort(self::$logStatus);

	foreach(self::$logStatus as $name=>$time)
	{
		if($nowf > $time)
		{
			if(!empty(self::$logCache[$name]))
			{
				$key = 1;
				break;
			}
		}
	}

	if($key)
	{
		self::send_log($name,0);
	}
}

public	static	function resp_log_send($result,$count,$effect=0, $sql=null)
{
	if($result && ($count != $effect))
		write_log('log_save_err', array($sql,$count,$effect));
}

/***************************************************************************
** 类私有方法
*/

/*
* 类私有方法-发送log缓存中的事件
* 平时异步存
* crash 时同步存
*/
private	static	function send_log($name, $cache)
{
	if( !count(self::$logCache[$name]) )
               	return false;

	$logDefine		= self::$logs[$name];
	$sendData		= self::$logCache[$name];
	self::$logCache[$name]	= array();
	self::$logStatus[$name]	= time();

	if(!$cache)
		if(\service\mysql::insertLogDB($logDefine['table'], $logDefine["column"], $sendData, array(array(__CLASS__,'resp_log_send'),count($sendData))))
			return true;
	
	\service\mysql::insertLogDB($logDefine["table"], $logDefine["column"], $sendData);

	return true;
}

}
}

?>
