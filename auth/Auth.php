<?php
/**
 * Created by PhpStorm.
 * User: hejxi
 * Date: 2015/11/24
 * Time: 17:38
 */

namespace jt\auth;

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
     * @return int 200,401,403
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
        $this->action->out('loginUrl', $this->loginUrl.'?ref='.$_SERVER['REQUEST_URI']);
        $this->action->fail('未登录或登录失败，请重登录', 401);
    }

    /**
     * 处理越权事件
     */
    protected function exceed()
    {
        $this->action->fail('无权使用该功能或访问该资源', 403);
    }

    /**
     * 检查是否有权访问当前资源
     *
     * @return bool
     */
    final public function check()
    {
        $code = $this->auth();
        switch ($code) {
            case 200:
                return true;
                break;
            case 401:
                $this->notLogin();
                break;
            case 403:
                $this->exceed();
                break;
            default:
                $this->action->status($code, [], false);
        }

        return false;
    }

    /**
     * @param $data
     * @return string
     */
    protected static function hold($data)
    {
        $token = Session::start(true);
        $data['token'] = $token;
        $_SESSION = $data;
        (new Action())->header('token', $token);

        return $token;
    }
}