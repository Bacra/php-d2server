<?php
// 三个目录形式 
// dir path file

// 设置时区
date_default_timezone_set("PRC");


// 外部调用程序及JAR路径
define('JAVA', 'JAVA');
define('CLOSURECOMPILER', '"%ClosureCompiler%compiler.jar"');
define('YUICOMPRESSOR', '"%YUIcompressor%yuicompressor.jar"');
define('LESSC', 'LESSC');



// 服务器域名根目录所对应的文件夹目录
define('DOMAINROOT', 'w:/');


// 注意：相对的是域名根目录
define('LESSPATH', 'Less/less-1.3.3.js');
// 配置文件相对HTML根目录的相对路径
// 修改此参数之后，注意修改build文件中的ROOT目录
define('CONFIGPATH', '.source/.buildconfig');
define('PARAMPATH', '.temp/.paramconfig');
define('COOKIENAME4HTMLPATH', 'WFHTMLPATH');


define('SOURCEDIR', '.source/');		// 存放开发文件的目录
define('BUILDDIR', '.temp/.build/');			// 存放生成文件的目录
define('MODULEDIR', '.temp/.module/');	// 存放生成文件时临时导出文件的目录
define('CACHEHTMLDIR', '.temp/.HTML/');



// 压缩文件的配置
define('JSMINCONSOLE', true);	// false压缩文件删除console测试代码
define('CSSMINIMGVARSION', true);	// 针对css中图片启动文件版本后缀

// HTML内容转化配置
// 配置之后HTML内容中自动添加相应的时间戳
// 注意：false的时候，也会添加文件更改的时间戳
define('HTMLNOCACHE', true);
define('HTMLALLLESS', false);		// 将所有的css全部转化为LESS进行加载


// 用来生成版本号 以及文件是否需要缓存的判断
define('TIME', time());









// 创建目录
function makeDirs($file) {
	$dirname = dirname($file);
	if (!is_dir($dirname)) {
		showCmdMsg("[MESSAGE PHP] 目录不存在 即将创建如下目录\n	$dirname");
		showCmdMsg(mkdirs($dirname) ? "[创建成功]\n": "[创建失败]\n");
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





function compressJS($inputFile, $outputFile){
	$execCommand = JAVA.' -jar '.CLOSURECOMPILER.' --js "'.$inputFile.'" --js_output_file "'.$outputFile.'"';
	$execCommand = str_replace('/', "\\", $execCommand);
	exec($execCommand, $return, $code);	

	if ($code != 0) {
		// printf("[ERROR Compiler] %s (%s)", join("\n", $return), $code);
		// echo "\n";

		return false;
	}
	
	return true;
}


function compressCss($inputFile, $outputFile){
	$execCommand = JAVA.' -jar '.YUICOMPRESSOR.' "'.$inputFile.'" -o "'.$outputFile.'"';
	$execCommand = str_replace('/', "\\", $execCommand);
	exec($execCommand, $return, $code);	

	if ($code != 0) {
		// printf("[ERROR YUIcompressor] %s (%s)", join("\n", $return), $code);
		// echo "\n";

		return false;
	}
	
	return true;
}


function buildLess($lessFile, $cssFile){
	// 处理文件中的$include的内容
	$fileContent = @file_get_contents($lessFile);
	$rs = includeLess($lessFile, $fileContent);
	if ($rs) {
		$tempLess = $cssFile.'.less';
		@file_put_contents($tempLess, $fileContent);
		$execCommand = LESSC.' "'.$tempLess.'" > "'.$cssFile.'"';
	} else {
		$execCommand = LESSC.' "'.$lessFile.'" > "'.$cssFile.'"';
	}
	
	$execCommand = str_replace('/', "\\", $execCommand);
	// echo $execCommand;
	exec($execCommand, $return, $code);

	if ($code != 0) {
		// printf("[ERROR LESS] %s (%s)", join("\n", $return), $code);
		// showCmdMsg("[ERROR] 合并文件中将不包含此文件内容\n	$lessFile\n");

		return false;
	}

	return true;
}


// 注意：返回值不是$fileContent
function includeLess($lessFile, &$fileContent){
	$lessPath = dirname($lessFile).'/';
	$edited = false;
	static $baseFileContents = array();

	$fileContent = preg_replace_callback('/\@include +(\'|")?(.*?)\1;/', function($matches) use(&$lessPath, &$baseFileContents, &$edited){
		$path = $lessPath.$matches[2];
		// echo $path;
		if (!isset($baseFileContents[$path])) {
			$baseFileContents[$path] = @file_get_contents($path);
		}
		$edited = true;

		return $baseFileContents[$path];
	}, $fileContent);

	return $edited;
}


function buildCss($filePath, $fileContent){
	$imgFiles = array();
	$errorImgFiles = array();

	// 读取文件目录信息
	if (!$fileContent) $fileContent = @file_get_contents($filePath);

	$fileContent = preg_replace_callback('/url\((["\']?)(.*?)\1\)/', function($matches) use($filePath, &$imgFiles, &$errorImgFiles){
		// 针对base64的图片直接返回
		if (preg_match('/^data:image\/(?:jpeg|png|gif);base64,/i', $matches[2])) return $matches[0];

		// 获取图片文件修改时间
		$imgSrc = trim($matches[2]);

		if (isset($imgFiles[$imgSrc])) {
			$imgFiles[$imgSrc]['num']++;
			return $imgFiles[$imgSrc]['returnValue'];
		} else {
			$imgPath = dirname($filePath).'/'.$imgSrc;
			if (file_exists($imgPath)) {
				$mtime = @filemtime($imgPath);
				// $version = $mtime;
				// $version = $mtime%10000000000;					// 不使用随机数字，使用时间进行随机排布 只大致进行版本设置
				// $version = base_convert($mtime, 10, 36);
				$version = cutInteger($mtime);
				$verPath = strpos($imgSrc, '?') === false ? '?' : '&';
				$verPath = $imgSrc.$verPath.'ver='.$version;
				$returnValue = 'url('.$verPath.')';
				
				$imgFiles[$imgSrc] = array(
					'mtime' => $mtime,
					'version' => $version,
					'verPath' => $verPath,
					'returnValue' => $returnValue,
					'num' => 1
				);
				// echo $returnValue;
				
				return $returnValue;
			} else if (isset($errorImgFiles[$imgSrc])) {
				$errorImgFiles[$imgSrc]++;
			} else {
				$errorImgFiles[$imgSrc] = 1;
			}
		}
		
		return $matches[0];
	}, $fileContent);


	// 输出统计情况
	$imgFileNum = count($imgFiles);
	$errorImgFileNum = count($errorImgFiles);
	if ($imgFileNum > 0 || $errorImgFileNum > 0) {
		echo "\n";
		showCmdMsg("[CSS IMG] 处理CSS文件中的图片信息\n");
		showCmdMsg("	$filePath\n");

		if (count($imgFiles) > 0) {
			showCmdMsg("[IMG VER] 针对以下图片添加版本编号\n");
			foreach ($imgFiles AS $key => $value) {
				// echo $value['mtime'].' '.dechex($value['mtime']);
				showCmdMsg("	[{$value['version']}][".date('y-m-d', $value['mtime'])."] $key [{$value['num']}次]\n");
			}
			echo "\n";
		}
		if (count($errorImgFiles) > 0) {
			showCmdMsg("[IMG ERROR] 以下图片文件不存在或则不可访问\n");
			foreach ($errorImgFiles AS $key => $value) {
				showCmdMsg("	$key [".$value."次]\n");		// BUG如果直接在引用中写入$value会带上“次”作为变量
			}
			echo "\n";
		}
	}

	return $fileContent;
}


// 检查缓存文件中是否存在
function tempLess($inputFile, $outputFile){
	static $arr = array();
	$key = $inputFile.$outputFile;
	if (!in_array($key, $arr)){
		if (!buildLess($inputFile, $outputFile)) return false;
		$arr[] = $key;
	}

	/* if (file_exists($outputFile) && @filemtime($outputFile) > @filemtime($inputFile)) {
		showCmdMsg("[SKIP LESS] 文件已存在[最新] 跳过\n	$inputFile\n");
	} else {
		// 生成文件
		return buildLess($inputFile, $outputFile);
	} */

	return true;
}


// 输出cmd信息
function showCmdMsg($msg) {
	echo mb_convert_encoding($msg, "GB2312", "UTF-8");
}




/**
 * 将10进制数字进行缩减
 * @param  [type] $num [description]
 * @param  [type] $arr [description]
 * @return [type]      [description]
 */
function cutInteger($num, $ascArr = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '|', '-', '_')) {
	$rs = array();
	convert23($num, count($ascArr), $ascArr, $rs);
	return implode($rs, '');
}
// cutInteger执行
// 回调函数
function convert23($num, $length, &$ascArr, &$rs) {
	if ($num > $length) convert23($num/$length, $length, $ascArr, $rs);	
	$rs[] = $ascArr[$num%$length];
}


function preg_encode($name){
	$name = iconv('UTF-8', 'UCS-2', $name);
	$len = strlen($name);
	$str = '';
	for ($i = 0; $i < $len - 1; $i = $i + 2) {
		$c = $name[$i];
		$c2 = $name[$i + 1];
		
		if (ord($c) == 0) {
			$str .= '\x{00';
		} else if (ord($c) < 16) {
			$str .= '\x{0'. base_convert(ord($c), 10, 16);
		} else {
			$str .= '\x{'. base_convert(ord($c), 10, 16);
		}
		if (ord($c2) == 0) {
			$str .= '00}';
		} else if (ord($c2) < 16) {
			$str .= '0'. base_convert(ord($c2), 10, 16).'}';
		} else {
			$str .= base_convert(ord($c2), 10, 16).'}';
		}
	}
	return $str;
}



function modelHTML($str, $partParam){
	$mainRegExp = '/<(\w+)([^>]*) mp="([\w ]+)"([^>]*)([^>\/]?)>/';		// 注意：不处理<div />这样直接结束的方法
	if (preg_match_all($mainRegExp, $str, $mainMatches, PREG_OFFSET_CAPTURE)) {
		$tagType = array();		// 仅作in_array判断使用
		// $startPlaces = array();
		// $tagsPlaces = array();
		$tagsArr = array();
		foreach($mainMatches[1] AS $i => $val){
			if (!in_array($val[0], $tagType)){
				$tagType[$val[1]] = $val[0];
				// $startPlaces[] = $val[1];
				// $tagsPlaces[$val[0]] = array();
			}

			$tagsArr[$val[1]-1] = array(
				'index' => $i,
				'key' => $val[0],
				'start' => $val[1]-1,
				'startTag' => $mainMatches[0][$i][0],
				'param' => $mainMatches[3][$i][0],
				// 'children' => array()
			);
			// $tagsPlaces[$val[0]][] = &$tagsArr[$key];
		}
		
		foreach($tagType AS $len => $tag){
			if (preg_match_all('/(<\/'.$tag.'>)|(<'.$tag.'(?: [^>]*)?[^\/]?>)/', $str, $matches, PREG_OFFSET_CAPTURE, $len-1)) {

				$endTag = '</'.$tag.'>';
				$strlen = strlen($endTag);
				$tempMatchs = array();
				// 核心算法
				foreach($matches[0] AS $val){
					if ($val[0] == $endTag) {
						$arrEnd = count($tempMatchs)-1;
						if (end($tempMatchs)) {
							$end = $val[1]+$strlen;
							$tempMatchs[$arrEnd]['end'] = $end;
							$tempMatchs[$arrEnd]['length'] = $end - $tempMatchs[$arrEnd]['start'];
						}
						array_pop($tempMatchs);
					} else {
						if (isset($tagsArr[$val[1]])) {
							$tempMatchs[] = &$tagsArr[$val[1]];
						} else {
							$tempMatchs[] = false;
						}
					}
				}
			}
		}
		
		// file_put_contents('D:/03.txt', substr($str, 4638, 2749));
		// 删除区间
		$offset = 0;
		$minSatrt = 0;
		foreach($tagsArr AS $key => $val) {
			if ($key > $minSatrt && isset($val['end']) && !preg_match('/\b'.$partParam.'\b/', $val['param'])) {
				$minSatrt = $val['end'];
				// $str = mb_strcut($str, 0, $val['start'] - $offset, 'utf-8').mb_strcut($str, $minSatrt, 99999, 'utf-8');
				$str = substr($str, 0, $val['start'] - $offset).substr($str, $val['end'] - $offset);
				$offset += $val['length'];
			}
		}

		// 最后清楚剩下的所有标签
		$str = preg_replace($mainRegExp, '<$1$2$4$5>', $str);
	}

	return $str;
}
?>