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
protected static $wait_pool = array(); //等待链接响应的连接
protected static $idle_pool = array(); //空闲连接
protected static $busy_pool = array(); //工作连接
protected static $wait_queue = array(); //等待的请求
protected static $wait_queue_max = 100; //等待队列的最大长度，超过后将拒绝新的请求
protected static $pre_connect = array();
protected static $serv;
/////异步mysql////////////////////////////////////////////////////

public	static	function connect()
{
	$server = array
	(
		'host'		=>self::$log_host,
		'user'		=>self::$log_user,
		'password'	=>self::$log_pass,
		'database'	=>self::$log_dbname,
	);

	$mysqli = new swoole_mysql;

	$mysqli->connect($server,function($mysqli,$r)
	{
		if($r==false)
		{
			write_log('err_dbconnect', 'connect -'.(self::$serv->worker_id).'-'.($mysqli->sock).' errno:'.($mysqli->connect_errno).'--error:'.($mysqli->connect_error));
			unset(self::$wait_pool[$mysqli->sock]);
			//self::connect();
			swoole_timer_after(1000, array(__CLASS__,'connect'));
			return false;
		}
		else
		{
			write_log('init_async_db_suc', 'connect:'.(self::$serv->worker_id).'-'.$mysqli->sock.'suc:'.$r);
			$mysqli->on('close', function($mysqli)
			{
				if(isset(self::$idle_pool[$mysqli->sock]))
				{
					unset(self::$idle_pool[$mysqli->sock]);
				}
				elseif(isset(self::$busy_pool[$mysqli->sock]))
				{
					self::$wait_queue[] = array
					(
						'callback'	=> self::$busy_pool[$mysqli->sock]['callback'],
						'sql'		=> self::$busy_pool[$mysqli->sock]['sql'],
						'transfer'	=> self::$busy_pool[$mysqli->sock]['transfer']
					);
					write_log('err_async_save', PHP_EOL.'SQL:'.(self::$busy_pool[$mysqli->sock]['sql']));
					unset(self::$busy_pool[$mysqli->sock]);
				}

				write_log('close_async_mysql',(self::$serv->worker_id).'-'.$mysqli->sock.'close');
				swoole_timer_after(1000, array(__CLASS__,'connect'));
			});
	
			$mysqli->query('select now();', function($mysqli,$r)
			{
				write_log('init_async_db_suc', $r);
				unset(self::$wait_pool[$mysqli->sock]);
				if($r)
				{
					write_log('init_async_db_suc', 'check:'.(self::$serv->worker_id).'-'.$mysqli->sock.'suc');
					\cached::add_mysql_count();
					$mysqli->query_time = time();
					self::$idle_pool[$mysqli->sock] = $mysqli;
					write_log('basesql', array_keys(self::$wait_pool));
					self::redo_sql();
				}
				
			});

		}
	});

	write_log('init_async_db_suc', 'begain:'.(self::$serv->worker_id).'-'.$mysqli->sock.'suc:');

	self::$wait_pool[$mysqli->sock] = $mysqli;
}

public static function on_start($serv)
{
	self::$serv = $serv;

	for ($i = 0; $i < self::$pool_size; ++$i)
	{
		self::connect();
	}
	
	return true;
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
	write_log('amysql',array($sql,$callback, $transfer));
	if(empty(self::$idle_pool))
	{
	        if (count(self::$wait_queue) < self::$wait_queue_max)
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

public static function do_query($sql, $callback, $transfer)
{
	//从空闲池中移除
	$mysqli = array_shift(self::$idle_pool);

	$res = $mysqli->query($sql, function($mysqli, $r)
	{
		$db = self::$busy_pool[$mysqli->sock];
		if($r === true)
		{
			if(!is_null($db['callback']))
				call_user_func_array($db['callback'], array($r, $db['transfer']));
		}
		elseif(is_array($r))
		{
			if(!is_null($db['callback']))
				call_user_func_array($db['callback'], array($r, $db['transfer']));
		}
		else
		{
			if(!isset($mysqli->errno))
			{
				write_log('query_blank_err', $db['sql']);
				return;
			}
			if(!is_null($db['callback']))
				call_user_func_array($db['callback'], array(false,$db['transfer']));
			write_log('err_async_query', PHP_EOL.'errno:'.($mysqli->errno).PHP_EOL.'error:'.($mysqli->error).PHP_EOL.'SQL:'.($db['sql']));
		}

		unset(self::$busy_pool[$mysqli->sock], $db['callback'],$db['sql'],$db['transfer']);
		self::$idle_pool[$mysqli->sock] = $mysqli;
		//这里可以取出一个等待请求
		self::redo_sql();
	});

	if(!$res)
	{
		self::$wait_queue[] = array
		(
			'callback'	=> $callback,
			'sql'		=> $sql,
			'transfer'	=> $transfer
		);

		write_log('err_async_query', (self::$serv->worker_id).'-'.$mysqli->sock.'close');
		return;
	}

	$mysqli->query_time = time();
	//加入工作池中
	self::$busy_pool[$mysqli->sock] = array
	(
		//'mysqli'	=> $mysqli,
		'callback'	=> $callback,
		'transfer'	=> $transfer,
		'sql'		=> $sql
	);

}

/////////////////////////////////////////////////////////////////////////////////////
public	static	function setup($serv)
{//@目前只支持LOG库
	self::$log_host		= DB_MYSQL_LOG_HOST;
	self::$log_user		= DB_MYSQL_LOG_USER;
	self::$log_pass		= DB_MYSQL_LOG_PASS;
	self::$log_port		= DB_MYSQL_LOG_PORT;
	self::$log_dbname	= DB_MYSQL_LOG_DBNAME;
	$serv->tick(18000000, array(__CLASS__, 'check_mysql'));
	return self::on_start($serv);
}

public	static	function crash()
{
	write_warn('resp_crash', 'async_mysql_'. (self::$serv->worker_id));
	if(empty(self::$wait_queue))
		return;
	
	write_warn('resp_crash', 'async_mysqli_'.(self::$serv->worker_id).' deal now!');

	foreach(self::$wait_queue as $sql_data)
	{
		$resp = \sync_mysql::queryi($sql_data['sql']);

		if(!$resp)
		{
			write_log('sql_save_err', PHP_EOL.'SQL:'.($sql_data['sql']));
			continue;
		}

		if($sql_data['callback'])
			call_user_func_array($sql_data['callback'], array($resp,$sql_data['transfer']));
	}


	return;
}

public	static	function check_mysql()
{
	$now = time();

	$k = 0;
	foreach(self::$idle_pool as $db)
	{
		if(($db->query_time+5400)<$now)
		{
			$k++;
		}
	}

	if($k)
	{
		$k+=M_ASUNC_MYSQL_COUNT;
		for($i =0; $i < $k; ++$i)
		{
			self::do_sql('select now();', array(__CLASS__, 'resp_check'));
		}
	}
}

public	static	function resp_check($mysqli, $r)
{
	if($r)
		write_log('mysql_check', 'suc:'.(self::$serv->worker_id).'-'.($mysqli->sock));
	else
		write_log('mysql_check', 'fal:'.(self::$serv->worker_id).'-'.($mysqli->sock));
}


}
?>
