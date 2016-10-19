<?php
class white_list
{
private static	$wlist = array
(
	'192.168.1.160' => 1,
	'192.168.1.77'  => 1,
	'123.57.44.214'	=> 1,
	'127.0.0.1'	=> 1,
);

public	static	function check($ip)
{
	return isset(self::$wlist[$ip])?true:false;
}


}
?>
