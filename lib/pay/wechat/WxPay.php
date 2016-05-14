<?php
/**
 *
 * 微信AppApi支付类
 * Created by Rocky
 * Date: 2015/9/3 17:38
 *
 */

namespace jt\lib\pay\wechat;

use jt\utils\Url;


require(__DIR__ . '/WxPay.Api.php');
require(__DIR__ . '/WxPay.JsApiPay.php');

class WxPay
{
    protected $notify_url = '';
    /**
     * 订单相关参数
     *
     * @var
     */
    protected $amount     = 0.0;
    protected $outTradeNo = '';
    protected $targetType = '';
    protected $data       = ['memo' => 'CSMALL', 'id' => ''];

    public function __construct(array $config, $targetType, $notify_url)
    {
        $this->targetType = $targetType;
        if (!preg_match('/^http[s]?:\/\//i', $notify_url)) {
            $notify_url = Url::host() . $notify_url;
        }

        $this->notify_url = $notify_url;

        \WxPayConfig::setConfig($config);
    }

    private function genInput()
    {
        $input = new \WxPayUnifiedOrder();
        $input->SetBody($this->data['memo']);//
        $input->SetAttach($this->data['id']);//用这个来记录订单id,回调的时候根据这个来更新订单状态
        $input->SetOut_trade_no($this->outTradeNo);//把订单ID放到这里去
        $input->SetTotal_fee($this->amount);
        $input->SetGoods_tag($this->data['memo']);
        $input->SetNotify_url($this->notify_url);

        return $input;
    }

    /**
     * 统一下单
     *
     * @return string
     */
    private function getAppUnifiedOrder()
    {
        $input = $this->genInput();
        $input->SetTrade_type("APP");
        $result = \WxPayApi::getPayRequestParam($input);
        $result['notify_url'] = $this->notify_url;
        $result['key']        = \WxPayConfig::$KEY;

        return $result;
    }

    private function getJsApiUnifiedOrder()
    {
        $tools  = new \JsApiPay();
        $openId = $tools->GetOpenid();    //获取用户openID

        $input = $this->genInput();

        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
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

    private function payInit($amount, $id, $name, $memo)
    {
        $this->amount       = ceil($amount * 100);//单位为分,所以要乘100
        $this->data['id']   = $id;
        $this->data['name'] = $name;
        $this->data['memo'] = $memo;

        $this->outTradeNo = $id;
    }


    /**
     * App支付
     *
     * @param $amount
     * @param $id
     * @param $name
     *
     * @return array
     */
    public function appPay($amount, $id, $name, $memo)
    {
        $this->payInit($amount, $id, $name, $memo);

        return $this->getAppUnifiedOrder();
    }

    /**
     * 移动端JS API支付
     *
     * @param $amount
     * @param $id
     * @param $memo
     * @return array
     */
    public function jsApiPay($amount, $id, $name, $memo)
    {
        $this->payInit($amount, $id, $name, $memo);

        return $this->getJsApiUnifiedOrder();
    }
}