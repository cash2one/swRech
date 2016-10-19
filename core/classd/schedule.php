<?php
class schedule
{
/*
 * 固定 每分钟 每小时 每天 执行的操作 （ 误差 1分钟）
 * 
 */
//@秒级定时器 使用 $serv->tick(1000,自行维护)
private	static	$timer_id	= array();
private	static	$data_day	= array();
private	static	$data_hour	= array();
private	static	$data_minute	= array();
private static $nextDayTimeStamp = 0;
private static $nextHourTimeStamp = 0;

public	static	function setup($serv)
{
	$now = time();
	self::$nextDayTimeStamp 	= mktime(0,0,0,date('m',$now), date('j',$now)+1, date('Y',$now));
	self::$nextHourTimeStamp	= mktime(date('H',$now)+1,0,0,date('m',$now), date('j',$now), date('Y',$now));
	self::$timer_id[] = $serv->tick(60000		, array(__CLASS__, "on_minute") );
	//$timer_id[] = $serv->tick(3600000	, array(__CLASS__, "on_hour") );
	//$timer_id[] = $serv->tick(86400000	, array(__CLASS__, "on_day") );
}

public	static	function add_minute( $flag, $callable, $params, $persistent=true)
{
	$data_minute[$flag]	= array($callable, $params, $persistent);
}

public	static	function add_hour( $flag, $callable, $params, $persistent=true)
{
	$data_hour[$flag]	= array($callable, $params, $persistent);
}

public	static	function add_day( $flag, $callable, $params, $persistent=true)
{
	$data_day[$flag]	= array($callable, $params, $persistent);
}


public	static	function on_minute($timer_id, $params=null)
{
	$now = time();
	if(!empty(self::$data_minute))
	{
		foreach( self::$data_minute as $flag=>$tdata )
		{
			call_user_func_array($tdata[0], $tdata[1]);
			
			if($tdata[2])
				continue;
			else
				unset(self::$data_minute[$flag]);
		}
	}
	
	if(!empty(self::$data_hour) && self::$nextHourTimeStamp <= $now)
	{
		self::$nextHourTimeStamp	= mktime(date('H',$now)+1,0,0,date('m',$now), date('j',$now), date('Y',$now));
		foreach( self::$data_hour as $flag=>$tdata )
		{
			call_user_func_array($tdata[0], $tdata[1]);
		
			if($tdata[2])
				continue;
			else
				unset(self::$data_hour[$flag]);
		}
	}
	
	if(!empty(self::$data_day) && self::$nextDayTimeStamp <= $now )
	{
		self::$nextDayTimeStamp 	= mktime(0,0,0,date('m',$now), date('j',$now)+1, date('Y',$now));
		foreach( self::$data_day as $flag=>$tdata )
		{
			call_user_func_array($tdata[0], $tdata[1]);
		
			if($tdata[2])
					continue;
			else
				unset(self::$data_day[$flag]);
		}
	}
}

public	static	function on_hour($timer_id, $params=null)
{
	if(empty(self::$data_hour))
		return;

	foreach( self::$data_hour as $flag=>$tdata )
	{
		call_user_func_array($tdata[0], $tdata[1]);
		
		if($tdata[2])
			continue;
		else
			unset(self::$data_hour[$flag]);
	}
}

public	static	function on_day($timer_id, $params=null)
{
	if(empty(self::$data_day))
		return;

	foreach( self::$data_day as $flag=>$tdata )
	{
		call_user_func_array($tdata[0], $tdata[1]);
		
		if($tdata[2])
			continue;
		else
			unset(self::$data_day[$flag]);
	}
}



}
?>