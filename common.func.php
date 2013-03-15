<?php
// 创建目录
function makeDirs($file) {
	$dirname = dirname($file);
	if (!is_dir($dirname)) {
		Console::warn("[MESSAGE PHP] 目录不存在 即将创建如下目录");
		Console::warn("	$dirname");
		Console::warn(mkdirs($dirname) ? "[创建成功]": "[创建失败]");
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
	$execCommand = JAVA.' -jar '.JSC.' --js "'.$inputFile.'" --js_output_file "'.$outputFile.'"';
	$execCommand = str_replace('/', "\\", $execCommand);
	@exec($execCommand, $return, $code);	

	if ($code != 0) {
		Console::error($return);
		return false;
	}
	
	return true;
}


function compressCss($inputFile, $outputFile){
	$execCommand = JAVA.' -jar '.CSSC.' "'.$inputFile.'" -o "'.$outputFile.'"';
	$execCommand = str_replace('/', "\\", $execCommand);
	@exec($execCommand, $return, $code);	

	if ($code != 0) {
		Console::error($return);
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
	@exec($execCommand, $return, $code);

	if ($code != 0) {
		Console::error($return);
		// Console::error("[ERROR] 合并文件中将不包含此文件内容");
		// Console::error("	$lessFile");

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
				$version = IntCuter::parse($mtime);
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
		Console::log("[CSS IMG] 处理CSS文件中的图片信息");
		Console::log("	$filePath");

		if (count($imgFiles) > 0) {
			Console::log("[IMG VER] 针对以下图片添加版本编号");
			foreach ($imgFiles AS $key => $value) {
				// echo $value['mtime'].' '.dechex($value['mtime']);
				Console::log("	[{$value['version']}][".date('y-m-d', $value['mtime'])."] $key [{$value['num']}次]");
			}
		}
		if (count($errorImgFiles) > 0) {
			Console::warn("[IMG ERROR] 以下图片文件不存在或则不可访问");
			foreach ($errorImgFiles AS $key => $value) {
				Console::warn("	$key [".$value."次]");		// BUG如果直接在引用中写入$value会带上“次”作为变量
			}
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
		showCmdMsg("[SKIP LESS] 文件已存在[最新] 跳过");
		showCmdMsg("	$inputFile");
	} else {
		// 生成文件
		return buildLess($inputFile, $outputFile);
	} */

	return true;
}



// 保存需要输出的信息
class Console {
	private static $logMsg = array();
	private static $errorMsg = array();
	private static $warnMsg = array();

	private static function addMsg($msg, $toMsg){
		if (is_array($msg)) {
			$toMsg = array_merge($msg, $toMsg);
		} else {
			$toMsg[] = $msg;
		}

		return $toMsg;
	}

	public static function log($msg){
		Console::$logMsg = Console::addMsg($msg, Console::$logMsg);
	}
	public static function warn($msg){
		Console::$warnMsg = Console::addMsg($msg, Console::$warnMsg);
		Console::$logMsg = Console::addMsg($msg, Console::$logMsg);
	}
	public static function error($msg){
		Console::$errorMsg = Console::addMsg($msg, Console::$errorMsg);
		Console::$logMsg = Console::addMsg($msg, Console::$logMsg);
	}
	public static function getLog(){
		return Console::$logMsg;
	}
	public static function getWarn(){
		return Console::$warnMsg;
	}
	public static function getError(){
		return Console::$errorMsg;
	}
}




/**
 * 将10进制数字进行缩减
 * @param  [type] $num [description]
 * @param  [type] $arr [description]
 * @return [type]      [description]
 */
class IntCuter {
	static public $ascArr = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '|', '-', '_');
	
	static public function parse($num) {
		$rs = array();
		self::convert23($num, count(self::$ascArr), $rs);
		return implode($rs, '');
	}

	static public function convert23($num, $length, &$rs) {
		if ($num > $length) self::convert23($num/$length, $length, $rs);	
		$rs[] = self::$ascArr[$num % $length];
	}
}



function str2int16preg($name){
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




/*
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

function template($content, $path){
	$content = preg_replace_callback('/<template +file=("|\')([^"\'>]+)\1 *\/>/i', function($matches) use($path){
		$filePath = $path.SOURCE_REDIR.trim($matches[2]);
		if (file_exists($filePath)) {
			return template(file_get_contents($filePath), $path);
		} else {
			return "<!-- $filePath 文件不存在 -->";
		}
	}, $content);

	return $content;
}
*/

?>