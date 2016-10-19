<?php
class json_protocol
{
public static	function encode($data, $compress=true, $sha = true)
{
	$msg	= json_encode($data);

	if($sha)
		$msg = \aes::encode($msg);
	
	if($compress)
		$msg = gzcompress($msg);
	
	if($sha)
		$msg = base64_encode($msg);
	
	$msg = md5($msg.PROTOCOL_SECRET_KEY).$msg;

	$msg = pack(PROTOCOL_LENGTH_TYPE, strlen($msg)).$msg;

	return $msg;
}

public static	function decode($msg, $compress=true, $sha = true)
{//[1001,"S1","ASSSSSSDDDDDDDSSSSDDSSDSDSDSDSDS","",[]]" 52
	if( strlen($msg) < 40 )
		return false;
	
	$sign	= substr($msg,2,32);
	$recv	= substr($msg,34);
	
	if($sign != md5($recv.PROTOCOL_SECRET_KEY))
		return 0;
	
	if($compress) 
		$recv = gzuncompress(base64_decode($recv));
	
	if($sha)
		$recv = \aes::decode($recv);
	
	return json_decode($recv,true);
}


}
?>
