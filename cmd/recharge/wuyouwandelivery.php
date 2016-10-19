<?php
namespace cmd\recharge;
/*
* WUYOUWAN发货处理接口
* auther zzd
*/
class wuyouwandelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/wuyouwandelivery.xl';
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

	if(!isset($data['Sign']))
	{
		$response->status(404);
		$response->end('');
		return;
	}

	$pindex	= array
	(
		'OrderNo',
		'OutPayNo',
		'UserID',
		'ServerNo',
		'PayType',
		'Money',
		'PMoney',
		'PayTime',
	);

	$temp = "";

	foreach($pindex as $k)
	{
		if(!isset($data[$k]))
		{
			$response->end('4');
			return;
		}
		$temp .= $data[$k];
	}

	if(!check_simple_order_format($data['OutPayNo']))
	{
		$response->end('i8');
		return;
	}

	if(md5($temp.SECRET_WUYOUWAN_SECRET) !== strtolower($data['Sign']))
	{
		write_log('wuyouwan_fail', 'sign:'.($temp.SECRET_WUYOUWAN_SECRET));
		$response->end('fail');
		return;
	}

	$order	= $data['OutPayNo'];
	$cash	= $data['Money'];
	$ooid	= $data['OrderNo'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_WUYOUWANXQJ_ORIGIN)))
	{
		write_log('wuyouwan_fail', 'set_deliveryed_status:'.$order);
		$response->end('fail');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_WUYOUWANXQJ_ORIGIN)));
			break;
		case 2://@非法订单
			break;
		default://已发货
			break;
	}
	///////////////////////////////////////////////////
	$response->end(1);

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
