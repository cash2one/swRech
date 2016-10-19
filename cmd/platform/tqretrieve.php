<?php
namespace cmd\platform;
/*
* 天趣用户验证码认证
* auther zzd
*/
class tqretrieve extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/tq/retrieve.xl';
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

	if(strlen($data['code']) != 6 || !is_numeric($data['code']))
	{
		$response->end('{"ret":404}');
		return;
	}

	$account	= $data['uid'];
	
	if( !\sign::isMobile($account) )
	{
		$response->end('{"ret":404}');
		return;
	}

	if (!$checked = checkUserReg($account) )
	{
		$response->end('{"ret":101}');
		return;
	}

	if( $checked == 1 )
	{
		$response->end('{"ret":102-2}');
		return;
	}

	$code = $data['code'];

	$ip = $request->header["x-real-ip"];

	if (!getRegCode($account,$ip,$code))
	{
		$response->end('{"ret":108}');
		return;
	}

	$password	= \sign::encodePassword($password);

	\service\mysql::selectLogDB('telephone,user', 'id,account',[[__CLASS__,'resp_user_select'],[$response, $account, $password]], 'telephone.phone='.$account.' and telephone.uid=user.id');

	return;
}

/*
* 自定义任务响应方法
*/
public	static	function resp_user_select($result,$transfer, $errno=0)
{
	$response	= $transfer[0];

	if(!is_array($result) || empty($result)){
		var_dump($result);
		$response->end('{"ret":101}');
		return;
	}
	$phone		= $transfer[1];
	$password	= $transfer[2];
	$account	= $result[0]['account'];
	$openid		= $result[0]['id'];
	//@check
	$regData	= json_encode(array($openid,$password));
	if ( false === setNewPasswd($account,$regData) )
	{
		$response->end('{"ret":101}');
		return;
	}

	setNewPasswd($phone,$regData);
	\service\mysql::simpleUpdateLogDB('user', ['passwd'=>$password],"id='".$openid."'", [[__CLASS__, 'resp_user_update'],[$response]]);
	return;
}

public	static	function resp_user_update($result,$transfer, $errno=0)
{
	$response	= $transfer[0];

	if( $result === true )
	{
		$response->end('{"ret":0}');
		return;
	}

	write_log('retrieve', 0);

	$response->end('{"ret":"101-2"}');
}

}
