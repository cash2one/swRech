<?php
namespace cmd\player
{

/*
* GM手动推送充值 
* auther zzd
*/
class hand_push extends \command_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM = 2001;
const ARG_COUNT= 2;

/*
* simple function for deal tcp protocol
*/
public	static	function handler($serv, $fd, $pid, $args)
{
	if( (time()-$args[0]) > 10 )
	{
		$serv->close();
		return;
	}

	if($args[1] < 1 || $args[1] > 5)
	{
		$serv->close();
		return;
	}

	call_client_func_fd( $fd, array(CS_PROTOCOL_MANAGER_DATA, $args) );

	$serv->task($args[1]);
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
