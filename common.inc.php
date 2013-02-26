<?php
// 三个目录形式 
// redir dir path file

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
define('LESSFILE', '//www.test.com:3316/Less/less-1.3.3.js');
// 配置文件相对HTML根目录的相对路径
// 修改此参数之后，注意修改build文件中的ROOT目录
define('CONFIGPATH', '.source/.buildconfig');
define('PARAMPATH', '.temp/.paramconfig');
define('COOKIENAME4HTMLDIR', 'WFHTMLDIR');



define('SOURCE_REDIR', '.source/');		// 存放开发文件的目录
define('BUILD_REDIR', '.temp/.build/');			// 存放生成文件的目录
define('MODULE_REDIR', '.temp/.module/');	// 存放生成文件时临时导出文件的目录

define('NOCACHE', false);					// 不使用缓存（每次都编译模版生成缓存）
define('HTML_REDIR', '.source/HTML/');
define('CACHEHTML_REDIR', '.temp/.HTML/');



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


require 'common.func.php';
?>