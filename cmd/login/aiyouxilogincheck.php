<?php
namespace cmd\login;
/*
* AIYOUX登录校验 
* auther zzd
*/
class aiyouxilogincheck extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM 	= '/aiyouxiloginstate.xl';
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
	//$sign = md5(SECRET_TIANYOU_APPID.$token.SECRET_TIANYOU_APPID);
	//$sourceStr = 'AppID='.SECRET_TIANYOU_APPID.'&userid='.$uid.'&usertoken='.$token;
	$postData	= array
	(
		'client_id' => SECRET_AIYOUXI_APPID,
		'client_secret'=> SECRET_AIYOUXI_APPKEY,
		'code' => $token,
		'grant_type' => 'authorization_code',
	);

	\async_http::do_http(SECRET_AIYOUXI_CHECK_IP,SECRET_AIYOUXI_CHECK_URI,$postData,array(__CLASS__, 'resp_check_login'),array($response,$uid,$session),443, true);
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

	if($ress && ($res = json_decode($ress,true)) && isset($res['user_id']) )
	{
		$response->end(json_encode(array('resultCode'=>1,'userID'=>$res['user_id'],'sessionID'=>$session)));
		return;
	}

	write_log('loginstate_err', 'tianyou:'.$ress);

	$response->end(json_encode(array('resultCode'=>-1,'sessionID'=>$session)));
}

}
