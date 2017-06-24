<?php
/**
 * Auth: ax
 * Created: 2017/5/3 1:35
 */

namespace jt\log;

abstract class Writer
{
    /**
     * @var array
     */
    protected $info = [
        'method'   => '',
        'url'      => '',
        'action'   => '',
        'header'   => '',
        'cookie'   => '',
        'query'    => '',
        'body'     => '',
        'response' => '',
        'version'  => '',
        'ip'       => ''
    ];
    /**
     * @var \jt\auth\Operator
     */
    protected $operator = null;

    public function __construct(array $info)
    {
        $this->info = array_replace($this->info, $info);
    }

    public function set($name, $value){
        $this->info[$name] = $value;
    }

    /**
     * 写未授权访问的空接口
     */
    abstract public function inExceed();

    /**
     * 写未授权访问的空接口
     */
    abstract public function outExceed();

    /**
     * 写访问成功日志的空接口
     */
    abstract public function success();

    /**
     * 写访问失败日志
     */
    abstract public function failLog();

    /**
     * 未登录直接访问的情况
     */
    abstract public function notLogin();
}