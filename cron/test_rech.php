<?php
require_once __DIR__.'/../conf/secret_key.php';
require_once __DIR__.'/../conf/sign.php';
if(!isset($argv[0]))
	exit('dddddddd');

$cli = new swoole_http_client('127.0.0.1', 80);
$cli->on('error', function($a){var_dump($a);});
$bgid = 8888;
$cli->setHeaders(['User-Agent' => "swoole"]);
$data = array("appid" =>$bgid,'openid'=>101997,'token'=>'1231','exinfo'=>rand(111,999), 'ts'=>time());
$data['sig']= \sign::asc_sign('POST','/tq/apply.xl',$data);

$cli->post('/tq/apply.xl', $data, function (swoole_http_client $cli) use ($bgid)
{
	$data = json_decode($cli->body,true);

	if(!is_array($data))
	{
		echo '反馈异常'.$cli->body.PHP_EOL;
		exit();
	}
	echo $cli->body.PHP_EOL;

	$oid = $data['oid'];
	$fee = 100;
	$data = array('orderno'=>$oid,'fee'=>$fee,);
	$data['token'] = md5($oid.$fee);

	$clis = new swoole_http_client('127.0.0.1', 80);
	$clis->post('/tq/lft/delivery.xl', $data,function (swoole_http_client $clis)
	{
		echo $clis->body.PHP_EOL;
		exit();
   	});
});
?>
