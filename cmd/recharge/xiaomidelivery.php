<?php
namespace cmd\recharge;
/*
* xiaomi发货处理接口
* auther zzd
*/
class xiaomidelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/xiaomidelivery.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_RECHARGE;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if(!isset($request->get) )
	{
		$response->status(404);
		$response->end('0');
		return;
	}


	$data = &$request->get;

	if(!isset($data['signature']) || !isset($data['orderStatus']) || $data['orderStatus'] !== 'TRADE_SUCCESS')
	{
		$response->status(404);
		$response->end('1');
		return;
	}


	$sign = $data['signature'];
	unset($data['signature']);

	if(!\sign::xiaomi_decode($data, $sign))
	{
		$response->end('{"errcode":1525}');
		return;
	}

	if(!isset($data['payFee']) || $data['payFee'] < 0.1 )

	if(!isset($data['appId']) || $data['appId'] !== SECRET_XIAOMI_APPID)
	{
		$response->end('FAILURE');
		return;
	}

	if(!isset($data['cpOrderId']) || !check_simple_order_format($data['cpOrderId']))
	{
		$response->end('FAILURE');
		return;
	}

	$order	= $data['cpOrderId'];
	$cash	= round($data['payFee']/100,2);
	$ooid	= $data['orderId'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_XIAOMI_ORIGIN)))
	{
		write_log('xiaomi_fail', 'set_deliveryed_status:'.$order);
		$response->end('FAILURE');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_XIAOMI_ORIGIN)));
			break;
		case 2://@非法订单
			break;
		default://已发货
			break;
	}
	///////////////////////////////////////////////////
	$response->end('{"errcode":200}');

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
