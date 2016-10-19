<?php
namespace cmd\player
{

/*
* 玩家留存统计 
* 2016-07-26
* auther zzd
*/
class keep_alive extends \command_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM = 6667; 
const ARG_COUNT= 1;

private	static	$time_cd = 0;
private	static	$tday	= 0;
private	static	$tmonth = 0;
private	static	$tyear	= 0;

/*
* simple function for deal tcp protocol
*/
public	static	function handler($serv, $fd, $pid, $args)
{
	if(time() < self::$time_cd)
	{
		$serv->close();
		return;
	}

	self::$time_cd = time() + 60;

	if($args[0] !== 'all')
	{
		if(!\channel::check($args[0]))
		{
			$serv->close();
			return;
		}
		$gids = array($args[0]);
	}
	else
	{
		$gids = \channel::clist();
	}

	$moment			= mktime(0,0,0,date('n'),date('j'));
	self::$tday		= date('j',$moment);
	self::$tmonth	= date('n',$moment);
	self::$tyear	= date('Y',$moment);

	$start_moment = mktime(0,0,0,self::$tmonth,self::$tday-1,self::$tyear);
	$final_moment = mktime(0,0,0,self::$tmonth,self::$tday,self::$tyear) - 1;

	$table	= 'list_account';
	$cols	= 'count(distinct uid) as uids';
	$conds	= sprintf(' time between %d and %d ', $start_moment,$final_moment);

	foreach($gids as $gid)
	{
		\service\mysql::selectLogDB($table,$cols,array(array(__CLASS__, 'resp_day_first_login'),$gid), $conds.' and `gid` = \''.$gid.'\'');
	}

	write_log('keep_alive', 'start:'.($args[0]).'time:'.$start_moment);

	$serv->send($fd,'ok');
}

public	static	function resp_day_first_login($res,$gid)
{
	if(is_bool($res))
	{
		write_log('keep_alive', 'step:-2 | '.$gid);
		return;
	}

	$k	= 0;
	$grid	= array();
	$start_moment = mktime(0,0,0,self::$tmonth,self::$tday-1,self::$tyear);

	//@TIME 必须放在最后
	//$temp	= array('gid'=>$gid,'login1'=>$res[0]['uids'],'time'=>$start_moment);

	if(!empty($res))
		\service\mysql::insertLogDB('keep_by_day', array('gid','login1','time'),array(array($gid,$res[0]['uids'],$start_moment)),array(array(__CLASS__, 'resp_insert_day_login'),$gid));
	write_log('keep_alive', 'resp_day_first_login:'.$gid);
}

public	static	function resp_insert_day_login($res,$gid)
{
	if(!$res)
	{
		write_log('keep_alive', 'step:-3 | '.$gid);
		return;
	}

	$table	= 'platform_login';
	$cols	= 'distinct uid as uid';
	$conds	= sprintf(' time between %d and %d ', mktime(0,0,0,self::$tmonth,self::$tday-1,self::$tyear),mktime(0,0,0,self::$tmonth,self::$tday,self::$tyear)-1);
	$conds	.= ' and `gid` = \''.$gid.'\'';
	\service\mysql::selectLogDB($table,$cols,array(array(__CLASS__, 'resp_day_login'),$gid), $conds);
}

public	static	function resp_day_login($res, $gid)
{
	if(!$res)
	{
		write_log('keep_alive', 'resp_day_login:'.$gid);
		return;
	}

	$uids = array();
	foreach($res as $vdata)
	{
		$uids[$vdata['uid']] = 1;
	}

	$save_key = 2;

	$table	= 'list_account';
	$cols	= 'distinct uid as uid';
	$conds	= sprintf(' time between %d and %d ', mktime(0,0,0,self::$tmonth,self::$tday-$save_key,self::$tyear),mktime(0,0,0,self::$tmonth,self::$tday-$save_key+1,self::$tyear)-1);
	$conds	.= ' and `gid` = \''.$gid.'\'';
	\service\mysql::selectLogDB($table,$cols,array(array(__CLASS__, 'resp_day_keep2_login'),array($gid,$uids,$save_key)), $conds);

	$table	= 'platform_login';
	\service\mysql::selectLogDB($table,$cols,array(array(__CLASS__, 'resp_day2_login'),array($gid,$uids,$save_key+1)), $conds);
}

public	static	function resp_day2_login($res, $transfer)
{
	$gid		= $transfer[0];
	$save_key	= $transfer[2];

	if(!$res)
	{
		write_log('keep_alive', 'resp_day2_login-gid:'.$gid.'-key:'.$save_key);
		return;
	}

	$t_uids		= $transfer[1];

	$uids	= array();

	foreach($res as $udata)
	{
		if(isset($t_uids[$udata['uid']]))
		{
			$uids[$udata['uid']] = 1;
		}
	}

	unset($t_uids,$res);

	$table	= 'list_account';
	$cols	= 'distinct uid as uid';
	$conds	= sprintf(' time between %d and %d ', mktime(0,0,0,self::$tmonth,self::$tday-$save_key,self::$tyear),mktime(0,0,0,self::$tmonth,self::$tday-$save_key+1,self::$tyear)-1);
	$conds	.= ' and `gid` = \''.$gid.'\'';
	\service\mysql::selectLogDB($table,$cols,array(array(__CLASS__, 'resp_day_keep2_login'),array($gid,$uids, $save_key)), $conds);

	$save_key += 1;

	if($save_key>7)
	{
		write_log('keep_alive', 'end:'.$save_key);
		return;
	}

	$table	= 'platform_login';
	\service\mysql::selectLogDB($table,$cols,array(array(__CLASS__, 'resp_day2_login'),array($gid,$uids,$save_key)), $conds);

}

public	static	function resp_day_keep2_login($res, $transfer)
{
	$gid		= $transfer[0];
	$uids		= $transfer[1];
	$save_key	= $transfer[2];

	if($res)
	{
		$temp = 0;
		foreach($res as $vdata)
		{
			if(isset($uids[$vdata['uid']]))
			{
				$temp += 1;
			}
		}

		if($temp)
		{
			$sets	= array('login'.$save_key=>$temp);
			$conds	= sprintf('`time`=%d and gid=%d ', mktime(0,0,0,self::$tmonth,self::$tday-$save_key,self::$tyear),$gid);

			\service\mysql::simpleUpdateLogDB('keep_by_day', $sets, $conds,array(array(__CLASS__, 'resp_simple'),array($gid,$save_key)));
		}
	}
}

public	static	function resp_simple($res,$data)
{
	write_log('keep_alive',array('dbop',$res,$data));
}

/*
* 自定义任务响应方法
*/
//public	static	function resp_task($serv,$arg, $pass)
//{
//	return;
//}

}
}
?>
