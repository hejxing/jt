<?php

/**
 * 支付宝支付接口
 *
 * @copyright jentian.com
 * @date 2012-02-21 22:36:39
 * @author hejxing
 */

namespace jt\lib\pay\alipay;

use jt\utils\Url;

class AliPay
{
    /**
     *支付宝网关地址（新）
     */
    protected $alipayGateWay = 'https://mapi.alipay.com/gateway.do?';
    /**
     * 接收支付宝反馈的页面
     *
     * @var string
     */
    protected $notifyUrl = '';
    /**
     * 支付成功后要跳转的页面
     *
     * @var string
     */
    protected $returnUrl = '';
    /**
     * 显示商品地址的页面
     *
     * @var string
     */
    protected $showUrl = '';

    /**
     * 签名方式 目前只支持该模式
     *
     * @var string
     */
    protected $signType = 'RSA';
    /**
     * 字符编码
     *
     * @var string
     */
    protected $inputCharset = 'utf-8';
    /**
     * 传输协议 http或https
     *
     * @var string
     */
    protected $transport = 'http';
    /**
     * 支付总额
     *
     * @var float
     */
    protected $amount = 0;
    /**
     * 购买的产品信息
     *
     * @var array
     */
    protected $product = [
        'name' => '',//商品名
        'id'   => '',//商品ID
        'desc' => ''//商品描述
    ];
    /**
     * 收货人信息
     *
     * @var array
     */
    protected $receive = [
        'name'    => '',//收货人姓名
        'address' => '',//收货人地址
        'zip'     => '',//邮编
        'phone'   => '',//电话
        'mobile'  => ''//手机
    ];

    protected $config = [];
    protected $data   = [];

    public function __construct(array $config, $targetType, $notify_url)
    {
        $this->targetType = $targetType;
        if (!preg_match('/^http[s]?:\/\//i', $notify_url)) {
            $notify_url = Url::host() . $notify_url;
        }

        $this->notify_url = $notify_url;

        $this->config                     = $config;
        $this->config['private_key_path'] = $this->config['key_path'] . '/rsa_private_key.pem';
    }

    private function payInit($amount, $id, $name, $memo)
    {
        $this->amount       = ceil($amount * 100);//单位为分,所以要乘100
        $this->data['id']   = $id;
        $this->data['name'] = $name;
        $this->data['memo'] = $memo;
    }

    /**
     * APP支付
     *
     * @param float  $amount 支付金额
     * @param string $id 支付对象ID
     * @param string $name 在支付平台显示的支付内容
     * @param string $memo 支付备注
     * @return array
     */
    public function appPay($amount, $id, $name, $memo)
    {
        $this->payInit($amount, $id, $name, $memo);

        $param  = [
            'partner'        => $this->config['pid'],
            'seller_id'      => $this->config['seller_email'],
            'out_trade_no'   => $this->data['id'],
            'subject'        => $this->data['name'],
            'body'           => '',//商品详情,
            'total_fee'      => $this->amount,
            'notify_url'     => $this->notify_url,
            'service'        => 'mobile.securitypay.pay',
            'payment_type'   => '1',
            '_input_charset' => $this->inputCharset,
            'it_b_pay'       => '30m',
            'return_url'     => $this->returnUrl,
            'paymethod'      => 'expressGateway'
        ];
        $buffer = [];
        ksort($param);
        foreach ($param as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $buffer[] = $key . '="' . $value.'"';
        }
        $queryString = implode('&', $buffer);

        $priKey = file_get_contents($this->config['private_key_path']);
        $res = openssl_get_privatekey($priKey);
        openssl_sign($queryString, $sign, $res);
        openssl_free_key($res);
        $sign = base64_encode($sign);

        return ['pay_url' => $this->alipayGateWay . $queryString . '&sign="' . $sign.'"&sign_type="'.$this->signType.'"'];
    }

    /**
     * 直接到账支付,返回支付页地址
     *
     * @return string
     */
    public function directPay()
    {
        $config = [
                "service"      => "create_direct_pay_by_user",
                "payment_type" => "1",

                "paymethod"   => '',
                "defaultbank" => '',

                "anti_phishing_key" => '',
                "exter_invoke_ip"   => '',

                "extra_common_param" => '',

                "royalty_type"       => '',
                "royalty_parameters" => '',

                "show_url" => $this->showUrl
            ] + $this->packageConfig() + $this->packageProduct();

        return $this->alipay($config);
    }

    /**
     * 担保交易支付,返回支付页地址
     *
     * @return string
     */
    public function escowPay()
    {
        return $this->alipay([
                "service"      => "create_partner_trade_by_buyer",
                "payment_type" => "1",
                "show_url"     => $this->showUrl
            ] + $this->packageConfig() + $this->packageProduct() + $this->packageLogistics() + $this->packageReceive());
    }

    /**
     * 双接口模式,返回支付页地址
     *
     * @return string
     */
    public function dualPay()
    {
        return $this->alipay([
                "service"      => "trade_create_by_buyer",
                "payment_type" => "1",
                "show_url"     => $this->showUrl
            ] + $this->packageConfig() + $this->packageProduct() + $this->packageLogistics() + $this->packageReceive());
    }

    /**
     * 银行直接支付,返回支付页地址
     *
     * @return string
     */
    public function bankPay()
    {
        return $this->alipay([
                "service"      => "create_direct_pay_by_user",
                "payment_type" => "1",

                "paymethod"   => '',
                "defaultbank" => $this->config['defaultBank'],

                "anti_phishing_key" => '',
                "exter_invoke_ip"   => '',

                "extra_common_param" => '',

                "royalty_type"       => '',
                "royalty_parameters" => '',

                "show_url" => $this->showUrl
            ] + $this->packageConfig() + $this->packageProduct());
    }

    /**
     * 获取支付页面地址
     *
     * @param array $config 支付方式等的配置
     * @return string
     */
    protected function alipay($config)
    {
        require_once(__DIR__ . '/alipay_submit.class.php');
        $alipaySubmit = new \AlipaySubmit($config);

        $para      = $alipaySubmit->buildRequestPara($this->packagePayServiceConfig());
        $urlBuffer = [];
        foreach ($para as $key => $val) {
            $urlBuffer[] = $key . '=' . $val;
        }

        return $this->alipayGateWay . implode('&', $urlBuffer);
    }

    /**
     * 获取回调、通知页面配置
     *
     * @return array
     */
    protected function packageReturnConfig()
    {
        return [
            "return_url" => $this->returnUrl,
            "notify_url" => $this->notifyUrl
        ];
    }

    /**
     * 打包支付配置
     *
     * @return array
     */
    protected function packagePayServiceConfig()
    {
        return $this->packageServiceConfig() + $this->packageReturnConfig();
    }

    /**
     * 获取支付宝主配置
     *
     * @return array
     */
    protected function packageServiceConfig()
    {
        return [
            'partner'          => $this->config['pid'],
            'key'              => $this->config['key'],
            'seller_email'     => $this->config['seller_email'],
            'sign_type'        => $this->signType,
            'private_key_path' => $this->config['private_key_path'],
            "input_charset"    => $this->inputCharset,
            "transport"        => $this->transport
        ];
    }

    /**
     * 获取支付配置
     *
     * @return array
     */
    protected function packageConfig()
    {
        return [
            'partner'          => $this->config['pid'],
            'seller_email'     => $this->config['seller_email'],
            'sign_type'        => $this->signType,
            'private_key_path' => $this->config['private_key_path'],
            '_input_charset'   => $this->inputCharset,
            "return_url"       => $this->returnUrl,
            "notify_url"       => $this->notifyUrl
        ];
    }

    /**
     * 获取商品信息 (支付内容)
     *
     * @return array
     */
    protected function packageProduct()
    {
        return [
            "out_trade_no" => $this->product['id'],
            "subject"      => $this->product['name'],//订单名称
            "body"         => $this->product['desc'],
            "price"        => $this->amount,
            "quantity"     => 1
        ];
    }

    /**
     * 获取收获人信息
     *
     * @return array
     */
    protected function packageReceive()
    {
        return [
            "receive_name"    => $this->receive['name'],
            "receive_address" => $this->receive['address'],
            "receive_zip"     => $this->receive['zip'],
            "receive_phone"   => $this->receive['phone'],
            "receive_mobile"  => $this->receive['mobile']
        ];
    }

    /**
     * 获取物流信息,包括物流方式和费用
     *
     * @return array
     */
    protected function packageLogistics()
    {
        return [
            'logistics_fee'     => '0.00',
            'logistics_type'    => 'EXPRESS',
            'logistics_payment' => 'SELLER_PAY'
        ];
    }

    /**
     * 验证支付成功反馈的真实性
     *
     * @return bool
     */
    public function notify()
    {
        require_once(__DIR__ . '/alipay_notify.class.php');
        $alipayNotify = new \AlipayNotify($this->packagePayServiceConfig());

        return $alipayNotify->verifyNotify();
    }

    ///**
    // * 发货通知
    // *
    // * @param \bll\finance\pay\Pay $pay 支付单
    // * @param string               $name 物流公司名
    // * @param string               $no 快递单号
    // * @param string               $type 物流类型
    // */
    //public function sendGoods(\bll\finance\pay\Pay $pay, $name, $no, $type)
    //{
    //    if ($pay->getStatus() != 11) {
    //        return;
    //    }
    //    require_once(__DIR__ . '/alipayLib/alipay_submit.class.php');
    //    $alipaySubmit = new \AlipaySubmit();
    //    //构造要请求的参数数组，无需改动
    //    $parameter = [
    //        "service"        => "send_goods_confirm_by_platform",
    //        "partner"        => $this->pid,
    //        "_input_charset" => $this->inputCharset,
    //        "trade_no"       => $pay->getTradeNo(),
    //        "logistics_name" => $name,
    //        "invoice_no"     => $no,
    //        "transport_type" => $type
    //    ];
    //
    //    $doc   = $alipaySubmit->sendPostInfo($parameter, $this->alipayGateWay, $this->packageServiceConfig());
    //}
}
