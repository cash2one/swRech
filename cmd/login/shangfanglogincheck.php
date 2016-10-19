<?php
namespace cmd\login;
/*
* shangfang易接登录校验 
* auther zzd
*/
class shangfanglogincheck extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM 	= '/shangfangloginstate.xl';
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
	$token	= json_decode($data['token'],true);

	$postData	= array
	(
		'sdk' => $token['sdk'],
		'app'=> SECRET_SHANGFANG_APPID,
		'uin'=> $token['uin'],
		'sess' => $token['sess'],
	);

	\async_http::do_http(SECRET_YIJIE_CHECK_IP,SECRET_YIJIE_CHECK_URI."?".http_build_query($postData),0,array(__CLASS__, 'resp_check_login'),array($response,$uid,$session));
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

	if($ress == 0 && is_numeric($ress) )
	{
		$response->end(json_encode(array('resultCode'=>1,'userID'=>$uid,'sessionID'=>$session)));
		return;
	}

	write_log('loginstate_err', 'shangfang:'.$ress);

	$response->end(json_encode(array('resultCode'=>-1,'sessionID'=>$session)));
}

}
