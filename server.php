<?php

require 'org.uuland.login.qq/class.login.php';

$uin = @$_GET['uin'];

$do = $_GET['do'];

is_numeric($uin) || exit('UIN.Invaild');

$login = new UULAND_QQLogin('cache/');

if ($do == 'check')
{
	$vfcode = $login->Captcha_check($uin);
	if ($vfcode['need'])
	{
		echo $vfcode['uin'].'?'.$vfcode['data'];
	}
	else
	{
		echo $vfcode['uin'].':'.$vfcode['data'];
	}
}
if ($do == 'captcha')
{
	echo $login->Captcha_GET($uin);
}
if ($do == 'login')
{
	$password = $_GET['password'];
	$vfcode = $_GET['vfcode'];
	$r = $login->Login($uin, $password, $vfcode);
	if ($r['ops'] == 'true')
	{
		echo 'ok:'.$r['name'];
	}
	else
	{
		echo 'err:'.$r['err'];
	}
}
if ($do == 'url')
{
	include 'org.uuland.login.qq/class.http.php';
	$http = new UULAND_QQLogin_HTTP('cache/', $uin);
	$url = $_GET['url'];
	echo $http->get($url);
}
if ($do == 'cookies')
{
	// tmp code for read cookies file
	$HASH = md5($_SERVER['SERVER_NAME']);
	$cookie_file = 'cache/cookies~'.$uin.'@'.$HASH.'.php';
	echo is_file($cookie_file) ? htmlspecialchars(file_get_contents($cookie_file)) : 'null';
}

?>