<?php
define("CMD",	0);
define("SID",   1);
define("UID",	2);
define("PID",	3);
define("ARGS",	4);
class worker
{
private	static	$worker_id	= -1;
private	static	$task		= array();
private	static	$script		= array();
private	static	$commands	= array();
private	static	$requests	= array();
private	static	$connects	= array();
private	static	$temp_connects	= array();
private	static	$services	= array();
private	static	$serv		= null;

public	static	function get_worker_id()
{
	return self::$worker_id;
}

private	static	function check_login($serv, $fd, $message)
{
	if(SERVER_PCONNECT)
	{
		if( false != ($odata = get_online_data_by_fd($fd)) )
		{
			return $odata['pd'];
		}
		return 0;
	}
	elseif( false != ($odata = get_online_data_by_pid($message[PID])) )
	{
		if( $odata['fd'] == $fd )
			return $message[PID];
		
		return 0;
	}

	return self::login( $serv, $fd, $message);
}

private	static	function login( $serv, $fd, $data )
{
	if( !$pid = dealOtherDb("hget", "UID_PID", $data[UID]) )
	{
		$pid = "s".time().rand(10000,99999);

		if( !dealOtherDb("hset", "UID_PID", $data[UID],  $pid) )
			return fasle;
	}
	elseif( false != ($odata = get_online_data_by_pid($pid)) )
	{
		if( $odata['fd'] == $fd )
			return $pid;
		
		if( !$serv->close($odata['fd']) )
			self::deal_close_data($odata['fd']);
	}

	if(!set_online_data($fd,$pid,$serv->worker_id))
	{
		write_log("login","set_online_data err worker_id:".$serv->worker_id." fd:".$fd." pid:".$pid);
		return false;
	}
	
	return $pid;
}

//@处理上行协议
public	static	function deal_command($serv, $fd, $from_id, $data)
{
	if (!get_server_status())
	{
		$serv->close();
		return;
	}
	if (!$message = call_user_func_array(array(BASE_PROTOCOL,'decode'),array($data)))
	{
		CMD_DEBUG && var_dump('decode err !!');
		self::deal_close($serv, $fd);
		return;
	}
	
	CMD_DEBUG && write_log('command_data', 'worker_id:' . $serv->worker_id . ' fd:' . $fd . PHP_EOL . $data);
	CMD_DEBUG && write_log('command_data', $message);
		
	if( isset(self::$commands[$message[CMD]][0]) && count($message[ARGS]) == self::$commands[$message[CMD]][1])
	{
		$cmd = self::$commands[$message[CMD]][0];

		$cmd::handler($serv, $fd, $message[PID], $message[ARGS]);
	}
	else
	{
		$serv->close($fd);
	}
}

//@响应HTTP 请求
public	static	function deal_request($request,$response)
{
	if(!get_server_status())
	{
		$response->end(500);
		return;
	}

	if(!isset($request->header['x-real-ip']))
	{
		$response->end(501);
		return;
	}

	$tcmd = $request->server['request_uri'];

	$save = 'ip:['.($request->header['x-real-ip']).']-uri:'.$tcmd." ";
	
	if(isset(self::$requests[$tcmd][0]))
	{
		if(isset($request->get))
			CORE_DEBUG && write_log('request', $save.http_build_query($request->get));
		else
			CORE_DEBUG && write_log('request', $save.$request->rawContent());

		$cmd = self::$requests[$tcmd][0];
		$cmd::handler($request,$response);
	}
	else
	{
		CORE_DEBUG && write_log('request', 'err:'.$save );
		$response->status(404);
		$response->end('');
	}
}

//@给task推送数据
public	static	function task_push_by_tid($serv, $data, $tid, $callable = false, $callpass=null)
{
	if( $callable )
	{
	 	if( !is_callable($callable) )
			return false;
		
		$task_id = $serv->task($data, $tid);
		
		self::$task[$task_id] = array($callable, $callpass);
	}
	else
	{
		$serv->task($data, $tid);
	}
	
	return true;
}

//@给task推送数据
public	static	function task_push($data, $callable = false, $callpass=null)
{
	if( $callable )
	{
	 	if( !is_callable($callable) )
			return false;
		
		$task_id = self::$serv->task($data);
		
	var_dump('add',self::$serv->worker_id,$task_id);
		self::$task[$task_id] = array($callable, $callpass);
	}
	else
	{
		self::$serv->task($data);
	}
	
	return true;
}

//@task完成任务啦
public	static	function task_finish($serv, $task_id, $data)
{
	if(isset(self::$task[$task_id]))
	{
		call_user_func_array(self::$task[$task_id][0], array($serv, $data, self::$task[$task_id][1]));
		unset(self::$task[$task_id]);
		echo "Finish tid:".(self::$serv->worker_id).'_'.$task_id."\n";
	}
}

public	static	function test($ret,$size)
{
	var_dump('recv',$ret,$size,rand(1,9999));
}
//@worker start
public	static	function start($serv, $worker_id)
{
	//\dbmysql::setup($serv);
	self::$worker_id	= $worker_id;
	self::$serv		= $serv;
	$sms = new clSms();
	\sms::setup($sms);
	
	if($worker_id == 0)
	{
		//var_dump(\async_mysql::setup($serv));
		ksort(self::$commands);
		write_log("init_worker_command", self::$commands);
		write_log('init_worker_service', self::$services);
		write_log('init_worker_request', self::$requests);
		//$serv->after(3000,function(){\async_mysql::do_sql('show tables;', function($res,$t){var_dump($res,$t);}, 444);});
	//	\async_http::do_http('127.0.0.1','/test.php',array(1,'a'=>1,4),function($data){ echo $data.'--asdazzz'.PHP_EOL;});
	}

	\async_http::setup();

	//@随机一个开始时间进行全链接攻击检测
	$serv->after(rand(2000,8000), array(__CLASS__, "start_deal_connect_timeout") );

	return true;
}

/*
* 响应worker关闭事件，并做响应的处理
*/
public	static	function stop($serv, $worker_id)
{
	foreach(self::$connects as $fd=>$pid)
	{
		call_client_func_fd($fd,array(9999,array(123131,453535)));
	}

	foreach(self::$services as $service)
	{
		if(!$service::crash())
			write_warn('service', 'worker:'.$worker_id.' close fail :'.$service);
	}

}

/*
* 处理客户端发起的链接
*/
public	static	function deal_connect($serv, $fd, $from_id)
//public        static  function deal_connect()
{
	if(rand(1,10) > 7)
	write_log("connect", $fd." connect");
	self::$temp_connects[$fd] = 2;
	
/*	var_dump("connect:".$fd,self::$worker_id,time());
	write_log("connect", $fd);
*/
}

/*
* 开始定时检测客户端链接不发消息的情况
*/
public	static	function start_deal_connect_timeout()
{
	self::$serv->tick(5000, array(__CLASS__, "deal_connect_timeout") );
}

/*
* 检测链接后一定时间内不发送登录消息的链接并断开
*/
public	static	function deal_connect_timeout()
{
	if(empty(self::$temp_connects))
		return;

	foreach(self::$temp_connects as $k=>$v)
	{
		$v = $v>>1;

		if($v)
		{
			self::$temp_connects[$k] = $v;
		}
		else
		{
			global $serv;
			$serv->close($k);
				
		}
	}
}

public	static	function deal_close($serv, $fd)
//public        static  function deal_close()
{
	CMD_DEBUG && write_log("connect", $fd." close");
	if(isset(self::$temp_connects[$fd]))
	{
		unset(self::$temp_connects[$fd]);
		return true;
	}
	
	//self::deal_close_data($fd);
	return true;
}

public	static	function deal_close_data($fd)
{
	del_online_data($fd);

	//@内存中没有玩家数据的处理方式，如果内存中有玩家数据需要存储，需要修改关闭链接方式
//	dealOnlineDb("evalsha", DOUBLE_RELATE_DEL_LUA, array("fd", $fd), 2 );
	//if(dealOnlineDb())
//	var_dump($fd."离线了a",self::$worker_id,time());

}		

//*注册客户端指令
public	static	function register_command(array $c_data)
{
	if(!isset($c_data[0]) || !isset($c_data[1]))
	{
		var_dump("reg_comd err",$c_data);
		return false;
	}

	if(is_int($c_data[0]))
	{	
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
	}
	else
	{
		if(isset(self::$requests[$c_data[0]]))
		{
			var_dump(array("协议号重复啦",$c_data));
			return false;
		}
		
		if(array_search($c_data[1], self::$requests) !== false)
		{
			var_dump(array("协议的功能重复啦",$c_data));
			return false;
		}

		self::$requests[$c_data[0]] = $c_data[1];

	}
	return true;
}

public	static	function register_service($service)
{
	self::$services[] = $service;
	return true;
}


}
