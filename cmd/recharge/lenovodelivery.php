<?php
namespace cmd\recharge;
/*
* LENOVO发货处理接口
* auther zzd
*/
class lenovodelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/lianxiangdelivery.xl';
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

	if(!isset($data['transdata'])) 
	{
		$response->end('FAILTURE');
		return;
	}

	if(!$resp = \sign::lenovo_decode($data['transdata'], $data['sign']))
	{
		$response->end('FAILTURE');
		return;
	}

	if(!$resp = json_decode($resp, true))
	{
		$response->end('FAILTURE');
		return;
	}

	if(!isset($resp['appid']) || $resp['appid'] !== SECRET_LENOVO_APPID)
	{
		$response->end('FAILTURE');
		return;
	}

	if(!isset($resp['result']) || $resp['result'] !== 0)
	{
		$response->end('FAILTURE');
		return;
	}

	$ooid	 		= $resp['transid'];
	$cash			= round($resp['money']/100,2);
	$order			= $resp['exorderno'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_LENOVO_ORIGIN)))
	{
		write_log('lenovo_fail', 'set_deliveryed_status:'.$order);
		$response->end('FAILTURE');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_LENOVO_ORIGIN)));
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
//public	static	function resp_task($serv,$arg, $pass)
//{
//	echo "响应了 她上课";
//}

}
