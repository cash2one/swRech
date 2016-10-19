<?php
namespace cmd\platform;
/*
* 天趣用户验证码
* auther zzd
*/
class tquserbind extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/tq/userbind.xl';
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
		'phone',
		'token',
		'code',
		'appid',
		'ts',
		'sig'
	);

	if(count($index ) != count($data))
	{
		$response->end('{"ret":4}');
		return;
	}

	foreach($index as $k)
	{
		if( !isset($data[$k]) || $data[$k] == "")
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

	$openid		= $data['openid'];
	$phone		= $data['phone'];

	if(!\sign::checkOpenId($openid)) {
		$response->end('{"ret":109}');
		return;
	}

	if (!checkPlatformCD('CD_PUBLIC_'.$openid)) {
		$response->end('{"ret":107}');
		return;
	}

	if (!\sign::checkAppId($data['appid'])) {
		$response->end('{"ret":100}');
		return;
	}

	if ( !\sign::isMobile($phone) ) {
		$response->end('{"ret":104}');
		return;
	}

	if($data['token'] !== checkUserLogin($openid) ) {
		$response->end('{"ret":115}');
		return;
	}

	$ip = $request->header["x-real-ip"];

	if(!getRegCode($phone,$ip,$data['code']))
	{
		$response->end('{"ret":108}');
		return;
	}

	if (setNewUser($phone) != 1)
	{
		$response->end('{"ret":102}');
		return;
	}

	$where	= "`id`=".$openid;
	$table	= 'user';

	$res = \service\mysql::storeProcedure(sprintf('call bindphone(%s,%s)',$phone,$openid));

	if(!is_array($res) || !isset($res[0]['account']) )
	{
		setNewUserFail($phone);
		$response->end('{"ret":110}');
		return;
	}

	setNewUserSuc($phone, json_encode([$openid,$res[0]['passwd']]));

	$response->end('{"ret":0}');
	return;
}

/*
* 自定义任务响应方法
*/


}
