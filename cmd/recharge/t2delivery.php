<?php
namespace cmd\recharge;
/*
* 腾讯直购发货处理接口
* auther zzd
*/
class t2delivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/tyybdelivery.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_RECHARGE;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if(isset($request->post))
	{
		$data = &$request->post;
		if(!isset($data['sig']))
		{
			$response->status('404');
			$response->end('');
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
		$response->status('404');
		$response->end('');
		return;
	}

	if( !isset($data['oid']) || !check_simple_order_format($data['oid']))
	{
		$response->end('{"ret":4}');
		return;
	}


	$pindex = array
	(
		'gid',
		'oid',
		'sid',
		'uid',
		'pid',
		'cash',
		'time',
		'openkey',
		'pf',
		'pfkey',
		'loginMode'
	);

	foreach($pindex as $k)
	{
			if(!isset($data[$k]))
			{
					$response->end('{"ret":4}');
					return;
			}
	}


	$order	= $data['oid'];
	$sid	= $data['sid'];
	$uid	= $data['uid'];
	$pid	= $data['pid'];
	$gid	= $data['gid'];
	$openkey= $data['openkey'];
	$pf		= $data['pf'];
	$pfkey	= $data['pfkey'];
	//$pay_token = $data['pay_token'];
	$loginMode = intval($data['loginMode']);

	//@校验钱
//	$cash = intval($data['cash']);

//	if(!$cash || $cash/10)
//	{
//		$response->end('{"ret":404}');
//		return;
//	}


	switch( $loginMode )
	{
		case 0: $response->end('{"ret":604}'); return;
		case 1:
			$cookie		= array('session_id'=>'openid', 'session_type'=>'kp_actoken', 'org_loc'=>urlencode('/mpay/pay_m'));
			$cookie2	= array('session_id'=>'openid', 'session_type'=>'kp_actoken', 'org_loc'=>urlencode('/mpay/cancel_pay_m'));
			$cookie3	= array('session_id'=>'openid', 'session_type'=>'kp_actoken', 'org_loc'=>urlencode('/mpay/get_balance_m'));
			break;
		case 2:
			$cookie		= array('session_id'=>'hy_gameid', 'session_type'=>'wc_actoken', 'org_loc'=>urlencode('/mpay/pay_m'));
			$cookie2	= array('session_id'=>'hy_gameid', 'session_type'=>'wc_actoken', 'org_loc'=>urlencode('/mpay/cancel_pay_m'));
			$cookie3	= array('session_id'=>'hy_gameid', 'session_type'=>'wc_actoken', 'org_loc'=>urlencode('/mpay/get_balance_m'));
			break;
		default:
			$response->end('{"ret":404}');
			return;
	}

	/////////////////////////////

	if( !$redis_res = check_deliveryed_status($order,$gid) )
	{
		//@redis 异常
		write_log('tencent_fail', 'check_deliveryed_status:'.$order);
		$response->end('{"ret":7}');
		return;
	}

	switch($redis_res)
	{
		case 1://@进行receipt进行校验
			break;
		case 2://@该订单正在发货
			$response->end('{"ret":20}');
			return;
		case 4:
			$response->end('{"ret":20}');
			return;
		default:
			//@非法订单
			$response->end('{"ret":21}');
			return;
	}

	////////////////////////////////////

	$buy_data = array
	(
		'openid'=>$uid,
		'openkey'=>$openkey,
		'appid'=>SECRET_TENCENT_APPID,
		'pf'=>$pf,
		'pfkey'=>$pfkey,
		'ts'=>time(),
		'zoneid'=>$sid,
		'format'=>'json',
		'userip'=>$request->header['x-real-ip'],
	);
	$sourcestr				= \sign::tencent_encode('POST','/v3/r'.SECRET_TENCENT_PAY_QUERY_URI,$buy_data, 'sig');

	//unset($buy_data['sig']);
	$buy_data['billno']		= $order;
	//setheader
	\async_http::do_http(SECRET_TENCENT_PAY_IP, SECRET_TENCENT_PAY_QUERY_URI,$sourcestr,array(__CLASS__, 'resp_check_gold'), array($response,$buy_data,$cookie, $cookie2),443,true,$cookie3);
//	\worker::task_push(array(WT_PROTOCOL_CURL_DEAL, array(1,SECRET_TENCENT_PAY_IP.SECRET_TENCENT_PAY_QUERY_URI.$sourcestr,0,array(CURLOPT_COOKIE=>implode('; ', array('session_id=openid', 'session_type=kp_actoken', 'org_loc='.rawurlencode('/mpay/get_balance_m')))))),array(__CLASS__,'resp_check_gold'),array($response,$buy_data,$cookie, $cookie2));
}


public	static	function resp_check_gold($result,$transfer)
//public	static	function resp_check_gold($serv,$result,$transfer)
{
	$response	= $transfer[0];
	$buy_data	= $transfer[1];
	$cookie		= $transfer[2];
	$cookie2	= $transfer[3];

//	$result = $simple_function->curl_send('/mpay/get_balance_m', $buy_data, $cookie3);
	var_dump($result);

	$rdata = json_decode($result,true);

	if(!is_array($rdata))
	{
		$response->end('{"ret":22}');
		return;
	}

	if( $rdata['ret'] !== 0 )
	{
		write_log('tencent', $rdata);
		$response->end('{"ret":23}');
		return;
	}

	if($rdata['balance'] <= 0)
	{
		$response->end('{"ret":35}');
		return;
	}

	$buy_data['amt'] = $rdata['balance'];
	$sourcestr = '?'.\sign::tencent_encode('GET','/v3/r'.SECRET_TENCENT_PAY_EXEC_URI,$buy_data, 'sig');;
	//unset($buy_data['sig']);
	//setheader
	\async_http::do_http(SECRET_TENCENT_PAY_IP, SECRET_TENCENT_PAY_EXEC_URI.$sourcestr,0, array(__CLASS__, 'resp_pay_gold'), array($response,$buy_data,$cookie2),443,true, $cookie);
}

public	static	function resp_pay_gold($result, $transfer)
{
	$rdata = json_decode($result,true);

	if(!is_array($rdata))
	{
		$response->end('{"ret":22}');
		return;
	}

	if( $rdata['ret'] !== 0 )
	{
		write_log('tencent', $rdata);
		$response->end('{"ret":23}');
		return;
	}

	$response	= $transfer[0];
	$buy_data	= $transfer[1];
	$cookie2	= $transfer[2];
	$order		= $buy_data['billno'];
	$cash		= round($buy_data['amt']/10,1);

	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),'tencent',SECRET_TENCENT_ORIGIN)))
	{
		cancel_pay($buy_data, $cookie2);
		write_log('tyyb_fail', 'set_deliveryed_status:'.$order);
		$response->end('FAILURE');
		return;
	}

	$ooid = 'txyyb';

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_TENCET_ORIGIN)));
			break;
		case 2://@非法订单
		default://已发货
			break;
	}
	///////////////////////////////////////////////////
	$response->end('SUCCESS');

	return;
}

private	static	function cancel_pay($buy_data, $cookie)
{
	$sourcestr = '?'.\sign::tencent_encode('GET','/v3/r'.SECRET_TENCENT_PAY_CANCEL_URI,$buy_data, 'sig');;
	\async_http::do_http(SECRET_TENCENT_PAY_IP, SECRET_TENCENT_PAY_CANCEL_URI.$sourcestr,0, array(__CLASS__, 'resp_check_gold'), array(),443,true,$cookie);
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
