<?php
class sign
{
private	static	$appData = ['8888'=>1,];

public	static	function checkAppId($appid)
{
	return isset(self::$appData[$appid])?true:false;
}

public	static	function checkOpenid($openid)
{
	if(strlen($openid) == 12 && is_numeric($openid))
		return true;
	return false;
}

public	static	function encodePassword($password)
{
	return md5($password);
}

public	static	function getToken($account)
{
	return  md5($account . time());
}

public	static	function isMobile($mobile)
{
	if(preg_match("/^1[34578]{1}\d{9}$/", $mobile))
		return true;
	return false;
}

public	static	function strToHex($string)
{
	$hex	= "";
	$len	= strlen($string);

	for($i=0; $i<$len; $i++)
	{
		$hex .= dechex(ord($string[$i]));
	}
	return $hex;
}
//@16进制数字转字符串
public	static	function hexToStr($hex)
{
	$string="";
	for($i=0;$i<strlen($hex)-1;$i+=2)
		$string.=chr(hexdec($hex[$i].$hex[$i+1]));
	return  $string;
}
//拼接http字符串不用&链接
public	static	function make_string_without_and(&$params)
{
	ksort($params);

	$query_string = array();
	foreach($params as $key=>$val)
	{
		$query_string[] = $key . '=' . $val;
	}
	return implode('', $query_string);
}

public	static	function make_string_without_blank(&$params)
{
	ksort($params);

	$query_string = array();
	foreach($params as $key=>$val)
	{
		if(is_null($val) || $val==="")
			continue;
		$query_string[] = $key . '=' . $val;
	}
	return implode('&', $query_string);
}

public	static	function make_string(&$params)
{
	ksort($params);

	$query_string = array();
	foreach($params as $key=>$val)
	{
		$query_string[] = $key . '=' . $val;
	}
	return implode('&', $query_string);
}

public	static	function make_string_with_value(&$params)
{
	ksort($params);

	return implode('', $params);
}

public	static	function asc_sign($method, $url, $params)
{
	$query_string = self::make_string($params);
	return md5(strtoupper($method).'&'.rawurlencode($url).'&'.$query_string.'&'.SECRET_HTTP_KEY);
}

public	static	function asc_encode($method, $url, $params, $signkey)
{
	$query_string = self::make_string($params);

	$sign = md5(strtoupper($method).'&'.rawurlencode($url).'&'.$query_string.'&'.SECRET_HTTP_KEY);

	return $query_string.'&'.$signkey.'='.$sign;
}

public	static	function asc_decode($method, $url, $params, $sign)
{
	$query_string = self::make_string($params);
	//var_dump(md5(strtoupper($method).'&'.rawurlencode($url).'&'. $query_string.'&'.PROTOCOL_HTTP_KEY));

	if(!defined('SECRET_HTTP_KEY'))
		define('SECRET_HTTP_KEY', 'asssssssssssssss');

	if($sign === md5(strtoupper($method).'&'.rawurlencode($url).'&'. $query_string.'&'.SECRET_HTTP_KEY))
		return true;

	write_log('sign', array($sign, strtoupper($method).'&'.rawurlencode($url).'&'. $query_string.'&'.SECRET_HTTP_KEY,md5(strtoupper($method).'&'.rawurlencode($url).'&'. $query_string.'&'.SECRET_HTTP_KEY), $params));
	return false;
}

public	static	function tencent_encode($method, $url, &$params, $signkey='sig')
{
	$query_string = self::make_string($params);
	
	//write_log('tencent', $query_string);

	$mk = strtoupper($method).'&'.rawurlencode($url).'&'.rawurlencode($query_string);

	//write_log('tencent', $mk);

	$sign = base64_encode(hash_hmac( "sha1", $mk, strtr( SECRET_TENCENT_PAY_SECRET, '-_', '+/'), true ));
	//write_log('tencent',$sign);
	$sign = rawurlencode($sign);

	//write_log('tencent',$sign);

	return $query_string .'&sig='.$sign ;
}

public	static	function tencent_decode($method, $url, &$params, $signkey='sig')
{
	if(!isset($params[$signkey]))
		return false;
	
	$sign = $params[$signkey];
	unset($params[$signkey]);

	foreach($params as $k => $v)
	{
		$params[$k] = self::encodeValue( $v );
	}

	$mk	= self::make_string($params);

	$tsign	= hash_hmac( 'sha1', $mk, strtr( SECRET_TENCENT_APPKEY, '-_', '+/'), true );

	if($sign === base64_encode($tsign))
		return true;

	return false;
}

private function encodeValue( $value )
{
	$rst = '';
	$len = strlen( $value );
	
	for ($i=0; $i<$len; $i++)
	{
		$c = $value[$i];
		if( preg_match( '/[a-zA-Z0-9!\(\)*]{1,1}/', $c ) )
		{
			$rst .= $c;
		}
		else
		{
			$rst .= ( '%' . sprintf('%02X', ord($c)) );
		}
	}
	return $rst;
}

//@微信支付通知签名
public	static	function wxin_decode($method, $url, $params, $sign)
{
	$query_string = self::make_string($params);

	$query_string .= SECRET_WINXIN_SECRET;

	if($sign === strtoupper(md5($query_string)))
		return true;
	return false;
}

//@360支付通知签名
public	static	function qh_decode($method, $url, &$params, $sign)
{
	ksort($params);
	$query_string  = array();

	foreach($params as $v)
	{
		$query_string[] = $v;
	}

	$query_string = implode('#', $query_string);

	$query_string .= SECRET_360_SECRET;

	if($sign === md5($query_string))
		return true;
	return false;
}

//@UC签名
public	static	function uc_decode($method, $url, $params, $sign)
{
	$query_string = self::make_string_without_and($params);

	$query_string .= SECRET_UC_APPKEY;

	if($sign === md5($query_string))
		return true;
	return false;
}

//@淘手游支付通知签名
public	static	function tsy_decode($method, $url, $params, $sign)
{
	$query_string = self::make_string($params);

	$query_string .= SECRET_TAOSHOUYOU_SECRET;

	if($sign === md5($query_string))
		return true;
	return false;
}

//@魅族支付通知签名
public	static	function meizu_encode($params)
{
	$query_string = self::make_string($params);

	$query_string .= SECRET_MEIZU_SECRET;

	return md5($query_string);
}

//@魅族支付通知签名
public	static	function meizu_decode($method, $url, $params, $sign)
{
	if($sign === self::meizu_encode($params))
		return true;
	return false;
}

//@LESHI支付通知签名
public	static	function leshi_decode($method, $url, $params, $sign)
{
	$query_string = self::make_string($params);

	$query_string .= '&key='.SECRET_LESHI_SECRET;

	if($sign === md5($query_string))
		return true;
	return false;
}

public	static	function vivo_encode($params)
{
	$query_string = self::make_string_without_blank($params);
	$query_string .= SECRET_VIVO_SECRET;

	return md5($query_string);
}

//@步步高VIVO支付通知签名
public	static	function vivo_decode($method, $url, $params, $sign)
{//k1=v1&v2=v2&..&md5(secret_key)
	if($sign === self::vivo_encode($params))
		return true;
	return false;
}

//@小米签名
public	static	function xiaomi_encode($params)
{//k1=v1&v2=v2&..&md5(secret_key)
	$query_string = self::make_string($params);

	return hash_hmac('sha1', $query_string, SECRET_XIAOMI_SECRET,false);
}

//@小米支付通知签名
public	static	function xiaomi_decode($params, $sign)
{//k1=v1&v2=v2&..&md5(secret_key)
	if($sign === self::xiaomi_encode($params))
		return true;
	return false;
}

//@07073支付通知签名
public	static	function zero73_decode($params, $sign)
{//k1=v1&v2=v2&..&md5(secret_key)
	$query_string = self::make_string($params);

	if($sign === md5($query_string.SECRET_07073_SECRET))
		return true;
	return false;
}

public	static	function kupai_decode($content,&$respJson)
{
	$arr=array_map(create_function('$v', 'return explode("=", $v);'), explode('&', $content));

	foreach($arr as $value)
	{
		$resp[($value[0])] = urldecode($value[1]);
	}

	if(array_key_exists("transdata", $resp))
	{
		$respJson = json_decode($resp["transdata"],true);
	}
	else
	{
		return false;
	}

	if(array_key_exists("sign", $resp))
	{
		return \rsa::verify_md5($resp["transdata"], $resp["sign"], SECRET_KUPAI_PUBKEY);
	}
	
	return false;
}

public	static	function wandou_decode($content,$sign)
{
	return \rsa::verify($content, $sign, SECRET_WANDOU_PUBKEY);
}

public	static	function huawei_decode($method, $url, $params, $sign)
{
	$query_string = self::make_string($params);
	return \rsa::verify($query_string, $sign, SECRET_HUAWEI_PUBKEY);
}

public	static	function jinli_decode( $params, $sign)
{
	$query_string = self::make_string($params);
	return \rsa::verify($query_string, $sign, SECRET_JINLI_PUBKEY);
}

public	static	function jinli_encode($params)
{
	$query_string = self::make_string_with_value($params);
	return \rsa::sign($query_string, SECRET_JINLI_PRIKEY);
}

//@oppo签名
public	static	function oppo_encode($paramstr)
{//k1=v1&v2=v2&..&md5(secret_key)
	return base64_encode(hash_hmac('sha1', $paramstr, SECRET_OPPO_SECRET,true));
}

public	static	function lenovo_decode($content,$sign)
{
	return \rsa::sign($content, SECRET_LENOVO_PRIKEY) === $sign;
}

public	static	function anfeng_decode($method, $url, $params, $sign)
{
	$query_string = self::make_string($params).'&signKey='.SECRET_ANFENG_APPKEY;

	if(md5($query_string) === $sign)
		return true;
	return false;
}

public	static	function google_decode($token, $sign, $keyFile)
{
	return \rsa::verify($token, $sign, $keyFile);
}

}

?>
