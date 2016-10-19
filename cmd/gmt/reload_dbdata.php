<?php
namespace cmd\gmt
{

/*
* for GM to update server share memory variable
* auther zzd
*/
class reload_dbdata extends \command_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM = 7000;
const ARG_COUNT= 3;

/*
* simple function for deal tcp protocol
* params args array(timestamp int,key int,special arg)
*/
public	static	function handler($serv, $fd, $pid, $args)
{
	if( (time()-$args[0]) > 10 )
	{
		//call_client_func_fd( $fd, array(CS_PROTOCOL_MANAGER_HANDRECH, array(1)) );
		$serv->close();
		return;
	}

	$key = $args[1];

	if($key <1 || $key > 6)
	{
		$serv->close();
		return;
	}

	switch($key)
	{
		case 1:
		case 2:
		case 4:
			if($pid = @file_get_contents(PROJECT_PID_FILE))
			{
				$res = posix_kill($pid, SIGUSR2);
			}
			else
			{
				$res = false;
			}

			break;
		case 3:
			//$serv->close();
			break;
	}

	call_client_func_fd( $fd, array(self::CMD_NUM, array($key, $res)) );

}

/*
* yourself defined callback 
*/
//public	static	function resp_task($serv,$arg, $pass)
//{
//	return;
//}

}
}
?>
