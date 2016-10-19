<?php
/*
* all app data
*
*/

class platform
{
const	select = 'SELECT appid,appkey,url,port,rechUri FROM applications';

public	static	function data($gid)
{
	if( !$data=\cached::get(CASHED_SHARE_PLATFORM_LIST,array($gid)) )
		return false;
	
	return $data;
}

public	static	function clist()
{
	if(!$data = \cached::klist(CASHED_SHARE_PLATFORM_LIST))
		return false;
	
	return $data;
}

//@按条依次初始化数据
public	static	function set_list()
{
	if(!$p_list = \sync_mysql::queryi_from_gmt(self::select))
		return 0;

	$list	= array();
	foreach($p_list as $server)
	{
		$appid	= $server['appid'];
		unset($server['appid']);

		$list[$appid] = $server;
	}

	if(!\cached::init(CASHED_SHARE_PLATFORM_LIST,$list))
		return 0;

	write_log('init_platform_list', $list);

	return 1;
}

}
?>
