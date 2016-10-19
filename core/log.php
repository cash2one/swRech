<?php
class log
{

public static function setup()
{
	error_reporting(E_ALL);
	set_error_handler(array(__CLASS__, 'errorHandler'));
	register_shutdown_function( array(__CLASS__, 'fatalHandler') );
	self::checkWriteable();
}

public static function save( $file, $msg )
{
	$log_dir = PROJECT_LOG_DIR . '/' . date('Y-m-d');
	
	umask(0);
	
	if (!is_dir($log_dir))
	{
		@mkdir($log_dir, 0700, true);
	}
	
	$log_file = $log_dir . '/' . $file;
	
	if (is_object($msg))
		file_put_contents($log_file, date('[H:i:s]') . ' class | ' . get_class($msg) . ' ERR'.PHP_EOL, FILE_APPEND);
	elseif (is_array($msg))
		file_put_contents($log_file, date('[H:i:s]') . ' array | ' . var_export($msg, true) . PHP_EOL, FILE_APPEND);
	else
		file_put_contents($log_file, date('[H:i:s]') . ' string | ' . $msg . PHP_EOL, FILE_APPEND);
}

public static function errorHandler( $errno, $errstr, $errfile, $errline )
{
	$arr = array(
		$errno,	
		$errstr,
		$errfile,
		'line:' . $errline
	);
	// 写入错误日志
	// 格式 ： 时间 uri | 错误消息 文件位置 第几行
	self::save('error', implode(' ', $arr) );
}

public static function fatalHandler()
{
	global $serv;
	
	if (\master::get_worker_status())
	{
		if($serv->taskworker)
			call_user_func_array(array( '\task', 'stop'  ), array( $serv, $serv->worker_id));
		else
			call_user_func_array(array( '\worker', 'stop'  ), array( $serv, $serv->worker_id));

		self::save('fatal_err', '异常退出出#wid:' . $serv->worker_id . ' pid:' . $serv->worker_pid .PHP_EOL.' err: '.memory_get_usage());
	}
	// 处理数据
	
	$error = error_get_last();
	
	if (isset($error['type']))
	{
		if (\master::get_worker_status() )
		{
			$message = $error['message'];
			$file = $error['file'];
			$line = $error['line'];
			$log = "$message ($file:$line)\nStack trace:\n";
			$trace = debug_backtrace();
			foreach ($trace as $i => $t)
			{
				if (! isset($t['file']))
				{
					$t['file'] = 'unknown';
				}
				if (! isset($t['line']))
				{
					$t['line'] = 0;
				}
				if (! isset($t['function']))
				{
					$t['function'] = 'unknown';
				}
				$log .= "#$i {$t['file']}({$t['line']}): ";
				if (isset($t['object']) && is_object($t['object']))
				{
					$log .= get_class($t['object']) . '->';
				}
				$log .= "{$t['function']}()\n";
			}
			self::save('fatal_err', $log);
		}
	}

	echo date('[Y-m-d H:i:s]').'FATAL ERR SO CLOSE!'.PHP_EOL;
//	$serv->shutdown();
//	sleep(2);
}

private static function checkWriteable()
{
	$ok = true;
	
	if (! is_dir(PROJECT_LOG_DIR))
	{
		umask(0);
		
		if (@mkdir(PROJECT_LOG_DIR, 0777) === false)
		{
			$ok = false;
		}
		@chmod(PROJECT_LOG_DIR, 0777);
	}
	
	if (! is_readable(PROJECT_LOG_DIR) || ! is_writeable(PROJECT_LOG_DIR))
	{
		$ok = false;
	}
	
	if (! $ok)
	{
		$pad_length = 26;
		var_dump(
			'------------------------LOG------------------------'.PHP_EOL. str_pad(PROJECT_LOG_DIR, $pad_length) . "\033[31;40m [NOT READABLE/WRITEABLE] \033[0m\n\n\033[31;40mDirectory " . WORKERMAN_LOG_DIR .
				 " Need to have read and write permissions\033[0m\n\n\033[31;40mproject start fail\033[0m\n\n");
	}
}

}
?>
