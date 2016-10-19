<?php
namespace cmd\platform;
/*
* 天趣用户info
* auther zzd
*/
class tqmodifysecret extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/tq/modifysecret.xl';
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
		'openid',
		'token',
		'pwd',
		'npwd',
		'rpwd',
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

	if(strlen($data['pwd']) != 32 || strlen($data['rpwd']) != 32 || $data['npwd'] !== $data['rpwd'] || $data['pwd'] === $data['npwd']) {
		$response->end('{"ret":404}');
		return;
	}

	$openid = $data["openid"];

	if(!\sign::checkOpenId($openid)) {
		$response->end('{"ret":109}');
		return;
	}

	if(!checkPlatformCD('CD_PUBLIC_'.$openid))
	{
		$response->end('{"ret":107}');
		return;
	}

	if (!\sign::checkAppId($data['appid'])) {
		$response->end('{"ret":100}');
		return;
	}

	//@check
	if($data['token'] !== checkUserLogin($openid) ) {
		$response->end('{"ret":115}');
		return;
	}

	// async_mysql query
	$where	= "`id`=".$openid." and passwd='".(\sign::encodePassword($data['pwd']))."'";
	$table	= 'user';

	$npasswd = \sign::encodePassword($data['npwd']);
	\service\mysql::selectLogDB($table, 'account,phone', [[__CLASS__, 'resp_user_select'],[$response,$openid,$npasswd]], $where);
}

public	static	function resp_user_select($result, $transfer)
{
	$response	= $transfer[0];

	if(!is_array($result) || !isset($result[0]))
	{
		write_log('platform_err', array('modify_secret_select_err',$result));
		$response->end('{"ret":101}');
		return;
	}
	$openid		= $transfer[1];
	$npasswd	= $transfer[2];
	$transfer[] = $result[0]['account'];
	$transfer[] = $result[0]['phone'];
	\service\mysql::simpleUpdateLogDB('user', ['passwd'=>$npasswd], "`id`=".$openid, [[__CLASS__, 'resp_user_modify'],$transfer]);
	return;
}

/*
* 自定义任务响应方法
*/
public	static	function resp_user_modify($result, $transfer, $errno=0)
{
	$response	= $transfer[0];
	$openid		= $transfer[1];
	$password	= $transfer[2];
	$account	= $transfer[3];
	$phone		= $transfer[4];

	if ($result===true)
	{
		setNewUserSuc($account, json_encode([$openid,$password]));
		if($phone)
			setNewUserSuc($phone, json_encode([$openid,$password]));
		$response->end('{"ret":0}');
		return;
	}
	write_log('platform_err', 'modify_secret_update_err');
	$response->end('{"ret":"103-2"}');
}

}
