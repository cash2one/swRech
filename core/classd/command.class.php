<?php
/**
* abstract class
* must be covering method handler and set const CMD_NUM with ARG_COUNT 
* @author zzd
*/

interface command_interface
{
	public	static	function handler($serv, $fd, $pid, $args);
}

abstract class command_class implements command_interface
{
	public	static	function setup()
	{
		\worker::register_command(array(static::CMD_NUM,array(get_called_class(), static::ARG_COUNT)));
		return 1;
	}
}
