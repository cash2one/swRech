<?php
namespace cmd\login;
/*
* 淘手游登录校验 
* auther zzd
*/
class tyyblogincheck extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/tyybloginstate.xl';
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

	$type	= substr($data['userID'],0,3);
	$uid	= substr($data['userID'],3);
	$session= $data['dwSessionID'];
	$token	= $data['token'];


	if($type==='QQ_')
	{
		$uri	= SECRET_TENCENT_QQ_CHECK_URI;
		$key	= SECRET_TENCENT_QQ_APPKEY;
	}
	elseif($type==='WX_')
	{
		$key	= SECRET_TENCENT_WX_APPKEY;
		$uri	= SECRET_TENCENT_WX_CHECK_URI;
	}
	else
	{
		$response->end('{"ret":4}');
		return;
	}

	$now = time();

	$postData = array
	(
		'appid'	=> SECRET_TENCENT_APPID,
		'openid'	=> $uid,
		'openkey'	=> rawurlencode($token),
		'timestamp'	=> $now,
		'sig'		=> md5($key.$now),
	);

	$sourcestr	= '?'.\sign::make_string($postData);

	\async_http::do_http(SECRET_TENCENT_CHECK_IP,$uri.$sourcestr,0,array(__CLASS__, 'resp_check_login'),array($response,$uid,$session));
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

	if($ress && ($res=json_decode($ress,true)) && $res['ret'] === 0)
	{
		$response->end(json_encode(array('resultCode'=>1,'userID'=>$uid,'sessionID'=>$session)));
		return;
	}

	write_log('loginstate_err', 'tyyb:'.$ress);

	$response->end(json_encode(array('resultCode'=>-1, 'sessionID'=>$session)));
}

}
