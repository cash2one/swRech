<?php
namespace cmd\recharge;
/*
* 开心玩充值回调处理接口
* auther zzd
*/
class kaixinwandelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/kaixinwan/delivery.xl';
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
		$response->end(404);
		return;
	}

	$data = &$request->post;

	if(!isset($data['sign']) || strlen($data['sign']) != 32 )
	{
		$response->end('{"status":2,"msg":"arg fail!"}');
		return;
	}


	$pindex	= array
	(
		'sid',
		'uid',
		'oid',
		'money',
		'gold',
		'time',
		'gameSN',
		'gameAttach',
		'api_key',
	);

	$temp = '';
	foreach($pindex as $k)
	{
		if(!isset($data[$k]))
		{
			$response->end('{"code":5}');
			return;
		}
		$temp[] = $k.'='.$data[$k];
	}

	if( $data['money'] < 1)
	{
		$response->end('{"code":4}');
		return;
	}

	if(!check_simple_order_format($data['gameSN']))
	{
		$response->end('{"code":33}');
		return;
	}

	$sign = $data['sign'];

	if(md5(md5(implode('&', $temp)).SECRET_KAIXINWAN_SECRET) !== $sign)
	{
		write_log('kaixinwan_fail', 'sign:'. md5(md5(implode('&', $temp)).SECRET_KAIXINWAN_SECRET). "sign:". $sign);
		$response->end('{"code":3}');
		return;
	}

	$order	= $data['gameSN'];
	$cash	= $data['money'];
	$ooid	= $data['oid'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_KAIXINWAN_ORIGIN)))
	{
		write_log('kaixinwan_fail', 'set_deliveryed_status:'.$order);
		$response->end('{"code":2}');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_KAIXINWAN_ORIGIN)));
			break;
		case 2://@非法订单
			break;
		default://已发货
			break;
	}
	///////////////////////////////////////////////////
	$response->end('{"code":1}');

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
