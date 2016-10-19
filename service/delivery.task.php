<?php
namespace service
{
class delivery_task extends \service_class
{
private	static	$time_id	= 0;
private	static	$time_id_s	= 0;
private	static	$time_id_t	= 0;
private	static	$time_id_f	= 0;
private	static	$time_id_ff	= 0;
private	static	$flag		= 3;

public	static	function setup()
{
	if ( SERVER_FOR_RECHARGE )
	{
		global $serv;
		parent::register(__CLASS__);
		self::$flag	= DB_REDIS_ORDER_SIZE-1;
		$serv->after(2000+500*($serv->worker_id), function() use ($serv){$serv->tick(5000,array(__CLASS__,'delivering'));});
		$serv->after(rand(9000,11999), array(__CLASS__,'delivery_sec'));
		$serv->after(rand(6000,7899), array(__CLASS__,'delivery_third'));
		$serv->after(rand(3500,4899), array(__CLASS__,'delivery_fourth'));
		$serv->after(rand(1000,2599), array(__CLASS__,'delivery_fifth'));
	}
	return 1;
	//@reg
}

public	static	function crash()
{
	return true;
}

public	static	function delivering()
{
	$k	= 0;
	$size	= 40;

	for($i=0; $i<$size; ++$i)
	{
		$k += self::rsp_delivery($i, 'get_simple_delivery_order_and_data', 'set_delivery_to_server_ok', 'set_delivery_to_server_fail');

		if(($i%DB_REDIS_ORDER_SIZE == self::$flag))
		{
			if($k == 0)
				break;
			else
				$k = 0;
		}
	}
	if($i==40)
	{
		 swoole_timer_after(1000, __METHOD__);
	}
}

public	static	function delivery_sec()
{
	$k	= 0;
	$i	= rand(0,10);
	$size	= $i+DB_REDIS_ORDER_SIZE;

	for($i; $i<$size;++$i)
	{
		$k += self::rsp_delivery($i, 'get_simple_delivery_order_and_data_1', 'set_delivery_to_server_ok_1', 'set_delivery_to_server_fail_1');
	}

	if($k==0)
	{
		if(!self::$time_id_s)
			self::$time_id_s=swoole_timer_tick(3600000+rand(1000,300000),__METHOD__);
	}
	else
	{
		swoole_timer_after(1000, __METHOD__);
	}
	return;
}

public	static	function delivery_third()
{
	$k	= 0;
	$i	= rand(0,10);
	$size	= $i+DB_REDIS_ORDER_SIZE;

	for($i; $i<$size;++$i)
	{
		$k -= self::rsp_delivery($i, 'get_simple_delivery_order_and_data_2', 'set_delivery_to_server_ok_2', 'set_delivery_to_server_fail_2');
	}

	if($k==0)
	{
		if(!self::$time_id_t)
			self::$time_id_t=swoole_timer_tick(3600000+rand(0,-59000),__METHOD__);
	}
	else
	{
		swoole_timer_after(1000, __METHOD__);
	}
	return;
}

public	static	function delivery_fourth()
{
	$k	= 0;
	$i	= rand(0,10);
	$size	= $i+DB_REDIS_ORDER_SIZE;

	for($i; $i<$size;++$i)
	{
		$k -= self::rsp_delivery($i, 'get_simple_delivery_order_and_data_3', 'set_delivery_to_server_ok_3', 'set_delivery_to_server_fail_3');
	}

	if($k==0)
	{
		if(!self::$time_id_f)
			self::$time_id_f=swoole_timer_tick(3600000+rand(-60000,-300000),__METHOD__);
	}
	else
	{
		swoole_timer_after(1000, __METHOD__);
	}
	return;
}

public	static	function delivery_fifth()
{
	$k	= 0;
	$i	= rand(0,10);
	$size	= $i+DB_REDIS_ORDER_SIZE;

	for($i; $i<$size;++$i)
	{
		$k -= self::rsp_delivery($i, 'get_simple_delivery_order_and_data_4', 'set_delivery_to_server_ok_4', 'set_delivery_to_server_fail_4', 0);
	}

	if($k==0)
	{
		if(!self::$time_id_ff)
			self::$time_id_ff=swoole_timer_tick(3600000+rand(-310000,-400000),__METHOD__);
	}
	else
	{
		swoole_timer_after(1000, __METHOD__);
	}
	return;
}


private	static	function rsp_delivery($dbkey, $get_func, $send_ok_func, $send_fail_func, $next=1)
{
	if(!get_server_status())
	{
		return 0;
	}
	
	$order_data = $get_func($dbkey);

	if(!is_array($order_data))
	{
		if($order_data != 1)
			SERVICE_DEBUG && write_log('delivery_err',$order_data);
		return 0;
	}
	elseif(empty($order_data))
	{
		SERVICE_DEBUG && write_log('delivery_err','空的充值订单！！ ');
		SERVICE_DEBUG && write_log('delivery_err',$order_data);
		return 0;
	}

	$rdata	= array();
	$size	= count($order_data);

	//@发货
	for($i=0; $i<$size;$i+=2)
	{
		$rdata[$order_data[$i]] = $order_data[$i+1]; 
	}

	$order_data = null;
	unset($rdata['status'],$rdata['rtime']);
	$order = $rdata['oid'];

	//发货函数
	$res = self::send($rdata);

	if($res)
	{//@成功
		$send_ok_func($order);
		$rdata['time'] = time();
		\event::dispatch(EVENT_DATA_LOG, array('platform_recharge', $rdata));
		write_log('recharge_ok', $order);
	}
	else
	{
		$send_fail_func($order);

		write_log('order_send_fail', $send_fail_func.'_'.$order);

		if(!$next)
		{
			$rdata['time'] = time();
			\event::dispatch(EVENT_DATA_LOG, array('order_notice_fail', $rdata));
			//write_log('delivery_final_fail', $rdata);
		}
	}
	
	return 1;
}

private	static	function send($data)
{
	if(!$cdata = \game_server::server_data($data['gid'],$data['sid']))
	{
		write_warn('game_server_err', 'unknown gid ['.$data['gid'].']');
		return false;
	}

	unset($data['ooid'],$data['origin'],$data['ip']);

	$url	= $cdata['url'];
	$port	= $cdata['port'];
	$html	= $cdata['rechUri'];
//	$data['cash'] = 1;
	$sdata	= \sign::asc_encode('POST', $html, $data, 'sig');

	if(!$res = \curl::post($url, $sdata, $port))
	{
		write_log('http_rech_fail',array($url,$port));
		return false;
	}

	//$rest = json_decode($res,true);

	if('{"ret":0}' !== $res)
	{
		write_warn('send_recharge', $url.' - fail gid:['.($data['gid']).'] and ['.$res.']');
		return false;
	}

	return true;

}


}


}
?>
