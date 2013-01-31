<?php
require dirname(dirname(__FILE__)).'/common.inc.php';

define('CONFIGFILE', $argv[1]);
define('ROOT', dirname(dirname(CONFIGFILE)).'/');
define('SOURCEDIR2', ROOT.SOURCEDIR);



if (file_exists(CONFIGFILE)) {
	$config = @include(CONFIGFILE);
} else {
	exit('Config File not exits');
}


// var_dump(pathinfo($configPath));
// exit();


if (is_array($config)) {
	foreach ($config['files'] AS $outputFile => $inputFiles) {
		$arr = array();			// ���������Ҫ�ϲ����ļ�
		
		foreach ($inputFiles AS $inputFile) {
			if (is_array($inputFile)) {
				$arr = array_merge($arr, getValidInputfiles($inputFile));
			} else {
				$rs = getValidInputfiles(array($inputFile));
				$arr[] = $rs[0];
			}
		}

		// ѹ���ļ���δѹ���ļ����Ŀ¼����
		$targetFile = ROOT.$outputFile;					// Ŀ���ļ� δѹ����
		// ���ļ�ת�Ƶ���ʽĿ¼��
		$compressFile = ROOT.BUILDDIR.$outputFile;		// ���ܻᱻѹ����
		makeDirs($targetFile);
		makeDirs($compressFile);


		// �ϲ��ļ�
		$str = '';
		foreach($arr AS $filepath) {
			$str .= @file_get_contents($filepath)."\n\n";
		}
		$rs = @file_put_contents($targetFile, $str."\n\n");		// ĩβ���\n\n��ȷ����������д��
		if (!$rs) {
			Console::error("�ļ�����д��ʧ��");
			Console::error("	$targetFile");
			exit();
		}

		
		$outputPathinfo = pathinfo($outputFile);
		
		// �ж��Ƿ���Ҫѹ��
		if (stripos($outputPathinfo['basename'], '.min.') === false) {
			copy($targetFile, $compressFile);
			continue;
		}

		// ��Ҫѹ�����ļ�����
		// �ȱ���һ��δѹ���ĵ�.build
		// copy($targetFile, str_replace('.min.', '.', $compressFile));

		// ѹ���ļ�
		$outputType = $outputPathinfo['extension'];
		if ($outputType == 'js') {
			compressJS($targetFile, $compressFile);
		} else if ($outputType == 'css') {
			compressCss($targetFile, $compressFile);
		}


		// ���ע����Ϣ
		// ע�⣺ֻ���ѹ������ļ����
		$fileContent = @file_get_contents($compressFile);
		if (JSMINCONSOLE && $outputType == 'js') {
			// ɾ��������console���Դ���
			// (?:log|info|warn|assert|debug)
			$fileContent = preg_replace_callback('/console\.log\(.*?\);?/', function($matches){
				Console::warn("����console���������ɾ��");
				Console::warn("	{$matches[0]}");
				return '';
			}, $fileContent);


		// css�ļ���img���Ӱ汾��
		// �汾��Ϊ�ļ��޸ĵ�ʱ���
		} else if (CSSMINIMGVARSION && $outputType == 'css') {
			$fileContent = buildCss($targetFile, $fileContent);
		}

		$fileContent = "/**********************************************\n"
				// ."		�� �䰮���� �����޸Ĵ��ļ� ��\n"
				// ."		=============================\n"
				."	���������� ����ϵǰ��Э���޸�\n"
				."	E-mail:wf@tianqu.com.cn   QQ:434537707\n"
				// ."	File:$outputFile\n"
				// ."	Update:".TIME."\n"
				."***********************************************/\n"
				.$fileContent;
		
		$rs = @file_put_contents($compressFile, $fileContent);
		if (!$rs) {
			Console::error("ѹ���ļ�����д��ʧ��");
			Console::error("	$compressFile");
			exit();
		}
	}
} else {
	exit('Config File has Wrong Content!!');
}



// �����Ϣ
$warnMsg = Console::getWarn();
$errorMsg = Console::getError();
$warnMsgNum = count($warnMsg);
$errorMsgNum = count($errorMsg);

if ($warnMsgNum || $errorMsgNum) {
	if ($warnMsgNum) echo "[Warn]\n".implode("\n", $warnMsg);
	if ($errorMsgNum) echo "[Error]\n".implode("\n", $errorMsg);
	echo "\n";
}











// ��ȡ��Ч�� �ļ�Ŀ¼
// �ڼ�Ҳ���less�ļ����б������
function getValidInputfiles($inputFiles) {
	$arr = array();
	
	foreach($inputFiles AS $filepath) {
		$pathinfo = pathinfo($filepath);
		$type = $pathinfo['extension'];
		$inputFile = SOURCEDIR2.$filepath;

		if (file_exists($inputFile)) {
			if ($type == 'less') {
				// ����less�ļ�
				$outputFile = ROOT.MODULEDIR.$pathinfo['dirname'].'/'.$pathinfo['filename'].'.css';
				makeDirs($outputFile);

				// �ж��ļ��Ƿ���� ���������µ�
				// ����������µ��ļ��������ļ�
				if (tempLess($inputFile, $outputFile)) $arr[] = $outputFile;

			} else {
				$arr[] = $inputFile;
			}
		} else {
			Console::error("�ļ������� ����");
			Console::error("	$inputFile");
		}
	}
	

	return $arr;
}
?>