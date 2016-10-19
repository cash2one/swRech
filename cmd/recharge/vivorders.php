<?php
namespace cmd\recharge;
/*
* VIVO订单申请接口
* auther zzd
*/
class vivorders extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/vivoorders.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_RECHARGE;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	$response->status(404);
	$response->end('');
	return;
	if(!isset($request->post))
	{
		$response->status(404);
		$response->end(404);
		return;
	}

	$pindex = array
	(
		'uid',
	);

	$data = &$request->post;

	if(!isset($data['sig']))
	{
		$response->end('{"ret":4}');
		return;
	}

	$sign = $data['sig'];
	unset($data['sig']);

	if(!\sign::asc_decode('POST', self::CMD_NUM, $data, $sign))
	{
		$response->end('{"ret":3}');
		return;
	}

	foreach($pindex as $k)
	{
		if(!isset($data[$k]))
		{
			$response->end('{"ret":4}');
			return;
		}
	}

	if(!isset($data['gid']) || $data['gid'] !== CHANNEL_ID_VIVO)
	{
		$response->end('{"ret":8}');
		return;
	}

	if(!isset($data['cash']) || $data['cash'] <=0 )
	{
		$response->end('{"ret":404}');
		return;
	}

	if(!isset($data['oid']) || !check_simple_order_format($data['oid']))
	{
		$response->end('{"ret":404}');
		return;
	}

	if(!$redis_res = check_deliveryed_status($data['oid'],CHANNEL_ID_VIVO))
	{
		$response->end('{"ret":6}');
		return;
	}

	switch($redis_res)
	{
		case 1:
			break;
		case 2:
			$response->end('{"ret":20}');
			return;
		case 4:
			$response->end('{"ret":211}');
			return;
		default:
			$response->end('{"ret":212}');
			return;
	}

	$postData = array
	(
		'version'		=>	'1.0.0',
		'cpId'			=>	SECRET_VIVO_CPID,
		'appId'			=>	SECRET_VIVO_APPID,
		'cpOrderNumber'	=>	$data['oid'],
		'notifyUrl'		=>	SECRET_VIVO_NOTIFY_URL,
		'orderTime'		=>	date('YmdHis'),	
		'orderAmount'	=>	$data['cash'],
		'orderTitle'	=>	'寻龙剑充值',
		'orderDesc'		=>	'充值游戏币,游戏更爽快！',
		'extInfo'		=>	'xljzzd'.rand(1000,9999),

	);

	$sourceStr	= \sign::make_string($postData);

	$postData['signMethod']	= 'MD5';
	$postData['sign']		= md5($sourceStr.SECRET_VIVO_SECRET);

	\async_http::do_http(SECRET_VIVO_CHECK_IP,SECRET_VIVO_CHECK_URI,$postData,array(__CLASS__, 'resp_check_login'),array($response, $oid, $cash), 443, true);
	return;
}

/*
* 自定义任务响应方法
*/
public	static	function resp_check_login($ress, $transfer)
{
	$response	= $transfer[0];
	$oid		= $transfer[1];
	$cash		= $transfer[2];

	if($ress && ($res = json_decode($ress,true)) && isset($res['respCode']) && $res['respCode'] == 200)
	{
		$sign = $res['signature'];
		unset($res['signature'], $res['signMethod']);
		if($cash == $res['orderAmount'] && $sign === md5(\sign::make_string($res)))
		{
			$response->end(array('ret'=>0, 'oid'=>$oid, 'data'=>array('accessKey'=>$res['accessKey'],'orderNumber'=>$res['orderNumber'],'orderAmount'=>$res['orderAmount'])));
			return;
		}
	}
	$response->end('{"ret":23}');
}

}
