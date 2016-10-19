<?php
namespace cmd\recharge;
/*
* 玩家申请购买处理接口
* auther zzd
*/
class tyouorders extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/tyouorders.xl';
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
			$response->end('{"code":4}');
			return;
		}

		foreach($pindex as $k)
		{
			if(!isset($data[$k]) || $data[$k] === "" )
			{
				$response->end('{"code":4}');
				return;
			}
		}

		if (!is_numeric($data['gid']))
		{
			$response->end('{"code":4}');
			return;
		}

		$cash = isset($data['cash'])?$data['cash']:0;
		$sign = $data['sig'];
		unset($data['sig'],$data['cash']);

		$str    = \sign::make_string($data).'&'.SECRET_TIANYOU_APPKEY;

		if($sign !== md5($str))
		{
			$response->end('{"code":3}');
			return;
		}
	}
	else
	{
		$response->status(404);
		$response->end('');
		return;
	}

	//@校验时间
	$now	= time();

	if(abs($now-$data['ts']) > 300)
	{
		$response->end('{"code":5}');
		return;
	}

	//@校验渠道
	if(!\game_server::check_sid($data['gid'],$data['sid']))
	{
		write_log('channel_check_err', $data);
		write_log('channel_check_err', ($data['gid']).'_'.($data['sid']));
		$response->end('{"code":8}');
		return;
	}

	//@获取新的订单号
	if (!$new_order = get_simple_order($data['gid']))
	{
		$response->end('{"code":6}');
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
		$response->end('{"code":7}');
		return;
	}

	$order_data['time'] = time();
	$order_data['cash'] = $cash;

	\event::dispatch(EVENT_DATA_LOG, array('order_apply', $order_data));

	$response->end(json_encode(array('code'=>200,'orderid'=>$new_order)));
}

/*
* 自定义任务响应方法
*/
//public	static	function resp_task($serv,$arg, $pass)
//{
//	echo "响应了 她上课";
//}

}
