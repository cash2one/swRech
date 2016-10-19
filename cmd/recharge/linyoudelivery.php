<?php
namespace cmd\recharge;
/*
* 麟游发货处理接口
* auther zzd
*/
class linyoudelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/linyoudelivery.xl';
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
		'orderId',
		'amount',
		'payChannel',
		'zone',
	);

	if(!isset($data['game']) || $data['game'] !== SECRET_LINYOU_APPID)
	{
		$response->end(404);
		return;
	}

	if(!isset($data['sign']) || strlen($data['sign']) != 32)
	{
		$response->end(404);
		return;
	}

	if(!isset($data['payExt']) || !check_simple_order_format($data['payExt']) )
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

	$cash	= round($data['amount']/100,2);

	if($cash <= 0 )
	{
			$response->end('fail');
			return;
	}

	if($data['sign'] !== md5(($data['game']).($data['orderId']).($data['amount']).($data['uid']).($data['zone']).($data['goodsId']).($data['payTime']).($data['payChannel']).($data['payExt']).'#'.SECRET_LINYOU_SECRET))
	{
		write_log('linyou_fail','sign');
		$response->end('fail');
		return;
	}

	$order	= $data['payExt'];
	$ooid	= $data['orderId'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_LINYOU_ORIGIN)))
	{
		write_log('linyou_fail', 'set_deliveryed_status:'.$order);
		$response->end('fail');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_LINYOU_ORIGIN)));
			break;
		case 2://@非法订单
			break;
		default://已发货
			break;
	}
	///////////////////////////////////////////////////
	$response->end(json_encode(array('errno'=>1000,'errmsg'=>'','data'=>array('orderId'=>$ooid,'amount'=>$data['amount'],'game'=>$data['game'],'zone'=>$data['zone'],'uid'=>$data['uid']))));

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
