<?php
namespace cmd\recharge;
/*
* SHEDIAOYJZX发货处理接口
* auther zzd
*/
class shediaoyjzxdelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/shediao_yjzx_delivery.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_RECHARGE;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if(!isset($request->get))
	{
		$response->status(404);
		$response->end(404);
		return;
	}

	$data = &$request->get;

	$pindex	= array
	(
		'appId',
		'chargedRmbs',
		'orderId',
		'platformOrder',
		'prodCount',
		'code',
		'userId',
		'prodId',
	);

	foreach($pindex as $k)
	{
		if(!isset($data[$k]))
		{
			$response->end('4');
			return;
		}
	}

	$apps = array
	(
		10069=>'e116313afb7644e693ee5ac8485cbae0',
		10065=>'0dc3c7fcf26e4425bb059d4813592c34',
		10066=>'3de2cceb323d4f8e873490f752aeb752',
	);

	if(!isset($apps[$data['appId']]))
	{
		$response->end('5');
		return;
	}

	if(strlen($data['code']) != 32)
	{
		$response->end('5');
		return;
	}

	if( $data['chargedRmbs'] <= 1)
	{
		$response->end('i7');
		return;
	}

	if(!check_simple_order_format($data['orderId']))
	{
		$response->end('i8');
		return;
	}

	$sign	= $data['code'];
	unset($data['code']);
	$md5_key = $apps[$data['appId']];

	if(md5($md5_key.\sign::make_string($data).$md5_key) != $sign)
	{
		write_log('shediaoyjzx_fail', 'sign:'.$md5_key.\sign::make_string($data).$md5_key);
		$response->end('fail');
		return;
	}

	$order	= $data['orderId'];
	$cash	= $data['chargedRmbs'];
	$ooid	= $data['platformOrder'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_SHEDIAOYJZX_ORIGIN)))
	{
		write_log('shediaoyjzx_fail', 'set_deliveryed_status:'.$order);
		$response->end('fail');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_SHEDIAOYJZX_ORIGIN)));
			break;
		case 2://@非法订单
			break;
		default://已发货
			break;
	}
	///////////////////////////////////////////////////
	$response->end('success');

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
