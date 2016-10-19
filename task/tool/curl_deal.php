<?php
namespace task\tool
{
/*
* resp worker`s async http
* auther zzd
*/
class curl_deal extends \task_class
{
//@communication protocol with worker must set it and can`t repeat with other task
public	static	$protocol	= WT_PROTOCOL_CURL_DEAL;

/*
* must be covering parent simple method
*/
public	static	function handler($serv, $task_id, $from_id, $data)
{//@data['post']

	if($data[0])
		$data = \curl::post_ssl('https://'.$data[1], $data[2], 5, $data[3] );
	else
		$data = \curl::post('http://'.$data[1], $data[2], 80, $data[3] );

	return $data;
}

}
}
?>
