<?php
/**
 * Created by PhpStorm.
 * User: hejxi
 * Date: 2015/11/24
 * Time: 17:38
 */

namespace jt;

/**
 * 权限控制基类
 *
 * @package jt
 */
abstract class Auth
{
    /**
     * @type \jt\Action
     */
    protected $action;
    /**
     * @type array
     */
    protected $ruler;
    /**
     * @type array
     */
    protected $param;
    /**
     * 登录页地址
     *
     * @type string
     */
    protected $loginUrl = '/login';

    /**
     * 执行权限检查
     *
     * @return int 200,401,402
     */
    abstract public function auth();

    abstract public function filter();

    /**
     * Auth constructor.
     */
    public function __construct()
    {
        $controller   = Controller::current();
        $this->action = $controller->getAction();
        $this->ruler  = $controller->getRuler();
        $this->param  = $controller->getParam();
    }

    /**
     * 处理未登录事件
     */
    protected function notLogin()
    {
        $this->action->out('loginUrl', $this->loginUrl);
        $this->action->out('ref', $_SERVER['REQUEST_URI']);
        $this->action->status(401.1, [], false);
    }

    /**
     * 处理越权事件
     */
    protected function exceed()
    {
        $this->action->status(401.4, [], false);
    }

    /**
     * 检查是否有权访问当前资源
     *
     * @return bool
     */
    public function check()
    {
        $code = $this->auth();
        switch ($code) {
            case 200:
                return true;
                break;
            case 401.1:
                $this->notLogin();
                break;
            case 401.4:
                $this->exceed();
                break;
            default:
                $this->action->status($code, [], false);
        }

        return false;
    }
}