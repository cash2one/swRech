<?php
namespace cmd\recharge;
/*
* IOS发货校验处理接口
* auther zzd
*/
class iosdelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/iosnotice.xl';
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
			'gid'		,
			'oid'		,
			'ooid'		,
			'receipt'	,
			'time'		,
			'sig'		,
		);

		$data = &$request->post;

		if(count($data) != count($pindex) )
		{
			$response->end('{"ret":4}');
			return;
		}

		foreach($pindex as $k)
		{
			if(!isset($data[$k]) || $data[$k] === '' )
			{
				$response->end('{"ret":404}');
				return;
			}
		}

		$sign = $data['sig'];
		unset($data['sig']);
		//$receipt= $data['receipt'];
		CMD_DEBUG && write_log('test_ios', $data['receipt']);
		$data['receipt'] = rawurlencode($data['receipt']);//str_replace(' ','+', $data['receipt']);
		CMD_DEBUG && write_log('test_ios', rawurlencode($data['receipt']));

		if(!\sign::asc_decode('POST', self::CMD_NUM, $data, $sign))
		{
			$response->end('{"ret":3}');
			return;
		}
		$data['receipt'] = rawurldecode($data['receipt']);//str_replace(' ','+', $data['receipt']);
	}
	else
	{
		if($request->header['x-real-ip'] != '127.0.0.1')
		{
			$response->end('{"ret":9}');
			return;
		}

		$data = array
		(
			'receipt'=>'sdasda',
			'oid'=>$request->get['oid'],
			'ooid'=>'sdadd',
			'time' =>time(),
		);
	}

	//@校验时间
	$now	= time();
	if(abs($now-$data['time']) > 60)
	{
		$response->end('{"ret":5}');
		return;
	}

	$gid	= intval($data['gid']);
	$order	= $data['oid'];
	$ooid	= $data['ooid'];
	$receipt= $data['receipt'];

	if( !$redis_res = check_deliveryed_status($order,$gid) )
	{
		//@redis 异常
		write_log('ios_fail', 'check_deliveryed_status:'.$order);
		$response->end('{"ret":7}');
		return;
	}

	switch($redis_res)
	{
		case 1://@进行receipt进行校验
			break;
		case 2://@该订单正在发货
			$response->end('{"ret":2013}');
			return;
		case 4:
			$response->end('{"ret":2014}');
			return;
		default:
			//@非法订单
			$response->end('{"ret":21}');
			return;
	}

	if(redis_deal(DB_FOR_IOS_RECEIPT,'EXISTS',$ooid))
	{
		$response->end('{"ret":6}');
		return;
	}

	$postData	= json_encode(array('receipt-data' => $receipt));//,'password'=>SECRET_IOS_APPKEY)); 

//	\worker::task_push(array(WT_PROTOCOL_IOS_CHECK,$postData),array(__CLASS__, 'resp_check_receipt'),array($response,$order,$ooid,$receipt));	

	$url = 'buy.itunes.apple.com';

	\async_http::do_http($url, '/verifyReceipt', $postData, array(__CLASS__, 'resp_check_receipt'), array($response,$order,$ooid,$receipt),443,true);

	return;
}

//@响应返回 一个是异步http 一个是异步task
public	static	function resp_check_receipt( $ress, $transfer)
//public	static	function resp_check_receipt($serv, $ress, $transfer)
{
	CMD_DEBUG && write_log('iosres', $ress);
	$response	= $transfer[0];
	$order		= $transfer[1];
	$ooid		= $transfer[2];
	$receipt	= $transfer[3];
	////@@计算分析@
	$res = json_decode($ress);

	if(!is_object($res))
	{
		CMD_DEBUG && write_log('ios_fail', $ress);
		$response->end('{"ret":22}');
		return;
	}

	if (!isset($res->status) || $res->status != 0)
	{
		if($res->status != 21007)
		{
			write_log('ios_fail', $res->status);
			$response->end('{"ret":23}');
			return;
		}

		$url = 'sandbox.itunes.apple.com';
		\async_http::do_http($url, '/verifyReceipt', json_encode(array('receipt-data' => $receipt)), array(__CLASS__, 'resp_recheck_receipt'), array($response,$order,$ooid,$receipt),443,true);
		return;
	}

	$origin_order	= $res->receipt->in_app[0]->transaction_id;

	if($origin_order != $ooid)
	{
		write_log('ios_fail', 'f:'.$origin_order.'-b:'.$ooid);
		$response->end('{"ret":24}');
		return;
	}

	if(!redis_deal(DB_FOR_IOS_RECEIPT,'SETNX',$ooid,1))
	{
		$response->end('{"ret":2012}');
		return;
	}

	$itemId		= $res->receipt->in_app[0]->product_id;
	//@验证
	if(!$cash = \ios_price::get($itemId))
	{
		$response->end('{"ret":10}');
		return;
	}

	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(), $ooid, SECRET_IOS_ORIGIN)))
	{//@订单库异常
		write_log('ios_fail', 'set_deliveryed_status:'.$redis_res);
		$response->end('{"ret":7}');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_IOS_ORIGIN)));
			//write_log('ios_delivery', 'order:'.$order.'-receipt:'.$receipt);
			break;
		case 2://@非法订单
			break;
		default://已发货
			break;
	}

	$response->end('{"ret":0}');

}

//@响应返回 一个是异步http 一个是异步task
public	static	function resp_recheck_receipt( $ress, $transfer)
//public	static	function resp_recheck_receipt($serv, $ress, $transfer)
{
	CMD_DEBUG && write_log('iosres', $ress);
	$response	= $transfer[0];
	$order		= $transfer[1];
	$ooid		= $transfer[2];
	$receipt	= $transfer[3];
	////@@计算分析@
	$res = json_decode($ress);

	if(!is_object($res))
	{
		CMD_DEBUG && write_log('ios_fail', $ress);
		$response->end('{"ret":22}');
		return;
	}

	if (!isset($res->status) || $res->status != 0)
	{
		write_log('ios_fail', $res->status);
		$response->end('{"ret":23}');
		return;
	}

	$origin_order	= $res->receipt->in_app[0]->transaction_id;

	if($origin_order != $ooid)
	{
		write_log('ios_fail', 'f:'.$origin_order.'-b:'.$ooid);
		$response->end('{"ret":24}');
		return;
	}

	if(!redis_deal(DB_FOR_IOS_RECEIPT,'SETNX',$ooid,1))
	{
		$response->end('{"ret":2012}');
		return;
	}

	$itemId		= $res->receipt->in_app[0]->product_id;
	//@验证
	if(!$cash = \ios_price::get($itemId))
	{
		$response->end('{"ret":10}');
		return;
	}

	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(), $ooid, SECRET_IOS_ORIGIN)))
	{//@订单库异常
		write_log('ios_fail', 'set_deliveryed_status:'.$redis_res);
		$response->end('{"ret":7}');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_IOS_ORIGIN)));
			//write_log('ios_delivery', 'order:'.$order.'-receipt:'.$receipt);
			break;
		case 2://@非法订单
			break;
		default://已发货
			break;
	}

	$response->end('{"ret":0}');

}

}
