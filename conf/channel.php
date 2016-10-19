<?php
DEFINE('CHANNEL_ID_IOS'			,	201);	//IOS正版
DEFINE('CHANNEL_ID_360'			,	202);	//360
DEFINE('CHANNEL_ID_GDIAN'		,	203);	//广点
DEFINE('CHANNEL_ID_TIANYOU'		,	213);	//天游
DEFINE('CHANNEL_ID_TAOSHOUYOU'	,	223);	//淘手游
DEFINE('CHANNEL_ID_TENCENT'		,	228);	//腾讯
DEFINE('CHANNEL_ID_UC'			,	230);	//UC
DEFINE('CHANNEL_ID_LESHI'		,	232);	//乐视
DEFINE('CHANNEL_ID_XIAOMI'		,	234);	//小米
DEFINE('CHANNEL_ID_BAIDU'		,	236);	//百度
DEFINE('CHANNEL_ID_OPPO'		,	238);	//oppo
DEFINE('CHANNEL_ID_VIVO'		,	240);	//步步高VIVO
DEFINE('CHANNEL_ID_HUAWEI'		,	242);	//华为
DEFINE('CHANNEL_ID_WANKE'		,	244);	//玩客
DEFINE('CHANNEL_ID_JINLI'		,	246);	//金立
DEFINE('CHANNEL_ID_KUPAI'		,	248);	//酷派
DEFINE('CHANNEL_ID_LENOVO'		,	250);	//联想
DEFINE('CHANNEL_ID_MEIZU'		,	260);	//魅族
DEFINE('CHANNEL_ID_ANZHI'		,	262);	//安智
DEFINE('CHANNEL_ID_WANDOU'		,	264);	//豌豆荚
DEFINE('CHANNEL_ID_HAIMA'		,	266);	//海马玩
DEFINE('CHANNEL_ID_DANGLE'		,	268);	//当乐
DEFINE('CHANNEL_ID_07073'		,	270);	//073
DEFINE('CHANNEL_ID_ANFENG'		,	272);	//安锋
DEFINE('CHANNEL_ID_LINYOU'		,	274);	//麟游
DEFINE('CHANNEL_ID_YUWAN'		,	276);	//鱼丸
DEFINE('CHANNEL_ID_XIANQU'		,	278);	//闲趣
DEFINE('CHANNEL_ID_4399'		,	280);	//4399
DEFINE('CHANNEL_ID_MOGELY'		,	282);	//摩格乐游
DEFINE('CHANNEL_ID_XIAOYAO'		,	283);	//逍遥
DEFINE('CHANNEL_ID_DIANYOU'		,	284);	//点游
DEFINE('CHANNEL_ID_SHEDIAOYJZX'	,	285);	//射雕御剑诛仙
DEFINE('CHANNEL_ID_TIANYOULJTX'	,	286);	//天游龙剑天下
DEFINE('CHANNEL_ID_XIANQUHCZJ'	,	287);	//闲趣幻城之剑
DEFINE('CHANNEL_ID_XIANQUCYJH'	,	288);	//闲趣赤影剑豪
DEFINE('CHANNEL_ID_TIANYOUXMCQ'	,	300);	//天游降魔传奇
DEFINE('CHANNEL_ID_TIANYOUXLJ3D',	301);	//天游寻龙剑3D

/*
* all channels data
*
*/

class channel
{
const	select = 'SELECT gid,ptid FROM channel';

public	static	function check($gid)
{
	if( !$data=\cached::get(CASHED_SHARE_CHANNEL_LIST,array($gid)) )
		return false;
	
	return $data['ptid'];
}

public	static	function clist()
{
	if(!$data = \cached::klist(CASHED_SHARE_CHANNEL_LIST))
		return false;
	
	return $data;
}

//@按条依次初始化数据
public	static	function set_list()
{
	if(!$c_list = \sync_mysql::queryi_from_gmt(self::select))
		return 1;

	$list = array();

	foreach($c_list as $c)
	{
		$list[$c['gid']] = array('ptid'=>$c['ptid']);
	}

	if( !\cached::init(CASHED_SHARE_CHANNEL_LIST, $list) )
		return 0;
	
	write_log('init_channel_list', $list);
	return 1;
}

}
?>
