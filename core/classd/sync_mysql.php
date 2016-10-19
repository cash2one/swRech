<?php

class sync_mysql 
{
private	static	$log_host;
private	static	$log_user;
private static	$log_pass;
private	static	$log_dbname;
private	static	$gmt_host;
private	static	$gmt_user;
private	static	$gmt_pass;
private	static	$gmt_dbname;

/////////////////////////////////////////////////////////////////////////////////////
public	static	function setup($serv)
{
	self::$log_host		= DB_MYSQL_LOG_HOST;
	self::$log_user		= DB_MYSQL_LOG_USER;
	self::$log_pass		= DB_MYSQL_LOG_PASS;
	self::$log_dbname	= DB_MYSQL_LOG_DBNAME;
	self::$gmt_host		= DB_MYSQL_GMT_HOST;
	self::$gmt_user		= DB_MYSQL_GMT_USER;
	self::$gmt_pass		= DB_MYSQL_GMT_PASS;
	self::$gmt_dbname	= DB_MYSQL_GMT_DBNAME;

	$data = array();

	if($serv->worker_id==0)
	{
		$data = get_worker_init_data();
		if($data === false)
			return false;
	}
	elseif(WORKER_NUM_COUNT == $serv->worker_id)
	{
        $data = get_task_init_data();
		if($data === false)
			return false;
	}
	else
	{
		if(!self::queryi('select sleep(1)'))
			return false;
	}

	if(!empty($data))
	{
		foreach($data as $value)
		{
//			$qdata = array();
//	
//			if($value[1])
//			{
//				if(!$qdata = self::queryi($value[1]))
//				{
//					var_dump(($value[1]).'query_false!');
//					return 0;
//				}
//				$qdata = array($qdata);
//			}
//	
//			if(!call_user_func_array($value[0],$qdata))
			if(!call_user_func($value))
			{
				var_dump($value,false);
				return 0;
			}
		}
		swoole_timer_after(2000,function(){var_dump(\platform::data('8888'));echo channel::check(250);});
	}

	return true;
}

private	static	function connect( $sql, $hostName, $userName, $password, $db )
{
	$mysqli =  mysqli_connect('p:'.$hostName, $userName, $password);

	if ( $mysqli->connect_errno)
	{
		write_warn('sync_mysql_query', 'err:'.$mysqli->connect_errno.'-'.$mysqli->connect_error.PHP_EOL.'sql:'.$sql.PHP_EOL);
		$mysqli = null;
		lock_server_status();
		return  false;
	}

	return $mysqli;
}

public	static	function queryi_from_gmt($sql)
{
	return self::query($sql, self::$gmt_host, self::$gmt_user, self::$gmt_pass, self::$gmt_dbname);
}

public	static	function queryi($sql)
{
	return self::query($sql, self::$log_host, self::$log_user, self::$log_pass, self::$log_dbname);
}

public	static	function query( $sql, $host, $user, $pass, $db)
{
	write_log('sync_mysql',$sql);
	$mysqli	= self::connect( $sql,$host, $user, $pass, $db);
	
	if( !$mysqli )
		return false;
	
	$mysqli->set_charset('utf8');
	
	if (!$mysqli->select_db($db))
	{
		write_warn('sync_mysql_query', 'err:'.$mysqli->errno.'-'.$mysqli->error.PHP_EOL.'sql:'.$sql.PHP_EOL);
		$mysqli->close();
		return false;
	}
	
	$result = $mysqli->query($sql);
	
	if (!$result)
	{
		write_warn('sync_mysql_query', 'err:'.$mysqli->errno.'-'.$mysqli->error.PHP_EOL.'sql:'.$sql.PHP_EOL);
		$mysqli->close();
		return false;
	}

	if( is_bool($result)  )
	{
		$mysqli->close();
		return $result;
	}

	$grid		= array();
	
	$row_count	= mysqli_num_rows($result);
	
	if( $row_count <= 0 )
	{
		$mysqli->close();
		return array();
	}
	
	for( $i = 0; $i < $row_count; ++$i )
	{
		$grid[] = mysqli_fetch_assoc( $result );
	}
	
	$mysqli->close();
	
	return $grid;
}


}


?>
