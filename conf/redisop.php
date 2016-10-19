<?php
/*
* redis 配置和操作文件
* create 20160513
* auther zzd
*/

DEFINE('DB_REDIS_SECRET',		'ngrxzgy');
DEFINE('DB_REDIS_START'			, 0); //redis 初始库序号
DEFINE('DB_REDIS_PORT'			, '6379,6380,6381,6382');
DEFINE('DB_REDIS_INSTANCES'		, 4); //redis 实例数 必须和上面的端口数对应
DEFINE("DB_REDIS_ORDER_SIZE"		, 4); //最大订单库数量 一般与实例数相同，且从第一个实例的DB_REDIS_START库开始
DEFINE("ALL_REDIS_DB_SIZE"		, 5); //最大redis数量
DEFINE('REDIS_SCRIPT_LOAD'		, 1);

function getRedisDbData()
{
//@前4个为订单专用 使用专有接口, 通用接口不得调用
$DEFINE_data =  array
(

/*
*0,4,8,12,16,20,24 第一档redis
*/
0=>DEFINE('DB_FOR_NEW_ORDER_NUM'	, 0),		//@新订单号获取
4=>DEFINE('DB_FOR_IOS_RECEIPT'		, 4),		//@ 用于存储IOS的receipt
/*
*1,5,9,13,17,21,25 第二档redis
*/
1=>DEFINE('DB_FOR_NOT_PAID_ORDER'	, 1),		//@存储未支付的订单信息

/*
*2,6,10,14,18,22,26 第三档redis
*/
2=>DEFINE('DB_FOR_PAID_ORDER'		, 2),		//存储已支付准备发货的订单

/*
*3,7,11,15,19,23,27 第四档redis
*/
3=>DEFINE('DB_FOR_DELIVERY_END'		, 3),		//@存储已经发货的定义 ，之后会回写mysql
);

if( count($DEFINE_data) != ALL_REDIS_DB_SIZE )
{
	return false;
}

return $DEFINE_data;
}

/*
* 与redis 进行通信;
* params $constdb = 上面定义的数据库宏, 宏定义DB_REDIS_ORDER_SIZE 内的库不得使用本函数;
* params $oprate = redis的操作函数 set,get etc;
* params $key	操作的第一层索引，
* params $field 操作的第二层索引 存储类型 array('key'=>array('field1'=>value1))
*/
function redis_deal($constdb, $oprate, $key=null, $field=null, $value= null)
{
	if($constdb < 0 || $constdb>= ALL_REDIS_DB_SIZE)
		return false;
	return dbredis::deal_game_db($constdb,$oprate,$key,$field, $value);
}

function checkUserReg($account)
{
	return redis_deal(crc32($account)%DB_REDIS_ORDER_SIZE, 'EVALSHA', REDIS_CHECK_PLATFORM_USER_LUA , array($account, 'PLATFORM_USER_LIST'),1);
}

function setUserLogin( $openid, $token)
{
	$account = 'LOGIN_'.$openid;
	return redis_deal(crc32($account)%DB_REDIS_ORDER_SIZE, 'SETEX', $account, 7200, $token);
}

function checkUserLogin($account)
{
	$account = 'LOGIN_'.$account;
	return redis_deal(crc32($account)%DB_REDIS_ORDER_SIZE, 'GET', $account);
}

function setNewPasswd($account,$data)
{
	return redis_deal(crc32($account)%DB_REDIS_ORDER_SIZE, 'HSET', 'PLATFORM_USER_LIST',$account, $data);
}

function setNewUser($account)
{
	return redis_deal(crc32($account)%DB_REDIS_ORDER_SIZE, 'EVALSHA', REDIS_SET_PLATFORM_USER_LUA , array($account,'PLATFORM_USER_LIST'),1);
}

function setNewUserSuc($account,$data)
{
	return redis_deal(crc32($account)%DB_REDIS_ORDER_SIZE, 'EVALSHA', REDIS_SET_PLATFORM_USER_SUC_LUA , array($account,'PLATFORM_USER_LIST',$data),1);
}

function setNewUserFail($account)
{
	return redis_deal(crc32($account)%DB_REDIS_ORDER_SIZE, 'HDEL', 'PLATFORM_USER_LIST', $account);
}


function setCodeTimeout( $account, $ip, $code)
{
	return redis_deal(crc32($account)%DB_REDIS_ORDER_SIZE, 'EVALSHA', REDIS_SET_PLATFROM_CODE_LUA , array($account,$ip, $code),2);
}

function checkPlatformCD($key, $cd=2)
{
	return redis_deal(crc32($key)%DB_REDIS_ORDER_SIZE, 'EVALSHA', REDIS_CHECK_PLATFORM_CD_LUA, array($key,$cd),1);
}

function getRegCode($account, $ip, $code)
{
	return redis_deal(crc32($account)%DB_REDIS_ORDER_SIZE, 'EVALSHA', REDIS_CHECK_PLATFORM_CODE_LUA, array($account, $ip, $code), 2);
}

function setUserModify($account,$phone)
{
	$account = 'MODIFY_USER_'.$account;
	return redis_deal(crc32($account)%DB_REDIS_ORDER_SIZE, 'SETEX', $account, 300, $phone);
}

function checkUserModify($account)
{
	$account = 'MODIFY_USER_'.$account;
	return redis_deal(crc32($account)%DB_REDIS_ORDER_SIZE, 'GET', $account);
}

function delUserModify($account)
{
	$account = 'MODIFY_USER_'.$account;
	return redis_deal(crc32($account)%DB_REDIS_ORDER_SIZE, 'DEL', $account);
}

function check_simple_order_format($order)
{
	if(strlen($order) != 19 || !is_numeric($order) || (substr($order,0,10)+172800) < time())
		return false;
	return true;
}

function check_simple_order_format_for_lose($order)
{
	if(strlen($order) != 18 || !is_numeric($order) || (substr($order,0,10)+86400*30) < time())
		return false;
	return true;
}

//@获取订单号
function get_simple_order($gid)
{
	if(!$new_order = redis_deal(DB_FOR_NEW_ORDER_NUM,'EVALSHA', REDIS_SIMPLE_ORDERID_LUA, array('SIMPLE_PLATFORM_ORDER'),1))
		return false;
	return sprintf("%d%04d%05d",time(),$gid,$new_order);
}

//@存储初始订单信息
function save_simple_order($new_order, $order_data)
{
//	var_dump($new_order."_g_".crc32($new_order)%DB_REDIS_ORDER_SIZE);
	return redis_deal($new_order%DB_REDIS_ORDER_SIZE, 'hmset',$new_order, $order_data);
}

//@设置收到发货 和金额
function check_deliveryed_status($new_order,$gid)
{
	return redis_deal($new_order%DB_REDIS_ORDER_SIZE,'EVALSHA', REDIS_CHECK_ORDER_STATUS_LUA,array($new_order,$gid),2);

}

function reset_order_to_queue($new_order)
{
	return redis_deal($new_order%DB_REDIS_ORDER_SIZE, 'EVALSHA',REDIS_RESET_ORDER_STATUS_LUA,array($new_order),0);
}

//@设置收到发货 和金额
function set_deliveryed_status($new_order,$args, $arg_count=1)
{//$arg_count 值 传入 0 怎么会返回渠道订单号，功能目前只适用于金立 
//	var_dump($new_order."_s_".crc32($new_order)%DB_REDIS_ORDER_SIZE);
	if($arg_count)
		return redis_deal($new_order%DB_REDIS_ORDER_SIZE,'EVALSHA', REDIS_CHANNEL_DELIVERY_LUA, $args, 1);
	else
		return redis_deal($new_order%DB_REDIS_ORDER_SIZE,'EVALSHA', REDIS_CHANNEL_DELIVERY_WITH_JINLI_LUA, $args, 1);

}

//@仅适用于发货不带渠道订单号的情况
function set_channel_orderid($new_order,$ooid)
{
	return redis_deal($new_order%DB_REDIS_ORDER_SIZE,'HSETNX', $new_order,'ooid', $ooid);
}

//@获取发货订单号
function get_simple_delivery_order_and_data($db)
{
//	var_dump('get:'.$db."_".$db%DB_REDIS_ORDER_SIZE);
	return redis_deal($db%DB_REDIS_ORDER_SIZE, 'EVALSHA', REDIS_GET_DELIVERY_DATA_LUA, array('SIMPLE_PLATFORM_DELIVERY', 'SIMPLE_PLATFORM_DELIVERY_BAK'),2);
}

//@发送游戏服OK
function set_delivery_to_server_ok($new_order)
{
	return redis_deal($new_order%DB_REDIS_ORDER_SIZE,'EVALSHA',REDIS_DELIVERY_TO_SERVER_OK_LUA, array($new_order,'SIMPLE_PLATFORM_DELIVERY_BAK'),2);
}

//@发送游戏服 fail 1
function set_delivery_to_server_fail($new_order)
{
	return redis_deal($new_order%DB_REDIS_ORDER_SIZE,'EVALSHA',REDIS_DELIVERY_TO_SERVER_FAIL_LUA, array($new_order,'SIMPLE_PLATFORM_DELIVERY_BAK','SIMPLE_PLATFORM_DELIVERY1'),3);
}

//@获取发货订单号
function get_simple_delivery_order_and_data_1($db)
{
	return redis_deal($db%DB_REDIS_ORDER_SIZE, 'EVALSHA', REDIS_GET_DELIVERY_DATA_LUA, array('SIMPLE_PLATFORM_DELIVERY1', 'SIMPLE_PLATFORM_DELIVERY_BAK1'), 2);
}

//@发送游戏服OK
function set_delivery_to_server_ok_1($new_order)
{
	return redis_deal($new_order%DB_REDIS_ORDER_SIZE,'EVALSHA',REDIS_DELIVERY_TO_SERVER_OK_LUA, array($new_order,'SIMPLE_PLATFORM_DELIVERY_BAK1'), 2);
}

//@发送游戏服 fail 2
function set_delivery_to_server_fail_1($new_order)
{
	return redis_deal($new_order%DB_REDIS_ORDER_SIZE,'EVALSHA',REDIS_DELIVERY_TO_SERVER_FAIL_LUA, array($new_order,'SIMPLE_PLATFORM_DELIVERY_BAK1', 'SIMPLE_PLATFORM_DELIVERY2'), 3);
}

//@获取发货订单号
function get_simple_delivery_order_and_data_2($db)
{
	return redis_deal($db%DB_REDIS_ORDER_SIZE, 'EVALSHA', REDIS_GET_DELIVERY_DATA_LUA, array('SIMPLE_PLATFORM_DELIVERY2', 'SIMPLE_PLATFORM_DELIVERY_BAK2'), 2);
}

//@发送游戏服OK
function set_delivery_to_server_ok_2($new_order)
{
	return redis_deal($new_order%DB_REDIS_ORDER_SIZE,'EVALSHA',REDIS_DELIVERY_TO_SERVER_OK_LUA, array($new_order,'SIMPLE_PLATFORM_DELIVERY_BAK2'), 2);
}

//@发送游戏服 fail 3 
function set_delivery_to_server_fail_2($new_order)
{
	return redis_deal($new_order%DB_REDIS_ORDER_SIZE,'EVALSHA',REDIS_DELIVERY_TO_SERVER_FAIL_LUA, array($new_order,'SIMPLE_PLATFORM_DELIVERY_BAK2', 'SIMPLE_PLATFORM_DELIVERY3'), 3);
}

//@获取发货订单号
function get_simple_delivery_order_and_data_3($db)
{
	return redis_deal($db%DB_REDIS_ORDER_SIZE, 'EVALSHA', REDIS_GET_DELIVERY_DATA_LUA, array('SIMPLE_PLATFORM_DELIVERY3', 'SIMPLE_PLATFORM_DELIVERY_BAK3'), 2);
}

//@发送游戏服OK
function set_delivery_to_server_ok_3($new_order)
{
	return redis_deal($new_order%DB_REDIS_ORDER_SIZE,'EVALSHA',REDIS_DELIVERY_TO_SERVER_OK_LUA, array($new_order,'SIMPLE_PLATFORM_DELIVERY_BAK3'), 2);
}


//@发送游戏服 fail 4 
function set_delivery_to_server_fail_3($new_order)
{
	return redis_deal($new_order%DB_REDIS_ORDER_SIZE,'EVALSHA',REDIS_DELIVERY_TO_SERVER_FAIL_LUA, array($new_order,'SIMPLE_PLATFORM_DELIVERY_BAK3','SIMPLE_PLATFORM_DELIVERY4'),3);
}

//@获取发货订单号
function get_simple_delivery_order_and_data_4($db)
{
	return redis_deal($db%DB_REDIS_ORDER_SIZE, 'EVALSHA', REDIS_GET_DELIVERY_DATA_LUA, array('SIMPLE_PLATFORM_DELIVERY4', 'SIMPLE_PLATFORM_DELIVERY_BAK4'), 2);
}

//@发送游戏服OK
function set_delivery_to_server_ok_4($new_order)
{
	return redis_deal($new_order%DB_REDIS_ORDER_SIZE,'EVALSHA',REDIS_DELIVERY_TO_SERVER_OK_LUA, array($new_order,'SIMPLE_PLATFORM_DELIVERY_BAK4'), 2);
}

//@发送游戏服 fail 4 
function set_delivery_to_server_fail_4($new_order)
{
	return redis_deal($new_order%DB_REDIS_ORDER_SIZE,'EVALSHA',REDIS_DELIVERY_TO_SERVER_FINAL_FAIL_LUA, array($new_order),1);
}














?>
