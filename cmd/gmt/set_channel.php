<?php
namespace cmd\gmt
{

/*
* GM更新服务器缓存用 
* auther zzd
*/
class set_channel extends \command_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM = 7002;
const ARG_COUNT= 3;

/*
* simple function for deal tcp protocol
* params args array(时间戳int,关键字int,具体参数)
*/
public	static	function handler($serv, $fd, $pid, $args)
{
	if( (time()-$args[0]) > 10 )
	{
		//call_client_func_fd( $fd, array(CS_PROTOCOL_MANAGER_HANDRECH, array(1)) );
		$serv->close();
		return;
	}

	$key = $args[1];

	if($key <1 || $key > 6)
	{
		$serv->close();
		return;
	}

	switch($key)
	{
		case 1:
			break;
		case 2:
			break;
		case 3:
			$serv->close();
			return;
	}

	call_client_func_fd( $fd, array(self::CMD_NUM, array($key)) );

}

/*
* 自定义任务响应方法
*/
//public	static	function resp_task($serv,$arg, $pass)
//{
//	return;
//}

}
}
?>
