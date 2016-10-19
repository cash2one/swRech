<?php
/*
* bitmap 用于海量无符号整数查询是否存在 
* create 20160615
* auther zzd
* 需求600M内存
*/

DEFINE('WORD'	,	32);
DEFINE('SHIFT'	,	5);
DEFINE('MASK'	,	0x1F);
DEFINE('N'		,	4*1024*1024);

$bitmap = array_fill(0,1+N/WORD,0);

function set($i)
{
		global $bitmap;
		$bitmap[$i>>SHIFT] |= (1<<($i & MASK));
}

function clear($i)
{
		global $bitmap;
		$bitmap[$i>>SHIFT] &= ~(1<<($i & MASK));
}

FUNCTION test($i)
{
		global $bitmap;
		return 1 && $bitmap[$i>>SHIFT] & (1<<($i & MASK));
}

//set(0);
//set(1);
//set(2);
//set(3);
//set(4);
//set(5);
//set(6);
//set(7);
//set(899999);
//
//var_dump(test(1),test(0),test(899998));

FUNCTION	getT($i)
{
		if(!test($i) && !test($i+1))
			return 0;
		elseif(!test($i) && test($i+1))
			return 1;
		elseif(test($i) && !test($i+1))
			return 2;
		else
			return 3;
}

FUNCTION setT($i)
{
		$t= getT($i);
		switch($i)
		{
			case 0:set($i+1);break;
			case 1:set($i);clear($i+1);break;
			case 2:break;
		}
}

FUNCTION clearT($i)
{
		clear($i);
		clear($i+1);
}

$b = array();

for($i=0;$i<1000; $i++)
{
	setT($i*2);
	echo getT($i*2);
}

for($i=0; $i < 100000; $i+=2)
		if(getT($i)==1)
		echo $i.PHP_EOL;


