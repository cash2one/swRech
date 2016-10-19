<?php

define('ROOT', __DIR__.'/../log/');
$base = basename(dirname(__DIR__));

chdir(ROOT);

$front_time = mktime(0,0,0,date('n'),date('j')-2, date('Y'));
$from_dir = date('Y-m-d',$front_time);
echo $from_dir;

if(is_dir($from_dir))
{
	$target = '/data/log/bak_'.$base.'_'.$from_dir.date('_His',time()).'.tgz';
	$script = "tar -czf $target $from_dir --remove-files";
	echo $script;
	system($script,$res);

	if($res!==0)
		exit('dasdas');
}

?>
