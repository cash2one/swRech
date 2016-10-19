<?php
namespace cmd\login;
/*
* XIAOMI登录校验 
* auther zzd
*/
class xiaomilogincheck extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/xiaomiloginstate.xl';
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
		'appId'		=>SECRET_XIAOMI_APPID,
		'uid'		=>$uid,
		'session'	=>$token,
	);

	$postData['signature'] = \sign::xiaomi_encode($postData);

	//\worker::task_push(array(WT_PROTOCOL_CURL_DEAL, array(0,SECRET_XIAOMI_CHECK_IP.SECRET_XIAOMI_CHECK_URI,$postData)),array(__CLASS__,'resp_check_login'),array($response,$uid, $session));

	$sourceStr = '?'.http_build_query($postData);

	\async_http::do_http(SECRET_XIAOMI_CHECK_IP,SECRET_XIAOMI_CHECK_URI.$sourceStr,0,array(__CLASS__, 'resp_check_login'),array($response,$uid,$session));
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

	if($ress && ($res=json_decode($ress,true)) && isset($res['errcode']) && $res['errcode'] == 200)
	{
		$response->end(json_encode(array('resultCode'=>1,'userID'=>$uid,'sessionID'=>$session)));
		return;
	}

	write_log('loginstate_err', 'xiaomi:'.$ress);

	$response->end(json_encode(array('resultCode'=>-1,'sessionID'=>$session,)));
}

}
