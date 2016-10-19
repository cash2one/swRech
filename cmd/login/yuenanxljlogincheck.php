<?php
namespace cmd\login;
/*
* YUENANXLJ登录校验 
* auther zzd
*/
class yuenanxljlogincheck extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/yuenanxljloginstate.xl';
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

	$uid	= $data['userID'];
	$session= $data['dwSessionID'];
	$token	= $data['token'];
	$sourceStr = '?user_token='.rawurlencode($token);

//	\worker::task_push(array(WT_PROTOCOL_CURL_DEAL, array(0,SECRET_YUENANXLJ_CHECK_IP.SECRET_YUENANXLJ_CHECK_URI.$sourceStr,0)),array(__CLASS__,'resp_check_login'),array($response,$uid, $session));

	\async_http::do_http(SECRET_YUENANXLJ_CHECK_IP,SECRET_YUENANXLJ_CHECK_URI.$sourceStr,0,array(__CLASS__, 'resp_check_login'),array($response,$uid,$session),54321);
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

	if($ress && ($res=json_decode($ress,true)) && $res['code'] == 200)
	{
		$response->end(json_encode(array('resultCode'=>1,'userID'=>$uid,'sessionID'=>$session)));
		return;
	}

	write_log('loginstate_err', 'yuenanxlj:'.$ress);

	$response->end(json_encode(array('resultCode'=>-1,'sessionID'=>$session)));
}

}
