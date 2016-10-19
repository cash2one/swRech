<?php
namespace cmd\recharge;
/*
* 安锋发货处理接口
* auther zzd
*/
class anfengdelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/anfengdelivery.xl';
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
		'uid',
		'ucid',
		'fee',
		'sn',
	);

	if(!isset($data['vid']) || $data['vid'] !== SECRET_ANFENG_APPID)
	{
		$response->end(404);
		return;
	}

	if(!isset($data['sign']))
	{
		$response->end(404);
		return;
	}

	if(!isset($data['vorderid']) || !check_simple_order_format($data['vorderid']) )
	{
		$response->end(404);
		return;
	}

	foreach($pindex as $k)
	{
		if(!isset($data[$k]))
		{
			$response->end(404);
			return;
		}
	}

	$cash	= $data['fee'];

	if($cash < 0.1 )
	{
			$response->end('fail');
			return;
	}

	$sign = $data['sign'];
	unset($data['sign']);

	if(!\sign::anfeng_decode('POST', self::CMD_NUM, $data, $sign))
	{
		write_log('anfeng_fail',1);
		$response->end('fail');
		return;
	}

	$order	= $data['vorderid'];
	$ooid	= $data['sn'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_ANFENG_ORIGIN)))
	{
		write_log('anfeng_fail', 'set_deliveryed_status:'.$order);
		$response->end('fail');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_ANFENG_ORIGIN)));
			break;
		case 2://@非法订单
			break;
		default://已发货
			break;
	}
	///////////////////////////////////////////////////
	$response->end('SUCCESS');

	return;
}

/*
* 自定义任务响应方法
*/
//public	static	function resp_task($serv,$arg, $pass)
//{
//	echo "响应了 她上课";
//}
//
}
