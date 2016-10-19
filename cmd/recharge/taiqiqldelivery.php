<?php
namespace cmd\recharge;
/*
* TAIQIQL充值回调接口
* create 20160720
* auther zzd
*/
class taiqiqldelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/taiqiqldelivery.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_RECHARGE;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if(!isset($request->get))
	{
		$response->status(404);
		$response->end('');
		return;
	}

	$data = $request->get;

	if(!isset($data['respCode']) || $data['respCode'] !== '200')
	{
		$response->status(404);
		$response->end('');
		return;
	}

	if(!isset($data['signature']) || strlen($data['signature']) != 32 )
	{
		$response->status(404);
		$response->end('');
		return;
	}

	$pindex	= array
	(
		'transId',
		'markMsg',
		'respCode',
		'amount',
		'channelId',
		'userId',
	);

	$temp = "";

	foreach($pindex as $k)
	{
		if(!isset($data[$k]))
		{
			$response->end('{"code":1,"desc":"arg err"}');
			return;
		}

		$temp	.= $data[$k];
	}


	$sign = $data['signature'];

	if(md5($temp.SECRET_TAIQIQL_APPKEY) !== $sign)
	{
		write_log('taiqiql_fail', $sign);
		$response->end('{"code":2,"desc":"ticket err"}');
		return;
	}

	if(!check_simple_order_format($data['markMsg']) )
	{
		$response->end('{"code":3,"desc":"serverid err"}');
		return;
	}

	$order	= $data['markMsg'];
	$cash	= $data['amount'];
	$ooid	= $data['transId'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_TAIQIQL_ORIGIN)))
	{
		write_log('taiqiql_fail', 'set_deliveryed_status:'.$order);
		$response->end('{"code":4,"desc":"system err"}');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_TAIQIQL_ORIGIN)));
			break;
		case 2://@非法订单
			break;
		default://已发货
			break;
	}
	///////////////////////////////////////////////////
	$response->end('{"code":0}');

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
