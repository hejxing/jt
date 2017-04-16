<?php
/**
 * Auth: ax
 * Date: 2016/11/26 15:22
 */

namespace jt\developer;


use jt\compile\router\Router;
use jt\Controller;
use jt\Error;
use jt\utils\mind_tpl\Mind;

class Boot extends Controller
{
    /**
     * @var string 模板目录
     */
    const TPL_DIR = 'build';
    /**
     * @var string 模块名称
     */
    const MODULE_NAME = 'jt_framework_developer';

    protected $config = [];

    protected static function loadRouter()
    {
        $module = self::MODULE_NAME;
        if(isset(self::$routerMapPool[$module])){
            return self::$routerMapPool[$module];
        }
        $routerMapFile = RUNTIME_PATH_ROOT.'/router/'.$module.'.php';
        $root          = __DIR__;
        //加载不成功则生成
        if(RUN_MODE === 'develop'){ //生成路由
            $result = Router::general($routerMapFile, $root, $module);
        }else{ //加载路由
            $result = include($routerMapFile);
            if($result === false){ //生成并保存
                $result = Router::general($routerMapFile, $root, $module);
            }
        }

        if(!is_array($result)){
            Error::fatal('LoadRouterMapFail', '加载或生成路由表失败');
        }
        self::$routerMapPool[$module] = $result;

        return $result;
    }

    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * 加载并实例化接口
     *
     * @param string $class Action Class
     * @param string $method
     * @return bool
     */
    protected function loadAction($class, $method)
    {
        $res = parent::loadAction($class, $method);
        if($res && method_exists($this->action, 'setConfig')){
            $this->action->setConfig($this->config);
        }

        $this->getResponder()->setTplEngine(new Mind([
            'basePath' => __DIR__.'/view/'.self::TPL_DIR,
            'suffix'   => '.html'
        ]));

        if($this->getMime() === 'html'){
            $this->action->out('baseHref', $this->getBaseHref());
            $this->action->out('host', $_SERVER['HTTP_HOST']);
        }

        return $res;
    }

    /**
     * 获取当前相对地址
     *
     * @return string
     */
    public function getBaseHref()
    {
        $uri = $this->getRequestPath();
        $uri = substr($uri, strpos($uri, '/', 1));

        $scriptName = $_SERVER['SCRIPT_NAME'];
        $baseHref   = substr($scriptName, 0, strpos($scriptName, $uri)).'/';

        return $baseHref;
    }
}