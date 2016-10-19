<?php
require_once __DIR__.'/../conf/protocol.php';
require_once __DIR__.'/../core/classd/json_protocol.php';
require_once __DIR__.'/../core/classd/aes.php';

$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
//设置事件回调函数
$client->on("connect", function($cli) {
    $data = array(6666,0,'gmt','serv',array('all'));
    $cli->send(call_user_func_array(array(BASE_PROTOCOL,'encode'),array($data)));
});
$client->on("receive", function($cli, $data){
    echo "Received: ".$data."\n";
    $cli->close();
});
$client->on("error", function($cli){
    echo "Connect failed\n";
    exit();
});
$client->on("close", function($cli){
    echo "Connection close\n";
});
//发起网络连接
$client->connect('127.0.0.1', 9503, 0.5);
?>
