<?php
namespace cmd\login;
/*
* WUYOUWAN登录校验 
* auther zzd
*/
class wuyouwanlogincheck extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/wuyouwanloginstate.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_LOGIN;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if(isset($request->get))
	{
		if( IP_CHECK_DEBUG && !\white_list::check($request->header['x-real-ip']))
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

	if(!isset($data['userID']) || !isset($data['dwSessionID']) || !isset($data['token']))
	{
		$response->end('{"ret":4}');
		return;
	}

	$uid	= $data['userID'];
	$session= $data['dwSessionID'];
	$token	= $data['token'];

	$postData = array
	(
		'AppID'		=>SECRET_WUYOUWAN_APPID,
		'PUserID'	=>$uid,
		'Token'		=>$token,
		'Sign'		=>md5(SECRET_WUYOUWAN_APPID.$uid.$token.SECRET_WUYOUWAN_APPKEY),
	);

	//\worker::task_push(array(WT_PROTOCOL_CURL_DEAL, array(0,SECRET_WUYOUWAN_CHECK_IP.SECRET_WUYOUWAN_CHECK_URI,$postData)),array(__CLASS__,'resp_check_login'),array($response,$uid, $session));

	$sourceStr = '?'.http_build_query($postData);

	\async_http::do_http(SECRET_WUYOUWAN_CHECK_IP,SECRET_WUYOUWAN_CHECK_URI.$sourceStr,0,array(__CLASS__, 'resp_check_login'),array($response,$uid,$session));
	return;
}

/*
* 自定义任务响应方法
*/
public	static	function resp_check_login($ress, $transfer)
//public	static	function resp_check_login($serv, $ress, $transfer)
{
	$response	= $transfer[0];
	$uid		= $transfer[1];
	$session	= $transfer[2];

	if($ress != '-1')
	{
		$response->end(json_encode(array('resultCode'=>1,'userID'=>$uid,'sessionID'=>$session)));
		return;
	}

	write_log('loginstate_err', 'wuyouwan:'.$ress);

	$response->end(json_encode(array('resultCode'=>-1,'sessionID'=>$session,)));
}

}
