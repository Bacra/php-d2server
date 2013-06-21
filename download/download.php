<?php
define('ROOT', dirname(__FILE__).'/result/');

$fileExitLog = array();
$fileWriteError = array();
$sum = 0;

foreach(explode ("\n", $_POST['files']) AS $val) {
	$val = trim($val);
	if (!empty($val)) {
		$sum++;

		$file = ROOT.preg_replace('/^[\w\d]+?:\/\//i', '/', $val);
		if (file_exists($file)) {
			$fileExitLog[] = $file;
			continue;
		}

		$cont = @file_get_contents($val);
		if ($cont) {
			makeDirs($file);
			$rs = @file_put_contents($file, $cont);

			if (!$rs) $fileWriteError[] = $file;
		}
	}
}


if (!empty($fileWriteError)) {
	echo '<p>文件写入失败：（'.count($fileWriteError) .'/' .$sum.'）</p>';
	echo implode($fileWriteError, '<br />');
}
if (!empty($fileExitLog)) {
	echo '<p>以下文件已经存在，自动跳过：（'.count($fileExitLog) .'/' .$sum.'）</p>';
	echo implode($fileExitLog, '<br />');
}

echo '完成任务';



// 创建目录
function makeDirs($file) {
	$dirname = dirname($file);
	if (!is_dir($dirname)) {
		mkdirs($dirname);
	}
}


function mkdirs($dirname) {
	$parentDirname = dirname($dirname);
	if (!is_dir($parentDirname)) {
		mkdirs($parentDirname);
	}

	if (!is_dir($dirname)) {
		return mkdir($dirname, 0777);
	}

	return true;
}

?>