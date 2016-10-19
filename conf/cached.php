<?php
/*
* config for share memory
* only for config. don`t write init codes;
* later should change it and use .cnf file
* struct:
* array(
*		'unique variable'=>array(
*							'value count(N) in fact value is power(2,N)',
*							array(
*									'key1'=>array('value type','length'),
*									'key2'=>array('value type','length'),
*									),
*							),
*		);
* value type:str int float , float don`t need length, int is length must be in (1,2,4,8)
* create 20160616
* auther zzd
*
* can use these variables after init
* support function:
* func - \cached::init(unique variable,array(array('key',array('skey1'=>'svalue1')))) init this variable
* func - \cached::get(unique variable,array('key'))	get data and return array('skey1'=>'svalue1');
* func - \cached::exists(unique variable,array('key')) check key if not exists, return true or false
* func - \cached::klist(unique variable)   return all keys with array
* create a file to manage every variable
*/

DEFINE('CASHED_SHARE_IOS_PRICE'		,	'ios_price');
DEFINE('CASHED_SHARE_CHANNEL_LIST'	,	'channel');
DEFINE('CASHED_SHARE_PLATFORM_LIST'	,	'platform');
DEFINE('CASHED_SHARE_SERVER_LIST'	,	'serverlist');

/*
* get share memory variable config
* int str float
*/
function get_reset_table()
{
	return array
	(
		//@渠道=》平台ID
		CASHED_SHARE_CHANNEL_LIST	=>array(7,array('ptid'=>array('str',16))),
		//@serverlist
		CASHED_SHARE_SERVER_LIST	=>array(11,array('ip'=>array('str',32),'url'=>array('str',64),'rechUri'=>array('str',24),'port'=>array('int',2))),
		//@appid
		CASHED_SHARE_PLATFORM_LIST	=>array(7,array('appkey'=>array('str',64),'url'=>array('str',256),'rechUri'=>array('str',64),'port'=>array('int',2))),
		//@苹果支付货物对应RMB
		CASHED_SHARE_IOS_PRICE		=>array(4,array('price'=>array('int',4))),
	);
}

//@ unless you don`t have any task process to use this
//@ get init contents for worker 0 to init variables
function get_worker_init_data()
{
	$data = array
	(
	);

	if(empty($data))
		return $data;

	foreach($data as $d)
	{
		if(!is_callable($d))
			return false;
	}

	return $data;
}

/*
* get init contents for task 0 to init variables
* $data = array(
*	callback_init1,
*	callback_initn,
* )
*/
//@get init contents for task 0 to init variables
function get_task_init_data()
{
	$data = array
	(
		//'\game_server::set_list'	,
		'\platform::set_list'	,
		//'\channel::set_list'		,
		'\ios_price::init'		,
	);

	foreach($data as $d)
	{
		if(!is_callable($d))
		{
			return false;
		}
	}

	return $data;
}

