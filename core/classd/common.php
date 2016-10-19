<?php
require_once(PROJECT_ROOT.'core/classd/auto_loader.php');
require_once(PROJECT_ROOT.'core/classd/sync_mysql.php');
require_once(PROJECT_ROOT.'core/classd/async_mysql.php');
require_once(PROJECT_ROOT.'core/classd/async_http.php');
require_once(PROJECT_ROOT.'core/classd/dbredis.php');
require_once(PROJECT_ROOT.'core/classd/json_protocol.php');
require_once(PROJECT_ROOT.'core/classd/command.class.php');
require_once(PROJECT_ROOT.'core/classd/task.class.php');
require_once(PROJECT_ROOT.'core/classd/service.class.php');
require_once(PROJECT_ROOT.'core/classd/request.class.php');
require_once(PROJECT_ROOT.'core/classd/curl.php');
require_once(PROJECT_ROOT.'core/classd/schedule.php');
require_once(PROJECT_ROOT.'core/classd/aes.php');
require_once(PROJECT_ROOT.'core/classd/des3.php');
require_once(PROJECT_ROOT.'core/classd/rsa.php');
require_once(PROJECT_ROOT.'core/classd/sms.php');
require_once(PROJECT_ROOT.'core/classd/smtp.php');

static $serv = 1;

function	lock_server_status()
{
	\master::set_server_status();
	swoole_server_after(20000, function(){global $serv;echo 'mysql err so shutdown';$serv->shutdown();});
}

function	set_server_status($k=0)
{
		\master::set_server_status($k);
}

function	get_server_status()
{
	return \master::get_server_status();
}

function	write_log($file, $data)
{
	\log::save($file, $data);
}

function	write_warn($file, $data)
{
	\log::save($file, $data);
}

//@根据pid通知玩家
function	call_client_func($serv, $fd, $data)
{
	$serv->send($fd, call_user_func_array(array(BASE_PROTOCOL,'encode'),array($data)));
}

//@根据FD通知玩家
function call_client_func_pid($pid, $data)
{
	if( !$data = get_online_data_by_pid( $pid ) )
		return false;

	global $serv;

	$serv->send($data['fd'], call_user_func_array(array(BASE_PROTOCOL,'encode'),array($data)) );
}

//@根据FD通知玩家
function call_client_func_fd($fd, $data)
{
	global $serv;

	$serv->send($fd, call_user_func_array(array(BASE_PROTOCOL,'encode'),array($data)) );
}

function get_protocol_data($data)
{
	return call_user_func_array(array(BASE_PROTOCOL,'encode'),array($data));
}

function call_clients_func($fd_array, $data)
{
	global $serv;
	
	$sdata = call_user_func_array(array(BASE_PROTOCOL,'encode'),array($data));
	
	foreach($fd_array as $fd)
	{
		$serv->send($fd,$sdata);
	}
}

function check_name($name)
{
	$nlen = strlen($name);

	if (  $nlen > 21 || $nlen < 5 || strpos($name,'S')===0 ||strpos($name,'s')===0 ||! preg_match("/^[\x{4e00}-\x{9fa5}A-Z0-9a-z]+$/u",$name) )
	{
		return false;
	}
	
	//@关键字过滤
	
	return true;
}

?>
