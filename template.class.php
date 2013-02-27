<?php
/**
 * 解析phptal语法
 */
class TemplateNodeBuilder {
	protected $body = '';
	private $omit = array();

	function __construct($str){
		$doc = new DOMDocument();
		$doc->loadHTML($str);
		// 编码问题
		/*$nodes = $doc -> getElementsByTagName('meta');
		$i = 0;
		$isCharSet = false;
		while($node = $nodes -> item($i++)) {
			if ($node -> hasAttribute('http-equiv') && $node -> hasAttribute('content') && preg_match('/charset=\w/i', $node -> getAttribute('content'))) {
				$isCharSet = true;
			}
		}
		if (!$isCharSet) {
			if (!$doc -> getElementsByTagName('html') -> length) {
				$doc -> insertBefore(new DOMElement('html'));
			}
			if (!$doc -> getElementsByTagName('head') -> length) {
				$doc -> getElementsByTagName('html') -> item(0) -> insertBefore(new DOMElement('head'));
			}
			$elem = new DOMElement('meta');
			$doc -> getElementsByTagName('head') -> item(0) -> insertBefore($elem);
			$elem -> setAttribute('http-equiv', 'Content-Type');
			$elem -> setAttribute('content', 'text/html; charset=utf-8');
		}*/


		$nodes = $doc -> getElementsByTagName('*');

		$i = 0;
		$omits = array();
		while($node = $nodes -> item($i++)) {
			
			// 当变量没有定义的时候，采用的默认值
			// 注意：必须是变量，不能是常量
			// 设置的时候，作用域为括号内
			self::defineVar($node, 'tq:default');
			// 直接定义变量
			self::defineVar($node, 'tq:define');

			// 修改属性值
			// 修改属性方式：= 直接替换或则赋值；+= 增加属性； -=删除属性
			// 判断条件的介入：不使用表达式，可以直接设置PHP变量，来实现attr的改变
			// self::doAttribute($node, 'tq:attr', function($node, $attrVal){});
			

			// 模块化
			self::doAttribute($node, 'tq:init-omit', function($node, $attrVal) use (&$omits){
				$omits[$attrVal] = $node;
			});
			self::doAttribute($node, 'tq:use-omit', function($node, $attrVal){
				$node -> nodeValue = '{tq:omit '.$attrVal.'}';
			});

			// 判断是否需要删除此标签
			self::doAttribute($node, 'tq:block', function($node, $attrVal){
				TemplateNodeBuilder::wrapNode($node, new DOMText('{tq:block '.$attrVal.'}'), new DOMText('{/tq:block}'));
			});
			
			// 设置重复次数
			self::doAttribute($node, 'tq:repeat', function($node, $attrVal){
				TemplateNodeBuilder::wrapNode($node, new DOMText('{tq:repeat '.$attrVal.'}'), new DOMText('{/tq:repeat}'));
			});

			// 替换标签内内容
			self::doAttribute($node, 'tq:replace', function($node, $attrVal){
				$node -> nodeValue = $attrVal;
			});


			if ($node -> hasAttribute('tq:label')) {
				self::noWrapNode($node);
				$i--;	// -1非常重要
			}
		}

		foreach ($omits AS $key => $val) {
			$this -> omit[$key] = $val -> C14N();
		}
		$this -> body = $doc->saveHTML();
	}



	public function getBody() {
		return $this -> body;
	}

	public function getOmit($name){
		return $this -> omit[$name];
	}
	public function hasOmit($name) {
		return isset($this -> omit[$name]);
	}


	public static function createUniqueVarName(){
		static $varName = 666666;
		return '$tq___'.($varName++);
	}



	// 在node前后插入新的node
	public static function wrapNode($refNode, $beforeNode, $afterNode, $outerMode=true) {
		if ($outerMode) {
			$refNode->parentNode->insertBefore($beforeNode, $refNode);
			
			if($refNode->nextSibling) {
				$refNode->parentNode->insertBefore($afterNode, $refNode->nextSibling);
			} else {
				$refNode->parentNode->appendChild($afterNode);
			}
		} else if ($refNode -> hasChildNodes()){
			$refNode -> insertBefore($beforeNode, $refNode -> firstChild);
			$refNode -> appendChild($afterNode);
		}
	}

	// 将外层的node（自己）去掉
	// 注意：会保留里面的内容
	public static function noWrapNode($node){
		if ($node -> hasChildNodes()) {
			while ($myNode = $node -> childNodes-> item(0)) {
				$node -> parentNode -> insertBefore($myNode, $node);
			}
		}
		$node -> parentNode -> removeChild($node);
	}

	// 处理标签中属性的一般流程封装
	public static function doAttribute($node, $attrName, $callback){
		if ($node -> hasAttribute($attrName)) {
			$attrVal = trim($node -> getAttribute($attrName));
			$node -> removeAttribute($attrName);
			if ($attrVal) {
				$callback($node, $attrVal);
			}
		}
	}




	// 定义参数值
	// 带有作用区间
	private static function defineVar($node, $attrName){
		self::doAttribute($node, $attrName, function($node, $attrVal) use ($attrName){
				$arr = explode(';', $attrVal);
				foreach($arr AS $val) {
					$val = trim($val);
					if ($val) {
						$val = explode('=', $val);
						if (isset($val[1])) {
							$val[0] = trim($val[0]);
							$val[1] = trim($val[1]);
							if ($val[0] && $val[1]) {
								$varName = TemplateNodeBuilder::createUniqueVarName();
								TemplateNodeBuilder::wrapNode($node, new DOMText('{'.$attrName.' '.$varName.' '.$val[0].' '.$val[1].'}'), new DOMText('{'.$attrName.'End '.$varName.' '.$val[0].'}'));
							}
						}
					}
				}
			});
	}
}

/**
 * 在使用DOM处理模版前，对文件字符串进行转义
 * 作用：修正编码和不可用的标签
 */
class TemplateNode extends TemplateNodeBuilder {
	private $filePath = '';

	function __construct($file){
		// 处理编码
		$htmlCharset = false;
		$content = file_get_contents($file);
		$content = preg_replace_callback('/<meta +charset=("|\')([\w_-]+)\\1 *\/?>/i', function($matchs) use (&$htmlCharset) {
			$new = '<meta http-equiv="Content-Type" content="text/html; charset='.$matchs[2].'">';
			$htmlCharset = array($matchs[0], $new);
			return $new;
		}, $content);

		// 处理不可用的标签
		$content = preg_replace('/<tq:label/', '<div tq:label', $content);
		$content = preg_replace('/<\/tq:label>/', '</div>', $content);

		parent::__construct($content);


		if ($htmlCharset) {
			$this -> body = str_replace($htmlCharset[1], $htmlCharset[0], $this -> body);
		}

		$this -> filePath = $file;
	}


	public function getFilePath(){
		return $this -> filePath;
	}
}














/**
 * 将smart语法解析成可执行代码
 */
class TemplateHTML {
	private static $HTMLPartParam = 'HTMLPART';		// 定义$htmlpart变量的变量名（也可以是常量）
	private $result = '';
	private $filePath = '';

	public function __construct($file, $str = ''){
		if (!$str) {
			$str = file_get_contents($file);
		}

		$this -> filePath = $file;

		// tq命名空间下的代码转换
		$str = preg_replace('/{tq:block ([^}]+)}/', '<?php if (!'.self::$HTMLPartParam.' || preg_match(\'/\b\'.'.self::$HTMLPartParam.'.\'\b/\', "$1")): ?>', $str);
		$str = preg_replace('/{\/tq:block}/i', '<?php endif; ?>', $str);

		$str = preg_replace('/{tq:repeat ([^}]+)}/', '<?php foreach(range(1, "$1") AS $tq2111111100): ?>', $str);
		$str = preg_replace('/{\/tq:repeat}/', '<?php endforeach; ?>', $str);

		$str = preg_replace('/{tq:default ([^ ]+) +([^ ]+) ([^}]+)}/', '<?php if (!isset($2)): $2 = "$3"; $1 = true; endif; ?>', $str);
		$str = preg_replace('/{tq:defaultEnd ([^ ]+) ([^}]+)}/', '<?php if (isset($1)): unset($2); endif; ?>', $str);

		$str = preg_replace('/{tq:define ([^ ]+) +([^ ]+) ([^}]+)}/', '<?php if (isset($2)): $1 = $2; endif; $2 = "$3"; ?>', $str);
		$str = preg_replace('/{tq:defineEnd ([^ ]+) ([^}]+)}/', '<?php if (isset($1)): $2 = $1; else: unset($2); endif; ?>', $str);
		$str = preg_replace_callback('/{tq:omit ([^}]+)}/', function($matchs)  use ($file){
			if (strpos($matchs[1], '#') !== false) {
				$var = explode('#', $matchs[1]);
				return '<?php include parseOmit("'.HTMLDIR.str_replace('[PATH]', HTML_REDIR, $var[0]).'", "'.$var[1].'"); ?>';
			} else {
				return '<?php include parseOmit("'.$file.'", "'.$matchs[1].'"); ?>';
			}
		}, $str);


		// 普通的转化
		/*$str = preg_replace('/{php}/i', '<?php', $str);
		$str = preg_replace('/{\/php}/i', '?>', $str);
		*/

		$str = preg_replace('/{foreach ([^ ]+) AS ([^}]+)}/i', '<?php if (is_array($1)): foreach ($1 AS $2):', $str);
		$str = preg_replace('/{\/foreach}/i', 'endforeach; endif;', $str);

		$str = preg_replace_callback('/{(if|elseif) ([^}]+)}/i', function($matchs){
			// 由于DOM编译过程会自动转化<>这些便签，所以需要转化一下才可以使用
			return '<?php '.$matchs[1].' ('.htmlspecialchars_decode($matchs[2]).'): ?>';
		}, $str);
		$str = preg_replace('/{else}/i', '<? else: ?>', $str);
		$str = preg_replace('/{\/if}/i', '<? endif; ?>', $str);

		$this -> result = $str;
	}

	public function getResult(){
		return $this -> result;
	}

	public function getFilePath(){
		return $this -> $filePath;
	}
}









/**
 * 解析模版，并缓存数据
 */
class TemplateParser {
	static private $cache4node = array();
	static private $cache4html = array();

	static public function parse($file) {
		self::parse4node($file);
		return self::parse4HTML($file);
	}

	static public function parse4node($file) {
		if (isset(self::$cache4node[$file])) {
			return self::$cache4node[$file];
		}

		$ob = new TemplateNode($file);
		self::$cache4node[$file] = $ob;
		return $ob;
	}
	static public function parse4HTML($file, $omit = '') {
		$key = CacheTemplate::getCachePath($file, $omit);
		if (isset(self::$cache4html[$key])) {
			return self::$cache4html[$key];
		}
		if ($omit) {
			$ob = new TemplateHTML($file, self::$cache4node[$file] -> getOmit($omit));
		} else {
			$ob = new TemplateHTML($file, self::$cache4node[$file] -> getBody());
		}
		
		$str = $ob -> getResult();
		self::$cache4html[$key] = $str;		// 值$str保存了也没啥用
		return $str;
	}

	static public function getHTML($key) {
		return self::$cache4html[$key];
	}

	static public function hasHTML($key) {
		return isset(self::$cache4html[$key]);
	}
	static public function hasNode($key) {
		return isset(self::$cache4node[$key]);
	}
	static public function getNode($key) {
		return self::$cache4node[$key];
	}
}



/**
 * 处理模版缓存
 */
class CacheTemplate {
	static public function load($file, $omit = '') {
		return file_get_contents(self::getCachePath($file, $omit));
	}
	static public function getCachePath($file, $omit ='') {
		$dirPrefix = strlen(HTMLDIR);
		$file = HTMLDIR . CACHEHTML_REDIR . str_replace('/', '~', substr($file, $dirPrefix));

		return $omit ? $file . '$' . $omit . '.tpl' : $file.'.tpl';
	}
	static public function save($tplFile, $content) {
		makeDirs($tplFile);
		return file_put_contents($tplFile, $content);
	}
	static public function update($file, $getContentCallback, $omit = '') {
		$tplFile = self::getCachePath($file, $omit);
		// self::save($tplFile, $getContentCallback($file, $tplFile, $omit));
		if (NOCACHE || !file_exists($tplFile) || filemtime($tplFile) < filemtime($file)) {
			self::save($tplFile, $getContentCallback($file, $tplFile, $omit));
		}

		return $tplFile;
	}
}



function parseTemplate($file) {
	if (!file_exists($file)) exit($file.' Not Find!!');
	return CacheTemplate:: update($file, function($file){
		return TemplateParser::parse($file);
	});
}

function parseOmit($file, $omit){
	if (!file_exists($file)) exit($file.' Not Find!!');
	if (!$omit) exit('Omit Param Missing');
	return CacheTemplate::update($file, function($file, $tplFile, $omit){
		if (!TemplateParser::hasHTML($tplFile)) {
			if (!TemplateParser::hasNode($file)) {
				parseTemplate($file);
			}
			$ob = TemplateParser::getNode($file);
			if (!$ob -> hasOmit($omit)) exit($omit . ' Not Find In '. $file);
			$content = TemplateParser::parse4HTML($file, $omit);
			
			// 出现莫名的'&#xD;'
			return str_replace('&#xD;', '', $content);
		}
	}, $omit);
}
?>