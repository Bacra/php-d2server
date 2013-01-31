<?php
require './common.inc.php';

if (isset($_GET('dir'))) {
	define('HTMLDIR', mb_convert_encoding(DOMAINROOT.urldecode($_GET['dir']).'/', "GB2312", "UTF-8"));
}
if (isset($_GET('htmlpath'))) {
	define('HTMLPATH', mb_convert_encoding(DOMAINROOT.urldecode($_GET['htmlpath']), "GB2312", "UTF-8"));
	define('HTMLDIR', dirname(HTMLPATH).'/');
}


switch($_GET('type')) {
	case 'config':
		$configpath = HTMLDIR.CONFIGPATH;
		$parampath = HTMLDIR.PARAMPATH;

		$configArr = array();
		if (file_exists($configpath)) {
			$arr = @include($configpath);
			if (is_array($arr)) {
				$configArr = array_merge($configArr, $arr);
			}
		}
		if (file_exists($parampath)) {
			$arr = @include($parampath);
			if (is_array($arr)) {
				$configArr = array_merge($configArr, $arr);
			}
		}

		echo json_encode(array('status' => true, 'config' => $arr));
		break;



	case 'build':
		$pathinfo = pathinfo(HTMLPATH);
		if (!file_exists(HTMLPATH)) {
			echo json_encode(array('status'=>'HTML文件不存在'));
			exit();
		}
		define('HTMLPART', isset($_GET['part']) ? $_GET['part'] : false);
		if (HTMLPART) {
			$content = file_get_contents(HTMLPATH);
			$content = modelHTML($content, HTMLPART);
		} else {
			$content = file_get_contents(HTMLPATH);
		}

		file_put_contents(HTMLPATH.$pathinfo['filename'].'-4-'.HTMLPART.'.'.$pathinfo['extension'], $content);
}

?>