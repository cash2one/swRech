<?php
namespace cmd\recharge;
/*
* kuapai发货处理接口
* auther zzd
*/
class kupaidelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/kupaidelivery.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_RECHARGE;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if(!($content = $request->rawContent()) )
	{
		$response->end('{"ret":404}');
		return;
	}

	if(!\sign::kupai_decode($content, $data))
	{
		$response->end('FAILURE');
		return;
	}

	if(!isset($data['result']) || !is_numeric($data['result']) || $data['result'] != 0)
	{
		$response->end('SUCCESS');
		return;
	}

	if(!isset($data['appid']) || $data['appid'] !== SECRET_KUPAI_APPID)
	{
		$response->end('FAILURE');
		return;
	}

	if(!isset($data['cporderid']) || !check_simple_order_format($data['cporderid']))
	{
		$response->end('FAILURE');
		return;
	}

	$order	= $data['cporderid'];
	$cash	= $data['money'];
	$ooid	= $data['transid'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_KUPAI_ORIGIN)))
	{
		write_log('kupai_fail', 'set_deliveryed_status:'.$order);
		$response->end('FAILURE');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_KUPAI_ORIGIN)));
		//	\service\log_cmd::make_log('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_KUPAI_ORIGIN));
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
