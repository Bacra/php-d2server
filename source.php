<?php
require dirname(__FILE__).'/common.inc.php';

// require './common.inc.php';

// ��ȡ�ļ����� �����ñ���
$charset = isset($_GET['charset']) ? strtolower($_GET['charset']) : 'utf-8';

// ������������
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
			��ȡ��Ӧ����Դ�ļ�
			����Ŀ¼��α��̬����
		 ******************************/

		header( 'Expires: -1' );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		header( 'Cache-Control: post-check=0, pre-check=0', false );
		header( 'Pragma: no-cache' ); //����http1.0��https


		$dealFiles = $_paramconfig['files'][$_GET['pathid']];


		// ��ȡ�ļ�����

		$filePathArr = array();
		$fileContent = '';
		$errorFileArr = array();

		if (is_array($dealFiles)) {
			foreach ($dealFiles AS $file) {
				loadFile($file, $fileContent, $filePathArr, $errorFileArr, true);
			}

			// ���ͷ����Ϣ
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

		// ����ļ�����
		echo $fileContent;
	} else {
		echo "/**********************************\n".
		"��������\n".
		"pathid��{$_GET['pathid']}\n".
		"files��{$_paramconfig['files']}\n".
		"**********************************/\n";
	}
} else {
	echo "/**********************************\n".
		"PARAMFILE �ļ�������\n".
		"COOKIE��".(isset($_COOKIE[COOKIENAME4HTMLDIR]) ? $_COOKIE[COOKIENAME4HTMLDIR] : 'undefined')."\n".
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
 * ��ȡ�ļ����ݣ�����������ģ��
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


		// LESS����baseLess�ļ�
		if ($extension == 'less') {
			includeLess($file, $content);
		}
	}


	// �Ƿ�Ҫ������һ��ע�ͣ������ļ��Ļ�������Ҫ��
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