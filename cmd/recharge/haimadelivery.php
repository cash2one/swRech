<?php
namespace cmd\recharge;
/*
* HAIMA发货处理接口
* auther zzd
*/
class haimadelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/haimadelivery.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_RECHARGE;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if(!($content = $request->rawContent()))
	{
		$response->status(404);
		$response->end('11');
		return;
	}

	$content = array_map(create_function('$v', 'return explode("=", $v);'), explode('&',$content));;

	$data = array();

	foreach($content as $value)
	{
		$data[$value[0]] = $value[1];
	}

	if(!isset($data['sign']) || strlen($data['sign']) != 32)
	{
		$response->status(404);
		$response->end('333');
		return;
	}

	$pindex	= array
	(
		'notify_time',
		'appid',
		'out_trade_no',
		'total_fee',
		'subject',
		'body',
		'trade_status',
	);

	$md5_str = array();

	foreach($pindex as $k)
	{
		if(!isset($data[$k]))
		{
			$response->end(404);
			return;
		}

		$md5_str[] = $k.'='.($data[$k]);
	}

	if($data['trade_status'] != 1)
	{
		$response->end('success');
		return;
	}

	if($data['appid'] !== SECRET_HAIMA_APPID)
	{
		$response->end(404);
		return;
	}

	if(!check_simple_order_format($data['out_trade_no']) )
	{
		$response->end(404);
		return;
	}

	$cash	= $data['total_fee'];

	if($cash < 1 )
	{
			$response->end('fail');
			return;
	}

	if( md5(implode('&', $md5_str).SECRET_HAIMA_SECRET) != $data['sign'])
	{
		write_log('haima_sign_fail',1);
		$response->end('fail');
		return;
	}

	$order	= $data['out_trade_no'];
	$ooid	= 'haima';

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_HAIMA_ORIGIN)))
	{
		write_log('haima_fail', 'set_deliveryed_status:'.$order);
		$response->end('fail');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_HAIMA_ORIGIN)));
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
