<?php
namespace cmd\login;
/*
* 淘手游登录校验 
* auther zzd
*/
class tsylogincheck extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/tsyloginstate.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_LOGIN;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if(isset($request->get))
	{
		//@由服务器发起 校验IP即可 无须签名
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

	$postData = array
	(
		'appid'	=> SECRET_TAOSHOUYOU_APPID,
		'userid'=> $uid,
		'token' => $token,
	);

	//\worker::task_push(array(WT_PROTOCOL_CURL_DEAL, array(0,SECRET_TAOSHOUYOU_CHECK_IP.SECRET_TAOSHOUYOU_CHECK_URI,$postData)),array(__CLASS__,'resp_check_login'),array($response,$uid, $session));

	\async_http::do_http(SECRET_TAOSHOUYOU_CHECK_IP,SECRET_TAOSHOUYOU_CHECK_URI,$postData,array(__CLASS__, 'resp_check_login'),array($response,$uid,$session));
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

	if($ress==='success')
	{
		$response->end(json_encode(array('resultCode'=>1,'userID'=>$uid,'sessionID'=>$session)));
		return;
	}

	write_log('loginstate_err', 'tsy:'.$ress);

	$response->end(json_encode(array('resultCode'=>-1, 'sessionID'=>$session)));
}

}
