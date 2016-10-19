<?php
namespace cmd\recharge;
/*
* LESHI发货处理接口
* auther zzd
*/
class leshidelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/leshidelivery.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_RECHARGE;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if(isset($request->get))
	{
		$data = &$request->get;
	}
	else
	{
		$response->end(404);
		return;
	}


	if(!isset($data['trade_result']) || $data['trade_result'] !== 'TRADE_SUCCESS')
	{
		$response->end('FAL');
		return;
	}

	if(!isset($data['app_id']) || $data['app_id'] != SECRET_LESHI_APPID)
	{
		$response->end('FAL');
		return;
	}

	if(!isset($data['sign']) || strlen($data['sign']) != 32)
	{
		$response->end('fal3');
		return;
	}

	if(!isset($data['original_price']) || $data['original_price'] < 0.01)
	{
		$response->end('fal3');
		return;
	}

	if(!isset($data['cooperator_order_no']) || !check_simple_order_format($data['cooperator_order_no']) )
	{
		$response->end('fal3');
		return;
	}

	$sign = $data['sign'];
	unset($data['sign'] );

	if(!\sign::leshi_decode('POST', self::CMD_NUM, $data, $sign))
	{
		$response->end(3);
		return;
	}

	if(!isset($data['lepay_order_no']))
	{
		$response->end('fal3');
		return;
	}

	$order	= $data['cooperator_order_no'];
	$cash	= $data['original_price'];
	$ooid	= $data['lepay_order_no'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_LESHI_ORIGIN)))
	{
		write_log('leshi_fail', 'set_deliveryed_status:'.$order);
		$response->end(404);
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_LESHI_ORIGIN)));
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
