<?php
// ����Ŀ¼��ʽ 
// dir redir path file

// ����ʱ��
date_default_timezone_set("PRC");


// �ⲿ���ó���JAR·��
define('JAVA', 'JAVA');
define('CLOSURECOMPILER', '"%ClosureCompiler%compiler.jar"');
define('YUICOMPRESSOR', '"%YUIcompressor%yuicompressor.jar"');
define('LESSC', 'LESSC');



// ������������Ŀ¼����Ӧ���ļ���Ŀ¼
define('DOMAINROOT', 'w:/');


// ע�⣺��Ե���������Ŀ¼
define('LESSFILE', '//www.test.com:3316/Less/less-1.3.3.js');
// �����ļ����HTML��Ŀ¼�����·��
// �޸Ĵ˲���֮��ע���޸�build�ļ��е�ROOTĿ¼
define('CONFIGPATH', '.source/.buildconfig');
define('PARAMPATH', '.temp/.paramconfig');
define('COOKIENAME4HTMLDIR', 'WFHTMLDIR');


define('SOURCE_REDIR', '.source/');		// ��ſ����ļ���Ŀ¼
define('BUILD_REDIR', '.temp/.build/');			// ��������ļ���Ŀ¼
define('MODULE_REDIR', '.temp/.module/');	// ��������ļ�ʱ��ʱ�����ļ���Ŀ¼
define('CACHEHTML_REDIR', '.temp/.HTML/');



// ѹ���ļ�������
define('JSMINCONSOLE', true);	// falseѹ���ļ�ɾ��console���Դ���
define('CSSMINIMGVARSION', true);	// ���css��ͼƬ�����ļ��汾��׺

// HTML����ת������
// ����֮��HTML�������Զ������Ӧ��ʱ���
// ע�⣺false��ʱ��Ҳ������ļ����ĵ�ʱ���
define('HTMLNOCACHE', true);
define('HTMLALLLESS', false);		// �����е�cssȫ��ת��ΪLESS���м���


// �������ɰ汾�� �Լ��ļ��Ƿ���Ҫ������ж�
define('TIME', time());









// ����Ŀ¼
function makeDirs($file) {
	$dirname = dirname($file);
	if (!is_dir($dirname)) {
		Console::warn("[MESSAGE PHP] Ŀ¼������ ������������Ŀ¼");
		Console::warn("	$dirname");
		Console::warn(mkdirs($dirname) ? "[�����ɹ�]": "[����ʧ��]");
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
	@exec($execCommand, $return, $code);	

	if ($code != 0) {
		Console::error($return);
		return false;
	}
	
	return true;
}


function compressCss($inputFile, $outputFile){
	$execCommand = JAVA.' -jar '.YUICOMPRESSOR.' "'.$inputFile.'" -o "'.$outputFile.'"';
	$execCommand = str_replace('/', "\\", $execCommand);
	@exec($execCommand, $return, $code);	

	if ($code != 0) {
		Console::error($return);
		return false;
	}
	
	return true;
}


function buildLess($lessFile, $cssFile){
	// �����ļ��е�$include������
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
		// Console::error("[ERROR] �ϲ��ļ��н����������ļ�����");
		// Console::error("	$lessFile");

		return false;
	}

	return true;
}


// ע�⣺����ֵ����$fileContent
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

	// ��ȡ�ļ�Ŀ¼��Ϣ
	if (!$fileContent) $fileContent = @file_get_contents($filePath);

	$fileContent = preg_replace_callback('/url\((["\']?)(.*?)\1\)/', function($matches) use($filePath, &$imgFiles, &$errorImgFiles){
		// ���base64��ͼƬֱ�ӷ���
		if (preg_match('/^data:image\/(?:jpeg|png|gif);base64,/i', $matches[2])) return $matches[0];

		// ��ȡͼƬ�ļ��޸�ʱ��
		$imgSrc = trim($matches[2]);

		if (isset($imgFiles[$imgSrc])) {
			$imgFiles[$imgSrc]['num']++;
			return $imgFiles[$imgSrc]['returnValue'];
		} else {
			$imgPath = dirname($filePath).'/'.$imgSrc;
			if (file_exists($imgPath)) {
				$mtime = @filemtime($imgPath);
				// $version = $mtime;
				// $version = $mtime%10000000000;					// ��ʹ��������֣�ʹ��ʱ���������Ų� ֻ���½��а汾����
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


	// ���ͳ�����
	$imgFileNum = count($imgFiles);
	$errorImgFileNum = count($errorImgFiles);
	if ($imgFileNum > 0 || $errorImgFileNum > 0) {
		Console::log("[CSS IMG] ����CSS�ļ��е�ͼƬ��Ϣ");
		Console::log("	$filePath");

		if (count($imgFiles) > 0) {
			Console::log("[IMG VER] �������ͼƬ��Ӱ汾���");
			foreach ($imgFiles AS $key => $value) {
				// echo $value['mtime'].' '.dechex($value['mtime']);
				Console::log("	[{$value['version']}][".date('y-m-d', $value['mtime'])."] $key [{$value['num']}��]");
			}
		}
		if (count($errorImgFiles) > 0) {
			Console::warn("[IMG ERROR] ����ͼƬ�ļ������ڻ��򲻿ɷ���");
			foreach ($errorImgFiles AS $key => $value) {
				Console::warn("	$key [".$value."��]");		// BUG���ֱ����������д��$value����ϡ��Ρ���Ϊ����
			}
		}
	}

	return $fileContent;
}


// ��黺���ļ����Ƿ����
function tempLess($inputFile, $outputFile){
	static $arr = array();
	$key = $inputFile.$outputFile;
	if (!in_array($key, $arr)){
		if (!buildLess($inputFile, $outputFile)) return false;
		$arr[] = $key;
	}

	/* if (file_exists($outputFile) && @filemtime($outputFile) > @filemtime($inputFile)) {
		showCmdMsg("[SKIP LESS] �ļ��Ѵ���[����] ����");
		showCmdMsg("	$inputFile");
	} else {
		// �����ļ�
		return buildLess($inputFile, $outputFile);
	} */

	return true;
}



// ������Ҫ�������Ϣ
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
 * ��10�������ֽ�������
 * @param  [type] $num [description]
 * @param  [type] $arr [description]
 * @return [type]      [description]
 */
function cutInteger($num, $ascArr = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '|', '-', '_')) {
	$rs = array();
	convert23($num, count($ascArr), $ascArr, $rs);
	return implode($rs, '');
}
// cutIntegerִ��
// �ص�����
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
	$mainRegExp = '/<(\w+)([^>]*) mp="([\w ]+)"([^>]*)([^>\/]?)>/';		// ע�⣺������<div />����ֱ�ӽ����ķ���
	if (preg_match_all($mainRegExp, $str, $mainMatches, PREG_OFFSET_CAPTURE)) {
		$tagType = array();		// ����in_array�ж�ʹ��
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
				// �����㷨
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
		// ɾ������
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

		// ������ʣ�µ����б�ǩ
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
			return "<!-- $filePath �ļ������� -->";
		}
	}, $content);

	return $content;
}

?>