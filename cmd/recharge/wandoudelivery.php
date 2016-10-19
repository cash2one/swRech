<?php
namespace cmd\recharge;
/*
* WANDOU发货处理接口
* auther zzd
*/
class wandoudelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/wandoudelivery.xl';
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

	$data = $request->post;

	if(!isset($data['content']))
	{
		$response->end('fail');
		return;
	}

	if(!isset($data['sign']))
	{
		$response->end('fail');
		return;
	}

	if(!\sign::wandou_decode($data['content'], $data['sign']))
	{
		$response->end('fail1');
		return;
	}

	if(!$data = json_decode($data['content'],true))
	{
		$response->end('fail2');
		return;
	}

	if(!isset($data['appKeyId']) || $data['appKeyId'] != SECRET_WANDOU_APPID)
	{
		$response->end('fail3');
		return;
	}


	if(!isset($data['money']) || $data['money'] < 0.01 )
	{
		$response->end('fail');
		return;
	}


	if(!isset($data['out_trade_no']) || !check_simple_order_format($data['out_trade_no']))
	{
		$response->end('fail');
		return;
	}


	$order	= $data['out_trade_no'];
	$cash	= round($data['money']/100,2);
	$ooid	= $data['orderId'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_WANDOU_ORIGIN)))
	{
		write_log('wandou_fail', 'set_deliveryed_status:'.$order);
		$response->end('fail}');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_WANDOU_ORIGIN)));
		//	\service\log_cmd::make_log('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_WANDOU_ORIGIN));
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
