<?php
namespace 
{
// define('PROJECT_ROOT', realpath(__DIR__.'/../').'/' );
// define('PROJECT_PID_FILE', PROJECT_ROOT.'/run/tp_worker.pid' );
require_once (PROJECT_ROOT . 'core/common.php');

class master
{
const SERVER_STATUS_RUN		= 1;
const SERVER_STATUS_STOP	= 0;
public static $serv;
public static $cli;
public static $worker_status	= self::SERVER_STATUS_RUN;
public static $script_data;
public static $server_status	= 0;

public	static	function get_main_class()
{
	if(self::$serv->taskworker)
		return '\task';
	return '\worker';
}

public static function get_worker()
{
	return self::$serv;
}

public static function get_worker_status()
{
	return self::$worker_status;
}

public	static	function set_server_status($key=0)
{
	self::$server_status = 0;
}
public	static	function get_server_status()
{
	return self::$server_status;
}

public	static	function echo_data($msg)
{
		echo date('[Ymd_H:i:s]').$msg.PHP_EOL;
}

public static function run()
{
	self::echo_data(swoole_version());

	$serv = new \swoole_http_server('0.0.0.0', FIRST_PORT);
	
	$port = $serv->addlistener('0.0.0.0', SECOND_PORT, SWOOLE_SOCK_TCP);

	$port->set( array
	(
		'open_length_check' => true,
		'package_max_length' => 65540,
		'package_length_type' => 'n',
		'package_length_offset' => 0,
		'package_body_offset' => 2,
	));

	$serv->set( array
	(
		'worker_num' => WORKER_NUM_COUNT, // 工作进程数量
		'task_worker_num' => TASK_NUM_COUNT,
		'daemonize' => DAEMONIZE, // 是否作为守护进程
		'log_file' => LOG_FILE_DIR,
		'open_length_check' => true,
		'package_max_length' => 65540,
		'package_length_type' => 'n',
		'package_length_offset' => 0,
		'package_body_offset' => 2,
		'task_tmpdir' => '/tmp/task/',
		'task_ipc_mode' => 2,
		'dispatch_mode' => DISPATCH_MODE,
		'open_cpu_affinity' => true,
		// 'cpu_affinity_ignore' => array(0),
		'heartbeat_idle_time' => 600,
		'heartbeat_check_interval' => 60,
		'open_tcp_nodelay' => true,
		'tcp_defer_accept' => 30,
		'max_conn' => SERVER_MAX_ONLINE_LIMIT
	)
	// 'chroot' => '/data/server/'
	);
	
	if(MAIN_PROCESS_TCP)
	{
		$serv->on('connect'	, array( '\worker', 'deal_connect'));
		$serv->on('receive'	, array( '\worker', 'deal_command'));
		$serv->on('close'	, array( '\worker', 'deal_close'));
	}
	else
	{
		$port->on('receive'	, array( '\worker', 'deal_command'));
		$port->on('connect'	, array( '\worker', 'deal_connect'));
		$port->on('close'	, array( '\worker', 'deal_close'));
		$serv->on('request'	, array( '\worker', 'deal_request'));
	}

	\cached::setup();
	\event::setup();

	$serv->on('Shutdown', function(){\master::echo_data(':Shutdown');});
	
	$serv->on('WorkerError', function($serv, $wid, $wpid, $exit_code )
	{
		\log::save('server_err', array( $wid, $wpid, $exit_code));
	});
	
	$serv->on('start', function ( $serv )
	{
		swoole_set_process_name(PROJECT_SIGNLE_FLAG.'_MASTER');

		$i = 1100;
		while(--$i)
		{
			$mysql_count = \cached::get_mysql_count();

			if(WORKER_NUM_COUNT*M_ASUNC_MYSQL_COUNT == $mysql_count)
				break;
			usleep(10000);
		}

		self::echo_data('ten second master data:'.(WORKER_NUM_COUNT*M_ASUNC_MYSQL_COUNT).'-'.$mysql_count);

		if($i==0)
		{
			self::echo_data('ten second master check mysql init timeout so close!!');
			$serv->shutdown();
			return;
		}

		self::echo_data('[START OK!]');
		\cached::set_server_status();

		if (DAEMONIZE)
			file_put_contents(PROJECT_PID_FILE, $serv->master_pid);
	});
	
	$serv->on('Managerstart', function ( $serv )
	{
		swoole_set_process_name(PROJECT_SIGNLE_FLAG.'_MANAGER');
	});
	
	$serv->on('WorkerStart', function ( $servs, $worker_id )
	{
		require_once(PROJECT_ROOT.'core/classd/common.php');
		global $serv;
		$serv		= $servs;
		self::$serv	= $serv;
		self::echo_data('start:	' . $worker_id . '	rsp	start');

		call_user_func_array(array('\\log' , 'setup'),array());

		require_once(PROJECT_ROOT.'conf/common.php');

		if(REDIS_SCRIPT_LOAD)
			self::$script_data = load_script(PROJECT_ROOT . 'script/');
		
		if (\dbredis::setup() !== true)
		{
			self::echo_data('dbredis load err so close!');
			$serv->shutdown();
			return;
		}
		
		// mysql
		if(!\sync_mysql::setup($serv))
		{
			self::echo_data('sync_mysql init err so close!');
			$serv->shutdown();
			return;
		}
		
		\schedule::setup($serv);
		
		if (! $serv->taskworker)
		{
			if(!\async_mysql::setup($serv))
			{
				self::echo_data(' async_mysql load fail so close!');
				$serv->shutdown();
				return;
			}

			swoole_set_process_name(PROJECT_SIGNLE_FLAG.'_WORKER_' . $worker_id);
	
			if(!load_dir(PROJECT_ROOT . 'cmd'))
			{
				self::echo_data('cmd load fail so close!');
				$serv->shutdown();
				return;
			}

			if(!load_unpattern_dir(PROJECT_ROOT . 'service', '/(\.task.php$)/i'))
			{
				self::echo_data('service.cmd load fail so close!');
				$serv->shutdown();
				return;
			}

			$res = call_user_func_array(array( '\worker', 'start'), array( $serv, $worker_id));
		}
		else
		{
			swoole_set_process_name(PROJECT_SIGNLE_FLAG.'_TASK_' . $worker_id);

			if( !load_dir(PROJECT_ROOT . 'task') )
			{
				self::echo_data('task load fail so close!');
				$serv->shutdown();
				return;
			}

			if( !load_unpattern_dir(PROJECT_ROOT . 'service', '/(\.cmd.php$)/i') )
			{
				self::echo_data('service.task load fail so close!');
				$serv->shutdown();
				return;
			}

			$res = call_user_func_array(array( '\task', 'start'), array( $serv, $worker_id));
		}

		if (!$res)
		{
			self::echo_data('base load err so close!');
			$serv->shutdown();
			return;
		}
		
		self::echo_data('start:	' . $worker_id . '	success!');
		self::$server_status = 1;
	});
	
	$serv->on('WorkerStop', function ( $serv, $worker_id )
	{
		self::echo_data('stop:	' . $worker_id . '	rsp	stop');
		write_warn('server', 'stop:	'.$worker_id . ' rsp stop'.PHP_EOL);
		
		if (! $serv->taskworker)
		{
			call_user_func_array(array( '\worker', 'stop'), array( $serv, $worker_id));
			\async_mysql::crash();
		}
		else
		{
			call_user_func_array(array( '\task', 'stop'  ), array( $serv, $worker_id));
		}
		
		self::echo_data('stop:	' . $worker_id . '	success!');
		write_warn('server', 'stop:	' . $worker_id . '	success!');
		
		\master::$worker_status = \master::SERVER_STATUS_STOP;
	});
	
	if (TASK_NUM_COUNT)
	{
		$serv->on('Task', array('\task', 'deal_task'));
		
		$serv->on('Finish', array('\worker', 'task_finish'));
	}

	self::echo_data('server start running!');
	
	$serv->start();
}

}
}


?>
