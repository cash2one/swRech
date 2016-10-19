<?php
namespace cmd\login;
/*
* OPPO登录校验 
* auther zzd
*/
class oppologincheck extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/oppologinstate.xl';
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

	$sourceStr = "?fileId=".$uid."&token=".rawurlencode($token);

	$time = microtime(true);
	$base_str = array
	(
		'oauthConsumerKey'		=>SECRET_OPPO_APPKEY,
		'oauthToken'			=>rawurlencode($token),
		'oauthSignatureMethod'	=>'HMAC-SHA1',
		'oauthTimestamp'		=>intval($time*1000),
		'oauthNonce'			=>intval($time) + rand(0,9),
		'oauthVersion'			=>'1.0',
	);

	$str = '';
	foreach($base_str as $k=>$v)
	{
		$str .= $k.'='.$v.'&';
	}

	$sign = \sign::oppo_encode($str);

	$header = array
	(

		'Host'			=> SECRET_OPPO_CHECK_IP,
		'param'			=> $str,
		'oauthSignature'=> rawurlencode($sign),
	);

	//\worker::task_push(array(WT_PROTOCOL_CURL_DEAL, array(0,SECRET_OPPO_CHECK_IP.SECRET_OPPO_CHECK_URI,$postData)),array(__CLASS__,'resp_check_login'),array($response,$uid, $session));

	\async_http::do_http(SECRET_OPPO_CHECK_IP,SECRET_OPPO_CHECK_URI.$sourceStr,0,array(__CLASS__, 'resp_check_login'),array($response,$uid,$session),80,false,null,$header);
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

	if($ress && ($res=json_decode($ress,true)) && isset($res['resultCode']) && $res['resultCode'] == 200)
	{
		$response->end(json_encode(array('resultCode'=>1,'userID'=>$res['ssoid'],'sessionID'=>$session)));
		return;
	}

	write_log('loginstate_err', 'oppo'.$ress);

	$response->end(json_encode(array('resultCode'=>-1,'sessionID'=>$session,)));
}

}

