<?php
namespace cmd\recharge;
/*
* GOOGLE发货处理接口
* auther zzd
*/
class googledelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/googlenotice.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_RECHARGE;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if(isset($request->post))
	{
		$data = &$request->post;
	}
	else
	{
		$response->end('{"ret":404}');
		return;
	}

	$pindex	= array
	(
		'gid',
		'oid',
		'receipt',
		'intent',
		'time',
		'sig',
	);

	foreach($pindex as $k)
	{
		if(!isset($data[$k]))
		{
			$response->end('{"ret":404}');
			return;
		}
	}

	if(strlen($data['sig']) != 32)
	{
		$response->end('{"ret":404}');
		return;
	}

	$secretKeys = array(
		501 => SECRET_PANGGAME_PUBKEY,
	);

	if(!isset($secretKeys[$data['gid']]))
	{
		$response->end('{"ret":404}');
		return;
	}

	unset($data['sig']);

	if(!\sign::asc_decode('POST', self::CMD_NUM, $data, $sign))
	{
		$response->end('{"ret":3}');
		return;
	}


	if(!\sign::google_decode($data["receipt"], $data["intent"], $secretKeys[$data['gid']]))
	{
		$response->end('{"ret":23}');
		return;
	}

	$order	= $data['nonce'];

	if(!check_simple_order_format($order))
	{
		$response->end('{"ret":4}');
		return;
	}

	$orders = json_decode($data["receipt"],true);

	if (!isset($orders[0]["productId"])) 
	{
		$response->end('{"ret":24}');
		return;
	}

	$cash     = \ios_price::get($itemId);

	if (!$cash)
	{
		$response->end('{"ret":10}');
		return;
	}

	if (!isset($orders[0]["orderId"]))
	{
		$response->end('{"ret":24}');
		return;
	}

	$ooid	= $orders[0]["orderId"];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_GOOGLEPLAY_ORIGIN)))
	{
		write_log('google_fail', 'set_deliveryed_status:'.$order);
		$response->end('{"ret":7}');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_GOOGLEPLAY_ORIGIN)));
		//	\service\log_cmd::make_log('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_360_ORIGIN));
			break;
		case 2://@非法订单
			break;
		default://已发货
			break;
	}
	///////////////////////////////////////////////////
	$response->end('{"ret":0}');

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
