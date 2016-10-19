<?php
namespace cmd\login;
/*
* LENOVO登录校验 
* auther zzd
*/
class lenovologincheck extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/lianxiangloginstate.xl';
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

	$postData = array
	(
		'lpsust'=>rawurlencode($token),
		'realm'	=>SECRET_LENOVO_APPID,
	);

	$sourceStr = '?'.\sign::make_string($postData);

	\async_http::do_http(SECRET_LENOVO_CHECK_IP,SECRET_LENOVO_CHECK_URI.$sourceStr,0,array(__CLASS__, 'resp_check_login'),array($response,$uid, $session),443,true);
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

	if($ress && is_object(@$res = simplexml_load_string($ress)) && isset($res->AccountID) )
	{
		$response->end(json_encode(array('resultCode'=>1,'userID'=>$res->AccountID,'sessionID'=>$session)));
		return;
	}

	write_log('loginstate_err', 'lenovo:'.$ress);

	$response->end(json_encode(array('resultCode'=>-1,'sessionID'=>$session)));
}

}
