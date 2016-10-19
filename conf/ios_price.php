<?php
class ios_price 
{
const	select = 'SELECT itemId,price FROM ios_price';

public	static	function init()
{
	if(!$price_list = \sync_mysql::queryi_from_gmt(self::select))
		return 1;

	$list = array();

	foreach($price_list as $pval)
	{
		$list[$pval['itemId']] = array('price'=>$pval['price']);
	}

	if(!\cached::init(CASHED_SHARE_IOS_PRICE,$list))
		return 0;

	write_log('init_ios_price', $list);
	return 1;
}

public	static	function get($key)
{
	$data = \cached::get(CASHED_SHARE_IOS_PRICE,array($key));
	if(!$data)
		return false;
	return $data['price'];
}


}
?>
