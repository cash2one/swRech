<?php
namespace cmd\recharge;
/*
* 天游发货处理接口
* auther zzd
*/
class xianqucyjhdelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/xianqu_cyjh_delivery.xl';
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

	if( !isset($data['verstring']) || strlen($data['verstring']) != 32)
	{
		$response->end('{"code":406}');
		return;
	}

	$pindex	= array
	(
		'trade_no',
		'cpid',
		'game_seq_num',
		'server_seq_num',
		'amount',
		'user_id',
		'ext_info',
		'timestamp',
	);

	$temp = array();

	foreach($pindex as $k)
	{
		if(!isset($data[$k]))
		{
			$response->end('{"code":4}');
			return;
		}

		$temp [] = $k ."=". ($data[$k]);
	}

	if( $data['cpid'] !== SECRET_XIANQUCYJH_CPID)
	{
		$response->end('{"code":405}');
		return;
	}

	if( $data['game_seq_num'] !== SECRET_XIANQUCYJH_APPID)
	{
		$response->end('{"code":405}');
		return;
	}

	$sign = $data['verstring'];
	unset($data['remark'], $data['verstring']);

	$temp	= implode('&', $temp).'&SecretKey='.SECRET_XIANQUCYJH_SECRET;

	if($sign !== md5($temp))
	{
		write_log('xianqucyjh_fail','sig'.$temp);
		$response->end('fail');
		return;
	}
	
	$now = time();

	if(isset($data['ext_info']) )
	{
		if(!check_simple_order_format($data['ext_info']))
		{
			$response->end('{"code":404}');
			return;
		}

		$order	= $data['ext_info'];
		$origin	= SECRET_XIANQUCYJH_ORIGIN;
	}
	else
	{
		$response->end('{"code":404}');
		return;
	}

	$cash	= $data['amount'];

	if($cash < 1 )
	{
			$response->end('{"code":407}');
			return;
	}

	$ooid	= $data['trade_no'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, $now,$ooid,$origin)))
	{
		write_log('xianqucyjh_fail', 'set_deliveryed_status:'.$order);
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
	$response->end("success");

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
