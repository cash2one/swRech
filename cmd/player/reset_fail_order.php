<?php
namespace cmd\player
{

/*
* GM手动推送充值 
* auther zzd
*/
class reset_fail_order extends \command_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM = 2003;
const ARG_COUNT= 2;

/*
* simple function for deal tcp protocol
*/
public	static	function handler($serv, $fd, $pid, $args)
{
	if( (time()-$args[0]) > 10 )
	{
		$serv->close($fd);
		return;
	}

	if(!check_simple_order_format_for_lose($args[1]))
	{
		write_log('reset_fail_order', $args[1]);
		$serv->close($fd);
		return;
	}

	$res = reset_order_to_queue($args[1]);

	if( $res ===false )
		call_client_func_fd( $fd, array(CS_PROTOCOL_MANAGER_RESEND, 0) );
	else
		call_client_func_fd( $fd, array(CS_PROTOCOL_MANAGER_RESEND, $res) );

	return;
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
