<?php
namespace cmd\recharge;
/*
* 天游发货处理接口
* auther zzd
*/
class tyouxlj3ddelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/tyou_xlj_delivery.xl';
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
		'userid',
		'orderid',
		'money',
		'appid',
		'sign',
	);

	foreach($pindex as $k)
	{
		if(!isset($data[$k]))
		{
			$response->end('{"code":4}');
			return;
		}
	}

	if( $data['appid'] !== SECRET_TIANYOUXLJ3D_APPID)
	{
		$response->end('{"code":405}');
		return;
	}

	if( strlen($data['sign']) != 32)
	{
		$response->end('{"code":406}');
		return;
	}

	$cash	= $data['money'];

	if($cash < 1 )
	{
			$response->end('{"code":407}');
			return;
	}

	$sign = $data['sign'];
	unset($data['sign'], $data['signtype']);

	$str	= \sign::make_string($data).'&'.SECRET_TIANYOUXLJ3D_APPKEY;

	if($sign !== md5($str))
	{
		write_log('tyouxlj_fail','sig'.$str);
		$response->end('{"code":3}');
		return;
	}
	
	$now = time();

	if(isset($data['custom_info']) )
	{
		if(!check_simple_order_format($data['custom_info']))
		{
			$response->end('{"code":404}');
			return;
		}

		$order	= $data['custom_info'];
		$origin	= SECRET_TIANYOUXLJ3D_ORIGIN;
	}
	else
	{
		$response->end('{"code":404}');
		return;
	}

	$ooid	= $data['orderid'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, $now,$ooid,$origin)))
	{
		write_log('tyouxlj_fail', 'set_deliveryed_status:'.$order);
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
	$response->end("{\"code\":200,\"order\":$order}");

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
