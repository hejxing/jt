<?php
/**
 * 网络请求
 * Class Transfer
 * @package lib
 */
namespace jt\utils;
class Transfer{
	/**
	 * 服务器返回的数据流
	 * @var string
	 */
	private $stream = '';
	/**
	 * 解析后的结果
	 * @var null
	 */
	private $body = null;
	/**
	 * 接口路径
	 * @var string
	 */
	private $gateway = '';
	/**
	 * 发送方式
	 * @var string
	 */
	private $method = '';
	/**
	 * 要发送的数据
	 * @var array
	 */
	private $data = [];

	/**
	 * 初始设置接口地址和发送方法
	 * @param $uri
	 * @param $method
	 */
	public function __construct($uri, $method){

		$this->setGateWay($uri);
		$this->method = $method;
	}

	/**
	 * 设置网络访问地址
	 * @param $url
	 */
	private function setGateWay($url){
		$this->gateway = $url;
	}

	/**
	 * 添加要发送的数据
	 * @param array $data
	 */
	public function addData(array $data){
		$this->data = array_merge($this->data, $data);
	}

	/**
	 * 与服务器通信
	 */
	private function transfer(){
		if (!function_exists('curl_init')){
			exit('Need to open the curl extension');
		}
		$ci = \curl_init();
		$options = [
			CURLOPT_TIMEOUT        => 60,
			CURLOPT_HEADER         => 0,        // 1:获取头部信息
			CURLOPT_RETURNTRANSFER => 1,        // 1:获取的信息以文件流的形式返回
			CURLOPT_POSTFIELDS     => $this->data,    // post数据
			CURLOPT_URL            => $this->gateway
		];
		curl_setopt_array($ci, $options);
		$this->stream = curl_exec($ci);
		//\jt\utils\Debug::log('TransferGetStream', $this->stream);
		curl_close($ci);
	}

	/**
	 * 分离解析数据
	 * @return array
	 */
	private function parse(){
		//分离: 协议、头部信息、主体内容
		//list(, $body) = \explode("\r\n\r\n", $this->stream);
		//var_dump($this->stream);
		//$this->stream = str_replace('null', '""', $this->stream);
		$this->body = \json_decode($this->stream, true);
	}

	/**
	 * 为发送的数据签名
	 */
	private function sign(){
		$this->data['key'] = \Config::PARTNER_KEY;
		if (\Config::IS_TEST){
			$this->data['is_test'] = 1;
		}
		ksort($this->data);
		$param = implode('&', $this->data);
		$this->data['sign'] = \md5($param . \Config::SIGN_SALT);
	}

	/**
	 * 发送请求
	 * @return array
	 */
	public function send(){
		//$this->sign();
		$this->transfer();
		$this->parse();

		return $this->body;
	}

	/**
	 * 直接获取结果，不解析
	 */
	public function getStream(){
		$this->transfer();
		return \explode("\r\n\r\n", $this->stream)[1];
	}
}