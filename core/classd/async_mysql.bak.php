<?php

class async_mysql
{
private	static	$game_host;
private	static	$game_user;
private static	$game_pass;
private	static	$game_port;
private	static	$game_dbname;
private	static	$log_host;
private	static	$log_user;
private static	$log_pass;
private	static	$log_port;
private	static	$log_dbname;

protected static $pool_size = M_ASUNC_MYSQL_COUNT;
protected static $idle_pool = array(); //空闲连接
protected static $busy_pool = array(); //工作连接
protected static $wait_queue = array(); //等待的请求
protected static $wait_queue_max = 100; //等待队列的最大长度，超过后将拒绝新的请求
protected static $pre_connect = array();
protected static $serv;
/////异步mysql////////////////////////////////////////////////////

private static function connect($host,$user,$pass,$db)
{
	$mysqli	= mysqli_init();

	if(!$mysqli)
	{
		write_log('dbconnect_err', array('init', $host,$user,$pass,$db));
		return false;
	}

	if(!$mysqli->options(MYSQLI_INIT_COMMAND, 'SET  NAMES UTF8'))
	{
		write_log('dbconnect_err', array('set auto', $host,$user,$pass,$db));
		return false;
	}

	if (!$mysqli->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1))
	{
		write_log('dbconnect_err', array('set int and float', $host,$user,$pass,$db));
		return false;
	}

	if (!$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5))
	{
		write_log('dbconnect_err', array('set timeout', $host,$user,$pass,$db));
		return false;
	}

	if(!$mysqli->real_connect($host,$user,$pass,$db))
	{
		write_log('dbconnect_err', array('real_connect', $mysqli->connect_errno, $host,$user,$pass,$db));
		return false;
	}

	return $mysqli;
}

private static	function new_connect($host,$user,$pass,$db)
{
	if(!$mysqli= self::connect(self::$log_host, self::$log_user, self::$log_pass, self::$log_dbname))
		return false;
	
	$db_sock = swoole_get_mysqli_sock($mysqli);
	swoole_event_add($db_sock, array(__CLASS__, 'on_sql_ready'));
	write_log('nsql', $db_sock.'_'.(self::$serv->worker_id));
	return array( 'mysqli' => $mysqli, 'db_sock' => $db_sock, 'callback' => null, 'time'=>time());
}

public static function on_start($serv)
{
	self::$serv = $serv;
	
	for ($i = 0; $i < self::$pool_size; ++$i)
	{
		if( !$db = self::new_connect(self::$log_host, self::$log_user, self::$log_pass, self::$log_dbname))
			return false;
	
		self::$idle_pool[] = $db;
	}
	
	return true;
}


public static function on_sql_ready($db_sock)
{
	if(!isset(self::$busy_pool[$db_sock]))
	{
		//swoole_event_del($db_sock);
		foreach(self::$idle_pool as $kk => $db)
		{
			if($db['db_sock'] == $db_sock)
			{
				self::reconnect($db);
				unset(self::$idle_pool[$kk]);
				self::$idle_pool[$kk] = $db;
				return;
			}
		}

		//swoole_event_del($db_sock);
		write_log('nofdresp', $db_sock);
		
		return;
	}

	$db_res		= self::$busy_pool[$db_sock];
	$mysqli		= $db_res['mysqli'];
	$callback	= $db_res['callback'];
	$transfer	= $db_res['transfer'];
	$sql		= $db_res['sql'];
	//    echo __METHOD__ . ": client_sock=$callback|db_sock=$db_sock\n";
	$result = $mysqli->reap_async_query();

	if(is_object($result))
	{
		$ret = $result->fetch_all(MYSQLI_ASSOC);
		
		mysqli_free_result($result);

		if(!is_null($callback))
		{
			call_user_func_array($callback, array($ret, $db_res['transfer']));
		}
	}
	else
	{
		if($result)
		{
			if(!is_null($callback))
				call_user_func_array($callback, array($result,$db_res['transfer'],$mysqli->affected_rows, $db_res['sql']));
		}
		else
		{
			if(!is_null($callback))
				call_user_func_array($callback, array($result,$db_res['transfer']));
			write_log('adb_query_err', 'resp:'.$mysqli->errno.':'.$mysqli->error.PHP_EOL.'sql:'.($db_res['sql']).PHP_EOL);

			if ($mysqli->errno == 2013 or $mysqli->errno == 2006)
			{ //@链接异常重连
				write_log('async_mysql_query_err', self::$serv->worker_id);
				self::$pre_connect[] = $db;
				self::after(2000, array(__CLASS__,'check_mysql'));
				unset(self::$busy_pool[$db_sock], $db_res['callback'],$db_res['sql'],$db_res['transfer']);
				return;
			}
		}
	}
	//release mysqli object
	unset(self::$busy_pool[$db_sock], $db_res['callback'],$db_res['sql'],$db_res['transfer']);

	self::$idle_pool[] = $db_res;
	//这里可以取出一个等待请求
	self::redo_sql();
}

private static	function redo_sql()
{
	if (count(self::$wait_queue) > 0)
	{
		$idle_n = count(self::$idle_pool);

		for ($i = 0; $i < $idle_n; ++$i)
		{
			$req = array_shift(self::$wait_queue);

			if(!is_array($req))
				break;

			self::do_query($req['sql'], $req['callback'], $req['transfer']);
		}
	}

}

public static function do_sql($sql, $callback, $transfer=null)
{
	write_log('amysql',$sql);
	if(empty(self::$idle_pool))
	{
	        if (!empty(self::$busy_pool) && count(self::$wait_queue) < self::$wait_queue_max)
	        {
	    		self::$wait_queue[] = array
	    		(
	    			'callback'	=> $callback,
	    			'sql'		=> $sql,
	    			'transfer'	=> $transfer
	    		);
	        }
	        else
	        {
			write_log('more_sql', $sql);
	        }
	}
	else
	{
	        self::do_query($sql, $callback, $transfer);
	}

	return true;
}

private static	function reconnect(&$db)
{
	$db_sock = $db['db_sock'];
	$db['mysqli']->close();

	swoole_event_del($db_sock);

	if(!$mysqli = self::connect(self::$log_host, self::$log_user, self::$log_pass, self::$log_dbname))
		return false;

	$db_sock	= swoole_get_mysqli_sock($mysqli);

	swoole_event_add($db_sock, array(__CLASS__, 'on_sql_ready'));
	write_log('nrsql', $db_sock.'_'.(self::$serv->worker_id));
	$db['mysqli']	= $mysqli;
	$db['db_sock']	= $db_sock;
	$db['time']	= time();

	return $mysqli;
}

public static function do_query($sql, $callback, $transfer)
{
	//从空闲池中移除
	$db = array_pop(self::$idle_pool);
	$mysqli = $db['mysqli'];

	for ($i = 0; $i < 2; ++$i)
	{
		$result = $mysqli->query($sql, MYSQLI_ASYNC);
		
		if ($result === false)
		{
			if ($mysqli->errno == 2013 or $mysqli->errno == 2006)
			{
			    	if ($mysqli = self::reconnect($db))
				{
					continue;
				}
			}

			self::$pre_connect[] = $db;
			self::after(2000, array(__CLASS__,'check_mysql'));
			self::do_sql($sql, $callback, $transfer);
			return false;
		}
		break;
	}

	$db['callback'] = $callback;
	$db['transfer'] = $transfer;
	$db['sql']	= $sql;
	$db['time']	= time();
	//加入工作池中
	self::$busy_pool[$db['db_sock']] = $db;
}

/////////////////////////////////////////////////////////////////////////////////////
public	static	function setup($serv)
{//@目前只支持LOG库
	self::$log_host		= DB_MYSQL_LOG_HOST;
	self::$log_user		= DB_MYSQL_LOG_USER;
	self::$log_pass		= DB_MYSQL_LOG_PASS;
	self::$log_port		= DB_MYSQL_LOG_PORT;
	self::$log_dbname	= DB_MYSQL_LOG_DBNAME;
	$serv->after(50000, array(__CLASS__, 'check_mysql'));
	return self::on_start($serv);
}

public	static	function crash()
{
	return;
}

public	static	function check_mysql()
{
	$now = time();

	foreach(self::$idle_pool as $key=>$db)
	{
		if(($db['time']+5400)<$now)
		{
			if(!mysqli_ping($db['mysqli']))
			{
				if(!self::reconnect($db))
				{
					self::$pre_connect[] = $db;
					unset(self::$idle_pool[$key]);
					continue;
				}
				self::$idle_pool[$key] = $db;
			}
		}
	}

	$db = null;

	foreach(self::$pre_connect as $k=>$odb)
	{
		if(!self::reconnect($odb))
			continue;
		unset(self::$pre_connect[$k]);
		self::$idle_pool[] = $odb;
	}

	if(!empty(self::$pre_connect))
		self::$serv->after(20000, __METHOD__);
	else
		self::$serv->after(3600000, __METHOD__);
}


}
?>
