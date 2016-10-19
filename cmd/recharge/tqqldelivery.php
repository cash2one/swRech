<?php
namespace cmd\recharge;
/*
* 玩家申请购买处理接口
* auther zzd
*/
class tqqldelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/tq/ql/delivery.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_RECHARGE;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if(isset($request->post))
	{
		$pindex	= array
		(
			'appid',
			'openid',
			'cash',
			'oid',
			'exinfo',
			'ts',
			'sig'
		);

		$data = &$request->post;

		if(count($data) < count($pindex) )
		{
			$response->end('{"ret":4}');
			return;
		}

		foreach($pindex as $k)
		{
			if(!isset($data[$k]) || $data[$k] === "" )
			{
				$response->end('{"ret":4}');
				return;
			}
		}

		$sign = $data['sig'];
		unset($data['sig']);

		if(!\sign::asc_decode('POST', self::CMD_NUM, $data, $sign))
		{
			$response->end('{"ret":3}');
			return;
		}
	}
	else
	{
		if($request->header['x-real-ip'] != '127.0.0.1')
		{
			$response->end(404);
			return;
		}
	}

	if(!check_simple_order_format($data['exinfo']))
	{
		$response->end('{"code":404}');
		return;
	}

	$order	= $data['exinfo'];
	$ooid	= $data['oid'];
	$cash	= round($data['cash']/100);
	$origin	= '999';

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, $now,$ooid,$origin)))
	{
		write_log('tq_fail', 'set_deliveryed_status:'.$order);
		$response->end('{"code":66}');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>$origin)));
			break;
		case 2://@非法订单
			break;
		default://已发货
			break;
	}
	///////////////////////////////////////////////////
	$response->end("ok");

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
