<?php
/**
* abstract class 
* must be covering method handler and set variable $protocol 
* @author zzd
*/

interface task_interface
{
	public	static	function handler($serv, $fd, $pid, $args);
}

abstract class task_class implements task_interface
{
	public	static	function setup()
	{
		\task::register_command(array(static::$protocol,get_called_class()));
		return 1;
	}
}
