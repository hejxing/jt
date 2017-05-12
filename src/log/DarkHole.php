<?php

/**
 * Auth: ax
 * Created: 2017/5/3 1:35
 */
namespace jt\log;

class DarkHole extends Writer
{
    /**
     * 写未授权访问的空接口
     */
    public function inExceed()
    {
        // TODO: Implement inExceed() method.
    }

    /**
     * 写未授权访问的空接口
     */
    public function outExceed()
    {
        // TODO: Implement outExceed() method.
    }

    /**
     * 写访问成功日志的空接口
     */
    public function success()
    {
        // TODO: Implement success() method.
    }

    /**
     * 写访问失败日志
     */
    public function failLog()
    {
        // TODO: Implement failLog() method.
    }

    /**
     * 未登录直接访问的情况
     */
    public function notLogin()
    {
        // TODO: Implement notLogin() method.
    }
}