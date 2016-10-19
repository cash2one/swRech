<?php
namespace task\recharge
{
/*
* hand recharge
*/
class delivery extends \task_class
{
//@communication protocol with worker must set it and can`t repeat with other task
public	static	$protocol	= WT_PROTOCOL_DELIVERY;

/*
* must be covering parent simple method
*/
public	static	function handler($serv, $task_id, $from_id, $data)
{
	switch($data)
	{
		case 1:
			$func = 'delivering';
			break;
		case 2:
			$func = 'delivery_sec';
			break;
		case 3:
			$func = 'delivery_third';
			break;
		case 4:
			$func = 'delivery_fourth';
			break;
		case 5:
			return 1;
		default:
			return 0;
	}

	swoole_timer_after(1000,array('\service\delivery_task',$func));

	return 1;
}

}
}
?>
