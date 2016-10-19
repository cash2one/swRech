<?php
namespace cmd\platform;
/*
* 天趣用户验证码认证
* auther zzd
*/
class tqregister extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/tq/register.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_PLATFORM;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
	if (!isset($request->post))
	{
		$response->status(404);
		$response->end('');
		return;
	}

	$data = &$request->post;

	$index = array(
		'uid',
		'type',
		'pwd',
		'appid',
		'ts',
		'code',
		'sig'
	);

	if(count($index) != count($data))
	{
		$response->end('{"ret":4}');
		return;
	}

	foreach($index as $k)
	{
		if( !isset($data[$k]) )
		{
			$response->end('{"ret":4}');
			return;
		}
	}

	$sign = $data['sig'];
	unset($data['sig']);

	if(!\sign::asc_decode('POST', self::CMD_NUM, $data, $sign))
	{
		$response->end('{"ret":3}');
		return;
	}

	if (!\sign::checkAppId($data['appid']))
	{
		$response->end('{"ret":100}');
		return;
	}

	$password = $data['pwd'];

	if(strlen($password) != 32)
	{
		$response->end('{"ret":100}');
		return;
	}

	$account	= $data['uid'];
	
	$code = $data['code'];

	$ip = $request->header['x-real-ip'];

	if($data['type'] === '1') {
		if(\sign::isMobile($account)) {
			if(strlen($code) != 6)
			{
				$response->end('{"ret":"404-11"}');
				return;
			}
			if (!getRegCode($account,$ip,$code))
			{
				$response->end('{"ret":108}');
				return;
			}
	
			$send	= 1; 
			$cmd	= "insert_phone_user";
		}
	} elseif($data['type'] === '2') {

		if($code !== '0')
		{
			$response->end('{"ret":"404-1"}');
			return;
		}
		if(strlen($account) < 6 || strlen($account) > 16 || is_numeric($account) || !preg_match("/^[0-9a-z]+$/",$account) )
		{
			$response->end('{"ret":"404-2"}');
			return;
		}

		if(!checkPlatformCD($ip,1) ){
			$response->end('{"ret":"404-1"}');
			return;
		}

		$send = 2;
		$cmd	= "insert_simple_user";
	} else {
		$response->end('{"ret":404}');
		return;
	}

	$password	= \sign::encodePassword($password);

	//@check
	$checked = setNewUser($account);

	if (!$checked)
	{
		$response->end('{"ret":101}');
		return;
	}

	if($checked == 2)
	{
		$response->end('{"ret":102}');
		return;
	}


	$res = \service\mysql::storeProcedure(sprintf("call %s('%s','%s','%s', '%s','%s')", $cmd, $send, $account, $password, $data['appid'], $ip));

	if(is_array($res) && $res[0]['@res'] > 10)
	{
		$openid = $res[0]['@res'];
		setNewUserSuc($account, json_encode([$openid,$password]));
		$token	= \sign::getToken($account);
		setUserLogin($openid, $token);
		$response->end('{"ret":0,"openid":"'.$openid.'", "token":"'.$token.'"}');
		return;
	}

	setNewUserFail($account);

	$response->end('{"ret":104}');
	return;
//	if ($send == 2) {
//		\service\mysql::insertDB('user',["type","email", "passwd", "origin", "ip" ], [$send,$account, $password, $appid, $ip], [[__CLASS__, "resp_user_insert"],[$response, $account, $openid]]);
//	} else {
//		\service\mysql::insertDB('user',["type","phone", "passwd", "origin", "ip"], [$send, $account, $password, $appid, $ip], [[__CLASS__, "resp_user_insert"],[$response, $account, $openid]]);
//	}

	return;
}

/*
* 自定义任务响应方法
*/
//public	static	function resp_user_insert($result,$transfer, $errno=0, $openid)
//{
//}

}
