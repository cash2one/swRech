<?php
namespace cmd\recharge;
/*
* HUAWEI发货处理接口
* auther zzd
*/
class huaweidelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/huaweidelivery.xl';
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

	if(!isset($data['result']) || !is_numeric($data['result'])|| $data['result'] != '0')
	{
		$response->end('{"result":1}');
		return;
	}

	if(!isset($data['sign']))
	{
		$response->end('{"result":2}');
		return;
	}

	if(!isset($data['orderId']))
	{
		$response->end('{"result":1}');
		return;
	}


	if(!isset($data['amount']) || $data['amount'] <= 0)
	{
		$response->end('{"result":3}');
		return;
	}

	if(!isset($data['requestId']) || !check_simple_order_format($data['requestId']))
	{
		$response->end('{"result":4}');
		return;
	}

	$sign = $data['sign'];
	unset($data['sign'], $data['sign_type']);

	if(!\sign::huawei_decode('POST', self::CMD_NUM, $data, $sign))
	{
		$response->end('{"result":5}');
		write_log('huawei_fail','sign');
		return;
	}

	$order	= $data['requestId'];
	$cash	= $data['amount'];
	$ooid	= $data['orderId'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_HUAWEI_ORIGIN)))
	{
		write_log('huawei_fail', 'set_deliveryed_status:'.$order);
		$response->end('{"result":94}');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_HUAWEI_ORIGIN)));
		//	\service\log_cmd::make_log('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_HUAWEI_ORIGIN));
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
