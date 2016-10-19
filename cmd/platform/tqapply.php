<?php
namespace cmd\platform;
/*
* 天趣用户订单申请 
* auther zzd
*/
class tqapply extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/tq/apply.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_PLATFORM;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if(isset($request->post))
	{
		$pindex	= array
		(
			'openid',
			'token',
			'appid',
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
			$response->end(404);
			return;
	}

	//@校验时间
	$now	= time();

	if(abs($now-$data['ts']) > 300)
	{
		$response->end('{"ret":5}');
		return;
	}

	if (!\sign::checkAppId($data['appid']))
	{
		$response->end('{"ret":8}');
		return;
	}
	
	//@校验IP
//	if(!\white_list::check($request->header['x-real-ip']))
//	{
//		var_dump($request->header['x-real-ip']);
//		$response->end(9);
//		return;
//	}

//	if($data['token'] !== checkUserLogin($data['openid']) ) {
//		$response->end('{"ret":109}');
//		return;
//	}

	//@获取新的订单号
	if (!$new_order = get_simple_order($data['appid']))
	{
		$response->end('{"ret":6}');
		return;
	}

	$order_data = array
	(
		'appid'		=> $data['appid'],
		'openid'	=> $data['openid'],
		'exinfo'	=> $data['exinfo'],
		'oid'		=> $new_order,
		'ip'		=> $request->header['x-real-ip'],
	);

	if(!save_simple_order($new_order, $order_data))
	{
		$response->end('{"ret":7}');
		return;
	}

	$order_data['time'] = time();

	\event::dispatch(EVENT_DATA_LOG, array('order_applys', $order_data));
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
