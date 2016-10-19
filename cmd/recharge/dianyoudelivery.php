<?php
namespace cmd\recharge;
/*
* DIANYOU充值回调接口
* create 20160720
* auther zzd
*/
class dianyoudelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/dianyoudelivery.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_RECHARGE;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if(!isset($request->post))
	{
		$response->end(404);
		return;
	}

	$data = $request->post;

	if(!isset($data['st']) || $data['st'] != 1)
	{
		$response->end('FAILURE0');
		return;
	}

	$pindex	= array
	(
		'app',
		'ct',
		'fee',
		'pt',
		'sdk',
		'ssid',
		'tcd',
		'uid',
		'ver',
		'sign',
	);

	foreach($pindex as $k)
	{
		if(!isset($data[$k]))
		{
			$response->end('FAILURE1');
			return;
		}
	}

	if($data['fee'] < 10)
	{
		$response->end('FAILURE2');
		return;
	}

//	if($data['app'] !== SECRET_DIANYOU_APPID)
//	{
//		$response->end('FAILURE3');
//		return;
//	}

	if(!check_simple_order_format($data['ssid']) )
	{
		$response->end('FAILURE');
		return;
	}


	$sign = $data['sign'];
	unset($data['sign']);

	$temp = \sign::make_string($data);

	if(\sign::strToHex(md5($temp.SECRET_DIANYOU_SECRET)) !== $sign)
	{
		write_log('dianyou_fail', sign);
		$response->end('FAILURE');
		return;
	}

	$order	= $data['ssid'];
	$cash	= intval(round($data['fee']/100,1));
	$ooid	= $data['tcd'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_DIANYOU_ORIGIN)))
	{
		write_log('dianyou_fail', 'set_deliveryed_status:'.$order);
		$response->end('FAILURE');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_DIANYOU_ORIGIN)));
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
public	static	function resp_task($serv,$arg, $pass)
{
	echo "响应了 她上课";
}

}
