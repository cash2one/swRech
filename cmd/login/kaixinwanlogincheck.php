<?php
namespace cmd\login;
/*
* 开心玩登录校验 
* auther zzd
*/
class kaixinwanlogincheck extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM 	= '/kaixinwanloginstate.xl';
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
			$response->end('{"ret":404}');
			return;
		}

		$data = &$request->post;
	}

	$uid	= $data['userID'];
	$session= $data['dwSessionID'];
	$token	= $data['token'];
	$postData	= array
	(
		'scode'		=> $token,
		'api_key'	=> SECRET_KAIXINWAN_APPKEY,
		'sign'		=> md5(md5('scode='.$token.'&api_key='.SECRET_KAIXINWAN_APPKEY).SECRET_KAIXINWAN_SECRET),
	);

	\async_http::do_http(SECRET_KAIXINWAN_CHECK_IP,SECRET_KAIXINWAN_CHECK_URI,$postData,array(__CLASS__, 'resp_check_login'),array($response,$uid,$session));
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

	if($ress && ($res = json_decode($ress,true)) && isset($res['code']) && $res['code'] == 1)
	{
		$response->end(json_encode(array('resultCode'=>1,'userID'=>"".$res['uid'],'sessionID'=>$session)));
		return;
	}

	write_log('loginstate_err', 'kaixinwan:'.$ress);

	$response->end(json_encode(array('resultCode'=>-1,'sessionID'=>$session)));
}

}
