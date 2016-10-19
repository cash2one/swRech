<?php
namespace cmd\recharge;
/*
* DIANZHI发货处理接口
* auther zzd
*/
class dianzhidelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/dianzhi/delivery.xl';
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

	if(!isset($data['sign']) || strlen($data['sign']) != 32)
	{
		$response->end('{"status":2,"msg":"arg fail!"}');
		return;
	}


	$pindex	= array
	(
		'game_id',
		'out_trade_no',
		'price',
		'extend',
	);

	$temp = [];

	foreach($pindex as $k)
	{
		if(!isset($data[$k]))
		{
			$response->end('{"status":2,"msg":"arg fail!"}');
			return;
		}
		$temp[$k] = $data[$k];
	}

	if( $data['price'] <= 1)
	{
		$response->end('{"status":2,"msg":"arg fail1!"}');
		return;
	}

	if(!check_simple_order_format($data['extend']))
	{
		$response->end('{"status":2,"msg":"arg fail2!"}');
		return;
	}

	if(md5(implode("",$temp)."DZQ!@#9527") !== $data['sign'])
	{
		write_log('dianzhi_fail', 'sign:');
		$response->end('{"status":2,"msg":"fail!"}');
		return;
	}

	$order	= $data['extend'];
	$cash	= $data['price'];
	$ooid	= "dianzhi";

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_DIANZHI_ORIGIN)))
	{
		write_log('dianzhi_fail', 'set_deliveryed_status:'.$order);
		$response->end('0');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_DIANZHI_ORIGIN)));
			break;
		case 2://@非法订单
			break;
		default://已发货
			break;
	}
	///////////////////////////////////////////////////
	$response->end('1');

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
