<?php
namespace cmd\recharge;
/*
* UC充值回调接口
* create 20160613
* auther zzd
*/
class ucdelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/ucdelivery.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_RECHARGE;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if(!$request->rawContent())
	{
		$response->end(404);
		return;
	}

	$data = json_decode($request->rawContent(),true);

	if(!is_array($data))
	{
			$response->end('FAILURE');
			write_log('ucRequestErr', SUBSTR($request->rawContent(),0,100));
			return;
	}

	if(!isset($data['data'],$data['sign']))
	{
			$response->end('FAILURE');
			return;
	}

	if(!isset($data['data']['orderStatus']) || $data['data']['orderStatus'] !== 'S')
	{
			$response->end('SUCCESS');
			return;
	}

	$pindex	= array
	(
		'orderId',
		'gameId',
		'accountId',
		'creator',
		'payWay',
		'failedDesc',
	);

	if(!isset($data['data']['amount']) || $data['data']['amount'] < 1)
	{
		$response->end('FAILURE');
		return;
	}

	if(!isset($data['data']['callbackInfo']) || !check_simple_order_format($data['data']['callbackInfo']) )
	{
		$response->end('FAILURE');
		return;
	}

	foreach($pindex as $k)
	{
		if(!isset($data['data'][$k]))
		{
			$response->end('FAILURE');
			return;
		}
	}

	$sign = $data['sign'];

	if(!\sign::uc_decode('POST', self::CMD_NUM, $data['data'], $sign))
	{
		write_log('uc_fail', 'sign');
		$response->end('FAILURE');
		return;
	}

	$order	= $data['data']['callbackInfo'];
	$cash	= intval($data['data']['amount']);
	$ooid	= $data['data']['orderId'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_UC_ORIGIN)))
	{
		write_log('uc_fail', 'set_deliveryed_status:'.$order);
		$response->end('FAILURE');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_UC_ORIGIN)));
		//	\service\log_cmd::make_log('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_UC_ORIGIN));
			break;
		case 2://@非法订单
			break;
		default://已发货
			break;
	}
	///////////////////////////////////////////////////
	$response->end('SUCCESS');

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
