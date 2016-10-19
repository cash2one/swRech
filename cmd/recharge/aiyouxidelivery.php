<?php
namespace cmd\recharge;
/*
* AIYOUXI发货处理接口
* auther zzd
*/
class aiyouxidelivery extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/aiyouxi/delivery.xl';
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
		$response->end(404);
		return;
	}

	$data = &$request->post;

	$pindex	= array
	(
		'cp_order_id',
		'correlator',
		'result_code',
		'fee',
		'pay_type',
		'method',
		'sign',
	);

	$temp = "";
	foreach($pindex as $k)
	{
		if(!isset($data[$k]))
		{
			$response->end('<cp_notify_resp><h_ret>20</h_ret></cp_notify_resp>}');
			return;
		}
		$temp .= $data[$k];
	}

	$order	= $data['cp_order_id'];

	if( $data['result_code'] !== "00"|| strlen($data['sign']) != 32)
	{
		$response->end('<cp_notify_resp><h_ret>21</h_ret><cp_order_id>'.$order.'</cp_order_id></cp_notify_resp>}');
		return;
	}

	if( $data['fee'] <= 1)
	{
		$response->end('<cp_notify_resp><h_ret>22</h_ret><cp_order_id>'.$order.'</cp_order_id></cp_notify_resp>}');
		return;
	}

	if(!check_simple_order_format($data['cp_order_id']))
	{
		$response->end('<cp_notify_resp><h_ret>2</h_ret><cp_order_id>'.$order.'</cp_order_id></cp_notify_resp>}');
		return;
	}

	$sign	= $data['sign'];

	if(md5($temp.SECRET_AIYOUXI_APPKEY) != $sign)
	{
		write_log('aiyouxi_fail', 'sign:');
		$response->end('<cp_notify_resp><h_ret>3</h_ret><cp_order_id>'.$order.'</cp_order_id></cp_notify_resp>}');
		return;
	}

	$cash	= $data['fee'];
	$ooid	= $data['correlator'];

	///////////////////////////////////////////////////////////////////
	if(!$redis_res = set_deliveryed_status($order,array($order, $cash, time(),$ooid,SECRET_AIYOUXI_ORIGIN)))
	{
		write_log('aiyouxi_fail', 'set_deliveryed_status:'.$order);
		$response->end('<cp_notify_resp><h_ret>7</h_ret><cp_order_id>'.$order.'</cp_order_id></cp_notify_resp>}');
		return;
	}

	switch($redis_res)
	{
		case 1:
			\event::dispatch(EVENT_DATA_LOG, array('order_delivery', array('time'=>time(),'cash'=>$cash, 'oid'=>$order, 'ooid'=>$ooid, 'origin'=>SECRET_AIYOUXI_ORIGIN)));
			break;
		case 2://@非法订单
			break;
		default://已发货
			break;
	}
	///////////////////////////////////////////////////
	$response->end('<cp_notify_resp><h_ret>0</h_ret><cp_order_id>'.$order.'</cp_order_id></cp_notify_resp>}');

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
