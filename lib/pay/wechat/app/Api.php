<?php
/**
 *
 * 微信AppApi支付类
 * Created by Rocky
 * Date: 2015/9/3 17:38
 *
 */

namespace jt\lib\pay\wechat\app;

use jt\utils\Helper;
use jt\utils\Url;


require(__DIR__.'/../WxPay.Api.php');

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
            $notify_url = Url::host() . '/pay/callback/wx_app';
        }

        $this->notify_url = $notify_url;

        $this->data = array_merge($this->data, $data);

        \WxPayConfig::setConfig(\Config::WECHAT_APP_PAY);
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
        $outTradeNo = Helper::uuid();
        $input = new \WxPayUnifiedOrder();
        $input->SetBody($this->data['memo']);//
        $input->SetOut_trade_no($outTradeNo);//把订单ID放到这里去
        $input->SetTotal_fee($this->amount);
        //$this->writeOuttradenoMap($outTradeNo, $this->data['id']);
        $input->SetAttach($this->data['id']);//用这个来记录订单id,回调的时候根据这个来更新订单状态
        //$input->SetTime_start(date("YmdHis", \Init::$now));
        //$input->SetTime_expire(date("YmdHis", \Init::$now + 1000));
        //echo $this->data['memo'];exit();
        $input->SetGoods_tag($this->data['memo']);
        $input->SetNotify_url($this->notify_url);
        $input->SetTrade_type("APP");
        $result = \WxPayApi::getPayRequestParam($input);
        if(isset($result['package'])){
            $result['packages'] = $result['package'];
            $result['nonce_str'] = $result['noncestr'];
            unset($result['package']);
            unset($result['noncestr']);
        }
        $result['notify_url'] = $this->notify_url;
        $result['key'] = \WxPayConfig::$KEY;
        return $result;
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