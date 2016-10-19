<?php
namespace cmd\platform;
/*
* 天发货处理接口
* auther zzd
*/
class tqlftdelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/tq/lft/delivery.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_PLATFORM;

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
		'orderno',
		'fee',
		'token',
	);

	foreach($pindex as $k)
	{
		if(!isset($data[$k]))
		{
			$response->end('{"code":4}');
			return;
		}
	}

	if( strlen($data['token']) != 32)
	{
		$response->end('{"code":406}');
		return;
	}

	$cash	= $data['fee'];

	if($cash < 1 )
	{
			$response->end('{"code":407}');
			return;
	}

	if($data['token'] !== md5($data['orderno'].$data['fee']))
	{
		write_log('tq_fail','sig'.$str);
		$response->end('{"code":3}');
		return;
	}
	
	$now = time();

	if(!check_simple_order_format($data['orderno']))
	{
		$response->end('{"code":404}');
		return;
	}

	$order	= $data['orderno'];
	$origin	= 0;

	$ooid	= 'lft';

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, $now,$ooid,$origin)))
	{
		write_log('tq_fail', 'set_deliveryed_status:'.$order);
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
	$response->end("ok");

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
