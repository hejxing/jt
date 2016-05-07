<?php
/**
 *
 * 微信JsApi支付类
 * Created by Rocky
 * Date: 2015/9/3 17:38
 *
 */

namespace jt\lib\pay\wechat\js_api;

use jt\utils\Helper;
use jt\utils\Url;


require('../WxPay.Api.php');
require('../WxPay.JsApiPay.php');

class Api
{
    protected $notify_url = '';
    /**
     * 订单相关参数
     *
     * @var
     */
    protected $amount = 0.0;
    protected $data   = ['memo' => 'CSMALL', 'id' => ''];

    public function __construct($notify_url = null, $data = [])
    {
        if (!$notify_url) {
            $notify_url = Url::host() . '/pay/callback/wx_js';
        }

        $this->notify_url = $notify_url;

        $this->data = array_merge($this->data, $data);
        
        \WxPayConfig::setConfig(\Config::WECHAT_JS_API_PAY);
    }

    /**
     * 统一下单
     *
     * @param $amount
     *
     * @return string
     */
    private function getUnifiedOrder()
    {
        $tools  = new \JsApiPay();
        $openId = $tools->GetOpenid();    //获取用户openID

        $outTradeNo = Helper::uuid();

        $input = new \WxPayUnifiedOrder();
        $input->SetBody($this->data['memo']);
        $input->SetAttach($this->data['id']);
        $input->SetOut_trade_no($outTradeNo);
        $input->SetTotal_fee($this->amount);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag($this->data['memo']);
        $input->SetNotify_url($this->notify_url);
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order = \WxPayApi::unifiedOrder($input);

        $jsApiParameters = $tools->GetJsApiParameters($order);
        //获取共享收货地址js函数参数
        $editAddress = $tools->GetEditAddressParameters();

        return [
            'jsApiParameters' => $jsApiParameters,
            'editAddress'     => $editAddress,
            'amount'          => $this->amount,
            'orderId'         => $this->data['id']
        ];
    }


    /**
     * 支付
     *
     * @param $amount
     * @param $id
     * @param $name
     *
     * @return array
     */
    public function pay($amount, $id, $name)
    {
        $this->amount       = ceil($amount * 100);//单位为分,所以要乘100
        $this->data['id']   = $id;
        $this->data['memo'] = $name;

        return $this->getUnifiedOrder();
    }
}