<?php
/*
* auther zzd
* @abstract 3des
*/
 //定义3DES加密key
//define('CRYPT_KEYS', 'cM3871p76OTwBU7bf6Nyxxxx');


class des 
{
//private $key = CRYPT_KEYS;
//只有CBC模式下需要iv，其他模式下iv会被忽略 
private static	$iv = '12345678';
 
/**
* 加密
*/
public	static	function encrypt($value, $key)
{
	//先确定加密模式，此处以ECB为例
	$td = mcrypt_module_open ( MCRYPT_3DES,'', MCRYPT_MODE_ECB,'');
	//$iv = pack ( 'H16', self::$iv );
	$value = self::PaddingPKCS7 ( $value ); //填充
	//$key = pack ( 'H48', $key );
	mcrypt_generic_init ( $td, $key, self::$iv);
	$ret = base64_encode ( mcrypt_generic ( $td, $value ) );
	mcrypt_generic_deinit ( $td );
	mcrypt_module_close ( $td );
 
	return $ret;
}
 
/**
* 解密
*/
public	static	function decrypt($value, $key)
{
	$td = mcrypt_module_open ( MCRYPT_3DES, '', MCRYPT_MODE_ECB, '' );
	//$iv = pack ( 'H16', self::$iv );
	//$key = pack ( 'H48', $key );
	mcrypt_generic_init ( $td, $key, self::$iv );
	$ret = trim ( mdecrypt_generic ( $td, base64_decode ( $value ) ) );
	$ret = self::UnPaddingPKCS7 ( $ret );
	mcrypt_generic_deinit ( $td );
	mcrypt_module_close ( $td );
	return $ret;
}
 
private	static	function PaddingPKCS7($data)
{
	$padlen =  8 - strlen( $data ) % 8 ;
	for($i = 0; $i < $padlen; $i ++)
		$data .= chr( $padlen );
	return $data;
}
 
private	static	function UnPaddingPKCS7($data)
{
	$padlen = ord (substr($data, (strlen( $data )-1), 1 ) );
	if ($padlen > 8 )
		return $data;
 
	for($i = -1*($padlen-strlen($data)); $i < strlen ( $data ); $i ++)
	{
		if (ord ( substr ( $data, $i, 1 ) ) != $padlen)return false;
	}
 
	return substr ( $data, 0, -1*($padlen-strlen ( $data ) ) );
}

}


