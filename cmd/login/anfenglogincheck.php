<?php
namespace cmd\login;
/*
* ANFENG登录校验 
* auther zzd
*/
class anfenglogincheck extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/anfengloginstate.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_LOGIN;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if(isset($request->get))
	{
		if(IP_CHECK_DEBUG && !\white_list::check($request->header['x-real-ip']))
		{
			$response->end('{"ret":9}');
			return;
		}

		$data = &$request->get;
	}
	else
	{
		if($request->header['x-real-ip'] !== '127.0.0.1')
		{
			$response->status(404);
			$response->end('{"ret":404}');
			return;
		}

		$data = &$request->post;
	}

	$uid	= $data['userID'];
	$session= $data['dwSessionID'];
	$token	= $data['token'];
	
	if(!$res = json_decode($data['token'],true) )
	{
		$response->end('{"ret":404}');
		return;
	}

	$postData = array
	(
		'uid'	=>$res['uid'],
		'ucid'	=>$res['ucid'],
		'uuid'	=>$res['uuid'],
		'appId'	=>SECRET_ANFENG_APPID,
	);

	$postData['sign'] = md5(\sign::make_string($postData).'&signKey='.SECRET_ANFENG_SECRET);

	\async_http::do_http(SECRET_ANFENG_CHECK_IP,SECRET_ANFENG_CHECK_URI,$postData,array(__CLASS__, 'resp_check_login'),array($response,$uid, $session));
	return;
}

/*
* 自定义任务响应方法
*/
public	static	function resp_check_login($ress, $transfer)
{
	$response	= $transfer[0];
	$uid		= $transfer[1];
	$session	= $transfer[2];

	if($ress && ($res = json_decode($ress,true)) && isset($res['returnCode']) && $res['returnCode'] === 0)
	{
		$response->end(json_encode(array('resultCode'=>1,'userID'=>$uid,'sessionID'=>$session)));
		return;
	}

	write_log('loginstate_err', 'anfeng:'.$ress);

	$response->end(json_encode(array('resultCode'=>-1,'sessionID'=>$session)));
}

}
