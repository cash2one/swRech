<?php
namespace task\player
{
/*
* map move
* auther zzd
*/
class delivery extends \task_class
{
//@communication protocol with worker must set it and can`t repeat with other task
public	static	$protocol	= WT_PROTOCOL_MAP_MOVE;

/*
* must be covering parent simple method
*/
public	static	function handler($serv, $task_id, $from_id, $data)
{
	call_client_func($serv, $data[0], array(9999,"move_over") );

	return 1;
}

}
}
?>
