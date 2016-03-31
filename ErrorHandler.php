<?php
/**
 * Created by PhpStorm.
 * User: hejxing
 * Date: 2015/5/19
 * Time: 2:57
 */

namespace jt;


class ErrorHandler extends Action
{
    /**
     * 找不到对应的ACTION或METHOD
     */
    public function _404()
    {
        header('Status: 404');
        $this->out('title', '404 页面没找到');
        Controller::current()->setTemplate('error/404');
    }

    /**
     * 以不允许的M动作访问
     */
    public function _405()
    {
        header('Status: 405');
    }

    /**
     * 用户未登录
     */
    public function _401()
    {
        header('Status: 401');
        Controller::current()->setTemplate('error/401');
    }

    /**
     * 业务层有抛出错误
     */
    public function _fail()
    {
        $code = $this->getFromHead('code');
        if ($code) {
            $this->out('code', $code);
        }
        $msg = $this->getFromHead('msg');
        if ($msg) {
            $this->out('code', $msg);
        }
        $this->out('title', '操作失败');
        Controller::current()->setTemplate('error/fail');
    }

    /**
     * 不明错误
     */
    public function unknown_error()
    {
        header('Status: 500');
    }

    /**
     * 不明错误
     */
    public function unknown_fail()
    {
        //header('Status: 500');
    }

    /**
     * 客户端错误
     */
    public function client_error()
    {

    }
}