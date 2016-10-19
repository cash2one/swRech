<?php
/**
 * 
 * php鏂囦欢鍙嶅皠绫昏嚜鍔ㄥ姞杞界浉鍏? * 
 * @author zzd
 * 
 */

//@璋冪敤绫讳腑setup鍑芥暟
function call_other($class, $func, $args = null)
{
	$r	= new \ReflectionClass($class);
	if (!$r->hasMethod($func))
	{
		return;
	}
	
	$func = $r->getMethod($func);
	if (!$func->isStatic())
	{
		return;
	}
	
	if (is_null($args))
	{
		return	$func->invokeargs(null, array());
	}
	else
	{
		return	$func->invokeargs(null, $args);
	}
}

//@鍔犺浇骞惰皟鐢╯etup
function loadClass($class)
{
	//@鏂囦欢鍚嶉渶鑰冭檻澶у皬鍐?	
	$file	= str_replace('\\', '/', $class);
	
	require_once	PROJECT_ROOT. $file. '.php';

	echo "load ". PROJECT_ROOT. $file. '.php'.PHP_EOL;
	
	call_other($class, 'setup');
}

//@鍔犺浇鐩稿叧鏂囦欢
function activeLoadClass($file)
{
	require_once $file;

	$class = substr($file,0,strlen($file)-4);
	$class =  str_replace(array(PROJECT_ROOT,'/','.'), array('\\','\\', '_'), $class);

	return call_other($class, 'setup');
}

//@鍔犺浇鑴氭湰鐩稿叧鏂囦欢
function activeLoadScriptClass($file)
{
	require_once $file;
}

//@鑷姩鍔犺浇
function load($name)
{
	loadClass($name);
}

spl_autoload_register('load');

function load_dir($dir, $mask = '/(\.php$)/i' )
{
	$d = opendir($dir);
		
	while ($file = readdir($d))
	{
		if ($file == '.' || $file == '..' || strpos($file, '.') === 0 )
			continue;
	
		if (is_dir($dir.'/'.$file))
		{
			load_dir($dir.'/'.$file);
			
			continue;
		}
		
		if( !preg_match($mask, $file) )
			continue;
		
		if( !activeLoadClass($dir.'/'.$file))
		{
			echo 'load err'.$dir.'/'.$file;
			return false;
		}
	}
	
	return true;
}

function load_unpattern_dir($dir, $mask = '/(\.php$)/i' )
{
	$d = opendir($dir);
		
	while ($file = readdir($d))
	{
		if ($file[0] === '.')
			continue;
	
		if (is_dir($dir.'/'.$file))
		{
			load_unpattern_dir($dir.'/'.$file);
			
			continue;
		}
		
		if ( preg_match($mask, $file) )
			continue;

		if (!activeLoadClass($dir.'/'.$file))
			return false;
	}
	
	return true;
}

function load_script($dir, $mask = '/(\.lua$)/i')
{
	static	$data = array();
	
	$d = opendir($dir);
	
	while($file = readdir($d))
	{
		if ($file == '.' || $file == '..' || strpos($file, '.') === 0 )
			continue;
	
		if (is_dir($dir.'/'.$file))
		{
			load_script($dir.'/'.$file);
			
			continue;
		}
		
		if( !preg_match($mask, $file) )
			continue;		
		
		$temp = strtoupper(str_replace('.', '_', $file));

		if( $contents = file_get_contents($dir.'/'.$file) )
		{
			$data[$temp] = $contents;
			//define( $temp, $contents );
		}
		else
		{
			var_dump($dir.'/'.$file.'读取失败，程序终止');
		}
	}
	
	return $data;
}


?>
