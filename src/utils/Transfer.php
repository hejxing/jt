<?php
/**
 * 网络请求
 * Class Transfer
 *
 * @package lib
 */

namespace jt\utils;

use jt\Exception;

class Transfer
{
    /**
     * @var string 服务器返回的数据流
     */
    protected $stream = '';
    /**
     * @var null 解析后的结果
     */
    protected $body = null;
    /**
     * @var string 接口路径
     */
    protected $gateway = '';
    /**
     * 发送方式
     *
     * @var string
     */
    protected $method = '';
    /**
     * @var int 请求超时时间
     */
    protected $timeout = 3;
    /**
     * @var array 要发送的数据
     */
    protected $data = [];
    /**
     * @var bool 是否需要签名
     */
    private $needSign = false;
    /**
     *
     * @var string
     */
    protected $asJson = false;

    public static function getContent($url, $timeout = 3)
    {
        $transfer = new self($url, 'get');
        $transfer->setTimeout($timeout);

        return $transfer->getStream();
    }

    /**
     * 初始设置接口地址和发送方法
     *
     * @param      $url
     * @param      $method
     */
    public function __construct($url, $method = 'get')
    {
        $this->setGateWay($url);
        $this->method = strtoupper($method);
    }

    /**
     * 设置超时时间 (单位:s)
     *
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = intval($timeout);
    }

    /**
     * 设置是否需要签名
     * @param bool $need
     */
    public function setNeedSign(bool $need){
        $this->needSign = $need;
    }

    public function sendAsJson()
    {
        $this->asJson = true;
    }

    /**
     * 设置网络访问地址
     *
     * @param $url
     */
    private function setGateWay($url)
    {
        $this->gateway = $url;
    }

    /**
     * 添加要发送的数据
     *
     * @param array $data
     */
    public function addData(array $data)
    {
        $this->data = array_merge($this->data, $data);
    }

    /**
     * 与服务器通信
     */
    protected function transfer()
    {
        if(!function_exists('curl_init')){
            throw new Exception('NeedCurlExtension:Need to open the curl extension');
        }
        $ci   = \curl_init();
        $data = $this->asJson? json_encode($this->data, JSON_UNESCAPED_UNICODE): $this->data;
        if($this->method === 'GET'){
            $this->gateway = Url::addQueryParam($data, $this->gateway);
            $data          = null;
        }

        $options = [
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HEADER         => 0,        // 1:获取头部信息
            CURLOPT_RETURNTRANSFER => 1,        // 1:获取的信息以文件流的形式返回
            CURLOPT_POSTFIELDS     => $data,    // post数据
            CURLOPT_URL            => $this->gateway,
            CURLOPT_CUSTOMREQUEST  => $this->method
        ];
        curl_setopt_array($ci, $options);
        $this->stream = curl_exec($ci);
        //\jt\utils\Debug::log('TransferGetStream', $this->stream);
        curl_close($ci);
    }

    /**
     * 分离解析数据
     */
    private function parse()
    {
        //分离: 协议、头部信息、主体内容
        //list(, $body) = \explode("\r\n\r\n", $this->stream);
        //var_dump($this->stream);
        //$this->stream = str_replace('null', '""', $this->stream);
        $this->body = \json_decode($this->stream, true);
    }

    /**
     * 为发送的数据签名
     */
    private function sign()
    {
        $this->data['key'] = \Config::PARTNER_KEY;
        if(RUN_MODE === 'test'){
            $this->data['is_test'] = 1;
        }
        ksort($this->data);
        $param              = implode('&', $this->data);
        $this->data['sign'] = \md5($param.\Config::SIGN_SALT);
    }

    /**
     * 发送请求
     *
     * @return array
     */
    public function send()
    {
        if($this->needSign){
            $this->sign();
        }

        $this->transfer();
        $this->parse();

        return $this->body;
    }

    /**
     * 直接获取结果，不解析
     */
    public function getStream()
    {
        $this->transfer();

        return $this->stream;
    }
}