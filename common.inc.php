<?php
// 三个目录形式 
// redir dir path file

// 设置时区
date_default_timezone_set("PRC");

define('DEBUG', false);		// 受到影响的内容有：模版解析时最初的HTML parse => convert.php
							// 不使用模版的缓存（每次都编译模版生成缓存）=> template.class.php
							// 当不存在配置文件时，直接提示，而不是显示可能存在的html文件源码
							// convert.php生成的HTML带有当前时间戳
							// build.php true的时候，会生成所有的文件，而不管文件的修改时间


// 外部调用程序及JAR路径
define('JAVA', 'JAVA');
define('JSC', '"%ClosureCompiler%compiler.jar"');
define('CSSC', '"%YUIcompressor%yuicompressor.jar"');
define('LESSC', 'LESSC');


// 服务器域名根目录所对应的文件夹目录
define('DOMAINROOT', 'w:/');


// 注意：相对的是域名根目录
define('LESSFILE', '//www.test.com:3316/Less/myLess.js');
// 配置文件相对HTML根目录的相对路径
// 修改此参数之后，注意修改build文件中的ROOT目录
define('CONFIGPATH', '.source/.buildconfig');
define('PARAMPATH', '.temp/.paramconfig');
define('COOKIENAME4HTMLDIR', 'WFHTMLDIR');


define('SOURCE_REDIR', '.source/');		// 存放开发文件的目录
define('BUILD_REDIR', '.temp/.build/');			// 存放生成文件的目录
define('MODULE_REDIR', '.temp/.module/');	// 存放生成文件时临时导出文件的目录

define('HTML_REDIR', '.source/HTML/');
define('CACHEHTML_REDIR', '.temp/.HTML/');


// 压缩文件的配置
define('CSSMINIMGVARSION', true);	// 针对css中图片启动文件版本后缀


// 用来生成版本号 以及文件是否需要缓存的判断
define('TIME', time());



require 'common.func.php';
?>