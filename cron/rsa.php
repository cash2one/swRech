<?php
class AES_128_CW {
    private $_iv = '';
    private $_secret = '';

    public function __construct($iv,$secret){
        $this->_iv = substr($iv.'0000000000000000', 0,16);//可以忽略这一步，只要你保证iv长度是16
        $this->_secret = hash('md5',$secret,true);
    }

    public function decode($secretData){
        return openssl_decrypt(urldecode($secretData),'aes-128-cbc',$this->_secret,false,$this->_iv);
    }

    public function encode($data){
        return urlencode(openssl_encrypt($data,'aes-128-cbc',$this->_secret,false,$this->_iv));
    }
}

if(!isset($argv[1], $argv[2],$argv[3], $argv[4]))
	exit('arg fail');

$file = $argv[1];
$secret = md5($argv[2]);
$iv = $argv[3];
$key = $argv[4];
$crypt = new AES_128_CW("1111111111", $secret);

if($key)
{
	$data = file_get_contents($file.".tgz");
	$data = $crypt->encode($data);
	$end = ".bz";
}
else
{
	$data = file_get_contents($file.".bz");
	$data = $crypt->decode($data);
	$end = ".tgz";
}

file_put_contents("new_".$file.$end,$data);

