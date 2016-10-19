<?php
namespace cmd\login;
/*
* KUPAI登录校验 
* auther zzd
*/
class kupailogincheck extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/kupailoginstate.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_LOGIN;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if(isset($request->post))
	{
		$data = &$request->post;

		if(!isset($data['code']))
		{
			$response->end('{"ret":9}');
			return;
		}
	}
	else
	{
		if($request->header['x-real-ip'] !== '127.0.0.1')
		{
			$response->end('{"ret":404}');
			return;
		}

		$data = &$request->get;
	}

//	$uid	= $data['userID'];
//	$session= $data['dwSessionID'];
	$token	= $data['code'];
	$postData = array
	(
		'grant_type'	=>'authorization_code',
		'client_id'		=>SECRET_KUPAI_APPID,
		'client_secret'	=>SECRET_KUPAI_APPKEY,
		'code'			=>$token,
		'redirect_uri'	=>SECRET_KUPAI_APPKEY,
	);
	$sourceStr = '?'.http_build_query($postData);

	//\worker::task_push(array(WT_PROTOCOL_CURL_DEAL, array(0,SECRET_KUPAI_CHECK_IP.SECRET_KUPAI_CHECK_URI.$sourceStr,0)),array(__CLASS__,'resp_check_login'),array($response,$uid, $session));

	\async_http::do_http(SECRET_KUPAI_CHECK_IP,SECRET_KUPAI_CHECK_URI.$sourceStr,0,array(__CLASS__, 'resp_check_login'),array($response),443,true);
	return;
}

/*
* 自定义任务响应方法
*/
public	static	function resp_check_login($ress, $transfer)
//public	static	function resp_check_login($serv,$ress, $transfer)
{
	$response	= $transfer[0];
//	$uid		= $transfer[1];
//	$session	= $transfer[2];

	if($ress && ($res = json_decode($ress,true)) && isset($res['openid']))
	{
		$response->end(json_encode(array('resultCode'=>1,'userID'=>$res['openid'],'token'=>$res['access_token'])));
		return;
	}

	write_log('loginstate_err', 'kupai:'.$ress);

	$response->end(json_encode(array('resultCode'=>-1)));
}

}
