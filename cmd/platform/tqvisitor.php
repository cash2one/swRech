<?php
namespace cmd\platform;
/*
* 天趣用户登录接口
* auther zzd
*/
class tqvisitor extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/tq/visitor.xl';
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
		'nonce',
		'appid',
		'ts',
		'sig'
	);

	if(count($data) != count($index))
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

	if (!\sign::checkAppId($data['appid'])) {
		$response->end('{"ret":100}');
		return;
	}

	$password = $data["nonce"];

	if(strlen($password) != 32)
	{
		$response->end('{"ret":100}');
		return;
	}

	$ip	= $request->header['x-real-ip'];
	//@CD
	$password = \sign::encodePassword($password);

//	$table	= 'user';
//	$cols	= ['type', 'passwd', 'origin', 'ip'];
//	$values	= [3,$password,$appid,$ip];
//
//	\service\mysql::insertDB($table, $cols, $values,[[__CLASS__, 'resp_visitor_reg'],[$response,$password]]);
//	return;
	
	$i = 3;

	while($i--) {
		$account = chr(rand(97, 122)).(microtime(true)*10000).rand(0,9);
	
		$checked = setNewUser($account);
	
		if (!$checked)
		{
			$response->end('{"ret":101}');
			return;
		}
	
		if($checked == 2)
		{
			if($i == 0){
				$response->end('{"ret":102}');
				return;
			}
			continue;
		}
		break;
	}


	$send = 3;
	$cmd    = "insert_simple_user";

	$ip = $request->header['x-real-ip'];

	$res = \service\mysql::storeProcedure(sprintf("call %s('%s','%s','%s', '%s','%s')", $cmd, $send, $account, $password, $data['appid'], $ip));

	if(is_array($res) && $res[0]['@res'] > 10)
	{
		$openid = $res[0]['@res'];
		setNewUserSuc($account, json_encode([$openid,$password]));
		$token	= \sign::getToken($account);
		setUserLogin($openid, $token);
		$response->end('{"ret":0,"uid":"'.$account.'","openid":"'.$openid.'", "token":"'.$token.'"}');
		return;
	}

	setNewUserFail($account);

	$response->end('{"ret":104}');

	return;
}

/*
* 自定义任务响应方法
*/
public	static	function resp_visitor_reg($result, $transfer, $errno=0, $openid)
{
//	$response	= $transfer[0];
//	$password	= $transfer[1];
//	if ($result === true)
//	{
//		$i = 2;
//		while($i--)
//		{
//			if (!setNewUser($openid)){
//				write_log('visitor','setNewUser fail:'.$i."acc:".$account);
//				continue;
//			} else {
//				break;
//			}
//		}
//		setUserLogin($openid, $openid);
//		$response->end('{"ret":0,"openid":"'.$openid.'","token":'.rand(1111,9999).'}');
//		return;
//	}
//	write_log('visitor_err', $result);
//	$response->end('{"ret":"103-2"}');
}

}
