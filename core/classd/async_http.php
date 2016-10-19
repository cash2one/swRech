<?php
/*
* 异步httpclient
* 最多单进程 10个链接工作
* auther zzd
*/

class async_http
{
protected static $busy_pool	= array();	//工作连接
protected static $busy_max	= 20;		//最大工作链接
protected static $wait_queue	= array();	//等待的请求
protected static $wait_queue_max = 100;	//等待队列的最大长度，超过后将拒绝新的请求
protected static $clikey=0;		//唯一key
protected static $time_id;		//超时检查	

private static function http_request($ip,$path, $data, $call_back, $transfer, $port, $ssl, $host=null, $cookie=null,$header=null)
{
	$k = self::$clikey;

	if(self::$clikey>99998)
		self::$clikey = 0;
	else
		++self::$clikey;
	
	$cli	= new \swoole_http_client($ip, $port, $ssl);

	self::$busy_pool[$k]= array
	(
		'cli'	=> $cli,
		'ts'	=> time()+15,
		'cback'	=> array($ip,$path, $data, $call_back, $transfer, $port)
	);

	if($host)
		$cli->setHeaders(array('Host'=> $host));
	
	if(is_array($cookie))
		$cli->setCookies($cookie);	
	if(is_array($header))
		$cli->setHeaders($header);

	if($data)
	{	
		$cli->post($path, $data,function ($cli) use ($k)
		{
			call_user_func_array(self::$busy_pool[$k]['cback'][3],array($cli->body, self::$busy_pool[$k]['cback'][4]));
			unset($cli,self::$busy_pool[$k]);
			if(count(self::$wait_queue))
			{
				call_user_func_array(array(__CLASS__,'http_request'), array_shift(self::$wait_queue));
			}
		});

	}
	else
	{
		$cli->get($path, function ($cli) use ($k)
		{
			if(isset(self::$busy_pool[$k]))
			{
				call_user_func_array(self::$busy_pool[$k]['cback'][3],array($cli->body, self::$busy_pool[$k]['cback'][4]));
			}
			else
			{
				write_log('ahttp_err', 'undefine k:'.$k);
			}
			unset($cli,self::$busy_pool[$k]);
			if(count(self::$wait_queue))
			{
				call_user_func_array(array(__CLASS__,'http_request'), array_shift(self::$wait_queue));
			}
		});
	}
}

/*
* http query ( if domain , query IP first)
* param $host		-> www.baidu.com or 192.168.1.1
* param $path		-> /index.html
* param $data		-> string 
* param $call_back 	-> is_callable function
* param $transfer	-> mixed 透传参数
* paran $port		-> 默认 80
*
*/
public	static	function do_http($host,$path, $data, $call_back=null, $transfer=null, $port=80, $ssl=false, $cookie=null, $header=null)
{
	CLASSED_DEBUG && write_log('ahttp', array($host,$path, $data, $call_back, $transfer, $port,$ssl, $cookie,$header));

	if(!filter_var($host, FILTER_VALIDATE_IP) )
	{
		swoole_async_dns_lookup($host, function($host,$ip) use ($path, $data, $call_back, $transfer, $port, $ssl, $cookie,$header)
		{	
			if(count(self::$busy_pool)<self::$wait_queue_max)
				self::http_request($ip,$path, $data, $call_back, $transfer, $port,$ssl,$host, $cookie, $header);
			else
				self::$wait_queue[] = array($ip,$path, $data, $call_back, $transfer, $port,$ssl,$host, $cookie,$header);
		});
	}
	else
	{
			if(count(self::$busy_pool)<self::$wait_queue_max)
				self::http_request($host,$path, $data, $call_back, $transfer, $port,$ssl, null, $cookie,$header);
			else
				self::$wait_queue[] = array($host,$path, $data, $call_back, $transfer, $port,$ssl, null, $cookie,$header);
	}
}

public	static	function check_http_time_out()
{
	if(empty(self::$busy_pool))
		return true;

	$now	= time();
	$bak	= array();


	write_log('test_ahttp', count(self::$busy_pool));
	foreach(self::$busy_pool as $k=>$data)
	{
		if( $now < $data['ts'] )
			continue;
write_log('test_ahttp', 'timeout');
write_log('test_ahttp', $data);
		
		if(is_callable($data['cback'][3]))
			call_user_func_array($data['cback'][3],array(false, $data['cback'][4]));	

		$bak[] = $data['cback'];
		unset($data['cli'],self::$busy_pool[$k]);
	}

	if(!$size = count($bak))
		return true;

	for($i=0; $i<$size; ++$i)
	{
		if(!$req = array_shift(self::$wait_queue))
			break;

		call_user_func_array(array(__CLASS__,'http_request'), $req);
	}

	write_warn('low_http_err', $bak);
}

public	static	function setup()
{
	self::$time_id  = swoole_timer_tick(1000+rand(100,500),array(__CLASS__, 'check_http_time_out'));
}


}
?>
