<?php
namespace cmd\recharge;
/*
* jinli发货处理接口
* auther zzd
*/
class jinlidelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/jinlidelivery.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_RECHARGE;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if(!isset($request->post) )
	{
		$response->status(404);
		$response->end('');
		return;
	}


	$data = &$request->post;

	if(!isset($data['sign']))
	{
		$response->status(404);
		$response->end('');
		return;
	}


	$sign = $data['sign'];
	unset($data['sign']);

	if(!\sign::jinli_decode($data, $sign))
	{
		$response->end('FAILURE1');
		return;
	}

	if(!isset($data['api_key']) || $data['api_key'] !== SECRET_JINLI_APPKEY)
	{
		$response->end('FAILURE');
		return;
	}

	if(!isset($data['out_order_no']) || !check_simple_order_format($data['out_order_no']))
	{
		$response->end('FAILURE2');
		return;
	}

	$order	= $data['out_order_no'];
	$cash	= $data['deal_price'];
	$ooid	= '1';

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_JINLI_ORIGIN),0))
	{
		write_log('jinli_fail', 'set_deliveryed_status:'.$order);
		$response->end('FAILURE');
		return;
	}

	if(strlen($redis_res) > 1)
	{
		\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$redis_res, 'origin'=>SECRET_JINLI_ORIGIN)));
	}

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
