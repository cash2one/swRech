<?php
class cached
{
private	static	$server_status;
private	static	$async_mysql_count;
#private	static	$list_channel;
#private	static	$list_server;
private	static	$list_simple;

public	static	function setup()
{
	self::$server_status = new swoole_atomic(2);
	self::$server_status->set(0);
	self::$async_mysql_count = new swoole_atomic(WORKER_NUM_COUNT*M_ASUNC_MYSQL_COUNT+100);
	self::$async_mysql_count->set(0);

	$simple = get_reset_table();

	foreach($simple as $var=>$data)
	{
			$tmp = new swoole_table(1<<$data[0]);

			foreach($data[1] as $k=>$kdata)
			{
					switch($kdata[0])
					{
							case 'int':
								$tmp->column($k,swoole_table::TYPE_INT,$kdata[1]);
								break;
							case 'str':
								$tmp->column($k,swoole_table::TYPE_STRING,$kdata[1]);
								break;
							case 'float':
								$tmp->column($k,swoole_table::TYPE_FLOAT);
								break;
							default:
								return 0;
					}
			}

			$tmp->create();
			self::$list_simple[$var] = $tmp;
			$tmp = null;
	}

	return 1;
}

public	static	function __callStatic($func, $args)
{
		if(!isset(self::$list_simple[$args[0]]))
		{
			echo '未知的变量:'.($args[0]);
			return false;
		}

		$var = self::$list_simple[$args[0]];

		switch($func)
		{
			case 'exists':
				return call_user_func_array(array($var,'exist')	, $args[1]);
			case 'get':
				return call_user_func_array(array($var,'get')	, $args[1]);
			case 'klist':
			{
				$list = array();

				foreach($var as $k=>$v)
					$list[] = $k;

				return $list;
			}
			case 'init':
			{
				$callback = array($var,'set');

				foreach($args[1] as $sk=>$value)
				{
					if(!is_array($value))
					{
						echo 'init args err！';
						return 0;
					}
					call_user_func_array($callback, array($sk,$value));
					write_log('init_cached', 'key#'.$sk."\t\tvalue#".json_encode(call_user_func_array(array($var,'get'),array($sk))));
				}
				break;
			}
			default:
				return 0;
		}

		return 1;
}

//@专用接口
public	static	function add_mysql_count()
{
	if(self::get_server_status())
		return;
	return self::$async_mysql_count->add();
}

public	static	function get_mysql_count()
{
	return self::$async_mysql_count->get();
}

public	static	function get_server_status()
{
	return self::$server_status->get();
}

public	static	function set_server_status()
{
	self::$server_status->set(1);
}

}
