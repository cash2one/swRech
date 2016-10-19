<?php
namespace cmd\gmt
{

/*
* GM更新服务器缓存用 
* auther zzd
*/
class server_close extends \command_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM = 7001;
const ARG_COUNT= 3;

/*
* simple function for deal tcp protocol
* params args array(时间戳int,关键字int,具体参数)
*/
public	static	function handler($serv, $fd, $pid, $args)
{
	if( (time()-$args[0]) > 10 )
	{
		//call_client_func_fd( $fd, array(self::CMD_NUM, array(1)) );
		$serv->close();
		return;
	}

	if($args[1] !== 0 || $args[1] !== 1)
	{
			call_client_func_fd( $fd, array(self::CMD_NUM, array(2)) );
			return;
	}

	set_server_status($args[1]);

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
