<?php
namespace cmd\recharge;
/*
* ANZHI发货处理接口
* auther zzd
*/
class anzhidelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/anzhidelivery.xl';
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
		$response->end('');
		return;
	}

	$data = &$request->post;

	if(!isset($data['data'])) 
	{
		$response->end('failed');
		return;
	}

	if(!$resp = \des::decrypt($data['data'], SECRET_ANZHI_SECRET))
	{
		$response->end('failed');
		return;
	}

	if(!$resp = json_decode($resp, true))
	{
		$response->end('failed');
		return;
	}

	if(!isset($resp['code']) || $resp['code'] != 1)
	{
		$response->end('failed');
		return;
	}

	//$uid			= $resp['uid']; //匿名支付为空
	//$orderId 		= $resp['orderId'];
	$ooid	 		= $resp['orderId'];
	//$orderAmount	= $resp['orderAmount'];
	$cash			= $resp['orderAmount'];
//	$orderTime		= $resp['orderTime'];
//	$orderAccount	= $resp['orderAccount'];
//	$code			= $resp['code'];
//	$payAmount		= $resp['payAmount'];
//	$cpInfo			= $resp['cpInfo'];
	$order			= $resp['cpInfo'];
//	$notifyTime		= $resp['notifyTime'];
//	$memo			= $resp['memo'];

	if($cash < 100)
	{
		$response->end('failed');
		return;
	}

	if(isset($resp['redBagMoney']))
		$cash += $resp['redBagMoney'];
	
	$cash = round($cash/100,1);


	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_ANZHI_ORIGIN)))
	{
		write_log('anzhi_fail', 'set_deliveryed_status:'.$order);
		$response->end('failed');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_ANZHI_ORIGIN)));
		//	\service\log_cmd::make_log('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_ANZHI_ORIGIN));
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
