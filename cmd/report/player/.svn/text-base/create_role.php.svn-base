<?php
namespace cmd\report\player
{

/*
* SDK启动上报处理接口
* auther zzd
*/
class create_role extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/crole.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_REPORT;

/*
* simple function for deal tcp protocol
*/
public	static	function handler($request, $response)
{
	if(isset($request->post))
	{
		$method	= 'POST';
		$data	= &$request->post;
	}
	elseif (isset($request->get))
	{
		$method	= 'GET';
		$data	= &$request->get;
	}
	else
	{
			$response->status(404);
			$response->end('');
			return;
	}

	$pindex	= array
	(
		'gid',
		'sid',
		'uid',
		'pid',
		'name',
		'imei',
		'time',
		'sig',
	);

	if(count($data) != count($pindex))
	{
		$response->end('{"ret":404}');
		return;
	}

	foreach($pindex as $k)
	{
		if(!isset($data[$k]) || $data[$k] === "")
		{
			$response->end('{"ret":404}');
			return;
		}
	}

	if(!\game_server::check_sid($data['gid'],$data['sid']))
	{
		$response->end('{"ret":8}');
		return;
	}

//	if(abs(time()-$data['time']) > 60)
//	{
//			$response->end(5);
//			return;
//	}

	$sign = $data['sig'];
	unset($data['sig']);

	if(!\sign::asc_decode($method, self::CMD_NUM, $data, $sign))
	{
		$response->end('{"ret":3}');
		return;
	}

	$data['ip'] = $request->header['x-real-ip'];

	\event::dispatch(EVENT_DATA_LOG, array('new_role', $data));

	$response->end('{"ret":0}');
	
}

/*
* 自定义任务响应方法
*/
//public	static	function resp_task($serv,$arg, $pass)
//{
//	return;
//}

}
}
?>
