<?php
namespace cmd\platform;
/*
* 天趣用户登录接口
* auther zzd
*/
class tqlogin extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/tq/login.xl';
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
		'pwd',
		'appid',
		'ts',
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

	$account = $data["uid"];

	$account_len = strlen($account);

	if($account_len < 6 || $account_len > 16 || ! preg_match("/^[0-9a-z]+$/",$account) )
	{
		$response->end('{"ret":100}');
		return;
	}

	$send = 2;

	if (!\sign::checkAppId($data['appid']))
	{
		$response->end('{"ret":100}');
		return;
	}

	$password = $data["pwd"];

	if(strlen($password) != 32)
	{
		$response->end('{"ret":100}');
		return;
	}

	$password = \sign::encodePassword($password);
	//@check
	if ($checked = checkUserReg($account) )
	{
		if($checked == 1)
		{
			$response->end('{"ret":103}');
			return;
		}

		$regData = json_decode($checked);

		if( $password !== $regData[1] )
		{
			$response->end('{"ret":103}');
			return;
		}

		$openid	= $regData[0];
	
		if($token = checkUserLogin($openid) )
		{
			setUserLogin($openid, $token);
		} else {
			$token  = \sign::getToken($openid);
			setUserLogin($openid, $token);
		}

		$response->end('{"ret":0,"openid":"'.$openid.'", "token":"'.$token.'"}');
		return;
	}

	if(!checkPlatformCD('CD_PUBLIC_'.time(),1))
	{
		$response->end('{"ret":107}');
		return;
	}


	// async_mysql query
	switch($send)
	{
		case 1:
			$where	= " telephone.phone=".$account." and telephone.uid=user.id and passwd='".md5($password)."'";
			$table	= 'telephone,user';
			break;
		case 2:
			$where	= "simple_user.account='".$account."' and simple_user.uid=user.id and passwd='".md5($password)."'";
			$table	= 'simple_user,user';
			break;
		default:
			$where	= "`id`=".$openid." and passwd='".md5($password)."'";
			$table	= 'user';
			break;
	}

	\service\mysql::selectLogDB($table, 'id', [[__CLASS__, 'resp_user_login'],[$response,$account]], $where);
	return;
}

/*
* 自定义任务响应方法
*/
public	static	function resp_user_login($result, $transfer, $errno=0)
{
	$response	= $transfer[0];
	$account	= $transfer[1];
	if (is_array($result) && isset($result[0]))
	{
		$openid	= $result[0]['id'];
		$token	= \sign::getToken($account);
		setUserLogin($openid, $token);
		$response->end('{"ret":0,"openid":"'.$openid.'", "token":"'.$token.'"}');
		return;
	}
	write_log('login_err', $result);
	$response->end('{"ret":"103-2"}');
}

}
