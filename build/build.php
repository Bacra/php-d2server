<?php
require dirname(dirname(__FILE__)).'/common.inc.php';

define('CONFIGFILE', $argv[1]);
define('ROOT', dirname(dirname(CONFIGFILE)).'/');
define('SOURCEDIR', ROOT.SOURCE_REDIR);



if (!file_exists(CONFIGFILE)) exit('Config File not exits');
$CONFIG = json_decode(file_get_contents(CONFIGFILE), true);


foreach ($CONFIG['files'] AS $outputPath => $inputPaths) {
	// 压缩文件和未压缩文件存放目录设置
	$targetFile = ROOT.$outputPath;					// 目标文件 未压缩的
	// 将文件转移到正式目录下
	$compressFile = ROOT.BUILD_REDIR.$outputPath;		// 可能会被压缩的

	// 判断是否需要进行下面的操作
	if (!DEBUG && file_exists($targetFile)) {
		$noNeed = false;
		foreach ($inputPaths AS $inputPath) {
			if (filemtime($targetFile) > filemtime(SOURCEDIR.$inputPath)) {
				$noNeed = true;
				break;
			}
		}
		if ($noNeed) continue;
	}
	
	// 获取可用的文件地址，并读取文件合并内容
	$str = '';
	foreach ($inputPaths AS $inputPath) {
		$rs = getValidInputfiles($inputPath);
		if (!$rs) continue;
		$str .= file_get_contents($rs)."\n\n";;
	}

	makeDirs($targetFile);
	makeDirs($compressFile);
	$rs = file_put_contents($targetFile, $str."\n\n");		// 末尾添加\n\n，确保必有内容写入
	if (!$rs) {
		Console::error("文件内容写入失败");
		Console::error("	$targetFile");
		exit();
	}

	$outputPathinfo = pathinfo($outputPath);
	// 判断是否需要压缩
	if (stripos($outputPathinfo['basename'], '.min.') === false) {
		copy($targetFile, $compressFile);
		continue;
	}

	// 需要压缩的文件处理
	// 先备份一份未压缩的到.build
	// copy($targetFile, str_replace('.min.', '.', $compressFile));

	// 压缩文件
	$outputType = $outputPathinfo['extension'];
	if ($outputType == 'js') {
		compressJS($targetFile, $compressFile);
	} else if ($outputType == 'css') {
		compressCss($targetFile, $compressFile);
	}


	// 添加注释信息
	// 注意：只针对压缩后的文件添加
	$fileContent = @file_get_contents($compressFile);
	if (CSSMINIMGVARSION && $outputType == 'css') {
		$fileContent = buildCss($targetFile, $fileContent);
	}

	$fileContent = "/**********************************************\n"
			// ."		★ 珍爱生命 请勿修改此文件 ★\n"
			// ."		=============================\n"
			."	需求变更 请联系\n"
			."	E-mail:wf@tianqu.com.cn   QQ:434537707\n"
			// ."	File:$outputPath\n"
			// ."	Update:".TIME."\n"
			."***********************************************/\n"
			.$fileContent;
	
	$rs = @file_put_contents($compressFile, $fileContent);
	if (!$rs) {
		Console::error("压缩文件内容写入失败");
		Console::error("	$compressFile");
		exit();
	}
}




// 输出信息
$warnMsg = Console::getWarn();
$errorMsg = Console::getError();
$warnMsgNum = count($warnMsg);
$errorMsgNum = count($errorMsg);

if ($warnMsgNum || $errorMsgNum) {
	if ($warnMsgNum) echo mb_convert_encoding("[Warn]\n".implode("\n", $warnMsg), "GB2312", "UTF-8");
	if ($errorMsgNum) echo mb_convert_encoding("[Error]\n".implode("\n", $errorMsg), "GB2312", "UTF-8");
	echo "\n";
}

// end






// 使用到的函数
function synchFile($outputPath, $source){

}



// 获取有效的 文件目录
// 期间也会对less文件进行编译操作
function getValidInputfiles($filepath) {
	$inputFile = SOURCEDIR.$filepath;
	if (!file_exists($inputFile)) {
		Console::error("文件不存在 跳过");
		Console::error("	$inputFile");
		return false;
	}

	$pathinfo = pathinfo($filepath);
	$type = $pathinfo['extension'];

	if ($type == 'less') {
		// 编译less文件
		$outputFile = ROOT.MODULE_REDIR.$pathinfo['dirname'].'/'.$pathinfo['filename'].'.css';
		makeDirs($outputFile);

		// 判断文件是否存在 并且是最新的
		// 如果不是最新的文件，生成文件
		if (tempLess($inputFile, $outputFile)) return $outputFile;
	} else {
		return $inputFile;
	}
}
?>