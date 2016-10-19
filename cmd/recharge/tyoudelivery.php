<?php
namespace cmd\recharge;
/*
* 天游发货处理接口
* auther zzd
*/
class tyoudelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/tyoudelivery.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_RECHARGE;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if(!isset($request->post))
	{
		$response->status(404);
		$response->end('');
		return;
	}

	$data = &$request->post;

	$pindex	= array
	(
		'userid',
		'orderid',
		'money',
		'appid',
		'sign',
	);

	foreach($pindex as $k)
	{
		if(!isset($data[$k]))
		{
			$response->end('{"code":4}');
			return;
		}
	}

	$secret_key = array
	(
		SECRET_TIANYOU_APPID		=> SECRET_TIANYOU_APPKEY,
		SECRET_TIANYOUXMCQ_APPID	=> SECRET_TIANYOUXMCQ_APPKEY,
		SECRET_TIANYOULJTX_APPID	=> SECRET_TIANYOULJTX_APPKEY,
		SECRET_TIANYOUXLJ3D_APPID	=> SECRET_TIANYOUXLJ3D_APPKEY,
		1029	=> 'MTAyOSZ0aWFueW91eGkmeG1jcS50aWFueW91eGkuY29t',
		1030	=> 'MTAzMCZ0aWFueW91eGkmeHloei50aWFueW91eGkuY29t',
		1022	=>	'MTAyMiZ0aWFueW91eGkmc2p6Yi50aWFueW91eGkuY29t',
	);

	if( !isset($secret_key[$data['appid']]) )
	{
		$response->end('{"code":405}');
		return;
	}

	if( strlen($data['sign']) != 32)
	{
		$response->end('{"code":406}');
		return;
	}

	$cash	= $data['money'];

	if($cash < 1 )
	{
			$response->end('{"code":407}');
			return;
	}

	$sign = $data['sign'];
	unset($data['sign'], $data['signtype']);


	$str	= \sign::make_string($data).'&'.$secret_key[$data['appid']];

	if($sign !== md5($str))
	{
		write_log('tyou_fail','sig'.$str);
		$response->end('{"code":3}');
		return;
	}
	
	$now = time();

	if(isset($data['custom_info']) )
	{
		if(!check_simple_order_format($data['custom_info']))
		{
			$response->end('{"code":404}');
			return;
		}

		$order	= $data['custom_info'];
		$origin	= SECRET_TIANYOU_ORIGIN;
	}
	else
	{
		$response->end('{"code":404}');
		return;
//		if(abs($now-$data['timestr']) > 180)
//		{
//			$response->end('{"code":5}');
//			return;
//		}
//
//		if(!isset($data['gid'], $data['serverid'], $data['roleid']))
//		{
//			$response->end('{"code":404}');
//			return;
//		}
//
//		if (!is_numeric($data['gid']) || ($data['gid'] != CHANNEL_ID_IOS && $data['gid'] != CHANNEL_ID_TIANYOU))
//		{
//			$response->end('{"code":8}');
//			return;
//		}
//
//		$sid = $data['serverid'];
//		
//		if(!\game_server::check_sid($data['gid'],$sid))
//		{
//			write_log('channel_check_err', ($data['gid']).'_'.$sid);
//			$response->end('{"code":8}');
//			return;
//		}
//
//		if (!$order = get_simple_order($data['gid']))
//		{
//			$response->end('{"code":6}');
//			return;
//		}
//		
//		$order_data = array
//		(
//			'gid'	=> $data['gid'],
//			'sid'	=> $sid,
//			'uid'	=> $data['userid'],
//			'pid'	=> $data['roleid'],
//			'oid'	=> $order,
//			'ip'	=> $request->header['x-real-ip'],
//		);
//
//		if(!save_simple_order($order, $order_data))
//		{
//			$response->end('{"code":7}');
//			return;
//		}
//
//		$order_data['time'] = $now;
//		$order_data['cash'] = $cash;
//	
//		\event::dispatch(EVENT_DATA_LOG, array('order_apply', $order_data));
//		$origin	= SECRET_TIANYOU_PLATFORM_ORIGIN;
	}

	$ooid	= $data['orderid'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, $now,$ooid,$origin)))
	{
		write_log('tyou_fail', 'set_deliveryed_status:'.$order);
		$response->end('{"code":66}');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>$origin)));
			break;
		case 2://@非法订单
			break;
		default://已发货
			break;
	}
	///////////////////////////////////////////////////
	$response->end("{\"code\":200,\"order\":$order}");

	return;
}

/*
* 自定义任务响应方法
*/
//public	static	function resp_task($serv,$arg, $pass)
//{
//	echo "响应了 她上课";
//}

}
