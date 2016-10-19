<?php
namespace cmd\recharge;
/*
* MEIZU发货处理接口
* auther zzd
*/
class meizudelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/meizudelivery.xl';
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
		'notify_time',
		'notify_id',
		'order_id',
		'app_id',
		'uid',
		'partner_id',
		'product_id',
		'create_time',
		'pay_time',
		'sign_type',
	);

	if(!isset($data['trade_status']) || !is_numeric($data['trade_status'])|| $data['trade_status'] != 3)
	{
		$response->end('{"code":200}');
		return;
	}

	if(!isset($data['sign']) || strlen($data['sign']) != 32)
	{
		$response->end('{"code":900000}');
		return;
	}

	if(!isset($data['total_price']) || $data['total_price'] <= 0)
	{
		$response->end('{"code":900001}');
		return;
	}

	if(!isset($data['cp_order_id']) || !check_simple_order_format($data['cp_order_id']))
	{
		$response->end('{"code":900002}');
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
	unset($data['sign'], $data['sign_type']);

	if(!\sign::meizu_decode('POST', self::CMD_NUM, $data, $sign))
	{
		$response->end('{"code":900003}');
		return;
	}

	$order	= $data['cp_order_id'];
	$cash	= $data['total_price'];
	$ooid	= $data['order_id'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_MEIZU_ORIGIN)))
	{
		write_log('meizu_fail', 'set_deliveryed_status:'.$order);
		$response->end('{"code":120014}');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_MEIZU_ORIGIN)));
		//	\service\log_cmd::make_log('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_MEIZU_ORIGIN));
			break;
		case 2://@非法订单
			break;
		default://已发货
			break;
	}
	///////////////////////////////////////////////////
	$response->end('{"code":200}');

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
