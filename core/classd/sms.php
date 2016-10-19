<?php
class sms
{
//@短信发送类
private static	$smsOb;

public	static	function setup($smsOb)
{
	self::$smsOb = $smsOb;
}


public	static	function send($mobile, $data)
{
	return self::$smsOb->send($mobile, $data);
}

}
