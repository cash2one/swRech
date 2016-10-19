<?php
namespace cmd\recharge;
/*
* XIAOYAO充值回调接口
* create 20160720
* auther zzd
*/
class xiaoyaodelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/xiaoyaodelivery.xl';
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
		write_log('xiaoyaoRequestErr', SUBSTR($request->rawContent(),0,100));
		return;
	}

	$pindex	= array
	(
		'amount',
		'custom',
		'order_no',
		'paid',
		'pay_type',
		'role_id',
		'server_id',
		'subject',
		'timestamp',
		'username',
		'signature'
	);

	foreach($pindex as $k)
	{
		if(!isset($data[$k]))
		{
			$response->end('FAILURE');
			return;
		}
	}

	if($data['amount'] < 10)
	{
		$response->end('FAILURE');
		return;
	}

	if(!check_simple_order_format($data['custom']) )
	{
		$response->end('FAILURE');
		return;
	}


	$sign = $data['signature'];
	unset($data['signature']);

	$temp = array();

	foreach($data as $k=>$v)
	{
		$temp[] = $k.'='.$v;
	}

	$temp[] = SECRET_XIAOYAO_SECRET;

	$temp = implode('&', $temp);

	if(md5($temp) !== $sign)
	{
		write_log('xiaoyao_fail', 'sign');
		$response->end('FAILURE');
		return;
	}

	$order	= $data['callbackInfo'];
	$cash	= intval(round($data['amount']/10,1));
	$ooid	= $data['order_no'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_XIAOYAO_ORIGIN)))
	{
		write_log('xiaoyao_fail', 'set_deliveryed_status:'.$order);
		$response->end('FAILURE');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_XIAOYAO_ORIGIN)));
		//	\service\log_cmd::make_log('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_XIAOYAO_ORIGIN));
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
