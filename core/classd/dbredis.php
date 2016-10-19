<?php

class dbRedis
{
private	static	$redis_count	= 0;
private	static	$redis_port	= array();
private	static	$game_db	= 9999;
private	static	$game_instance	= array();
private	static	$auth_key	= DB_REDIS_SECRET;
private	static	$instanceError	= null;
private	static	$operate_time	= 0;

public	static	function setup()
{
	self::$game_db		= DB_REDIS_START;
	$redis_port		= DB_REDIS_PORT;
		
	if( is_null($redis_port) && is_null(self::$game_db) )
		return true;
	
	$db_data 		= getRedisDbData();
		
	$redis_port 	= explode(",", $redis_port);
	$size		= count($redis_port);
	
	if( !$size )
		return false;
	
	self::$game_instance	= array();
	self::$operate_time	= array();
	self::$redis_port	= array();
	
	foreach( $db_data as $i=>$define )
	{
		$db		= $i+self::$game_db;
		$port		= $db%$size;
		$game_instance	= self::connect_to_redis($redis_port[$port], $db);
		
		if( !$game_instance )
			return false;
	
		self::$game_instance[$i]	= $game_instance;
		self::$operate_time[$i]	= time();
		self::$redis_port[$i]	= $redis_port[$port];
		++self::$redis_count;

		if(REDIS_SCRIPT_LOAD)
		{
			foreach(\master::$script_data as $file => $contents)
			{
				//$game_instance->script("flush");
				$temp_sha	= $game_instance->script("load", $contents);
				
				var_dump($file."#".$temp_sha);
				if(!defined($file))
					define( $file, $temp_sha);
			}
		}
	}
	
	return true;
}

	//@澶勭悊娓告垙鏁版嵁瀛樺偍鐨剅edis璇锋眰
public	static	function	deal_game_db( $db, $oprate, $key, $field=null, $value=null )
{
	$instance = self::get_game_instance($db);

	if( $instance )
	{
		if($oprate == "multi")
		{
			if($field)
				$instance->watch($field);
			
			$instance->set($field,1);
			
			if( !$instance->multi() )
			{
				if($field)
					$instance->unwatch($field);
				return false;
			}

			foreach($key as $d)
			{
				switch(count($d))
				{
					case 1:
						$instance->$d[0];
						break;
					case 2:
						$instance->$d[0]($d[1]);
						break;
					case 3:
						$instance->$d[0]($d[1],$d[2]);
						break;
					case 4:
						$instance->$d[0]($d[1],$d[2],$d[3]);
						break;
					default:
						break;
				}
			}
			
			return $instance->exec();
		}
//		$instance->select($db+self::$gameDb);
		
		if( !is_null($field) )
		{
			if( !is_null($value) )
			{
				CLASSED_DEBUG && write_log('redis', array($db, $oprate, $key, $field, $value));
				$data = $instance->$oprate($key, $field, $value);
			}
			else
			{
				CLASSED_DEBUG && write_log('redis', array($db, $oprate, $key, $field));
					
				if( strtolower($oprate) === 'hdel' && is_array($field) )
				{
					$ret =	$instance->multi();
						
					foreach( $field as $fd )
						$instance->$oprate($key, $fd);
						
					$data = $instance->exec();
							
//						$data = $instance->$oprate($key, $field);
				}
				else
				{
					$data = $instance->$oprate($key, $field);
				}
			}
		}
		elseif(!is_null($key))
		{
			CLASSED_DEBUG && write_log('redis', array($db, $oprate,$key));
			$data = $instance->$oprate($key);
		}
		else
		{
			$data = $instance->$oprate();
		}
			
		if( $data === false && !is_null($instance->getLastError()) )
		{
			var_dump(array($db, $oprate, $key, $instance->getLastError() ));
			write_warn('redisErr', array($db, $oprate, $key, $instance->getLastError() ) );
		}
			
		return $data;
	}
		
	return NULL;
}


//@获取数据库
private	static	function	get_game_instance($db)
{
	if(!self::$redis_count)
		return false;
		
	if( !isset(self::$game_instance[$db]) || !self::$game_instance[$db])
	{
		self::$game_instance[$db] = self::connect_to_redis(self::$redis_port[$db], self::$game_db+$db);
	}
		
	if(!self::$game_instance[$db] || !self::$game_instance[$db]->select(self::$game_db+$db))
	{
		self::$game_instance[$db] = self::connect_to_redis(self::$redis_port[$db], self::$game_db+$db);

		if(self::$game_instance[$db])
			self::$game_instance[$db]->select(self::$game_db+$db);
	}
		
	return self::$game_instance[$db];
}

private	static	function connect_to_redis($port, $db)
{
	$instance = new Redis();
	
	if( !$instance )
		return false;
	
	$sock = '/tmp/redis'.$port.'.sock';
	write_warn("redis", $sock);
	if( !$instance->pconnect($sock) )
	{
		write_warn("redis", $sock.$instance->getLastError());
		return false;
	}

	if( !$instance->auth(self::$auth_key) )
		return false;
		
	if( !$instance->select($db) )
		return false;
		
	return $instance;
}




}


?>
