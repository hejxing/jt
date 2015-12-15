<?php
/**
 * 模板类
 */
namespace jt;

require CORE_ROOT . '/jt/libs/smarty3/Smarty.class.php';

use Config;

class Template extends \Smarty{
	/**
	 * 保存js的html
	 *
	 * @var string
	 */
	private static $js = [];

	/**
	 * 保存css的html
	 *
	 * @var string
	 */
	private static $css = [];

	/**
	 * 构造方法
	 */
	public function __construct(){
		$this->setConfigs();
		parent::__construct();
	}

	/**
	 * 设置模板相对路径，模板主题+模板文件
	 *
	 * @param string $template
	 * @return string
	 */
	private function seekTemplateFile($template){
		return $template . Config::TPL_SUFFIX;
	}

	/**
	 * 渲染文件
	 *
	 * @param string $template
	 * @param array  $data
	 * @param string $compile_id
	 * @return string
	 */
	public function render($template, array $data, $compile_id = null){
		$templateFile = $this->seekTemplateFile($template);
		$data         = \array_merge(Config::$webDefaultData, $data);

		return $this->fetch($templateFile, $data, $compile_id);
	}

	/**
	 * smarty配置
	 */
	private function setConfigs(){
		$this->setTemplateDir(Config::TPL_PATH_ROOT);
		$this->setCompileDir(Config::RUNTIME_PATH_ROOT . '/smarty/compile');
		$this->setCacheDir(Config::RUNTIME_PATH_ROOT . '/smarty/cache');

		$this->caching         = Config::TPL_CACHING;
		$this->debugging       = Config::TPL_DEBUGGING;
		$this->force_compile   = Config::TPL_FORCE_COMPILE;
		$this->cache_lifetime  = Config::TPL_CACHE_LIFETIME;
		$this->left_delimiter  = Config::TPL_LEFT_DELIMITER;
		$this->right_delimiter = Config::TPL_RIGHT_DELIMITER;
	}

	/**
	 * 加载js文件
	 *
	 * @param string $string 多个js文件用逗号','分隔
	 * @example PHP：\jtlib\tools\Tools::addJs('#jsLibrary#jQuery-1.7.1.min.js,common.js')
	 * @example 模板：{$tools->addJs('#jsLibrary#jQuery-1.7.1.min.js,common.js')}，在引入header.html模板文件前调用
	 * @return NULL||string
	 */
	public static function addJs($string){
		if(!$string){
			return;
		}
		$arr = explode(',', $string);
		foreach($arr as $value){
			self::$js[] = trim($value);
		}
	}

	/**
	 * 加载css文件
	 *
	 * @param string $string css文件，多个样式文件用逗号','分隔
	 * @example PHP：\jtlib\tools\Tools::addCss('global.css,common.css')
	 * @example 模板：{$tools->addCss('global.css,common.css')}，在引入header.html模板文件前调用
	 * @return NULL||string
	 */
	public static function addCss($string){
		if(!$string){
			return;
		}
		$arr = explode(',', $string);
		foreach($arr as $value){
			$value = trim($value);
			if(preg_match('/^http/', $value)){
				self::$css[] = $value;
			}else{
				self::$css[] = Config::$path['css'] . $value;
			}
		}
	}

	/**
	 * 将加载的css及js写入到header中
	 *
	 * @return string
	 */
	public static function writeCssJs(){
		$html = '';
		foreach(self::$css as $src){
			$html .= "\t\t" . '<link type="text/css" rel="stylesheet" href="' . $src . '">' . "\n";
		}
		//$main = '';
		//if(count(self::$js)){
		//	$main = ' data-main="'.implode(', ', self::$js).'"';
		//}
		foreach(self::$js as $src){
			$html .= "\t\t" . '<script src="' . $src . '"></script>' . "\n";
		}

		return $html;
	}
}