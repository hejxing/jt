<?php
/**
 * 模板类
 */
namespace jt;

require JT_FRAMEWORK_ROOT . '/jt/lib/smarty3/Smarty.class.php';
use Config;

class Template extends \Smarty
{
    /**
     * 模板根目录
     *
     * @type string
     */
    public $template_dir = '/template';
    public $left_delimiter = '{{';
    public $right_delimiter = '}}';
    /**
     * 模板后缀
     *
     * @type string
     */
    protected $suffix = '.tpl';
    /**
     * 插入到模板中的插件
     *
     * @type array
     */
    protected $plugins = [];
    /**
     * 所有模板都会用到的基础数据
     *
     * @type array
     */
    protected $baseData = [];
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
     *
     * @param array $config
     */
    public function __construct($config)
    {
        $this->setConfigs($config);
        parent::__construct();
        foreach ($this->plugins as $name => $plugin) {
            $this->assignGlobal($name, new $plugin());
        }
    }

    /**
     * 设置模板相对路径，模板主题+模板文件
     *
     * @param string $template
     * @return string
     */
    private function seekTemplateFile($template)
    {
        return $this->template_dir . $template . $this->suffix;//Config::TPL_SUFFIX;
    }

    /**
     * 渲染文件
     *
     * @param string $template
     * @param array  $data
     * @param string $compile_id
     * @return string
     */
    public function render($template, array $data, $compile_id = null)
    {
        $templateFile = $this->seekTemplateFile($template);

        return $this->fetch($templateFile, array_merge($this->baseData, $data), $compile_id);
    }

    /**
     * smarty配置
     *
     * @param array $config 配置选项
     */
    private function setConfigs($config)
    {
        $this->setCompileDir(Config::RUNTIME_PATH_ROOT . '/smarty/compile');
        $this->setCacheDir(Config::RUNTIME_PATH_ROOT . '/smarty/cache');
        foreach ($config as $name => $value) {
            $this->$name = $value;
        }
        if(isset($config['template_dir'])){
            $this->setTemplateDir($config['template_dir']);
        }
    }

    /**
     * 加载js文件
     *
     * @param string $string 多个js文件用逗号','分隔
     * @example PHP：\jtlib\tools\Tools::addJs('#jsLibrary#jQuery-1.7.1.min.js,common.js')
     * @example 模板：{$tools->addJs('#jsLibrary#jQuery-1.7.1.min.js,common.js')}，在引入header.html模板文件前调用
     * @return NULL||string
     */
    public static function addJs($string)
    {
        if (!$string) {
            return;
        }
        $arr = explode(',', $string);
        foreach ($arr as $value) {
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
    public static function addCss($string)
    {
        if (!$string) {
            return;
        }
        $arr = explode(',', $string);
        foreach ($arr as $value) {
            $value = trim($value);
            if (preg_match('/^http/', $value)) {
                self::$css[] = $value;
            }else {
                self::$css[] = Config::$path['css'] . $value;
            }
        }
    }

    /**
     * 将加载的css及js写入到header中
     *
     * @return string
     */
    public static function writeCssJs()
    {
        $html = '';
        foreach (self::$css as $src) {
            $html .= "\t\t" . '<link type="text/css" rel="stylesheet" href="' . $src . '">' . "\n";
        }
        //$main = '';
        //if(count(self::$js)){
        //	$main = ' data-main="'.implode(', ', self::$js).'"';
        //}
        foreach (self::$js as $src) {
            $html .= "\t\t" . '<script src="' . $src . '"></script>' . "\n";
        }

        return $html;
    }
}