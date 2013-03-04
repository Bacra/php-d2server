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
define('HTMLPART', isset($_GET['part']) ? $_GET['part'] : false);

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


// var_dump($_SERVER['REDIRECT_URL']);
// 文件缓存机制
// 注意：只缓存最后的结果，单单修改html或则修改config，都会导致脱离文件缓存机制

$action = $_GET['action'];
define('CACHEHTMLFILE', HTMLDIR.CACHEHTML_REDIR.basename(HTMLFILE));
define('ORIGINSOURSE', strpos($action, 'o') === false);		// 是否需要转化加载的资源文件

if (file_exists(HTMLFILE)) {

	if (file_exists(CONFIGFILE)) {
		// 要使用 CACHEHTMLFILE文件
		// 必须检查PARAMFILE是否存在 以及CONFIGFILE及HTMLFILE的更新时间
		if (!NOCACHE && ORIGINSOURSE && file_exists(CACHEHTMLFILE) && file_exists(PARAMFILE) && @filemtime(CACHEHTMLFILE) > @filemtime(HTMLFILE) && @filemtime(CACHEHTMLFILE) > @filemtime(CONFIGFILE)){
			include CACHEHTMLFILE;
			exit;
		} else if (ORIGINSOURSE) {
			$content = file_get_contents(parseTemplate(HTMLFILE));
		} else {
			if (HTMLPART) {
				include parseTemplate(HTMLFILE);
			} else {
				echo file_get_contents(HTMLFILE);
			}
			exit;
		}
	} else if (NOPROJECT) {
		echo file_get_contents(HTMLFILE);
		exit;
	} else {
		exit('CONFIGFILE not exits');
	}
} else {
	exit('HTML File not exits');
}





$_CONFIG = json_decode(file_get_contents(CONFIGFILE), true);

$hasLESS = false;

// session初始化
if (!isset($_PARAMCONFIG['path'])) {
	$_PARAMCONFIG['path'] = array();
	$_PARAMCONFIG['files'] = array();
}

// 遍历配置文件
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
		$printArr = array();
		foreach($inputPaths AS $key => $inputPath) {
			
			// 文件集合，需要合并到一个文件中
			if (is_array($inputPath)) {
				$myfiles = array();

				$sourcePathinfo = pathinfo($key);
				$sourceType = $sourcePathinfo['extension'];

				foreach($inputPath AS $val) {
					$filedir = HTMLDIR.SOURCE_REDIR.$val;

					// 判断文件是否存在
					if (!file_exists($filedir)) {
						$printArr[] = "\n\n<!-- [Convert ERROR] $inputPath 文件丢失 无法引入 -->\n\n";
						continue;
					}

					// less解析成css文件
					if ($sourceType == 'css') {
						$myPathinfo = pathinfo($val);
						if ($myPathinfo['extension'] == 'less') {
							$cssfile = HTMLDIR.MODULE_REDIR.$myPathinfo['dirname'].'/'.$myPathinfo['filename'].'.css';

							if (tempLess($filedir, $cssfile)) $myfiles[] = $cssfile;
							continue;
						}
					}

					$myfiles[] = $filedir;
				}

				// 设置files参数
				// 组装files[]参数集合
				// $urlfiles = array();
				// foreach ($myUrlfiles AS $val) {
				// 	$urlfiles[] = 'files[]='.urlencode($val);
				// }
				// $urlfiles = implode('&', $urlfiles);
				
				$inputPath = $key;

			// 单独一个文件
			} else {
				$myfiles = HTMLDIR.SOURCE_REDIR.$inputPath;
				// 如果文件不存在，就不添加
				// less如果文件不存在继续引入，会报错
				if (!file_exists($myfiles)) {
					$printArr[] = "\n\n<!-- [Convert ERROR] $inputPath 文件丢失 无法引入 -->\n\n";
					continue;
				}

				
				$sourcePathinfo = pathinfo($inputPath);
				$sourceType = $sourcePathinfo['extension'];
				
				// $urlfiles = 'files[]='.urlencode($filedir);
			}



			$queryPath = dirname($_GET['file']).'/'.$inputPath;
			if (isset($_PARAMCONFIG['path'][$queryPath])) {
				$index = $_PARAMCONFIG['path'][$queryPath];
				// $_PARAMCONFIG['files'][] = $myfiles;
			} else {
				$index = array_push($_PARAMCONFIG['files'], $myfiles) -1;
				$_PARAMCONFIG['path'][$queryPath] = $index;
			}

			// 获取文件命名
			$sourceBasename = preg_replace('/\.[^\.]+$/', '.m'.$index.'$0', $sourcePathinfo['basename']);


			// 注意：需要将文件夹合并到文件名中
			/*if ($sourcePathinfo['dirname'] && $sourcePathinfo['dirname'] != '.') {
				$sourceBasename = '__'.preg_replace('/[\/\\\\]/', '_._', $sourcePathinfo['dirname']).'__'.$sourceBasename;
			}*/


			// 使用php来获取素材文件
			// 原因：css中的地址，需要通过php来伪静态处理
			// 注意：由于less的id命名是根据文件名来处理的，所以……source中的文件名和目录必须转化为相应的文件名
			// $filePath = $outputPathinfo['dirname'].'/'.$sourceBasename.'.fsrc?extension='.$sourceType.'&'.$urlfiles;
				//.'&ver='.IntCuter::parse(TIME);
			
			$filePath = $outputPathinfo['dirname'].'/'.$sourceBasename;


			
			// 将css转为为less
			if ($sourceType == 'css' && HTMLALLLESS) $sourceType = 'less';


			// 生成写入的字符串
			if ($sourceType == 'css') {
				$printArr[] = '<link type="text/css" rel="stylesheet" href="'.$filePath.'" />';
			} else if ($sourceType == 'less') {
				$hasLESS = true;
				$printArr[] = '<link type="text/css" rel="stylesheet/less" href="'.$filePath.'" />';
			} else {
				$printArr[] = '<script type="text/javascript" src="'.$filePath.'"></script>';
			}
		}
		return implode("\r\n", $printArr);
	}, $content);
}



// 如果使用了LESS，就在head里面添加less处理文件
$content = str_replace('</head>', '<script type="text/javascript" src="'.LESSFILE.'"></script>'."\r\n".'</head>', $content);

// 添加说明文字
$content = str_replace('<head>', "<head>\n<!-- UPDATA BY ".CONFIGPATH." -->\n<!-- MTIME ".date('l dS \of F Y h:i:s A', @filemtime(HTMLFILE)).(HTMLNOCACHE ? " -->\n<!-- NOW ".date('l dS \of F Y h:i:s A', TIME) : '').' -->'
	 , $content);


// 创建相关配置文件的缓存
makeDirs(PARAMFILE);
file_put_contents(PARAMFILE, "<?php\nreturn ".var_export($_PARAMCONFIG, true).";\n?>");

// 创建HTML缓存
makeDirs(CACHEHTMLFILE);
file_put_contents(CACHEHTMLFILE, $content);

// 输出内容
// ob_clean();

include CACHEHTMLFILE;
?>