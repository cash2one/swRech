<?php
namespace cmd\recharge;
/*
* DANGLE发货处理接口
* auther zzd
*/
class dangledelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/dangledelivery.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_RECHARGE;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if(isset($request->get))
	{
		$data = &$request->get;
	}
	else
	{
		$response->end('405');
		return;
	}

	if(!isset($data['result']) || $data['result'] != 1)
	{
		$response->end('success');
		return;
	}

	if(!isset($data['signature']) || strlen($data['signature']) != 32)
	{
		$response->end('failure3');
		return;
	}

	if(!isset($data['money']) || $data['money'] < 0.01)
	{
		$response->end('failure1');
		return;
	}

	if(!isset($data['ext']) || !check_simple_order_format($data['ext']) )
	{
		$response->end('failure2');
		return;
	}

	$signstr = 'order='.($data['order']).'&money='.($data['money']).'&mid='.($data['mid']).'&time='.($data['time']).'&result='.($data['result']).'&ext='.($data['ext']).'&key='.SECRET_DANGLE_SECRET;

	if(md5($signstr) !== $data['signature'])
	{
		$response->end('failure');
		return;
	}

	$order	= $data['ext'];
	$cash	= $data['money'];
	$ooid	= $data['order'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_DANGLE_ORIGIN)))
	{
		write_log('dangle_fail', 'set_deliveryed_status:'.$order);
		$response->end('failure');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_DANGLE_ORIGIN)));
		//	\service\log_cmd::make_log('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_DANGLE_ORIGIN));
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
public	static	function resp_task($serv,$arg, $pass)
{
	echo "响应了 她上课";
}

}
