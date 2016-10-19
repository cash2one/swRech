<?php
namespace cmd\recharge;
/*
* 微信充值回调处理接口
* auther zzd
*/
class weixindelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/weixindelivery.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_RECHARGE;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	$response->end(111);
	return;

	if($method === 'POST')
	{
		$data = $request->rawContent();

		if(!$data)
		{
			$response->end('{"ret":404}');
			return;
		}

		$data = simplexml_load_string($data);

		if(!is_object($data))
		{
			$response->end('{"ret":404}');
			return;
		}

		$data = (array)$data;

		$pindex	= array
		(
			'appid',
			'mch_id',
			'sign',
			'openid',
			'out_trade_no',
			'transaction_id',
		);

		if(!isset($data['return_code']) || $data['return_code'] !== 'SUCCESS')
		{
			$response->end('{"ret":404}');
			return;
		}

		if(!isset($data['result_code']) || $data['result_code'] !== 'SUCCESS')
		{
			$response->end('{"ret":404}');
			return;
		}

		if(!isset($data['total_fee']) || $data['total_fee'] < 10)
		{
			$response->end('{"ret":404}');
			return;
		}

		foreach($pindex as $k)
		{
			if(!isset($data[$k]) || $data[$k] === '')
			{
				$response->end('{"ret":404}');
				return;
			}
		}


		$sign = $data['sign'];

		if(!\sign::wxin_decode('POST', self::CMD_NUM, $data, $sign))
		{
			$response->end('{"ret":3}');
			return;
		}
	}
	else
	{
		$response->end('{"ret":404}');
		return;
	}

	//@校验APPID
	if($data['appid'] !== SECRET_WEIXIN_APPID)
	{
		$response->end('{"ret":404}');
		return;
	}

	//@校验商户ID
	if($data['mch_id'] !== SECRET_WEIXIN_MCHID)
	{
		$response->end('{"ret":404}');
		return;
	}

	$order	= $data['out_trade_no'];
	$cash	= $data['total_fee']/100;
	$ooid	= $data['transaction_id'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid, SECRET_WEIXIN_ORIGIN)))
	{
		write_log('wixin_fail', 'set_deliveryed_status:'.$order);
		$response->end('{"ret":6}');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_WEIXIN_ORIGIN)));
			//\service\log_cmd::make_log('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_WEIXIN_ORIGIN));
			break;
		case 2://@非法订单
			break;
		default://已发货
			break;
	}
	///////////////////////////////////////////////////
	$response->end('<xml><return_code>SUCCESS</return_code></xml>');

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
