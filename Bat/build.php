<?php
require dirname(dirname(__FILE__)).'/common.inc.php';

define('CONFIGFILE', $argv[1]);
define('ROOT', dirname(dirname(CONFIGFILE)).'/');
define('SOURCEDIR', ROOT.SOURCE_REDIR);



if (!file_exists(CONFIGFILE)) exit('Config File not exits');
$CONFIG = json_decode(file_get_contents(CONFIGFILE), true);


foreach ($CONFIG['files'] AS $outputPath => $inputPaths) {
	$arr = array();			// 用来存放需要合并的文件
	
	foreach ($inputPaths AS $inputPath) {
		if (is_array($inputPath)) {
			$arr = array_merge($arr, getValidInputfiles($inputPath));
		} else {
			$rs = getValidInputfiles(array($inputPath));
			$arr[] = $rs[0];
		}
	}

	// 压缩文件和未压缩文件存放目录设置
	$targetFile = ROOT.$outputPath;					// 目标文件 未压缩的
	// 将文件转移到正式目录下
	$compressFile = ROOT.BUILD_REDIR.$outputPath;		// 可能会被压缩的
	makeDirs($targetFile);
	makeDirs($compressFile);


	// 合并文件
	$str = '';
	foreach($arr AS $filepath) {
		$str .= @file_get_contents($filepath)."\n\n";
	}
	$rs = @file_put_contents($targetFile, $str."\n\n");		// 末尾添加\n\n，确保必有内容写入
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
	if (JSMINCONSOLE && $outputType == 'js') {
		// 删除代码中console测试代码
		// (?:log|info|warn|assert|debug)
		$fileContent = preg_replace_callback('/console\.log\(.*?\);?/', function($matches){
			Console::warn("存在console.log命令，将予以删除");
			Console::warn("	{$matches[0]}");
			return '';
		}, $fileContent);


	// css文件中img增加版本号
	// 版本号为文件修改的时间戳
	} else if (CSSMINIMGVARSION && $outputType == 'css') {
		$fileContent = buildCss($targetFile, $fileContent);
	}

	$fileContent = "/**********************************************\n"
			// ."		★ 珍爱生命 请勿修改此文件 ★\n"
			// ."		=============================\n"
			."	如遇需求变更 请联系前端协调修改\n"
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
	if ($warnMsgNum) echo "[Warn]\n".implode("\n", $warnMsg);
	if ($errorMsgNum) echo "[Error]\n".implode("\n", $errorMsg);
	echo "\n";
}






function synchFile($outputPath, $source){

}





// 获取有效的 文件目录
// 期间也会对less文件进行编译操作
function getValidInputfiles($inputPaths) {
	$arr = array();
	
	foreach($inputPaths AS $filepath) {
		$pathinfo = pathinfo($filepath);
		$type = $pathinfo['extension'];
		$inputFile = SOURCEDIR.$filepath;

		if (file_exists($inputFile)) {
			if ($type == 'less') {
				// 编译less文件
				$outputFile = ROOT.MODULE_REDIR.$pathinfo['dirname'].'/'.$pathinfo['filename'].'.css';
				makeDirs($outputFile);

				// 判断文件是否存在 并且是最新的
				// 如果不是最新的文件，生成文件
				if (tempLess($inputFile, $outputFile)) $arr[] = $outputFile;

			} else {
				$arr[] = $inputFile;
			}
		} else {
			Console::error("文件不存在 跳过");
			Console::error("	$inputFile");
		}
	}
	

	return $arr;
}
?>