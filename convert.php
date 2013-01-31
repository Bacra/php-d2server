<?php
require './common.inc.php';

header("Content-Type:text/html;charset=utf-8");

// ���Windows��·���������� ������GB2312����
// $_SERVER["REDIRECT_URL"]
define('HTMLFILE', DOMAINROOT.$_GET['file']);		// HTML�ļ�·��
define('HTMLDIR', dirname(HTMLFILE).'/');		// HTML�����ļ�Ŀ¼
// �����ļ�Ŀ¼
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
// �ļ��������
// ע�⣺ֻ�������Ľ���������޸�html�����޸�config�����ᵼ�������ļ��������

$pathinfo = pathinfo(HTMLFILE);
$action = $_GET['action'];
define('CACHEHTMLFILE', HTMLDIR.CACHEHTMLDIR.$pathinfo['filename'].'-4-'.HTMLPART.'.'.$pathinfo['extension']);

if (file_exists(HTMLFILE)) {
	$refresh = strpos($action, 'r') === false ? false : true;
	if (!file_exists(CONFIGFILE) && !HTMLPART && !$refresh) {
		echo template(file_get_contents(HTMLFILE), HTMLDIR);
		exit();
	} else if (file_exists(CONFIGFILE)) {
		// Ҫʹ�� CACHEHTMLFILE�ļ�
		// ������PARAMFILE�Ƿ���� �Լ�CONFIGFILE��HTMLFILE�ĸ���ʱ��
		if (!$refresh && file_exists(CACHEHTMLFILE) && file_exists(PARAMFILE) && @filemtime(CACHEHTMLFILE) > @filemtime(HTMLFILE) && @filemtime(CACHEHTMLFILE) > @filemtime(CONFIGFILE)){
			echo file_get_contents(CACHEHTMLFILE);
			exit();
		} else {
			$content = template(file_get_contents(HTMLFILE), HTMLDIR);
			if (HTMLPART) $content = modelHTML($content, HTMLPART);
		}
	} else {		// ������CONFIGFILE ������HTMLPART
		
		/* if (file_exists(CACHEHTMLFILE) && @filemtime(CACHEHTMLFILE) > @filemtime(HTMLFILE)) {
			echo file_get_contents(CACHEHTMLFILE);
		} else {
			$content =  modelHTML(file_get_contents(HTMLFILE), HTMLPART);

			echo $content;
			mkdirs(dirname(CACHEHTMLFILE));
			file_put_contents(CACHEHTMLFILE, $content);
		}
		*/

		// ���CONFIGFILE �����ڣ�����ʹ��HTMLPART����
		exit('CONFIGFILE not exits');
	}
} else {
	exit('HTML File not exits');
}







// js css less ��ַת��
$config = @include(CONFIGFILE);

$hasLESS = false;
if (is_array($config)) {
	// session��ʼ��
	if (!isset($_paramconfig['path'])) {
		$_paramconfig['path'] = array();
		$_paramconfig['files'] = array();
	}
	// echo $content;
	// var_dump($config);

	// ���������ļ�
	foreach ($config['files'] AS $outputFile => $inputFiles) {
		$_paramconfig[$outputFile] = $inputFiles;
		// ��ȡ�ļ��ļ�����
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
				
				// �ļ����ϣ���Ҫ�ϲ���һ���ļ���
				if (is_array($inputFile)) {
					$myfiles = array();

					$sourcePathinfo = pathinfo($key);
					$sourceType = $sourcePathinfo['extension'];

					foreach($inputFile AS $val) {
						$filedir = HTMLDIR.SOURCEDIR.$val;

						// �ж��ļ��Ƿ����
						if (!file_exists($filedir)) {
							$printArr[] = "\n\n<!-- [Convert ERROR] $inputFile �ļ���ʧ �޷����� -->\n\n";
							continue;
						}

						// less������css�ļ�
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

					// ����files����
					// ��װfiles[]��������
					// $urlfiles = array();
					// foreach ($myUrlfiles AS $val) {
					// 	$urlfiles[] = 'files[]='.urlencode($val);
					// }
					// $urlfiles = implode('&', $urlfiles);
					
					$inputFile = $key;

				// ����һ���ļ�
				} else {
					$myfiles = HTMLDIR.SOURCEDIR.$inputFile;
					// ����ļ������ڣ��Ͳ����
					// less����ļ������ڼ������룬�ᱨ��
					if (!file_exists($myfiles)) {
						$printArr[] = "\n\n<!-- [Convert ERROR] $inputFile �ļ���ʧ �޷����� -->\n\n";
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

				// ��ȡ�ļ�����
				$sourceBasename = preg_replace('/\.[^\.]+$/', '.m'.$index.'$0', $sourcePathinfo['basename']);


				// ע�⣺��Ҫ���ļ��кϲ����ļ�����
				/*if ($sourcePathinfo['dirname'] && $sourcePathinfo['dirname'] != '.') {
					$sourceBasename = '__'.preg_replace('/[\/\\\\]/', '_._', $sourcePathinfo['dirname']).'__'.$sourceBasename;
				}*/


				// ʹ��php����ȡ�ز��ļ�
				// ԭ��css�еĵ�ַ����Ҫͨ��php��α��̬����
				// ע�⣺����less��id�����Ǹ����ļ���������ģ����ԡ���source�е��ļ�����Ŀ¼����ת��Ϊ��Ӧ���ļ���
				// $filePath = $outputPathinfo['dirname'].'/'.$sourceBasename.'.fsrc?extension='.$sourceType.'&'.$urlfiles;
					//.'&ver='.cutInteger(TIME);
				
				$filePath = $outputPathinfo['dirname'].'/'.$sourceBasename;


				
				// ��cssתΪΪless
				if ($sourceType == 'css' && HTMLALLLESS) $sourceType = 'less';


				// ����д����ַ���
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



	// ���ʹ����LESS������head�������less�����ļ�
	$content = str_replace('</head>', '<script type="text/javascript" src="'.LESSPATH.'"></script>'."\r\n".'</head>', $content);

	// ���˵������
	$content = str_replace('<head>', "<head>\n<!-- UPDATA BY ".CONFIGPATH." -->\n<!-- MTIME ".date('l dS \of F Y h:i:s A', @filemtime(HTMLFILE)).(HTMLNOCACHE ? " -->\n<!-- NOW ".date('l dS \of F Y h:i:s A', TIME) : '').' -->'
		 , $content);
}



file_put_contents(PARAMFILE, "<?php\nreturn ".var_export($_paramconfig, true).";\n?>");

// �������
// �����Ƿ����config����Ҫ���������
ob_clean();
echo $content;

// ��������
mkdirs(dirname(CACHEHTMLFILE));
file_put_contents(CACHEHTMLFILE, $content);
?>