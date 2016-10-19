<?php
class clSms
{
private $url = 'http://222.73.117.158/msg/HttpBatchSendSM?';
private	$postData = array
(
	'account'	=> 'TQYX88',
	'pswd'		=> 'Txb123456',
	'mobile'	=> '',
	'msg'		=> '',
	'needstatus'=> 'true',
);

function send($mobile, $msg)
{
	$this->postData['mobile']	= $mobile;
	$this->postData['msg']		= $msg;

	$post_data = http_build_query($this->postData);

	$result = \curl::post($this->url, $post_data);

	if (!$result)
		return false;
	
	if (substr($result,15,1) === '0')
		return true;
	
	var_dump($result);
	write_log('sms', $result);
	return false;
}

}
