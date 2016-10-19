<?php
namespace cmd\recharge;
/*
* 易接充值回调处理接口
* auther zzd
*/
class yijiedelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/yijie/delivery.xl';
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
		$response->end(404);
		return;
	}

	$data = &$request->get;

	$pindex	= array
	(
		'app',
		'cbi',
		'ct',
		'fee',
		'pt',
		'sdk',
		'ssid',
		'st',
		'tcd',
		'uid',
		'ver',
		'sign',
	);

	foreach($pindex as $k)
	{
		if(!isset($data[$k]))
		{
			$response->end('{"status":2,"msg":"arg fail!"}');
			return;
		}
	}

	$apps = array(
		SECRET_SHANGFANG_CPID=>SECRET_SHANGFANG_APPKEY,
	);

	if($data['st'] != 1 || !isset($apps[$data['app']]) || strlen($data['sign']) != 32 )
	{
		$response->end('{"status":2,"msg":"arg fail!"}');
		return;
	}

	if( $data['fee'] <= 100)
	{
		$response->end('{"status":2,"msg":"arg fail1!"}');
		return;
	}

	if(!check_simple_order_format($data['cbi']))
	{
		$response->end('{"status":2,"msg":"arg fail2!"}');
		return;
	}

	$sign	= $data['sign'];
	unset($data['sign']);

	if(md5(\sign::make_string($data).$apps[$data['app']]) != $sign)
	{
		write_log('yijie_fail', 'sign:');
		$response->end('{"status":2,"msg":"fail!"}');
		return;
	}

	$order	= $data['cbi'];
	$cash	= floor($data['fee']/100);
	$ooid	= $data['tcd'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_YIJIE_ORIGIN)))
	{
		write_log('yijie_fail', 'set_deliveryed_status:'.$order);
		$response->end('{"status":2,"msg":"arg fail1!"}');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_YIJIE_ORIGIN)));
			break;
		case 2://@非法订单
			break;
		default://已发货
			break;
	}
	///////////////////////////////////////////////////
	$response->end('{"status":0,"msg":"success","data":[]}');

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
