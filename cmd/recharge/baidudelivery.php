<?php
namespace cmd\recharge;
/*
* 百度发货处理接口
* auther zzd
*/
class baidudelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/baidudelivery.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_RECHARGE;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if(isset($request->post))
	{
		//if(!\sign::asc_decode($method, $request->server['request_uri'], $request->post,'Sign'))
		//{
		//	$response->end(402);
		//	return;
		//}
		$rdata = &$request->post;
	}
	else
	{
		$response->status(404);
		$response->end('');
		return;
		//if(!\sign::asc_decode($method, $request->server['request_uri'], $request->get, 'Sign'))
		//{
		//	$response->end(403);
		//	return;
		//}
		$rdata = &$request->get;
	}

	$params_arr = array
	(
		'OrderSerial',
		'CooperatorOrderSerial',
		'Sign',
		'AppID',
		'Content',
	);

	$resp = array
	(
		'AppID'=>SECRET_BAIDU_APPID,
		'ResultCode'=>1,
		'ResultMsg'=>'suc',
		'Sign'=>'',
	);

	foreach($params_arr as $nk)
	{
		if(!isset($rdata[$nk]) || !$rdata[$nk])
		{
			$resp['ResultCode']	= 1000;
			$resp['ResultMsg']	= rawurlencode('接收参数失败');
			$resp["Sign"]		= md5(SECRET_BAIDU_APPID.$resp['ResultCode'].SECRET_BAIDU_SECRET);
			$response->end(json_encode($resp));
			return;
		}
	}

	$ooid	= $rdata['OrderSerial'];
	$order		= $rdata['CooperatorOrderSerial'];
	$sign		= $rdata['Sign'];
	$appid		= $rdata['AppID'];
	$content	= $rdata['Content'];

	if(!check_simple_order_format($order))
	{
		$resp['ResultCode']	= 8002;
		$resp['ResultMsg']	= rawurlencode('订单库未知订单');
		$resp["Sign"]		= md5(SECRET_BAIDU_APPID.$resp['ResultCode'].SECRET_BAIDU_SECRET);
		$response->end(json_encode($resp));
		return;
	}

	//@校验APPID
	if($appid != SECRET_BAIDU_APPID)
	{
		$resp['ResultCode']	= 0;
		$resp['Sign']		= md5($appid.$resp['ResultCode'].SECRET_BAIDU_SECRET);
		$response->end(json_encode($resp));
		return;
	}
	//校验签名
	if($sign !== md5($appid.$ooid.$order.$content.SECRET_BAIDU_SECRET))
	{
		$resp['ResultCode']	= 1001;
		$resp['ResultMsg']	= rawurlencode('签名失败');
		$resp["Sign"]		= md5(SECRET_BAIDU_APPID.$resp['ResultCode'].SECRET_BAIDU_SECRET);
		$response->end(json_encode($resp));
		return;
	}

	$content_data	= json_decode(base64_decode(rawurldecode($content)),true);

	//@校验并提取contents内容
	if(!$content_data || !extract($content_data))
	{
		$resp['ResultCode']	= 1002;
		$resp['ResultMsg']	= rawurlencode('content解析失败');
		$resp["Sign"]		= md5(SECRET_BAIDU_APPID.$resp['ResultCode'].SECRET_BAIDU_SECRET);
		$response->end(json_encode($resp));
		return;
	}
	//$UID $MerchandiseName $OrderMoney $StartDateTime $BankDateTime $OrderStatus $StatusMsg $ExtInfo $VoucherMoney

	///////////////////////////////////////////////////////////////////
	if($OrderStatus == 1 && $OrderMoney > 0)
	{
		if(!$redis_res = set_deliveryed_status($order,array($order, $OrderMoney, time(),$ooid, SECRET_BAIDU_ORIGIN)))
		{
			$resp['ResultCode']	= 8002;
			$resp['ResultMsg']	= rawurlencode('订单库异常！');
			$resp["Sign"]		= md5(SECRET_BAIDU_APPID.$resp['ResultCode'].SECRET_BAIDU_SECRET);
			$response->end(json_encode($resp));
			write_log('baidu_fail', $order);
			return;
		}

		switch($redis_res)
		{
			case 1:
				\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$OrderMoney, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_BAIDU_ORIGIN)));
				break;
			case 2://@非法订单
				break;
		default://已发货
				break;
		}

		//\service\log_cmd::make_log('order_delivery', array('time'=>time(),'cash'=>$OrderMoney, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_BAIDU_ORIGIN));
	}
	///////////////////////////////////////////////////

	$resp['ResultCode']	= 1;
	$resp['ResultMsg']	= rawurlencode('成功');
	$resp["Sign"]		= md5(SECRET_BAIDU_APPID.$resp['ResultCode'].SECRET_BAIDU_SECRET);

	$response->end(json_encode($resp));

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
