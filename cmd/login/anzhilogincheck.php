<?php
namespace cmd\login;
/*
* ANZHI登录校验 
* auther zzd
*/
class anzhilogincheck extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/anzhiloginstate.xl';
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
	$time	= time();

	$postData = array
	(
		'appkey'	=>SECRET_ANZHI_APPKEY,
		'sid'		=>$token,
		'time'		=>time(),
	);

	$postData['sign']		= base64_encode(SECRET_ANZHI_APPKEY.$token.SECRET_ANZHI_SECRET);

	//$postData	= \sign::make_string($postData);


//	\worker::task_push(array(WT_PROTOCOL_CURL_DEAL, array(0,SECRET_ANZHI_CHECK_IP.SECRET_ANZHI_CHECK_URI,$postData)),array(__CLASS__,'resp_check_login'),array($response,$uid, $session));

	\async_http::do_http(SECRET_ANZHI_CHECK_IP,SECRET_ANZHI_CHECK_URI,$postData,array(__CLASS__, 'resp_check_login'),array($response,$uid, $session));
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
	$ress		= str_replace("'",'"', $ress);

	if($ress && ($res = json_decode($ress,true)) && $res['sc'] == 1)
	{
		$msg = json_decode(str_replace("'",'"',base64_decode($res['msg'])),true);
		$response->end(json_encode(array('resultCode'=>1,'userID'=>$msg['uid'],'sessionID'=>$session)));
		return;
	}

	write_log('loginstate_err', 'anzhi:'.$ress);

	$response->end(json_encode(array('resultCode'=>-1,'sessionID'=>$session)));
}

}
