<?php
namespace cmd\recharge;
/*
* YUWAN发货处理接口
* auther zzd
*/
class yuwandelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/yuwandelivery.xl';
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

	if(!isset($data['sign']))
	{
		$response->end('{"result":1}');
		return;
	}

	if(!isset($data['status']) || $data['status'] != 1)
	{
		$response->end('{"result":2}');
		return;
	}

	if(!isset($data['amount']) || $data['amount'] < 100)
	{
		$response->end('{"result":3}');
		return;
	}

	if(!isset($data['custominfo']) || !check_simple_order_format($data['custominfo']))
	{
		$response->end('{"result":4}');
		return;
	}

	$pindex = array
	(
		'serverid'	,
		'custominfo',
		'openid'	,
		'ordernum'	,
		'status'	,
		'paytype'	,
		'amount'	,
		'errdesc'	,
		'paytime'	,
	);

	foreach($pindex as $v)
	{
		if(!isset($data[$v]))
		{
			$response->end('{"result":404}');
			return;
		}

		$str_contents[] = $data[$v];
	}

	$str_contents[] = SECRET_YUWAN_SECRET;
	$str_contents = implode('|', $str_contents);

	if(md5($str_contents) !== strtolower($data['sign']))
	{
		$response->end('{"result":5}');
		return;
	}

	$order	= $data['custominfo'];
	$cash	= round($data['amount']/100,2);
	$ooid	= $data['ordernum'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_YUWAN_ORIGIN)))
	{
		write_log('yuwan_fail', 'set_deliveryed_status:'.$order);
		$response->end('{"result":94}');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_YUWAN_ORIGIN)));
			break;
		case 2://@非法订单
			break;
		default://已发货
			break;
	}
	///////////////////////////////////////////////////
	$response->end('{"result":0}');

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
