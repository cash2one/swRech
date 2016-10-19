<?php
/*
* 2bitmap 快速海量int查找不重复的数据
* create 20160615
* auther zzd
*/

$bitmap = array_fill(0,1025,0);

//x表示一个整数，num表示bitmap中已经拥有x的个数
//由于我们只能用2个bit来存储x的个数，所以num的个数最多为3 
function set($x,$num)
{
	$i = $x >> 2;
	$j = $x & 3;

	global $bitmap;
	//将x对于为值上的个数值先清零，但是有要保证其他位置上的数不变 | 重新对x的个数赋值
	$bitmap[$i] = $bitmap[$i] &~((0x3<<($j<<1)) & 0xFF) | (($num&3)<<($j<<1) & 0xFF);
}

function clear($x)
{
	$m = $x >> 2;
	$n = $x & 3;
	global $bitmap;

	$bitmap[$m] &= ~((0x3<<($n<<1)) & 0xFF);
}

function get($x)
{
	$i = $x >> 2;
	$j = $x & 3;
	global $bitmap;

	return ($bitmap[$i] & (0x3<<($j<<1))) >> ($j<<1);
}

//@此函数中2如果改为1 则表示排重
function add($x)
{
		$num = get($x);
		if($num&2)
				return;
		set($x,$num+1);
}

$a = array(1000,11,1, 3, 5, 7, 9, 1, 3, 5, 7, 1, 3, 5,1, 3, 1,10,2,4,6,8,0);
for($i=0;$i<sizeof($a);$i++)
{
	add($a[$i]);
}
for($i=0;$i<=max($a);++$i)
{
	if(get($i)==1)
		echo $i.'_'.get($i).PHP_EOL;
}











