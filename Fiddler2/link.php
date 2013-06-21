<?php
$config = array(
	'8684square' => array(
		'domain' => 'http://qu.8684.com/',
		'localpath' => 'W:\\8684square\\'
	),

);

if (isset($_GET['say'])) echo $_GET['say'];

if (!isset($_GET['ob']) || !isset($_GET['url']) ) exit('/* 缺少必要的参数 */');
if (!isset($config[$_GET['ob']])) exit('/* 错误的参数或则配置文件 */');


$object = $config[$_GET['ob']];
$root = $object['localpath'];

if(file_exists($root.$_GET['url'])) {
	echo "/* 代理读取文件 */\n";
} else {
	echo "/* 服务器上读取文件 */\n";
	$root = $object['domain'];
}

echo @file_get_contents($root.$_GET['url']);
?>