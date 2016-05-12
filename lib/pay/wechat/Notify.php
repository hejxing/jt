<?php
/**
 * @Copyright jentian.com
 * Auth: ax@jentian.com
 * Create: 2015/12/8 14:12
 */

namespace jt\lib\pay\wechat;

use jt\utils\Url;

require __DIR__ . '/WxPay.Api.php';
require __DIR__ . '/WxPay.Notify.php';

/**
 * 微信JsApi支付通知回调类
 * Created by Rocky
 * Date: 2015/9/3 17:35
 */
class Notify extends \WxPayNotify
{
    protected $targetType = '';
    protected $notify_url = '';
    protected $data       = ['memo' => 'CSMALL', 'id' => ''];
    protected $task   = null;

    public function __construct(array $config, $targetType)
    {
        $this->targetType = $targetType;

        \WxPayConfig::setConfig($config);
    }

    /**
     * 验证通过后调用此处
     *
     * @param array  $data
     * @param string $msg
     *
     * @return bool
     */
    public function NotifyProcess($data, &$msg)
    {
        try{
            $result = call_user_func($this->task, $data);

            return boolval($result);
        }catch (\WxPayException $e){
            $msg = $e->errorMessage();
        }

        return false;
    }

    /**
     * app支付回调
     * @param callable $callback
     */
    public function app(callable $callback){
        $this->task = $callback;
        $this->Handle();
    }
}