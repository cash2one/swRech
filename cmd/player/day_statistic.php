<?php
namespace cmd\player
{

/*
* GM手动发起充值 
* auther zzd
*/
class day_statistic extends \command_class
{
/*
* cmd and cmd arg`s count must modity
*/
const CMD_NUM = 6666; 
const ARG_COUNT= 1;

private	static	$time_cd = 0;
private	static	$start_moment;
private	static	$final_moment;

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
		$gid = $args[0];
	}
	else
	{
		$gid = null;
	}

	$tday	= date('j');
	$tmonth	= date('n');
	$tyear	= date('Y');

	$start_moment = mktime(0,0,0,$tmonth,$tday-1,$tyear);
	$final_moment = mktime(0,0,0) - 1;

	self::$start_moment	= $start_moment;
	self::$final_moment	= $final_moment;

	$table	= 'platform_login';
	$cols	= 'gid,count(distinct uid) as activeUid,count(distinct imei) as activeImei, count(gid) as loginCount';
	$conds	= sprintf(' time between %d and %d ', $start_moment,$final_moment);
	if(!is_null($gid))
		$conds	.= ' and `gid` = \''.$gid.'\'';
	$groups = '`gid`';

	\service\mysql::selectLogDB($table,$cols,array(array(__CLASS__, 'resp_day_login'),$gid), $conds, $groups);

	write_log('day_statistic', 'step:1 | '.$gid);

	$serv->send($fd,'ok');
}

public	static	function resp_day_login($res,$gid)
{
	if(is_bool($res))
	{
		write_log('day_statistic', 'step:-2 | '.$gid);
		return;
	}

	if(!is_null($gid))
	{
		$list = array($gid);
	}
	else
	{
		$list = \channel::clist();
	}

	$k	= 0;
	$grid	= array();
	$start_moment = self::$start_moment;

	//@TIME 必须放在最后
	$temp	= array('gid'=>0,'activeUid'=>0,'activeImei'=>0,'loginCount'=>0,'time'=>$start_moment);

	foreach($list as $g)
	{
		foreach($res as $value)
		{
			if($value['gid'] == $g)
			{
				$k=1;
				break;
			}
		}

		if($k == 1)
		{
			$k = 0;
			$value['time'] = $start_moment;
			$grid[] = $value; 
		}
		else
		{
			$temp['gid'] = $g;
			$grid[] = $temp;
		}
	}

	if(!empty($grid))
		\service\mysql::insertLogDB('statistic_by_day', array_keys($temp),$grid,array(array(__CLASS__, 'resp_insert_day_login'),$gid));
	write_log('day_statistic', 'step:2 | '.$gid);
}

public	static	function resp_insert_day_login($res,$gid)
{
	if(!$res)
	{
		write_log('day_statistic', 'step:-3 | '.$gid);
		return;
	}

	if(!is_null($gid))
	{
		$list = array($gid);
	}
	else
	{
		$list = \channel::clist();
	}

	self::save_platform_recharge($gid);
	self::save_new_uid($gid);
	self::save_new_imei($gid);
	self::save_new_role($gid);
}

//@首次登陆
public	static	function save_new_uid($gid)
{
	$start_moment = self::$start_moment;
	$final_moment = self::$final_moment;

	$table	= 'list_account';
	$cols	= 'gid,count(distinct uid) as newUid';
	$conds	= sprintf(' time between %d and %d ', $start_moment,$final_moment);

	if(!is_null($gid))
		$conds	.= ' and `gid` = \''.$gid.'\'';

	$groups = '`gid`';

	\service\mysql::selectLogDB($table,$cols,array(array(__CLASS__, 'resp_day_acc_list'),$gid), $conds, $groups);

	write_log('day_statistic', 'step:31 | '.$gid);
}
//@响应首次登陆并 更新
public	static	function resp_day_acc_list($res, $gid)
{
	if(!is_array($res))
	{
		write_log('day_statistic', 'step:-41 | '.$gid);
		return;
	}

	write_log('day_statistic', 'step:41 | '.$gid.'|'.empty($res));

	if(empty($res))
	{
		return;
	}

	if(!is_null($gid))
	{
		$list = array($gid);
	}
	else
	{
		$list = \channel::clist();
	}

	$k	= 0;

	$start_moment = self::$start_moment;

	//$temp = array('gid'=>0,'frechUid'=>0,'fcashs'=>0);
	$cols = array();
	$conds = ' `time`='.$start_moment;

	foreach($list as $g)
	{
		foreach($res as $value)
		{
			if($value['gid'] == $g)
			{
				$k=1;
				break;
			}
		}

		if($k == 1)
		{
			$k = 0;
			foreach($value as $k=>$v)
			{
				if($k == 'gid')
					continue;
				if(!isset($cols[$k]))
					$cols[$k] = array();
				if(!isset($cols[$k]['gid']))
					$cols[$k]['gid'] = array();

				$cols[$k]['gid'][$g] = $v; 
			}
		}
	}

	if(!empty($cols))
		\service\mysql::updateLogDB('statistic_by_day', $cols, $conds,array(array(__CLASS__, 'resp_simple'),$gid));
}
//@创建角色
public	static	function save_new_role($gid)
{
	$start_moment = self::$start_moment;
	$final_moment = self::$final_moment;

	$table	= 'list_role';
	$cols	= 'gid,count(distinct uid) as uidCreateRole,count(distinct pid) as newPid';
	$conds	= sprintf(' time between %d and %d ', $start_moment,$final_moment);
	if(!is_null($gid))
		$conds	.= ' and `gid` = \''.$gid.'\'';
	$groups = '`gid`';

	\service\mysql::selectLogDB($table,$cols,array(array(__CLASS__, 'resp_day_role_list'),$gid), $conds, $groups);

	write_log('day_statistic', 'step:save_new_role | '.$gid);
}
//@响应 创建角色和更新
public	static	function resp_day_role_list($res, $gid)
{
	if(!is_array($res))
	{
		write_log('day_statistic', 'step:-43 | '.$gid);
		return;
	}

	write_log('day_statistic', 'step:43 | '.$gid.'|'.empty($res));

	if(empty($res))
	{
		return;
	}

	if(!is_null($gid))
	{
		$list = array($gid);
	}
	else
	{
		$list = \channel::clist();
	}

	$k	= 0;

	$start_moment = self::$start_moment;

	//$temp = array('gid'=>0,'frechUid'=>0,'fcashs'=>0);
	$cols = array();
	$conds = ' `time`='.$start_moment;

	foreach($list as $g)
	{
		foreach($res as $value)
		{
			if($value['gid'] == $g)
			{
				$k=1;
				break;
			}
		}

		if($k == 1)
		{
			$k = 0;
			foreach($value as $k=>$v)
			{
				if($k == 'gid')
					continue;
				if(!isset($cols[$k]))
					$cols[$k] = array();
				if(!isset($cols[$k]['gid']))
					$cols[$k]['gid'] = array();

				$cols[$k]['gid'][$g] = $v; 
			}
		}
	}
	if(!empty($cols))
		\service\mysql::updateLogDB('statistic_by_day', $cols, $conds,array(array(__CLASS__, 'resp_simple'),$gid));
}

//@首次登陆IMEI
public	static	function save_new_imei($gid)
{
	$start_moment = self::$start_moment;
	$final_moment = self::$final_moment;

	$table	= 'list_imei';
	$cols	= 'gid,count(distinct imei) as newImei';
	$conds	= sprintf(' time between %d and %d ', $start_moment,$final_moment);
	if(!is_null($gid))
		$conds	.= ' and `gid` = \''.$gid.'\'';
	$groups = '`gid`';

	\service\mysql::selectLogDB($table,$cols,array(array(__CLASS__, 'resp_day_imei_list'),$gid), $conds, $groups);

	write_log('day_statistic', 'step:31 | '.$gid);
}
//@响应首次登陆并 更新
public	static	function resp_day_imei_list($res, $gid)
{
	if(!is_array($res))
	{
		write_log('day_statistic', 'step:-43 | '.$gid);
		return;
	}

	write_log('day_statistic', 'step:43 | '.$gid.'|'.empty($res));

	if(empty($res))
	{
		return;
	}

	if(!is_null($gid))
	{
		$list = array($gid);
	}
	else
	{
		$list = \channel::clist();
	}

	$k	= 0;

	$start_moment = self::$start_moment;

	//$temp = array('gid'=>0,'frechUid'=>0,'fcashs'=>0);
	$cols = array();
	$conds = ' `time`='.$start_moment;

	foreach($list as $g)
	{
		foreach($res as $value)
		{
			if($value['gid'] == $g)
			{
				$k=1;
				break;
			}
		}

		if($k == 1)
		{
			$k = 0;
			foreach($value as $k=>$v)
			{
				if($k == 'gid')
					continue;
				if(!isset($cols[$k]))
					$cols[$k] = array();
				if(!isset($cols[$k]['gid']))
					$cols[$k]['gid'] = array();

				$cols[$k]['gid'][$g] = $v; 
			}
		}
	}
	if(!empty($cols))
		\service\mysql::updateLogDB('statistic_by_day', $cols, $conds,array(array(__CLASS__, 'resp_simple'),$gid));
}


//@充值记录
public	static	function save_platform_recharge($gid)
{
	$start_moment = self::$start_moment * 100000000;
	$final_moment = (self::$final_moment+1)*100000000 - 1;

	$table	= 'platform_recharge';
	$cols	= 'gid,count(distinct uid) as rechUid,count(distinct pid) as rechPid,sum(cash) as cashs,count(distinct ip) as rechIp, count(oid) as rechOid';
	$conds	= sprintf(' oid between %d and %d ', $start_moment,$final_moment);

	if(!is_null($gid))
		$conds	.= ' and `gid` = \''.$gid.'\'';

	$groups = '`gid`';

	\service\mysql::selectLogDB($table,$cols,array(array(__CLASS__, 'resp_day_recharge'),$gid), $conds, $groups);

	write_log('day_statistic', 'step:32 | '.$gid);
}
//@响应充值记录查询并更新
public	static	function resp_day_recharge($res,$gid)
{
	if(!is_array($res))
	{
		write_log('day_statistic', 'step:-42 | '.$gid.' | '. $res);
		return;
	}

	write_log('day_statistic', 'step:42 | '.$gid.' | '.empty($res));

	if(empty($res))
	{
		return;
	}

	if(!is_null($gid))
	{
		$list = array($gid);
	}
	else
	{
		$list = \channel::clist();
	}

	$k	= 0;
	$start_moment = self::$start_moment;

	//$temp = array('gid'=>0,'frechUid'=>0,'fcashs'=>0);
	$cols = array();
	$conds = ' `time`='.$start_moment;

	foreach($list as $g)
	{
		foreach($res as $value)
		{
			if($value['gid'] == $g)
			{
				$k=1;
				break;
			}
		}

		if($k == 1)
		{
			$k = 0;
			foreach($value as $k=>$v)
			{
				if($k == 'gid')
					continue;
				if(!isset($cols[$k]))
					$cols[$k] = array();
				if(!isset($cols[$k]['gid']))
					$cols[$k]['gid'] = array();

				$cols[$k]['gid'][$g] = $v; 
			}
		}
	}
	if(!empty($cols))
		\service\mysql::updateLogDB('statistic_by_day', $cols, $conds, array(array(__CLASS__, 'resp_update_day_recharge'),$gid));
}


public	static	function resp_update_day_recharge($res,$gid)
{
	if(!$res)
	{
		write_log('day_statistic', 'step:-5 | '.$gid);
	}

	$start_moment = self::$start_moment * 100000000;
	$final_moment = (self::$final_moment+1)*100000000 - 1;

	$table	= 'first_recharge';
	$cols	= 'gid,count(distinct uid) as fRechUid,sum(cash) as fCashs';
	$conds	= sprintf(' oid between %d and %d ', $start_moment,$final_moment);

	if(!is_null($gid))
		$conds	.= ' and `gid` = \''.$gid.'\'';

	$groups = '`gid`';

	\service\mysql::selectLogDB($table,$cols,array(array(__CLASS__, 'resp_day_first_recharge'),$gid), $conds, $groups);

	write_log('day_statistic', 'step:5 | '.$gid);
}

public	static	function resp_day_first_recharge($res, $gid)
{
	if(!is_array($res))
	{
		write_log('day_statistic', 'step:-6 | '.$gid);
		return;
	}

	write_log('day_statistic', 'step:6 | '.$gid.' | '.empty($res));

	if(empty($res))
	{
		return;
	}

	if(!is_null($gid))
	{
		$list = array($gid);
	}
	else
	{
		$list = \channel::clist();
	}

	$k	= 0;
	$start_moment = self::$start_moment;

	//$temp = array('gid'=>0,'frechUid'=>0,'fcashs'=>0);
	$cols = array();
	$conds = ' `time`='.$start_moment;

	foreach($list as $g)
	{
		foreach($res as $value)
		{
			if($value['gid'] == $g)
			{
				$k=1;
				break;
			}
		}

		if($k == 1)
		{
			$k = 0;
			foreach($value as $k=>$v)
			{
				if('gid' == $k)
					continue;
				if(!isset($cols[$k]))
					$cols[$k] = array();
				if(!isset($cols[$k]['gid']))
					$cols[$k]['gid'] = array();

				$cols[$k]['gid'][$g] = $v; 
			}
		}
	}
	if(!empty($cols))
		\service\mysql::updateLogDB('statistic_by_day', $cols, $conds, array(array(__CLASS__, 'resp_simple'),$gid));
}

public	static	function resp_simple($res,$gid)
{
	write_log('day_statistic',$res.$gid);
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
