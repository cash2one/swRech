<?php
namespace cmd\recharge;
/*
* 玩家申请购买处理接口
* auther zzd
*/
class apply extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/apply.xl';
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
			'gid',
			'sid',
			'uid',
			'pid',
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

		$cash = isset($data['cash'])?$data['cash']:0;
		$sign = $data['sig'];
		unset($data['sig'],$data['cash']);

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

		$data = array
		(
			'gid'=>111,
			'sid'=>rand(11,99),
			'uid'=>rand(111,9999),
			'pid'=>rand(111111,999999),
			'ts' =>time(),
		);
	}

	//@校验时间
	$now	= time();

	if(abs($now-$data['ts']) > 300)
	{
		$response->end('{"ret":5}');
		return;
	}
	
	//@校验IP
//	if(!\white_list::check($request->header['x-real-ip']))
//	{
//		var_dump($request->header['x-real-ip']);
//		$response->end(9);
//		return;
//	}

	//@校验渠道
	if(!\game_server::check_sid($data['gid'],$data['sid']))
	{
		write_log('channel_check_err', $data);
		write_log('channel_check_err', ($data['gid']).'_'.($data['sid']));
		$response->end('{"ret":8}');
		return;
	}

	//@获取新的订单号
	if (!$new_order = get_simple_order($data['gid']))
	{
		$response->end('{"ret":6}');
		return;
	}

	$order_data = array
	(
		'gid'	=> $data['gid'],
		'sid'	=> $data['sid'],
		'uid'	=> $data['uid'],
		'pid'	=> $data['pid'],
		'oid'	=> $new_order,
		'ip'	=> $request->header['x-real-ip'],
	);

	if(!save_simple_order($new_order, $order_data))
	{
		$response->end('{"ret":7}');
		return;
	}

	$order_data['time'] = time();
	$order_data['cash'] = $cash;

	\event::dispatch(EVENT_DATA_LOG, array('order_apply', $order_data));
	//\service\log_cmd::make_log('order_apply', $order_data);

//	set_deliveryed_status($new_order,array($new_order, rand(10000,99999), time(), rand(1000000,9999999), '-1'));

	$response->end(json_encode(array('ret'=>0,'oid'=>$new_order)));
}

/*
* 自定义任务响应方法
*/
//public	static	function resp_task($serv,$arg, $pass)
//{
//	echo "响应了 她上课";
//}

}
