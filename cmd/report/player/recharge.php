<?php
namespace cmd\report\player
{

/*
* SDK启动上报处理接口
* auther zzd
*/
class recharge extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/clientsuc.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_REPORT;

/*
* simple function for deal tcp protocol
*/
public	static	function handler($request, $response)
{
	if(isset($request->post))
	{
		$pindex	= array
		(
			'gid',
			'sid',
			'uid',
			'pid',
			'oid',
			'ooid',
			'time',
			'cash',
			'sig',
		);

		$data = &$request->post;

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

//		if(abs(time()-$data['time']) > 60)
//		{
//			$response->end(5);
//			return;
//		}

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
		if( $request->header['x-real-ip'] !== '127.0.0.1' )
		{
			$response->end('{"ret":3}');
			return;
		}

		$data = array
		(
			'gid'=>111,
			'sid'=>1,
			'uid'=>123,
			'pid'=>'asdada',
			'oid'=>4223425442,
			'ooid'=>'asdasdada',
			'time'=>time(),
			'cash'=>11111,
		);
	}

	\event::dispatch(EVENT_DATA_LOG, array('recharge_log', $data));
	//\service\log_cmd::make_log('recharge_log', $data);

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
