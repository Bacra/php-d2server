<?php
require dirname(__FILE__).'/common.inc.php';

// require './common.inc.php';

// 获取文件类型 并设置编码
$charset = isset($_GET['charset']) ? strtolower($_GET['charset']) : 'utf-8';

// 设置数据类型
$extension = isset($_GET['extension']) ? $_GET['extension'] : 'js';

switch ($extension) {
	case 'js':
		$type = 'text/javascript';
		break;

	case 'less':
	case 'css':
		$type = 'text/css';
		break;

	case 'html':
		$type = 'text/html';
		break;
	
	default:
		$type = 'text/plain';
		break;
}

header("Content-Type:$type;charset=$charset");


define('HTMLPATH', isset($_COOKIE[COOKIENAME4HTMLDIR]) ? $_COOKIE[COOKIENAME4HTMLDIR] : DOMAINROOT);
define('PARAMFILE', HTMLPATH.PARAMPATH);


if (file_exists(PARAMFILE)) {
	$_paramconfig = @include(PARAMFILE);

	if (isset($_GET['pathid']) && isset($_paramconfig['files'][$_GET['pathid']])) {

		/******************************
			读取相应的资源文件
			便于目录的伪静态处理
		 ******************************/

		header( 'Expires: -1' );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		header( 'Cache-Control: post-check=0, pre-check=0', false );
		header( 'Pragma: no-cache' ); //兼容http1.0和https


		$dealFiles = $_paramconfig['files'][$_GET['pathid']];


		// 读取文件内容

		$filePathArr = array();
		$fileContent = '';
		$errorFileArr = array();

		if (is_array($dealFiles)) {
			foreach ($dealFiles AS $file) {
				loadFile($file, $fileContent, $filePathArr, $errorFileArr, true);
			}

			// 输出头部信息
			echo "/**********************************\n\n".
				"	Now: ".date('l dS \of F Y h:i:s A', time())."\n";

			if (count($filePathArr) > 0) {
				echo "\n	[Success FilePath]\n".
					"	".implode("\n	", $filePathArr)."\n";
			}

			echo "\n**********************************/\n";
		} else {
			loadFile($dealFiles, $fileContent, $filePathArr, $errorFileArr, false);
		}

		if (count($errorFileArr) > 0) {
			echo "/**********************************\n\n";
			echo "\n	[Error FilePath]\n".
				"	".implode("\n	", $filePathArr)."\n";
			echo "\n**********************************/\n";
		}

		// 输出文件内容
		echo $fileContent;
	} else {
		echo "/**********************************\n".
		"参数错误\n".
		"pathid：{$_GET['pathid']}\n".
		"files：{$_paramconfig['files']}\n".
		"**********************************/\n";
	}
} else {
	echo "/**********************************\n".
		"PARAMFILE 文件不存在\n".
		"COOKIE：".(isset($_COOKIE[COOKIENAME4HTMLDIR]) ? $_COOKIE[COOKIENAME4HTMLDIR] : 'undefined')."\n".
		"**********************************/\n";
}











function loadFile($file, &$fileContent, &$filePathArr, &$errorFileArr, $showFilePath){
	GLOBAL $charset;
	
	if (file_exists($file)) {
		if ($showFilePath) {
			$filepath = $charset == 'utf-8' ? mb_convert_encoding($file, "UTF-8", "GB2312") : $file;
		} else {
			$filepath = false;
		}

		$filePathArr[] = $filepath;
		$fileContent .= fileContentTpl($file, $filepath);
	} else {
		$filepath = $charset == 'utf-8' ? mb_convert_encoding($file, "UTF-8", "GB2312") : $file;
		$errorFileArr[] = $filepath;	
	} /*else {
		$file2 = mb_convert_encoding($file, "GB2312", "UTF-8");
		$filepath =  $charset == 'utf-8' ? $file : $file2;

		if (file_exists($file2)) {
			$filePathArr[] = $filepath;
			$fileContent .= fileContentTpl($file2, $filepath);
		} else {
			$errorFileArr[] = $filepath;
		}
	}*/
}

/**
 * 读取文件内容，并生成输入模版
 * @param  [type] $file     [description]
 * @param  [type] $filepath [description]
 * @return [type]           [description]
 */
function fileContentTpl($file, $filepath = false) {
	static $cssCharset = false;
	GLOBAL $charset, $extension;

	$content = @file_get_contents($file);

	if ($extension == 'css' || $extension == 'less') {
		$content = preg_replace_callback('/\@charset +(\'|")?[\w-_ ]+\1 *;/', function($matchs) use(&$cssCharset, $charset){
			if (!$cssCharset) {
				$cssCharset = true;
				return '@charset "'.$charset.'";';
			}
			return '';
		}, $content);


		// LESS包含baseLess文件
		if ($extension == 'less') {
			includeLess($file, $content);
		}
	}


	// 是否要生成这一段注释（单个文件的话，不需要）
	if ($filepath) {
		$str = "\n\n\n\n\n\n\n\n\n\n\n\n".
				"/**********************************\n\n".
				"	$filepath\n\n".
				"**********************************/\n\n";
	} else {
		$str = '';
	}

	return $str.$content;
}
?>