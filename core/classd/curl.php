<?php

class curl
{

//@ssl专用POST	
public	static function post_ssl( $url, $post = NULL, $timeout=5, array  $options = array() )
{
	$time = microtime(true);

	$defaults = array(
	        CURLOPT_HEADER => 0,
	        CURLOPT_URL => $url,
	        CURLOPT_FRESH_CONNECT => 1,
	        CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0,
	        CURLOPT_FORBID_REUSE => 1,
	        CURLOPT_TIMEOUT => $timeout,
	        //CURLOPT_PORT => 443,
	);

	if($post)
	{
		$defaults[CURLOPT_POST] = 1;
		$defaults[CURLOPT_URL] = $url;
		$defaults[CURLOPT_POSTFIELDS] = $post; 
		$ch = curl_init();
	}
	else
	{
		$ch = curl_init($url);
	}
	

	foreach($options as $ook=>$oov)
	{
			$defaults[$ook] = $oov;
	}

	curl_setopt_array($ch, $defaults);
	
	if (!$result = curl_exec($ch))
	{
		curl_close($ch);
		return false;
	}
	
	curl_close($ch);

	return $result;
}

public	static function post( $url, $post = NULL, $port=80, array  $options = array() )
{
	if(function_exists('write_log'))
	{
	if(is_array($post))
		write_log('curl', 'post:'.$url.'-'.json_encode($post));
	else
		write_log('curl', 'post:'.$url.'-'.$post);
	}
	$defaults = array(
		CURLOPT_HEADER => 0,
		CURLOPT_FRESH_CONNECT => 1,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_FORBID_REUSE => 1,
		CURLOPT_TIMEOUT => 3,
		CURLOPT_PORT => $port,
	);
	
	if($post)
	{
		$defaults[CURLOPT_POST] = 1;
		$defaults[CURLOPT_URL] = $url;
		$defaults[CURLOPT_POSTFIELDS] = $post; 
		$ch = curl_init();
	}
	else
	{
		$ch = curl_init($url);
	}
	
	curl_setopt_array($ch, ($options + $defaults));
	
	if (!$result = curl_exec($ch))
	{
		curl_close($ch);
		return false;
	}
	
	curl_close($ch);
	
	return $result;
}

}

//var_dump(\curl::post('http://localhost/dump.php','1', 80));


?>
