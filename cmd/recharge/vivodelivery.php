<?php
namespace cmd\recharge;
/*
* VIVO发货处理接口
* auther zzd
*/
class vivodelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/vivodelivery.xl';
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

	$pindex	= array
	(
		'respMsg',
		'tradeStatus',
		'uid',
		'orderNumber',
		'orderAmount',
		'extInfo',
		'payTime'
	);

	if(!isset($data['respCode']) || $data['respCode'] != 200)
	{
		$response->status(404);
		$response->end('202');
		return;
	}

	if(!isset($data['tradeStatus']) || $data['tradeStatus'] !== '0000')
	{
		$response->status(404);
		$response->end('202');
		return;
	}

	if(!isset($data['appId']) || $data['appId'] != SECRET_VIVO_APPID)
	{
		$response->status(404);
		$response->end('202');
		return;
	}

	if(!isset($data['cpId']) || $data['cpId'] != SECRET_VIVO_CPID)
	{
		$response->status(404);
		$response->end('202');
		return;
	}

	if(!isset($data['signature']) || strlen($data['signature']) != 32)
	{
		$response->status(404);
		$response->end('202');
		return;
	}

	if(!isset($data['orderAmount']) || $data['orderAmount'] <= 0)
	{
		$response->status(404);
		$response->end('202');
		return;
	}

	if(!isset($data['cpOrderNumber']) || !check_simple_order_format($data['cpOrderNumber']))
	{
		$response->end('202');
		return;
	}

	foreach($pindex as $k)
	{
		if(!isset($data[$k]))
		{
			$response->status(402);
			$response->end('202');
			return;
		}
	}

	$sign = $data['signature'];
	unset($data['signature'], $data['signMethod']);

	if(!\sign::vivo_decode('POST', self::CMD_NUM, $data, $sign))
	{
		$response->status(402);
		$response->end('203');
		return;
	}

	$order	= $data['cpOrderNumber'];
	$cash	= round($data['orderAmount']/100,2);
	$ooid	= $data['orderNumber'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_VIVO_ORIGIN)))
	{
		write_log('vivo_fail', 'set_deliveryed_status:'.$order);
		$response->status(404);
		$response->end('202');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_VIVO_ORIGIN)));
		//	\service\log_cmd::make_log('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_VIVO_ORIGIN));
			break;
		case 2://@非法订单
			break;
		default://已发货
			break;
	}
	///////////////////////////////////////////////////
	$response->end('200');

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
