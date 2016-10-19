<?php
namespace cmd\report\sdk
{

/*
* SDK启动上报处理接口
* auther zzd
*/
class start extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM = '/sdk.xl';
const ARG_COUNT= 0;
const INIT = SERVER_FOR_REPORT;

/*
* simple function for deal tcp protocol
*/
public	static	function handler($request,$response)
{
	if(isset($request->post))
	{
		$pindex = array
		(
			'mac',
			'ver',
			'wifi',
		);

		$data = &$request->post;

		if(!isset($data['imei']) || !is_numeric($data['imei']))
		{
			$response->end('{"ret":404}');
			return;
		}

		if(!isset($data['status']) || !in_array($data['status'],array(1,2,3)))
		{
			$response->end('{"ret":404}');
			return;
		}

		if(!isset($data['gid']) || !\channel::check($data['gid']))
		{
			$response->end('{"ret":8}');
			return;
		}

		if(!isset($data['sig']) || strlen($data['sig']) != 32)
		{
			$response->end('{"ret":404}');
		}

		$now	= time();

//		if(!isset($data['time']) || abs($now-$data['time']) > 20)
//		{
//			$response->end(5);
//			return;
//		}

		foreach($pindex as $k)
		{
			if(!isset($data[$k]) || strpos($data[$k],' ') !== false)
			{
				$response->end('{"ret":404}');
				return;
			}
		}



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
		if( $request->header['x-real-ip'] != '127.0.0.1' )
		{
			$response->end('{"ret":404}');
			return;
		}

		$data = array
		(
			'gid'=>111,
			'imei'=>rand(1111111,234354223),
			'mac'=>'sdaggrgasfdsfsdsf',
			'ver'=>'sdasdasdadadsdaasd',
			'wifi'=>'asdasdasdasdadsad',
			'time'=>time(),
			'status'=>rand(1,3),
		);


	}

	//@校验时间
	$now	= time();

	if(abs($now-$data['time']) > 60)
	{
		$response->end('{"ret":5}');
		return;
	}

	\event::dispatch(EVENT_DATA_LOG, array('detail_sdk', $data));
	//\service\log_cmd::make_log('detail_sdk', $data);

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
