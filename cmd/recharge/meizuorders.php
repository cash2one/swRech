<?php
namespace cmd\recharge;
/*
* MEIZU发货处理接口
* auther zzd
*/
class meizuorders extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/meizuorders.xl';
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
		$response->end('{"ret":404}');
		return;
	}

	$data = &$request->post;

	$sourceStr	= \sign::make_string($postData);

	$postData['app_id']		= SECRET_MEIZU_APPID;
	$postData['sign_type']	= 'md5';
	$postData['sign']		= md5($sourceStr.SECRET_MEIZU_SECRET);

	$response->end(json_encode(array('ret'=>0,'data'=>$postData)));

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
