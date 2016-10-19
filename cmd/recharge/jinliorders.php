<?php
namespace cmd\recharge;
/*
* JINLI订单申请接口
* auther zzd
*/
class jinliorders extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/jinliorders.xl';
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
		'sig'
	);


	$data = &$request->post;

	foreach($pindex as $k)
	{
		if(!isset($data[$k]))
		{
			$response->end('{"ret":4}');
			return;
		}
	}

	if(!isset($data['cash']) || $data['cash'] < 1)
	{
		$response->end('{"ret":4}');
		return;
	}

	if(!isset($data['oid']) || !check_simple_order_format($data['oid']))
	{
		$response->end('{"ret":404}');
		return;
	}

	if(!isset($data['gid']) || $data['gid'] !== CHANNEL_ID_JINLI)
	{
		$response->end('{"ret":8}');
		return;
	}

	$sign = $data['sig'];
	unset($data['sig']);

	if(!\sign::asc_decode('POST', self::CMD_NUM, $data, $sign))
	{
		$response->end('{"ret":3}');
		return;
	}

	if(!$redis_res = check_deliveryed_status($data['oid'],CHANNEL_ID_JINLI))
	{
		$response->end('{"ret":7}');
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
		'api_key'		=> SECRET_JINLI_APPKEY,
		'deal_price'	=> $data['cash'],
		'deliver_type'	=> 1,
		'out_order_no'	=> $data['oid'],
		'subject'		=> '游戏充值',
		'submit_time'	=> date('YmdHis'),
		'total_fee'		=> $data['cash'],
	);

	$postData['sign']		= \sign::jinli_encode($postData);
	$postData['player_id']	= $data['uid'];

	$sourceStr = json_encode($postData);

	\async_http::do_http(SECRET_JINLI_CHECK_IP,SECRET_JINLI_CHECK_URI,$sourceStr,array(__CLASS__, 'resp_create_order'),array($response,$oid),443,true);

	return;
}

public	static	function resp_create_order($ress, $transfer)
{
	$response = $transfer[0];
	if(!$ress || !($res = json_decode($ress,true)) || !isset($res['status']) || $res['status'] != '200010000')
	{
		$response->end('{"ret":40}');
		return;
	}

	if(SECRET_JINLI_APPKEY !== $res['api_key'])
	{
		$response->end('{"ret":41}');
		return;
	}

	$ooid	= $res['order_no'];
	$oid	= $res['out_order_no'];

	if($oid != $transfer[1])
	{
		$response->end('{"ret":24}');
		return;
	}

	//#rids set

	$response->end(json_encode(array('ret'=>0,'oid'=>$oid,'data'=>array('order_no'=>$res['order_no'],'submit_time'=>$res['submit_time']))));

}

/*
* 自定义任务响应方法
*/
//public	static	function resp_task($serv,$arg, $pass)
//{
//	echo "响应了 她上课";
//}

}
