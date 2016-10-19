<?php
require_once __DIR__.'/../conf/secret_key.php';
require_once __DIR__.'/../core/classd/sign.php';
if(!isset($argv[0]))
	exit('dddddddd');

$cli = new swoole_http_client('127.0.0.1', 80);
$cli->on('error', function($a){var_dump($a);});

$cli->setHeaders(['User-Agent' => "swoole"]);
$data = array("gid" =>201,'sid'=>rand(1,10),'uid'=>rand(111,999),'imei'=>rand(1111111111111999,9999999999999999), 'time'=>time());
$data['sig']= \sign::asc_sign('POST','/acclogin.xl',$data);

$cli->post('/acclogin.xl', $data, function (swoole_http_client $cli)
{

	if($cli->body !== '{"ret":0}')
	{
		echo '反馈异常'.$cli->body.PHP_EOL;
		exit();
	}
	echo $cli->body.PHP_EOL;
	exit();
});
?>
