<?php
/**
* 抽象类
* 必须实现handler方法
* @author zzd
*/

interface request_interface
{
	public	static	function handler($request, $response);
}

abstract class request_class implements request_interface
{
	public	static	function setup()
	{
		if(static::INIT)
			\worker::register_command(array(static::CMD_NUM,array(get_called_class(),static::ARG_COUNT)));
		return 1;
	}
}
