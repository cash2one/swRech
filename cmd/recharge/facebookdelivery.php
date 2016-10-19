<?php
namespace cmd\recharge;
/*
* FACEBOOK发货处理接口
* auther zzd
*/
class facebookdelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/facebooknotice.xl';
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
		'uid',
		'receipt',
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

	unset($data['sig']);

	if(!\sign::asc_decode('POST', self::CMD_NUM, $data, $sign))
	{
		$response->end('{"ret":3}');
		return;
	}

	$signed_request = explode('.', $data['receipt']);

	if(count($signed_request) != 2)
	{
		$response->end('{"ret":404}');
		return;
	}

	$sign = base64_decode(strtr($signed_request[0],'-_', '+/'));

	if(!hash_equals(hash_hmac('sha256', $signed_request[1], SECRET_FACEBOOKPLAY_SCRET), $sign))
	{
		$response->end('{"ret":23}');
		return;
	}

	$contents = json_decode(base64_decode(strtr($signed_request[1],'-_', '+/')),true);

	if( !is_array($contents) )
	{
		$response->end('{"ret":404}');
		return;
	}

	if( $contents['status'] !== "completed")
	{
		$response->end('{"ret":404}');
		return;
	}

	if(!check_simple_order_format($contents['request_id']))
	{
		$response->end('{"ret":4}');
		return;
	}


	$order	= $contents['request_id'];

	$cash	= $contents['amount'];//\ios_price::get($itemId);

	if (!$cash)
	{
		$response->end('{"ret":10}');
		return;
	}

	$ooid	= $contents['payment_id'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_FACEBOOKPLAY_ORIGIN)))
	{
		write_log('facebook_fail', 'set_deliveryed_status:'.$order);
		$response->end('{"ret":7}');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_FACEBOOKPLAY_ORIGIN)));
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
