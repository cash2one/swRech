<?php
namespace cmd\recharge;
/*
* 360发货处理接口
* auther zzd
*/
class qhdelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/qhdelivery.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_RECHARGE;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if(isset($request->post))
	{
		$data = &$request->post;
	}
	elseif(isset($request->get))
	{
		$data = &$request->get;
	}
	else
	{
		$response->end(404);
		return;
	}

	$pindex	= array
	(
		'product_id',
		'app_uid',
		'user_id',
		'order_id',
		'sign_return',
	);

	if(!isset($data['gateway_flag']) || $data['gateway_flag'] !== 'success')
	{
		$response->end('ok');
		return;
	}

	if(!isset($data['app_key']) || $data['app_key'] !== SECRET_360_APPKEY)
	{
		$response->end('FAL');
		return;
	}

	if(!isset($data['sign']) || strlen($data['sign']) != 32)
	{
		$response->end('fal3');
		return;
	}

	if(!isset($data['amount']) || $data['amount'] < 100)
	{
		$response->end('fal3');
		return;
	}

	if(!isset($data['app_order_id']) || !check_simple_order_format($data['app_order_id']))
	{
		$response->end('fal3');
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

	$sign = $data['sign'];
	unset($data['sign'], $data['sign_return']);

	if(!\sign::qh_decode('POST', self::CMD_NUM, $data, $sign))
	{
		$response->end(3);
		return;
	}

	$order	= $data['app_order_id'];
	$cash	= $data['amount']/100;
	$ooid	= $data['order_id'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_360_ORIGIN)))
	{
		write_log('qh_fail', 'set_deliveryed_status:'.$order);
		$response->end(404);
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_360_ORIGIN)));
		//	\service\log_cmd::make_log('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_360_ORIGIN));
			break;
		case 2://@非法订单
			break;
		default://已发货
			break;
	}
	///////////////////////////////////////////////////
	$response->end('ok');

	return;
}

/*
* 自定义任务响应方法
*/
public	static	function resp_task($serv,$arg, $pass)
{
	echo "响应了 她上课";
}

}
