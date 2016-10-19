<?php
namespace cmd\recharge;
/*
* 渠道订单需求接口
* auther zzd
*/
class orders extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/orders.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_RECHARGE;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if(!isset($request->post))
	{
		$response->status(404);
		$response->end('');
		return;
	}

	$pindex = array
	(
		'uid',
		'sid',
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

	if(!isset($data['gid']) || !is_numeric($data['gid']))
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

	if(!$redis_res = check_deliveryed_status($data['oid'],$data['gid']))
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

	switch($data['gid'])
	{
		case CHANNEL_ID_VIVO:
		{
			$postData = array
			(
				'version'		=>	'1.0.0',
				'cpId'			=>	SECRET_VIVO_CPID,
				'appId'			=>	SECRET_VIVO_APPID,
				'cpOrderNumber'	=>	$data['oid'],
				'notifyUrl'		=>	SECRET_VIVO_NOTIFY_URL,
				'orderTime'		=>	date('YmdHis'),	
				'orderAmount'	=>	$data['cash'],
				'orderTitle'	=>	'购买'.($data['cash']/10).'元宝',
				'orderDesc'		=>	'购买'.($data['cash']/10).'元宝',
				'extInfo'		=>	'xljzzd'.rand(1000,9999),
		
			);
		
			$sourceStr	= \sign::make_string($postData);
		
			$postData['signMethod']	= 'MD5';
			$postData['signature']		= md5($sourceStr.SECRET_VIVO_SECRET);
			\async_http::do_http(SECRET_VIVO_ORDER_IP,SECRET_VIVO_ORDER_URI,$postData,array(__CLASS__, 'resp_vivo_create_order'),array($response, $data['gid'],$data['oid'], $data['cash']), 443, true);
			return;
		}
		case CHANNEL_ID_JINLI:
		{
			$postData = array
			(
				'api_key'		=> SECRET_JINLI_APPKEY,
				'subject'		=> '购买'.($data['cash']*10).'元宝',
				'out_order_no'	=> $data['oid'],
				'deliver_type'	=> '1',
				'deal_price'	=> $data['cash'],
				'total_fee'		=> $data['cash'],
				'submit_time'	=> date('YmdHis'),
				'notify_url'	=> SECRET_JINLI_NOTIFY_URL,
			);

			$postData['sign']		= \sign::jinli_encode($postData);
			$postData['player_id']	= $data['uid'];

			$sourceStr = json_encode($postData);
		
			\async_http::do_http(SECRET_JINLI_ORDER_IP,SECRET_JINLI_ORDER_URI,$sourceStr,array(__CLASS__, 'resp_jinli_create_order'),array($response,$data['gid'],$data['oid']),443,true);

			return;
		}
		case CHANNEL_ID_MEIZU:
		{
			$postData = array
			(
				'app_id'		=> SECRET_MEIZU_APPID,
				'cp_order_id'	=> $data['oid'],
				'uid'			=> $data['uid'],
				'product_id'	=> '0',
				'product_subject'=>'购买'.($data['cash']*10).'元宝',
				'product_body'	=> '',
				'product_unit'	=> '',
				'buy_amount'	=> 1,
				'product_per_price' => $data['cash'],
				'total_price'	=> $data['cash'],
				'create_time'	=> time(),
				'pay_type'		=> 0,
				'user_info'		=> rand(1000,9999),
			);
			$postData['sign'] = \sign::meizu_encode($postData);
			$postData['sign_type'] = 'md5';

			$response->end(json_encode(array('ret'=>0, 'gid'=>$data['gid'], 'oid'=>$data['oid'], 'data'=>$postData)));
			break;
		}
	}

	return;
}

/*
* 自定义任务响应方法
*/
public	static	function resp_vivo_create_order($ress, $transfer)
{
	$response	= $transfer[0];
	$gid		= $transfer[1];
	$oid		= $transfer[2];
	$cash		= $transfer[3];

	if($ress && ($res = json_decode($ress,true)) && isset($res['respCode']) && $res['respCode'] == 200)
	{
		$sign = $res['signature'];
		unset($res['signature'], $res['signMethod']);
		if($cash == $res['orderAmount'] && \sign::vivo_decode(1,1,$res,$sign))
		{
			$response->end(json_encode(array('ret'=>0, 'gid'=>$gid,'oid'=>$oid, 'data'=>array('accessKey'=>$res['accessKey'],'orderNumber'=>$res['orderNumber'],'orderAmount'=>$res['orderAmount']))));
			return;
		}
	}

	write_log('gid_order_err', $gid.':'.$ress);

	$response->end('{"ret":23}');
}

public	static	function resp_jinli_create_order($ress, $transfer)
{
	$response = $transfer[0];
	if(!$ress || !($res = json_decode($ress,true)) || !isset($res['status']) || $res['status'] != '200010000')
	{
		write_log('gid_order_err',($transfer[1]).':'.$ress);
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

	if($oid != $transfer[2])
	{
		$response->end('{"ret":24}');
		return;
	}

	set_channel_orderid($oid, $ooid);
	//#rids set

	$response->end(json_encode(array('ret'=>0,'gid'=>$transfer[1],'oid'=>$oid,'data'=>array('order_no'=>$res['order_no'],'submit_time'=>$res['submit_time']))));

}





}
