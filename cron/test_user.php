<?php
require_once __DIR__.'/../conf/secret_key.php';
require_once __DIR__.'/../conf/sign.php';
if(!isset($argv[0]))
	exit('dddddddd'.PHP_EOL);

if(!isset($argv[1]))
	exit('undifine ftion'.PHP_EOL);

$bgid = 8888;
$uid = '13466605583';
$pwd = md5(1234567);

$openid = "100000000076";
$token	= "2238bab9775d330171334d7d6d084f15";
$npwd	= md5('12345678');


call_user_func_array($argv[1], array($uid, $pwd, $bgid));

function login($uid, $pwd, $bgid)
{
	$data = array("uid" =>$uid,'pwd'=>$pwd,'appid'=>$bgid, 'ts'=>time());
	$data['sig']= \sign::asc_sign('POST','/tq/login.xl',$data);
	request('/tq/login.xl', $data);
}

function request($url,$data)
{
$cli = new swoole_http_client('127.0.0.1', 80);
$cli->on('error', function($a){var_dump($a);});

$cli->setHeaders(['User-Agent' => "swoole"]);
$cli->post('/tq/login.xl', $data, function (swoole_http_client $cli)
{
	$data = json_decode($cli->body,true);

	if(!is_array($data))
	{
		echo '反馈异常'.$cli->body.PHP_EOL;
		exit();
	}
	if($data['ret'] !== 0)
	{
		echo '登录失败:'.$cli->body.PHP_EOL;
		exit();
	}

	echo '登录成功:'.$cli->body.PHP_EOL;
});

}

//	$oid = $data['oid'];
//	$fee = 100;
//	$data = array('orderno'=>$oid,'fee'=>$fee,);
//	$data['token'] = md5($oid.$fee);
//
//	$clis = new swoole_http_client('127.0.0.1', 80);
//	$clis->post('/tq/lft/delivery.xl', $data,function (swoole_http_client $clis)
//	{
//		echo $clis->body.PHP_EOL;
//		exit();
//   	});
//});
?>
