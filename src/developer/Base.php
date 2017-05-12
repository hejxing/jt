<?php

/**
 * User: ax
 * Date: 2016/11/26 12:27
 */
namespace jt\developer;

use jt\Action;

class Base extends Action
{
    /**
     * @type string 项目标识
     */
    protected $projectCode = '';
    /**
     * @type string 项目名称
     */
    protected $projectName = '项目开发管理';
    /**
     * @var string 功能菜单文件所在位置
     */
    protected $grantMenuListFile = 'auth/grantMenuList.php';
    /**
     * @var int 功能菜单最大嵌套层级
     */
    protected $maxMenuLevel = 2;
    /**
     * @var bool 是否允许功能结点不出现在组织末端
     */
    protected $mixing = false;

    private $configList = [
        'projectCode',
        'projectName',
        'maxMenuLevel',
        'grantMenuListFile',
        'mixing'
    ];

    protected function doAction($uri)
    {
        $this->quiet();
        $controller = new Boot([
            'SCRIPT_NAME'    => $uri,
            'REQUEST_METHOD' => $this->controller->getRequestMethod()
        ]);

        $config = [];
        foreach($this->configList as $name){
            $config[$name] = $this->$name;
        }

        $controller->setConfig($config);

        $controller->run();
    }

    public function setConfig($config)
    {
        foreach ($config as $name => $value) {
            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }
}