<?php
/**
 * User: ax
 * Date: 2015/5/19 2:57
 */

namespace jt;


class ErrorHandler extends Action
{
    /**
     * 找不到对应的ACTION或METHOD
     *
     * @param string $code
     * @param string $msg
     */
    public function _404($code = '', $msg = '')
    {
        header('Status: 404');
        $this->header('code', $code, self::FILL_IGNORE_EMPTY);
        $this->header('msg', $msg, self::FILL_IGNORE_EMPTY);
        $this->out('title', '404 页面没找到');
        $this->out('code', $code, self::FILL_IGNORE_EMPTY);
        $this->out('msg', $msg, self::FILL_IGNORE_EMPTY);
        Controller::current()->setTemplate('error/404');
    }

    /**
     * 以不允许的M动作访问
     *
     * @param string $code
     * @param string $msg
     */
    public function _405($code = '', $msg = '')
    {
        $this->header('code', $code, self::FILL_IGNORE_EMPTY);
        $this->header('msg', $msg, self::FILL_IGNORE_EMPTY);
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
     *
     * @param string $code
     * @param string $msg
     */
    public function _fail($code = '', $msg = '')
    {
        $this->header('code', $code?: $this->getFromHead('code'), self::FILL_IGNORE_EMPTY);
        $this->header('msg', $msg?: $this->getFromHead('msg'), self::FILL_IGNORE_EMPTY);
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