<?php
/**
 * @Copyright csmall.com
 * Auth: ax@csmall.com
 * Create: 2015/12/8 14:12
 */

namespace jt\lib\pay\alipay;

require __DIR__.'/alipay_notify.class.php';

/**
 * 支付宝支付回调
 * Created by Rocky
 * Date: 2015/9/3 17:35
 */
class Notify extends \AlipayNotify
{
    /**
     * 验证通过后调用此处
     *
     * @param array  $data
     * @param string $msg
     *
     * @return bool
     */
    public function __construct()
    {
        require_once(__DIR__.'/alipay.config.php');
        parent::__construct($alipay_config);
    }

    public function NotifyProcess()
    {
        return $this->verifyNotify();

    }

    /**
     * app支付回调
     *
     * @param callable $callback
     */
    public function app(callable $callback)
    {
        $this->task = $callback;
        $this->Handle();
    }
}