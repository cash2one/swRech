<?php
/**
* 抽象类
* 必须实现handler方法
* @author zzd
*/

interface service_interface
{
	static function setup();
	static function crash();
}

abstract class service_class implements service_interface
{
	static	 function register($service)
	{
		call_user_func_array(array(\master::get_main_class(),'register_service'), array($service));
	}
}
