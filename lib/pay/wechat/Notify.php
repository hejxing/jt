<?php
/**
 * @Copyright jentian.com
 * Auth: hejxi
 * Create: 2015/12/8 14:12
 */

namespace jt\lib\pay\wechat;

require __DIR__ . '/WxPay.Api.php';
require __DIR__ . '/WxPay.Notify.php';

/**
 * 微信JsApi支付通知回调类
 * Created by Rocky
 * Date: 2015/9/3 17:35
 */
class Notify extends \WxPayNotify
{
    protected $result = [];
    protected $task   = [];

    /**
     * 获取支付结果
     *
     * @return array
     */
    public function isSuccess()
    {
        $result = $this->values;

        return isset($result['return_code']) && isset($result['return_msg']) && $result["return_code"] == "SUCCESS" && $result["return_msg"] == "OK";
    }

    /**
     * 设置处理成功时的回调任务
     *
     * @param $task
     */
    public function setProcess($task)
    {
        $this->task = $task;
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

            return $result ? true : false;
        }catch (\WxPayException $e){
            $msg = $e->errorMessage();
        }

        return false;
    }
}