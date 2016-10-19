<?php
$sets = ['passwd'=>'224cf2b695a5e8ecaecfb9015161fa4b'];
 foreach($sets as $key=>$value)
     {
		         $temp[] = sprintf("`%s`='%s'", $key, $value);
				     }


var_dump(implode(',', $temp));
exit();
$a = new Redis();

$a->connect('127.0.0.1', 6379);

var_dump($a->auth('ngrxzgy'));
var_dump($a->set('ss',rand(1,99), 'NX', 'EX',180));
var_dump($a->get('ss'));
var_dump($a->del('ss'));


//$a = new clSms();

//\sms::setup($a);

//var_dump(\sms::send('13466605583', '您的验证码是128456, 4分钟有效。'));
