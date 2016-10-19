<?php
namespace task\player
{
/*
* exec worker`s broadcast task
* auther zzd
*/
class broadcast extends \task_class
{
//@must set it and can`t repeat with other task
public	static	$protocol = WT_PROTOCOL_C_BROADCAST;

/*
* must be covering parent simple method 
*/
public	static	function handler($serv, $task_id, $from_id, $data)
{
	DEBUG && var_dump('test_chat:'.$data[1]);
	//$protocol_data	= get_protocol_data(array(CS_PROTOCOL_C_BROADCAST,$data));
	call_clients_func($serv->connections, array(CS_PROTOCOL_C_BROADCAST,$data));
// 	foreach($serv->connections as $fd)
// 	{
// 	//	call_client_func_fd($fd,array(CS_PROTOCOL_C_BROADCAST,$data));
// 		$serv->send($fd, $protocol_data );
// 	}

	return 1;
}

}
}
?>
