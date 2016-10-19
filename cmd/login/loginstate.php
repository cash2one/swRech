<?php
namespace cmd\login;
/*
* 登录校验模板处理接口
* auther zzd
*/
class loginstate extends \request_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM	= '/innerloginstate.xl';
const ARG_COUNT	= 0;
const INIT		= SERVER_FOR_LOGIN;

/*
* simple function to handle HTTP protocol
*/
public	static	function handler($request,$response)
{
		$response->end(FIRST_PORT);
}

/*
* 自定义任务响应方法
*/
public	static	function resp_task($serv,$arg, $pass)
{
	echo "响应了 她上课";
}

}
