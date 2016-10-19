<?php
namespace cmd\recharge;
/*
* 07073发货处理接口
* auther zzd
*/
class zero73delivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/zero73_delivery.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_RECHARGE;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if(!isset($request->post) && !isset($request->post['data']))
	{
		$response->status(404);
		$response->end('');
		return;
	}

	$data = json_decode($request->post['data'],true);

	write_log('073', $data);

	if(!$data)
	{
		$response->end('fail1');
		return;
	}

	if(!isset($data['sign'],$data['orderid'],$data['extendsInfo']))
	{
		$response->end('fail2');
		return;
	}

	$sign	= $data['sign'];
	$order	= $data['extendsInfo'];
	unset($data['sign'],$data['extendsInfo']);

	ksort($data);

	if( $sign != md5(http_build_query($data).SECRET_07073_SECRET) )
	{
		write_log('zero73_sign_fail',md5(http_build_query($data).SECRET_07073_SECRET));
		$response->end('fail');
		return;
	}

	if(!isset($data['gameid']) || $data['gameid'] !== SECRET_07073_APPID)
	{
		$response->end('fail3');
		return;
	}

	if( !check_simple_order_format($order) )
	{
		$response->end('fail4');
		return;
	}

	$cash	= $data['amount'];

	if($cash <= 0 )
	{
			$response->end('fail5');
			return;
	}

	$ooid	= $data['orderid'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_07073_ORIGIN)))
	{
		write_log('zero73_fail', 'set_deliveryed_status:'.$order);
		$response->end('fail');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_07073_ORIGIN)));
			break;
		case 2://@非法订单
			break;
		default://已发货
			break;
	}
	///////////////////////////////////////////////////
	$response->end('succ');

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
