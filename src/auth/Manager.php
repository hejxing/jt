<?php
/**
 * Auth: ax
 * Date: 2016/11/16 11:32
 */

namespace jt\auth;


use jt\Action;
use jt\Controller;
use jt\utils\mind_tpl\Mind;

class Manager extends Action
{
    protected $projectName = '功能配置';

    /**
     * 返回权限列表
     */
    public function getGrantList()
    {
        $list   = [];
        $router = $this->loadRouter();
        foreach($router['action'] as $classIndex => $classInfo){
            $list[$classIndex] = $this->map(['auth' => 'Auth', 'create' => 'Create', 'title', 'desc', 'notice'], $classInfo);
            foreach($classInfo['methods'] as $methodIndex => $method){
                $list[$classIndex]['methods'][$methodIndex] = $this->map([
                    'key' => 'uri',
                    'methods',
                    'auth',
                    'affix',
                    'name',
                    'desc',
                    'notice'
                ], $method);
            }
        }

        return $list;
    }

    public function saveFeatureList()
    {
        return true;
    }

    private function loadRouter()
    {
        $parseFile = RUNTIME_PATH_ROOT.'/cache/router/'.MODULE.'.php';

        return require($parseFile);
    }

    protected function page()
    {
        $this->setMime('html');
        $this->out('projectName', $this->projectName);

        $this->out('host', $_SERVER['HTTP_HOST']);
        $this->setTpl('/grant/index');
    }

    public static function __init($className)
    {
        if($className === __CLASS__){
            Controller::current()->getResponder()->setTplEngine(new Mind([
                'basePath' => JT_FRAMEWORK_ROOT.'/jt/view/tpl'
            ]));
        }
    }
}