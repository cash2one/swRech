<?php
namespace cmd\platform;
/*
* 天趣用户info
* auther zzd
*/
class tquserinfo extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/tq/userinfo.xl';
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

	$openid = $data["openid"];

	if( !\sign::checkOpenId($openid) ) {
		$response->end('{"ret":100}');
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

	if($data['token'] !== checkUserLogin($openid) ) {
		$response->end('{"ret":115}');
		return;
	}

	// async_mysql query
	$where	= "`id`=".$openid;
	$table	= 'user';

	\service\mysql::selectLogDB($table, 'account,phone', [[__CLASS__, 'resp_user_login'],[$response]], $where);
	return;
}

/*
* 自定义任务响应方法
*/
public	static	function resp_user_login($result, $transfer, $errno=0)
{
	$response	= $transfer[0];
	if (is_array($result) && isset($transfer[0]))
	{
		$response->end('{"ret":0,"uid":"'.($result[0]['account']).'", "phone":'.($result[0]['phone']).'}');
		return;
	}
	write_log('login_err', $result);
	$response->end('{"ret":"103-2"}');
}

}
