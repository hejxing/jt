<?php
/**
 * Auth: ax
 * Date: 2015/11/24 17:38
 */

namespace jt\auth;

use jt\Controller;
use jt\Session;

/**
 * 权限控制基类
 *
 * @package jt
 */
abstract class Auth
{
    const LOGIN_SUCCESS      = 20;//登录成功
    const LOGIN_PASSWORD_ILL = 30;//密码错误
    const LOGIN_BLOCK        = 40;//禁止登录
    const LOGIN_NEED_VERIFY  = 43;//需要输入验证码才能登录
    const LOGIN_FAIL         = 50;//登录失败
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
     * @var string 调用处传递过来的标记,可以根据标记实现些特殊需求
     */
    protected $mark = null;
    /**
     * @var string 配置类
     */
    protected $configClass = 'AuthConfigurator';
    /**
     * @type Operator 操作员
     */
    private $operator = null;
    /**
     * @var AuthConfigurator
     */
    private $configurator = null;
    /**
     * @var array 授权模式
     */
    protected static $grantMode = ['*' => 1, 'auto' => 0];

    /**
     * 执行权限检查
     *
     * @return int 200,401,403
     */
    abstract protected function auth();

    /**
     * 访问结果过滤
     *
     * @return int
     */
    abstract protected function filter();

    /**
     * 初始化操作员信息
     */
    protected function createOperator()
    {
        $this->setOperator(new Operator('undefined', '', ''));
    }

    /**
     * 设置操作员
     *
     * @param \jt\auth\Operator $operator
     */
    protected function setOperator(Operator $operator)
    {
        $this->operator = $operator;
    }

    /**
     * 创建授权界面管理器
     */
    protected function createConfigurator()
    {
        $this->configurator = new $this->configClass();
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
        if(Controller::current()->getMime() === 'html'){
            $this->loginPage();
        }else{
            $this->action->fail('未登录或登录失败，请重登录', 401, ['loginUrl' => $this->loginUrl]);
        }
    }

    /**
     * 未登录的跳转页面
     */
    protected function loginPage()
    {
        $this->action->redirect($this->loginUrl.'?ref='.$_SERVER['REQUEST_URI']);
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
        switch($code){
            case 200:
                return true;
                break;
            case 401:
                $this->notLogin();
                break;
            case null:
            case 403:
                $this->inExceed();
                break;
            default:
                $this->action->status($code, '', [], false);
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
        switch($code){
            case 200:
                return true;
                break;
            case null:
            case 403:
                $this->outExceed();
                break;
            default:
                $this->action->status($code, '', [], false);
        }

        return false;
    }

    /**
     * @param $data
     * @return string
     */
    public static function hold($data)
    {
        $token            = Session::start(true, '', true);
        $data['token']    = $token;
        $_SESSION['user'] = $data;
        Controller::current()->getAction()->header('token', $token);

        return $token;
    }

    /**
     * 获取当前登录用户信息
     *
     * @return array
     */
    public static function userInfo()
    {
        Session::start();

        return $_SESSION['user']??[];
    }

    /**
     * 判断是否登录
     *
     * @return bool
     */
    public static function isLogin()
    {
        return isset($_SESSION['user']);
    }

    /**
     * 获取操作员
     *
     * @return \jt\auth\Operator
     */
    public function getOperator()
    {
        if($this->operator === null){
            $this->createOperator();
        }

        return $this->operator;
    }

    /**
     * 获取授权配置器，用于管理授权相关配置信息
     */
    public function getConfigurator()
    {
        if($this->configurator === null){
            $this->createConfigurator();
        }

        return $this->configurator;
    }

    /**
     * @param string $mark
     */
    public function setMark($mark)
    {
        $this->mark = $mark;
    }

    /**
     * 获取授权模式，生成权限表时需要
     *
     * @param string $mark
     *
     * @return int 0:无需授权 1:直接授权 2:带授权选项
     */
    public static function getGrantMode($mark)
    {
        if($mark && isset(static::$grantMode[$mark])){
            return static::$grantMode[$mark];
        }

        return static::$grantMode['*']??0;
    }

    /**
     * 获取授权参数
     *
     * @param $mark
     * @return array
     */
    public static function getGrantParam($mark)
    {
        return [];
    }
}