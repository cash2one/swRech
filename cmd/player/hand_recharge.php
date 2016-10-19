<?php
namespace cmd\player
{

/*
* GM手动发起充值 
* auther zzd
*/
class hand_recharge extends \command_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM = 2002;
const ARG_COUNT= 6;

/*
* simple function for deal tcp protocol
*/
public	static	function handler($serv, $fd, $pid, $args)
{
	$now = time();

	if( abs($now-$args[0]) > 10 )
	{
		call_client_func_fd( $fd, array(self::CMD_NUM, array(1)) );
		return;
	}

	write_log('hand', $args);

	if(!\game_server::check_sid($args['gid'],$args['sid']))
	{
		call_client_func_fd( $fd, array(self::CMD_NUM, array(2)) );
		return;
	}

	if (!$new_order = get_simple_order($args['gid']))
	{
		call_client_func_fd( $fd, array(self::CMD_NUM, array(3)) );
		return;
	}
	
	if($connect_info = $serv->connection_info($fd))
		$ip = $connect_info['remote_ip'];
	else
		$ip = 'close';

	$order_data = array
	(
		'gid'	=> $args['gid'],
		'sid'	=> $args['sid'],
		'uid'	=> $args['uid'],
		'pid'	=> $args['pid'],
		'oid'	=> $new_order,
		'ip'	=> $ip,
	);

	if(!save_simple_order($new_order, $order_data))
	{
		call_client_func_fd( $fd, array(self::CMD_NUM, array(4)) );
		return;
	}

	write_log('hand_recharge',$new_order);

	$ooid	= 'gmt';

	set_deliveryed_status($new_order,array($new_order, $args['cash'], $now, $ooid, SECRET_INNER_ORIGIN));
	
	$order_data['time'] = $now;

	\event::dispatch(EVENT_DATA_LOG, array('order_apply', $order_data));
	\event::dispatch(EVENT_DATA_LOG, array('order_delivery',array('time'=>$now,'cash'=>$args['cash'], 'oid'=>$new_order, 'ooid'=>$ooid, 'origin'=>SECRET_INNER_ORIGIN)));

	call_client_func_fd( $fd, array(self::CMD_NUM, $new_order) );

//	$serv->task($args[1]);
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
