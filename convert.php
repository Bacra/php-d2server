<?php
require 'common.inc.php';
require 'template.class.php';

header("Content-Type:text/html;charset=utf-8");

// 针对Windows的路径编码问题 必须是GB2312编码
// $_SERVER["REDIRECT_URL"]
define('HTMLFILE', DOMAINROOT.$_GET['file']);		// HTML文件路径
define('HTMLDIR', dirname(HTMLFILE).'/');		// HTML所在文件目录
// 配置文件目录
define('CONFIGFILE', HTMLDIR.CONFIGPATH);
define('PARAMFILE', HTMLDIR.PARAMPATH);
define('OMIT', $_GET['omit']);

if (!isset($_COOKIE[COOKIENAME4HTMLDIR])){
	setcookie(COOKIENAME4HTMLDIR, HTMLDIR, time() + 24*3600*30, "/".dirname($_GET['file']));
}

if (file_exists(PARAMFILE)) {
	$_PARAMCONFIG = include(PARAMFILE);
} else {
	$_PARAMCONFIG = array(
		'files' => array(),
		'path' => array()
	);
}

// var_dump($_GET);



// var_dump($_SERVER['REDIRECT_URL']);
// 文件缓存机制
// 注意：只缓存最后的结果，单单修改html或则修改config，都会导致脱离文件缓存机制

define('CACHEHTMLFILE', HTMLDIR.CACHEHTML_REDIR.basename(HTMLFILE).'.pgsc.tpl');		// pgsc = pagesource
define('MERGERSOURSE', $_GET['merger'] ? true : false);		// 是否需要转化加载的资源文件

if (file_exists(HTMLFILE)) {

	if (file_exists(CONFIGFILE)) {
		$_CONFIG = json_decode(file_get_contents(CONFIGFILE), true);

		// 要使用 CACHEHTMLFILE文件
		// 必须检查PARAMFILE是否存在 以及CONFIGFILE及HTMLFILE的更新时间
		if (!DEBUG && !MERGERSOURSE && file_exists(CACHEHTMLFILE) && file_exists(PARAMFILE) && @filemtime(CACHEHTMLFILE) > @filemtime(HTMLFILE) && @filemtime(CACHEHTMLFILE) > @filemtime(CONFIGFILE)){
			// 调用最全面的缓存模版
			useTemplate(CACHEHTMLFILE, OMIT);
		} else if (MERGERSOURSE) {
			if (OMIT) {
				useTemplate(parseTemplate(HTMLFILE), OMIT);
			} else {
				echo file_get_contents(HTMLFILE);
			}
		} else {
			// 生成并创建HTML缓存
			makeDirs(CACHEHTMLFILE);
			file_put_contents(CACHEHTMLFILE, replaceSource(file_get_contents(parseTemplate(HTMLFILE)), $_CONFIG));

			// 输出内容
			useTemplate(CACHEHTMLFILE, OMIT);
		}

		// 显示附加内容
		showExtJS($_CONFIG);
	} else if (DEBUG) {
		exit('CONFIGFILE not exits');
	} else {
		echo file_get_contents(HTMLFILE);
	}


	if (DEBUG) echo '<div style="position: fixed; bottom: 4px; right: 4px; border: 3px solid #333; color: #333; border-radius: 4px; background: #CD9; padding: 12px;"><p style="font-size: 16px; font-weight: bold;">DEBUG Mode</p><p>'.date('F dS Y H:i:s', TIME).'</p></div>';
	exit;
} else {
	exit('HTML File not exits');
}





function replaceSource($content, $_CONFIG){
	$hasLESS = false;

	// session初始化
	if (!isset($_PARAMCONFIG['path'])) {
		$_PARAMCONFIG['path'] = array();
		$_PARAMCONFIG['files'] = array();
	}

	// 遍历配置文件
	if (isset($_CONFIG['files'])) {
		foreach ($_CONFIG['files'] AS $outputPath => $inputPaths) {
			// $_PARAMCONFIG[$outputPath] = $inputPaths;
			// 获取文件文件类型
			$outputPathinfo = pathinfo($outputPath);
			$outputType = $outputPathinfo['extension'];
			$preg = str2int16preg($outputPath);

			// echo $outputType;
			if ($outputType == 'js') {
				$preg = '/<script (?:(?!<script ).)*?src="'.$preg.'.*?><\/script>/iu';
			} else {
				$preg = '/<link (?:(?!<link ).)*?href="'.$preg.'.*?>/iu';
			}
			// preg_match_all($preg, $content, $m);
			// var_export($m);
			$content = preg_replace_callback($preg, function($matches) use ($inputPaths, $outputPathinfo, &$_PARAMCONFIG){
				// var_dump($matches);
				$echoArr = array();
				foreach($inputPaths AS $inputPath) {
					$inputFile = HTMLDIR.SOURCE_REDIR.$inputPath;
					// 如果文件不存在，就不添加
					// less如果文件不存在继续引入，会报错
					if (!file_exists($inputFile)) {
						$echoArr[] = "\n\n<!-- [Convert ERROR] $inputPath 文件丢失 无法引入 -->\n\n";
						continue;
					}


					$sourcePathinfo = pathinfo($inputPath);
					$sourceType = $sourcePathinfo['extension'];


					$queryPath = dirname($_GET['file']).'/'.$inputPath;
					if (isset($_PARAMCONFIG['path'][$queryPath])) {
						$index = $_PARAMCONFIG['path'][$queryPath];
						// $_PARAMCONFIG['files'][] = $inputFile;
					} else {
						$index = array_push($_PARAMCONFIG['files'], $inputFile) -1;
						$_PARAMCONFIG['path'][$queryPath] = $index;
					}

					// 获取文件命名
					$sourceBasename = preg_replace('/\.[^\.]+$/', '.m'.$index.'$0', $sourcePathinfo['basename']);
					$filePath = $outputPathinfo['dirname'].'/'.$sourceBasename;


					// 生成写入的字符串
					if ($sourceType == 'css') {
						$echoArr[] = '<link type="text/css" rel="stylesheet" href="'.$filePath.'" />';
					} else if ($sourceType == 'less') {
						$hasLESS = true;
						$echoArr[] = '<link type="text/css" rel="stylesheet/less" href="'.$filePath.'" />';
					} else {
						$echoArr[] = '<script type="text/javascript" src="'.$filePath.'"></script>';
					}
				}
				return implode("\r\n", $echoArr);
			}, $content);
		}
	}


	// 如果使用了LESS，就在head里面添加less处理文件
	$content = str_replace('</head>', '<script type="text/javascript" src="'.LESSFILE.'"></script>'."\r\n".'</head>', $content);

	// 添加说明文字
	$content = str_replace('<head>', "<head>\n<!-- UPDATA BY ".CONFIGPATH." -->\n<!-- MTIME ".date('l dS \of F Y h:i:s A', @filemtime(HTMLFILE))." -->\n", $content);


	// 创建相关配置文件的缓存
	makeDirs(PARAMFILE);
	file_put_contents(PARAMFILE, "<?php\nreturn ".var_export($_PARAMCONFIG, true).";\n?>");

	return $content;
}



function showExtJS($_CONFIG){
	// 插入附加JS
	if (isset($_CONFIG['html'])) {
		$pathinfo = pathinfo(HTMLFILE);
		if (isset($_CONFIG['html'][$pathinfo['filename']])) {
			// 配置相关参数
			$jsCont = '<script type="text/javascript">var __PAGE_OMITS__ = '.json_encode($_CONFIG['html'][$pathinfo['filename']]).';</script>';
		}
	}
	echo $jsCont.'<script type="text/javascript" src="'.EXTJSFILE.'"></script>';
}
?>