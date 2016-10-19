<?php
namespace cmd\recharge;
/*
* OPPO发货处理接口
* auther zzd
*/
class oppodelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/oppodelivery.xl';
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

	if(!isset($data['notifyId']))
	{
		$response->end('{"result":2}');
		return;
	}

	if(!isset($data['price']) || $data['price'] < 1)
	{
		$response->end('{"result":3}');
		return;
	}

	if(!isset($data['partnerOrder']) || !check_simple_order_format($data['partnerOrder']))
	{
		$response->end('{"result":4}');
		return;
	}

	$sign = $data['sign'];
	unset($data['sign'] );

	$contents = $data;
	$str_contents = "notifyId={$contents['notifyId']}&partnerOrder={$contents['partnerOrder']}&productName={$contents['productName']}&productDesc={$contents['productDesc']}&price={$contents['price']}&count={$contents['count']}&attach={$contents['attach']}";

	if(!\rsa::verify($str_contents, $sign, SECRET_OPPO_PUBKEY))
	{
		$response->end('{"result":5}');
		return;
	}

	$order	= $data['partnerOrder'];
	$cash	= round($data['price']/100,2);
	$ooid	= $data['notifyId'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_OPPO_ORIGIN)))
	{
		write_log('oppo_fail', 'set_deliveryed_status:'.$order);
		$response->end('{"result":94}');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_OPPO_ORIGIN)));
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
