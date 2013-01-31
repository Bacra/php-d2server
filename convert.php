<?php
require './common.inc.php';

header("Content-Type:text/html;charset=utf-8");

// 针对Windows的路径编码问题 必须是GB2312编码
// $_SERVER["REDIRECT_URL"]
define('HTMLFILE', DOMAINROOT.$_GET['file']);		// HTML文件路径
define('HTMLDIR', dirname(HTMLFILE).'/');		// HTML所在文件目录
// 配置文件目录
define('CONFIGFILE', HTMLDIR.CONFIGPATH);
define('PARAMFILE', HTMLDIR.PARAMPATH);
define('HTMLPART', isset($_GET['part']) ? $_GET['part'] : false);

if (!isset($_COOKIE[COOKIENAME4HTMLPATH])){
	setcookie(COOKIENAME4HTMLPATH, HTMLDIR, time() + 24*3600*30, "/".dirname($_GET['file']));
}

if (file_exists(PARAMFILE)) {
	$_paramconfig = @include(PARAMFILE);
} else {
	$_paramconfig = array(
		'files' => array(),
		'path' => array()
	);
}


// var_dump($_SERVER['REDIRECT_URL']);
// 文件缓存机制
// 注意：只缓存最后的结果，单单修改html或则修改config，都会导致脱离文件缓存机制

$pathinfo = pathinfo(HTMLFILE);
$action = $_GET['action'];
define('CACHEHTMLFILE', HTMLDIR.CACHEHTMLDIR.$pathinfo['filename'].'-4-'.HTMLPART.'.'.$pathinfo['extension']);

if (file_exists(HTMLFILE)) {
	$refresh = strpos($action, 'r') === false ? false : true;
	if (!file_exists(CONFIGFILE) && !HTMLPART && !$refresh) {
		echo template(file_get_contents(HTMLFILE), HTMLDIR);
		exit();
	} else if (file_exists(CONFIGFILE)) {
		// 要使用 CACHEHTMLFILE文件
		// 必须检查PARAMFILE是否存在 以及CONFIGFILE及HTMLFILE的更新时间
		if (!$refresh && file_exists(CACHEHTMLFILE) && file_exists(PARAMFILE) && @filemtime(CACHEHTMLFILE) > @filemtime(HTMLFILE) && @filemtime(CACHEHTMLFILE) > @filemtime(CONFIGFILE)){
			echo file_get_contents(CACHEHTMLFILE);
			exit();
		} else {
			$content = template(file_get_contents(HTMLFILE), HTMLDIR);
			if (HTMLPART) $content = modelHTML($content, HTMLPART);
		}
	} else {		// 不存在CONFIGFILE 但存在HTMLPART
		
		/* if (file_exists(CACHEHTMLFILE) && @filemtime(CACHEHTMLFILE) > @filemtime(HTMLFILE)) {
			echo file_get_contents(CACHEHTMLFILE);
		} else {
			$content =  modelHTML(file_get_contents(HTMLFILE), HTMLPART);

			echo $content;
			mkdirs(dirname(CACHEHTMLFILE));
			file_put_contents(CACHEHTMLFILE, $content);
		}
		*/

		// 如果CONFIGFILE 不存在，不能使用HTMLPART参数
		exit('CONFIGFILE not exits');
	}
} else {
	exit('HTML File not exits');
}







// js css less 地址转化
$config = @include(CONFIGFILE);

$hasLESS = false;
if (is_array($config)) {
	// session初始化
	if (!isset($_paramconfig['path'])) {
		$_paramconfig['path'] = array();
		$_paramconfig['files'] = array();
	}
	// echo $content;
	// var_dump($config);

	// 遍历配置文件
	foreach ($config['files'] AS $outputFile => $inputFiles) {
		$_paramconfig[$outputFile] = $inputFiles;
		// 获取文件文件类型
		$outputPathinfo = pathinfo($outputFile);
		$outputType = $outputPathinfo['extension'];
		$preg = preg_encode($outputFile);

		// echo $outputType;
		if ($outputType == 'js') {
			$preg = '/<script (?:(?!<script ).)*?src="'.$preg.'.*?><\/script>/iu';
		} else {
			$preg = '/<link (?:(?!<link ).)*?href="'.$preg.'.*?>/iu';
		}
		// preg_match_all($preg, $content, $m);
		// var_export($m);
		$content = preg_replace_callback($preg, function($matches) use ($inputFiles, $outputPathinfo, &$_paramconfig){
			// var_dump($matches);
			$printArr = array();
			foreach($inputFiles AS $key => $inputFile) {
				
				// 文件集合，需要合并到一个文件中
				if (is_array($inputFile)) {
					$myfiles = array();

					$sourcePathinfo = pathinfo($key);
					$sourceType = $sourcePathinfo['extension'];

					foreach($inputFile AS $val) {
						$filedir = HTMLDIR.SOURCEDIR.$val;

						// 判断文件是否存在
						if (!file_exists($filedir)) {
							$printArr[] = "\n\n<!-- [Convert ERROR] $inputFile 文件丢失 无法引入 -->\n\n";
							continue;
						}

						// less解析成css文件
						if ($sourceType == 'css') {
							$myPathinfo = pathinfo($val);
							if ($myPathinfo['extension'] == 'less') {
								$cssfile = HTMLDIR.MODULEDIR.$myPathinfo['dirname'].'/'.$myPathinfo['filename'].'.css';

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
					
					$inputFile = $key;

				// 单独一个文件
				} else {
					$myfiles = HTMLDIR.SOURCEDIR.$inputFile;
					// 如果文件不存在，就不添加
					// less如果文件不存在继续引入，会报错
					if (!file_exists($myfiles)) {
						$printArr[] = "\n\n<!-- [Convert ERROR] $inputFile 文件丢失 无法引入 -->\n\n";
						continue;
					}

					
					$sourcePathinfo = pathinfo($inputFile);
					$sourceType = $sourcePathinfo['extension'];
					
					// $urlfiles = 'files[]='.urlencode($filedir);
				}



				$fullPath = dirname($_GET['file']).'/'.$inputFile;
				if (isset($_paramconfig['path'][$fullPath])) {
					$index = $_paramconfig['path'][$fullPath];
					$_paramconfig['files'][] = $myfiles;
				} else {
					$index = array_push($_paramconfig['files'], $myfiles) -1;
					$_paramconfig['path'][$fullPath] = $index;
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
					//.'&ver='.cutInteger(TIME);
				
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
	$content = str_replace('</head>', '<script type="text/javascript" src="'.LESSPATH.'"></script>'."\r\n".'</head>', $content);

	// 添加说明文字
	$content = str_replace('<head>', "<head>\n<!-- UPDATA BY ".CONFIGPATH." -->\n<!-- MTIME ".date('l dS \of F Y h:i:s A', @filemtime(HTMLFILE)).(HTMLNOCACHE ? " -->\n<!-- NOW ".date('l dS \of F Y h:i:s A', TIME) : '').' -->'
		 , $content);
}



file_put_contents(PARAMFILE, "<?php\nreturn ".var_export($_paramconfig, true).";\n?>");

// 输出内容
// 不管是否存在config，都要输入出内容
ob_clean();
echo $content;

// 创建缓存
mkdirs(dirname(CACHEHTMLFILE));
file_put_contents(CACHEHTMLFILE, $content);
?>