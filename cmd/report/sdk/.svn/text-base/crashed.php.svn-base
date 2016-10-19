<?php
namespace cmd\report\sdk
{

/*
* SDK启动上报处理接口
* auther zzd
*/
class crashed extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM = '/crash.xl';
const ARG_COUNT= 0;
const INIT = SERVER_FOR_REPORT;

/*
* simple function for deal tcp protocol
*/
public	static	function handler($request,$response)
{
	if(!isset($request->files) || !isset($request->files['userfile']['tmp_name']) || $request->files['userfile']['size'] > 81920)
	{
		$response->end('{"ret":0}');
		return;
	}

	rename($request->files['userfile']['tmp_name'],'/data/ttp/log/'.$request->files['userfile']['name']);

	$response->end('{"ret":0}');
	return;
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
