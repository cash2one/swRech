<?php
namespace cmd\recharge;
/*
* MOGELY发货处理接口
* auther zzd
*/
class mogelydelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/mogelydelivery.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_RECHARGE;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if(!($content = $request->rawContent()))
	{
		$response->status(404);
		$response->end(404);
		return;
	}

	$content = array_map(create_function('$v', 'return explode("=", $v);'), explode('&',$content));

	$data = array();

	foreach($content as $value)
	{
		$data[$value[0]] = $value[1];
	}


	if(!isset($data['sign']) || strlen($data['sign']) != 32 )
	{
		$response->status(404);
		$response->end('');
		return;
	}

	$pindex = array
	(
		'orderid',
		'username',
		'gameid',
		'roleid',
		'serverid',
		'paytype',
		'amount',
		'paytime',
		'attach',
	);

	$str_contents = array();

	foreach($pindex as $v)
	{
		if(!isset($data[$v]))
		{
			$response->end('{"result":404}');
			return;
		}

		$str_contents[] = $v.'='.$data[$v];
	}

	$str_contents	= implode('&', $str_contents);
	$str_contents	.= '&appkey='.SECRET_MOGELY_APPKEY;

	if(md5($str_contents) !== $data['sign'])
	{
		$response->end('errorSign');
		return;
	}

	if(!check_simple_order_format($data['attach']))
	{
		$response->end('{"result":4}');
		return;
	}

	if($data['amount'] < 1)
	{
		$response->end('{"result":3}');
		return;
	}


	$order	= $data['attach'];
	$cash	= $data['amount'];
	$ooid	= $data['orderid'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_MOGELY_ORIGIN)))
	{
		write_log('mogely_fail', 'set_deliveryed_status:'.$order);
		$response->end('{"result":94}');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_MOGELY_ORIGIN)));
			break;
		case 2://@非法订单
			break;
		default://已发货
			break;
	}
	///////////////////////////////////////////////////
	$response->end('success');

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
