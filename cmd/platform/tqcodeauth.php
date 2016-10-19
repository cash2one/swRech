<?php
namespace cmd\platform;
/*
* 天趣用户验证码
* auther zzd
*/
class tqcodeauth extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/tq/codeauth.xl';
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
		'type',	//@ 1 申请， 2 找回
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

	$account = $data["uid"];

	if (!\sign::checkAppId($data['appid']))
	{
		$response->end('{"ret":100}');
		return;
	}

	if ( !\sign::isMobile($account) ) {
		$response->end('{"ret":104}');
		return;
	}

	if (!$checked = checkUserReg($account) )
	{
		$response->end('{"ret":101}');
		return;
	}

	$ip = $request->header["x-real-ip"];

	if($data['type'] == 1) {
		if ( $checked != 1 ) {
			$response->end('{"ret":102}');
			return ;
		}
	} elseif ($data['type'] == 2) {
		if ( $checked == 1 ) {
			$response->end('{"ret":"102-2"}');
			return;
		}
	} else {
		$response->end('{"ret":404}');
		return;
	}

	$ncode = rand(100000,900000)+rand(1,99999);

	if(!$res = setCodeTimeout($account, $ip, $ncode))
	{
		$response->end('{"ret":1011}');
		return;
	}

	if($res != 1 )
	{
		$response->end('{"ret":107}');
		return;
	}

//	if($send == 1) {
	//	$smtp = new \smtp();
	//	$state = $smtp->sendmail($account, "adzzd2004@163.com", "【天趣游戏】验证码", "<h1>请输入下面的注册验证码 3分钟有效。</h1><font>".$ncode."</font>");

	//	if(!$state)
	//	{
	//		$response->end('{"ret":105}');
	//		return;
	//	}
	//发送邮件激活码
		//发送手机激活码
		$state = \sms::send($account,'您的验证码是'.$ncode.', 5分钟有效。');
		if(!$state)
		{
			$response->end('{"ret":106}');
			return;
		}
//	}

	$response->end('{"ret":0}');
	return;
}

/*
* 自定义任务响应方法
*/

}
