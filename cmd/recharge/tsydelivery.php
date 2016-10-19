<?php
namespace cmd\recharge;
/*
* 淘手游发货处理接口
* auther zzd
*/
class tsydelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/tsydelivery.xl';
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
		'bizno',
		'total_fee',
	);

	if(!isset($data['appid']) || $data['appid'] !== SECRET_TAOSHOUYOU_APPID)
	{
		$response->end(404);
		return;
	}

	if(!isset($data['signature']) || strlen($data['signature']) != 32)
	{
		$response->end(404);
		return;
	}

	if(!isset($data['goods_data']) || !check_simple_order_format($data['goods_data']) )
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

	$cash	= $data['total_fee'];

	if($cash <= 0 )
	{
			$response->end('fail');
			return;
	}

	$sign = $data['signature'];
	unset($data['signature']);

	if(!\sign::tsy_decode('POST', self::CMD_NUM, $data, $sign))
	{
		write_log('tsy_sign_fail',1);
		$response->end('fail');
		return;
	}

	$order	= $data['goods_data'];
	$ooid	= $data['bizno'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_TAOSHOUYOU_ORIGIN)))
	{
		write_log('tsy_fail', 'set_deliveryed_status:'.$order);
		$response->end('fail');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_TAOSHOUYOU_ORIGIN)));
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
