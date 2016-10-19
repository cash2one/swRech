<?php
/*
* 数据库存储log定义
* 所有表均使用mysql分区功能 
* 按按时间戳《 分区每天一区，两年和一次区
* column 字段程序内会进行升序排列，所有未知的key进行存储的需 进行升序排列
* auther zzd
*/

DEFINE('LOG_SAVE_MIN_LIMIT_TIME', 3000);//@毫秒, 条数不足时定时自动存储

function getLogDefine()
{
	$logDefineData = array
	(
		'order_notice_fail' =>array
		(
			'cacheCount'	=> 10,
			'table'		=> 'order_notice_fail',
			'column'	=> array('time','cash', 'oid', 'ooid','dtime','origin'),
		),
		'first_recharge'=>array
		(
			'cacheCount'	=>100,
			'table'		=>'first_recharge',
			'column'	=>array('time','gid', 'sid', 'uid','pid','cash','oid'),
		),
		'platform_recharge'=>array
		(
			'cacheCount'	=>50,
			'table'		=>'platform_recharge',
			'column'	=>array ( 'time', 'cash', 'oid', 'ooid','dtime', 'origin'),
		),
		'recharge_log'=>array
		(
			'cacheCount'	=>50,
			'table'		=>'recharge_log',
			'column'	=>array ( 'time','gid', 'sid', 'uid', 'pid','cash', 'oid', 'ooid'),
		),
	
		'order_applys'=>array
		(
			'cacheCount'	=>50,
			'table'		=>'order_applys',
			'column'	=>array ( 'time','appid', 'openid', 'exinfo', 'ip', 'oid'),
		),
		'order_delivery'=>array
		(
			'cacheCount'	=>50,
			'table'		=>'order_delivery',
			'column'	=>array ( 'time', 'cash', 'oid', 'ooid', 'origin'),
		),
	
		'new_acc' =>array
		(
			'cacheCount'	=>50,
			'table'		=>'list_account',
			'column'	=>array('time','gid','sid','uid'),
		),
		'new_role' =>array
		(
			'cacheCount'	=>50,
			'table'		=>'list_role',
			'column'	=>array('time','gid','sid','uid','pid','name','ip','imei'),
		),
	
		'platform_login' =>array
		(
			'cacheCount'	=>50,
			'table'		=>'platform_login',
			'column'	=>array('time','gid','sid','uid','imei'),
		),
	
		'detail_sdk'	=>array
		(
			'cacheCount'	=>50,
			'table'		=>'detail_sdk',
			'column'	=>array('time','gid','imei','mac','ver','wifi','status'),
		),

	);

	return $logDefineData;
}



?>
