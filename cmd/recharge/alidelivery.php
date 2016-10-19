<?php
namespace cmd\recharge;
/*
* albb发货处理接口
* auther zzd
*/
class alidelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/albbdelivery.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_RECHARGE;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	$response->status(404);
	$response->end('{"ret":4}');
	return;
	if(isset($request->post))
	{
		$pindex	= array
		(
			'oid'		,
			'ooid'		,
			'cash'		,
			'time'		,
		//	'sig'		,
		);

		$data = &$request->post;

		if(count($data) != count($pindex) )
		{
			$response->end('{"ret":4}');
			return;
		}

		//@验证IP
		if($request->header['x-real-ip'] !== '127.0.0.1')
		{
			$response->end('{"ret":9}');
			return;
		}

//		foreach($pindex as $k)
//		{
//			if(!isset($data[$k]) || $data[$k] === '' )
//			{
//				$response->end(404);
//				return;
//			}
//		}
//
//		$sign = $data['sig'];
//		unset($data['sig']);
//
//		if(!\sign::asc_decode('POST', self::CMD_NUM, $data, $sign))
//		{
//			$response->end(3);
//			return;
//		}
	}
	else
	{
		if($request->header['x-real-ip'] !== '127.0.0.1')
		{
			$response->end('{"ret":9}');
			return;
		}

		$data = array
		(
			'gid'=>111,
			'oid'=>$request->get['oid'],
			'ooid'=>'sdadd',
			'time' =>time(),
		);
	}

	//@校验时间
	$now	= time();
	if(abs($now-$data['time']) > 3)
	{
		$response->end('{"ret":5}');
		return;
	}

	$oid	= $data['oid'];
	$ooid	= $data['ooid'];
	$cash	= $data['cash'];

	if(!$redis_res = set_deliveryed_status($oid,array($oid, $cash, time(),$ooid,SECRET_ALBB_ORIGIN)))
	{//@订单库异常
		$response->end('{"ret":7}');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$oid, 'ooid'=>$ooid,'origin'=>SECRET_ALBB_ORIGIN)));
			//\service\log_cmd::make_log('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$oid, 'ooid'=>$ooid));
			break;
		case 2://@非法订单
			$response->end('{"ret":21}');
			return;
		default://已发货
			break;
	}

	$response->end('{"ret":0}');

	return;
}

}
?>
