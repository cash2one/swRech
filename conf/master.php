<?php
//@决定本服务器是否接受登录请求
DEFINE('SERVER_FOR_LOGIN'		,	0);
//@决定本服务器是否接受充值请求
DEFINE('SERVER_FOR_RECHARGE'	,	0);
//@决定本服务器是否接受上报请求`
DEFINE('SERVER_FOR_REPORT'		,	0);
//@决定本服务器是否接受平台操作
DEFINE('SERVER_FOR_PLATFORM'	,	1);
//@决定本服务器是TCP 还是 HTTP
DEFINE('MAIN_PROCESS_TCP'		,	0);

DEFINE('SERVER_MAX_ONLINE_LIMIT',	4096);
DEFINE('SERVER_PCONNECT'		,	1);
DEFINE('PROJECT_LOG_DIR'		,	PROJECT_ROOT.'log');

//@redis
//@mysql
DEFINE('WT_MYSQL_OP'			,	1);
DEFINE('WT_REDIS_OP'			,   2);
DEFINE('WT_BROADCAST'			,	3);
DEFINE('WT_FILE_READ'			,	4);
DEFINE('WT_FILE_WRITE'			,	5);
DEFINE('M_ASUNC_MYSQL_COUNT'	,	5);	//单个worker开启异步MySQL数量

//@用于处理请求
DEFINE('GATEWAY_WORKER_NUM'		,	8);
//@用于处理一些特殊功能
//@具体需单独定义
DEFINE('BUSSNISS_WORKER_NUM'	,	0);
//@总的worker数
DEFINE('WORKER_NUM_COUNT'		,	GATEWAY_WORKER_NUM+BUSSNISS_WORKER_NUM);

//@总得任务进程数
DEFINE('TASK_NUM_COUNT'			,	5);
//@竞技场进程
DEFINE('TASK_FOR_ARENA'			,	0);//相同互动操作指向同一个task 根据实际情况设置
//@移动进程
DEFINE('TASK_FOR_MOVE_MAP'		,	1);//相同互动操作指向同一个task 根据实际情况设置
//@战斗进程
DEFINE('TASK_FOR_FIGHT'			,	2);//相同互动操作指向同一个task 根据实际情况设置
//@群聊进程
DEFINE('TASK_FOR_BREADCAST'		,	3);//相同互动操作指向同一个task 根据实际情况设置
//@地图管理进程
DEFINE('MAP_TASK_START'			,	9);
DEFINE('MAP_TASK_END'			,	16);

DEFINE('DISPATCH_MODE'			,	3);
DEFINE('LOG_FILE_DIR'			,	'/data/log/'.PROJECT_SIGNLE_FLAG.'.log');
DEFINE('DAEMONIZE'				,	1);
DEFINE('FIRST_PORT'				,	9504);
DEFINE('SECOND_PORT'			,	9503);

require_once(PROJECT_ROOT.'conf/cached.php');



