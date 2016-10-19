<?php

$url = array
(
	'/wandouloginstate.xl',
	'/anzhiloginstate.xl',
	'/baiduloginstate.xl',
	'/huaweiloginstate.xl',
	'/meizuloginstate.xl',
	'/qhloginstate.xl',
	'/tsyloginstate.xl',
	'/ucloginstate.xl',
	'/vivologinstate.xl',
	'/dangleloginstate.xl',
	'/kupailoginstate.xl',
	'/leshiloginstate.xl',
	'/jinliloginstate.xl',
	'/xiaomiloginstate.xl',
);

var_dump($argv[1]);
if(isset($argv[1]) && isset($url[$argv[1]]))
	$key = $argv[1];
else
	$key = count($url)-1;

$cli = new swoole_http_client('127.0.0.1', 9504);
$cli->on('error', function($a){var_dump($a);});

$cli->setHeaders(['User-Agent' => "swoole"]);
$data = array("userID" =>201,'token'=>'asdadadadsada','dwSessionID'=>'asdaadsa');
$data = http_build_query($data);

echo ($url[$key]).'-start check'.PHP_EOL;
$url = ($url[$key]).'?'.$data;
echo $url.PHP_EOL;

$cli->get($url, function (swoole_http_client $cli)
{

	if($data = json_decode($cli->body))
		var_dump($data);
	else
		echo $cli->body.PHP_EOL;
	echo 'CHECK END'.PHP_EOL;
	exit();
});
?>
