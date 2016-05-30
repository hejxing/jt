<?php
/**
 * Created by PhpStorm.
 * User: ax@jentian.com
 * Date: 2015/11/24
 * Time: 17:38
 */

namespace jt\auth;

use jt\Controller;
use jt\Session;
use jt\Action;

/**
 * 权限控制基类
 *
 * @package jt
 */
abstract class Auth
{
    const LOGIN_SUCCESS      = 1;
    const LOGIN_PASSWORD_ILL = 2;
    const LOGIN_BLOCK        = 3;
    const LOGIN_FAIL         = 4;
    const LOGIN_OUT          = 5; //退出登录
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
     * 操作员
     *
     * @type null
     */
    private $operator = null;

    /**
     * 执行权限检查
     *
     * @return int 200,401,403
     */
    abstract public function auth();

    /**
     * 访问结果过滤
     *
     * @return int
     */
    abstract public function filter();

    /**
     * 初始化操作员信息
     *
     * @return \jt\auth\Operator
     */
    protected function createOperator()
    {
        return new Operator('undefined', '', '');
    }

    /**
     * 写未授权访问的空接口
     */
    public function writeInExceedLog()
    {
    }

    /**
     * 写未授权访问的空接口
     */
    public function writeOutExceedLog()
    {
    }

    /**
     * 写访问成功日志的空接口
     */
    public function writeSuccessLog()
    {
    }

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
        $this->action->out('loginUrl', $this->loginUrl . '?ref=' . $_SERVER['REQUEST_URI']);
        $this->action->fail('未登录或登录失败，请重登录', 401);
    }

    /**
     * 处理越权事件
     */
    protected function inExceed()
    {
        $this->action->fail('无权使用该功能', 403);
    }

    /**
     * 处理越权事件
     */
    protected function outExceed()
    {
        $this->action->fail('无权访问该资源', 403);
    }

    /**
     * 检查是否有权访问当前资源
     *
     * @return bool
     */
    final public function inCheck()
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
                $this->writeInExceedLog();
                $this->inExceed();
                break;
            default:
                $this->action->status($code, [], false);
        }

        return false;
    }

    /**
     * 访问完成时做的检查
     *
     * @return mixed
     */
    final public function outCheck()
    {
        $code = $this->filter();
        switch ($code) {
            case 200:
                $this->writeSuccessLog();

                return true;
                break;
            case 403:
                $this->writeOutExceedLog();
                $this->outExceed();
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
        $token         = Session::start(true);
        $data['token'] = $token;
        $_SESSION      = $data;
        (new Action())->header('token', $token);

        return $token;
    }

    /**
     * 获取操作员
     *
     * @return \jt\auth\Operator
     */
    public function getOperator()
    {
        if ($this->operator === null) {
            $this->operator = $this->createOperator();
        }

        return $this->operator;
    }
}