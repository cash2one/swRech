<?php
namespace cmd\recharge;
/*
* G265发货处理接口
* auther zzd
*/
class g265delivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/g265/delivery.xl';
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

	$pindex	= array
	(
		'gameid',
		'oid',
		'money',
		'nonce',
		'orderid',
		'timestamps',
		'sign',
	);

	foreach($pindex as $k)
	{
		if(!isset($data[$k]))
		{
			$response->end('{"status":2,"msg":"arg fail!"}');
			return;
		}
	}

	if(strlen($data['sign']) != 32)
	{
		$response->end('{"status":2,"msg":"arg fail!"}');
		return;
	}

	if( $data['money'] <= 1)
	{
		$response->end('{"status":2,"msg":"arg fail1!"}');
		return;
	}

	if(!check_simple_order_format($data['oid']))
	{
		$response->end('{"status":2,"msg":"arg fail2!"}');
		return;
	}

	$now	= time();

	if(abs($now-$data['timestamps']) > 120)
	{
		$response->end('{"status":2,"msg":"timestamps wrong !"}');
		return;
	}
	
	$sign	= $data['sign'];
	unset($data['sign']);

	if(md5(\sign::make_string_without_and($data).'jsd%adsg@1!sgfwdgt%233#$1g') != $sign)
	{
		write_log('g265_fail', 'sign:');
		$response->end('{"status":2,"msg":"fail!"}');
		return;
	}

	$order	= $data['oid'];
	$cash	= $data['cash'];
	$ooid	= $data['orderid'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_G265_ORIGIN)))
	{
		write_log('g265_fail', 'set_deliveryed_status:'.$order);
		$response->end('{"status":2,"msg":"arg fail1!"}');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_G265_ORIGIN)));
			break;
		case 2://@非法订单
			break;
		default://已发货
			break;
	}
	///////////////////////////////////////////////////
	$response->end('{"status":0,"msg":"success","data":[]}');

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
