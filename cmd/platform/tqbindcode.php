<?php
namespace cmd\platform;
/*
* 天趣用户验证码
* auther zzd
*/
class tqbindcode extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/tq/bindcode.xl';
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
		'type',	//@ 1 绑定， 2 更换验证原手机, 3 更换验证新手机
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

	$openid	= $data['openid'];
	$phone		= $data['phone'];

	if (!\sign::checkAppId($data['appid']))
	{
		$response->end('{"ret":100}');
		return;
	}

	if (!\sign::checkOpenId($openid)) {
		$response->end('{"ret":109}');
		return;
	}

	if ( !\sign::isMobile($phone) ) {
		$response->end('{"ret":104}');
		return;
	}

	if (!checkPlatformCD('CD_PUBLIC_'.$openid)) {
		$response->end('{"ret":107}');
		return;
	}

	if($data['token'] !== checkUserLogin($openid) ) {
		$response->end('{"ret":115}');
		return;
	}

	if (!$checked = checkUserReg($phone) ) {
		$response->end('{"ret":101}');
		return;
	}

	$type = $data['type'];
	if($type === '1') {
		if($checked !== 1) {
			$response->end('{"ret":110}');
			return;
		}
	} elseif ($type === '2') {
		if($checked === 1) {
			$response->end('{"ret":111}');
			return;
		}
	} elseif ($type === '3') {
		if($checked !== 1) {
			$response->end('{"ret":110}');
			return;
		}
	} else {
		$response->end('{"ret":404}');
		return;
	}


	$where	= "`id`=".$openid;
	$table	= 'user';

	$ip = $request->header["x-real-ip"];
	\service\mysql::selectLogDB($table, 'account,phone', [[__CLASS__, 'resp_user_login'],[$response,$phone,$ip,$type]], $where);
	return;
}

/*
* 自定义任务响应方法
*/
public	static	function resp_user_login($result, $transfer, $errno=0)
{
	$response	= $transfer[0];

	if(!is_array($result) || !isset($result[0])) {
		$response->end('{"ret":101}');
		return;
	}

	$type	= $transfer[3];
	$ophone = $result[0]['phone'];
	$nphone	= $transfer[1];

	if($type == 1) {
		if($ophone != 0) {
			$response->end('{"ret":112}');
			return;
		}

	} elseif ($type == 2) {
		if ($ophone == 0 ) {
			$response->end('{"ret":113}');
			return;
		} elseif ($ophone != $nphone) {
			$response->end('{"ret":111}');
			return;
		}
	} else {
		if ($ophone == $nphone) {
			$response->end('{"ret":114}');
			return;
		}

		if(!$ophone = checkUserModify($openid)) {
			$response->end('{"ret":116}');
			return;
		}
		//@检查 是否已经 验证过type 2
	}

	$ip		= $transfer[2];
	$ncode	= rand(100000,900000)+rand(1,99999);

	if(!$res = setCodeTimeout($nphone, $ip, $ncode)) {
		$response->end('{"ret":1011}');
		return;
	}

	if($res != 1 )
	{
		$response->end('{"ret":107}');
		return;
	}

	$state = \sms::send($nphone,'您的验证码是'.$ncode.', 5分钟有效。');

	if(!$state)
	{
		$response->end('{"ret":106}');
		return;
	}

	$response->end('{"ret":0}');
	return;
}


}
