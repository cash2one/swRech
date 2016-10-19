<?php
class game_server 
{
const	select = 'SELECT substring(platformId,3) as ptid,worldId as sid,gmServerIp as ip,gmServerPort as port FROM game_worlds';

public	static	function check_sid($gid,$sid)
{
	if(!$ptid = \channel::check($gid))
			return false;
	return \cached::exists(CASHED_SHARE_SERVER_LIST,array($sid));
}

public	static	function server_data($gid,$sid)
{
	if(!$ptid = \channel::check($gid))
		return false;

	return \cached::get(CASHED_SHARE_SERVER_LIST,array($sid));
}

public	static	function set_list()
{
	if(!$server_list = \sync_mysql::queryi_from_gmt(self::select))
		return 1;

	$list	= array();
	foreach($server_list as $server)
	{
		$ptid	= $server['ptid'];
		$sid	= $server['sid'];
		unset($server['ptid'],$server['sid']);
		$server['url'] = sprintf('http://%s/delivery.html', $server['ip'] );
		$server['rechUri'] = '/delivery.html';

		$list[$sid] = $server;
	}

	if(!\cached::init(CASHED_SHARE_SERVER_LIST,$list))
		return 0;

	write_log('init_server_list', $list);
	return 1;
}

}
?>
