<?php
namespace task\recharge
{
class ioscheck extends \task_class
{
//@communication protocol with worker must set it and can`t repeat with other task
public	static	$protocol	= WT_PROTOCOL_IOS_CHECK;

/*
* must be covering parent simple method
*/
public	static	function handler($serv, $task_id, $from_id, $data)
{//@data['post']

	if(SECRET_IOS_STATUS)
			$url = 'https://buy.itunes.apple.com/verifyReceipt';
	else
			$url = 'https://sandbox.itunes.apple.com/verifyReceipt';
	
	return \curl::post_ssl($url, $data, 8, array());
	
	return 1;
}

}
}
?>
