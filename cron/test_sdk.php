<?php
require_once __DIR__.'/../conf/secret_key.php';
require_once __DIR__.'/../core/classd/sign.php';
if(!isset($argv[0]))
	exit('dddddddd');

$cli = new swoole_http_client('127.0.0.1', 80);
$cli->on('error', function($a){var_dump($a);});

$cli->setHeaders(['User-Agent' => "swoole"]);
$data = array("gid" =>201,'imei'=>rand(1111111111111999,9999999999999999), 'mac'=>md5(rand(1000,9999)),'ver'=>'andriod_'.rand(4,8).'.0','wifi'=>'wifi'.rand(2,9),'time'=>time(),'status'=>rand(1,3));
$data['sig']= \sign::asc_sign('POST','/sdk.xl',$data);

$cli->post('/sdk.xl', $data, function (swoole_http_client $cli)
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
