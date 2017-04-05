<?php
/**
 * 发起并发请求
 * User: ax
 * Date: 2016/8/26 14:34
 */

namespace jt\utils;


use jt\Exception;
use Swoole\Client;
use Swoole\Process;

class MultiTransfer
{
    /**
     * @var string 通信主机IP
     */
    private $ip = '';
    /**
     * @var array url解析结果
     */
    protected $urlInfo = [];
    /**
     * @var array 配置选项
     */
    protected $option = [
        'ignoreHead' => true
    ];
    /**
     * @var string 发送的数据串
     */
    protected $dataSerial = '';
    /**
     * @var string 发送的头部数据
     */
    protected $headerSerial = '';
    /**
     * @var string 发送的Cookie数据
     */
    protected $cookieSerial = '';
    /**
     * @var int 当前启动的进程数
     */
    protected $threadsCount = 0;

    /**
     * MultiTransfer constructor.
     *
     * @param string $url
     * @param string $method
     */
    public function __construct($url, $method = 'get')
    {
        $this->urlInfo = parse_url($url);
        $this->method  = strtoupper($method);
        $this->host    = $this->urlInfo['host'];
        $this->ip      = gethostbyname($this->host);
        $this->uri     = $this->urlInfo['path'];
        if(!empty($this->urlInfo['query'])){
            $this->uri .= '?'.$this->urlInfo['query'];
        }
    }

    /**
     * 设置Header区数据
     *
     * @param array $data
     * @return $this
     */
    public function header(array $data)
    {
        //TODO serial header
        $this->headerSerial = $data;

        return $this;
    }

    /**
     * 设置Cookie
     *
     * @param array $data
     * @return $this
     */
    public function cookie(array $data)
    {
        //TODO serial cookie
        $this->dataSerial = $data;

        return $this;
    }

    /**
     * 设置发送的数据
     *
     * @param array $data
     * @param bool  $isQueryParam
     * @return $this
     */
    public function data(array $data, $isQueryParam = false)
    {
        if($isQueryParam){
            $this->uri = $this->urlInfo['path'].'?'.$this->serialParam($data);
        }else{
            $this->dataSerial = $this->serialParam($data);
        }

        return $this;
    }

    /**
     * 设置回调中返回的内容是否包含Header
     *
     * @param bool $ignore
     * @return $this
     */
    public function ignoreHead(bool $ignore = true)
    {
        $this->option['ignoreHead'] = $ignore;

        return $this;
    }

    /**
     * 设置最大并发线程数
     *
     * @param $count
     * @return $this
     */
    public function maxThreads($count)
    {
        $this->option['maxThreads'] = $count;

        return $this;
    }

    /**
     * 解析应答服务器应答的信息
     *
     * @param $stream
     * @return array|string
     */
    protected function parseStream($stream)
    {
        list(, $body) = explode("\r\n\r\n", $stream, 2);
        list($length, $body) = explode("\r\n", $body, 2);

        $body = substr($body, 0, hexdec($length));
        if($this->option['ignoreHead']){
            return $body;
        }
        $parsed           = [];
        $parsed['body']   = $body;
        $parsed['cookie'] = [];
        $parsed['header'] = [];

        return $parsed;
    }

    protected function controlThreadsCount()
    {
        if(!empty($this->option['maxThreads']) && $this->threadsCount >= $this->option['maxThreads']){
            //需要等待前面有事务处理完成后才能再继续发起请求了
            Process::wait();
            $this->threadsCount--;
        }
    }

    /**
     * 发送请求
     *
     * @param callable      $success 请求成功时的回调
     * @param callable|null $error 请求错误时的回调
     * @param callable|null $close 请求关闭时的回调
     */
    public function send(callable $success, callable $error = null, callable $close = null)
    {
        $this->controlThreadsCount();

        $process = new Process(function() use ($success, $error, $close){
            $client = $this->createTransfer();
            $client->on("receive", function(Client $cli, $stream) use ($success){
                if(empty($stream)){
                    $cli->close();
                }else{
                    $parsed = $this->parseStream($stream);
                    if($parsed){
                        call_user_func($success, $this->parseStream($stream));
                    }
                }
            });

            $client->on("error", $error?: function(Client $cli){

            });

            $client->on("close", $close?: function(Client $cli){

            });
            $client->connect($this->ip, 80, 0.5);
        });

        while($ret = Process::wait(false)){
            $this->threadsCount--;
        }

        $this->threadsCount++;
        $process->start();
    }

    /**
     * 等待子进程全退出后主进程再退出
     */
    public function waitUntilEnd()
    {
        while($ret = Process::wait()){
            $this->threadsCount--;
        }
    }

    /**
     * 创建请求发送器
     *
     * @return \Swoole\Client
     */
    protected function createTransfer()
    {
        $client = new Client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC); //异步非阻塞

        $client->set([
            'open_tcp_nodelay' => true,
            //'open_length_check' => 1,
            //'package_length_type'   => 'N',
            //'package_length_offset' => 0,       //第N个字节是包长度的值
            //'package_body_offset'   => 1,       //第几个字节开始计算长度
            //'open_eof_check'   => true,
            //'package_eof'      => "\r\n\r\n"
        ]);
        $client->on("connect", function(Client $cli){
            $content = implode("\r\n", [
                    strtoupper($this->method)." {$this->uri} HTTP/1.1",
                    "Host:$this->host",
                    'Content-Type: application/x-www-form-urlencoded',
                    "Content-Length:".strlen($this->dataSerial),
                    "Connection:close",
                    //"Accept-Encoding: gzip, deflate",
                    "\r\n"
                ]).$this->dataSerial;
            $cli->send($content);
        });

        return $client;
    }

    /**
     * 序列化字符串
     *
     * @param array $param
     * @return string
     */
    private function serialParam(array $param)
    {
        $dataSerial = '';
        foreach($param as $name => $value){
            $dataSerial .= '&'.$name.'='.urlencode($value);
        }

        return substr($dataSerial, 1);
    }

    /**
     * 判断是否满足执行条件
     *
     * @throws \jt\Exception
     */
    public static function __init($className)
    {
        if($className === __CLASS__){
            if(!class_exists('\Swoole\Client', false)){
                throw new Exception('RequireSwooleExtension:\Swoole\Client不存在，多线程模式依赖Swoole扩展');
            }
        }
    }
}