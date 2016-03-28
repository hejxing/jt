<?php

/**
 * @Copyright jentian.com
 * Auth: hejxi
 * Create: 2015/12/3 9:45
 */
namespace jt\service\weChat;

use jt\service\cache\Factory;
use jt\utils\Url;

class Auth
{
    //最新app用服务号
    /**
     * @type \Memcached
     */
    private $cacher = null;
    private $appId  = '';
    private $secret = '';
    private $code   = '';
    private $state  = 'enter';
    //给微信方的回调地址

    private static $authUrlPrefix = 'https://open.weixin.qq.com/connect/oauth2/authorize?';
    private static $readUrlPrefix = 'https://api.weixin.qq.com/sns/oauth2/access_token?';
    private static $infoUrlPrefix = 'https://api.weixin.qq.com/sns/userinfo?';

    //private static $redirectUrlPrefix = 'http://wxapp.jentian.com/callback';

    private static $responseType = 'code';

    /**
     * 初始设置相关配置
     *
     * @param string $appId
     * @param string $secret
     * @param string $code
     */
    public function __construct($appId, $secret = '', $code = '')
    {
        $this->appId  = $appId;
        $this->secret = $secret;
        $this->code   = $code;
        $this->cacher = Factory::memcached(\Config::MEMCACHED['serverList'], \Config::MEMCACHED['persistentId']);
    }

    /**
     * 准备获取微信端身份识别码 无需授权
     *
     * @param string $redirectUrl 成功后跳转页面
     */
    public function toGetAuthorize($redirectUrl)
    {
        //对code进行缓存
        Url::redirect($this->generateAuthUrl('snsapi_base', $redirectUrl));
    }

    /**
     * 获取微信用户信息 需要授权
     *
     * @param string $redirectUrl
     */
    public function toGetAccessToken($redirectUrl)
    {
        Url::redirect($this->generateAuthUrl('snsapi_userinfo', $redirectUrl));
    }

    /**
     * 获取微信返回内容
     *
     * @param string $code
     * @param string $openId
     * @return array
     */
    public function getUserInfo($code = null, $openId = null)
    {
        if ($code) {
            $this->code = $code;
        }//验证token是否存在,如果存在则不需要获取

        $token = $this->getAuthorize();
        $this->cacher->set('we_chat_access_token', $token['access_token'], floatval($token['expires_in']));
        $resultString = file_get_contents(self::generateInfoUrl($token['access_token'], $token['openid']));
        $info         = json_decode($resultString, true);

        return $info;
    }

    /**
     * 设置自定义的参数
     *
     * @param $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * 生成获取OPENID的链接
     *
     * @param $scope
     * @param $redirectUrl
     * @return string
     */
    protected function generateAuthUrl($scope, $redirectUrl)
    {
        $queryParam = [
            'appid=' . $this->appId,
            'redirect_uri=' . $redirectUrl,
            'response_type=' . self::$responseType,
            'scope=' . $scope,
            'state=' . $this->state
        ];

        return self::$authUrlPrefix . join('&', $queryParam) . '#wechat_redirect';
    }

    /**
     * 获取微信返回的本次调用授权口令
     *
     * @param string $code
     *
     * @return array
     */
    public function getAuthorize($code = null)
    {
        //对本口令进行缓存
        if ($code) {
            $this->code = $code;
        }
        $resultString = file_get_contents(self::generateAccessTokenUrl());
        $token        = json_decode($resultString, true);

        return $token;
    }

    /**
     * 生成获取微信返回内容的URL
     *
     * @return string
     */
    protected function generateAccessTokenUrl()
    {
        $queryParam = [
            'appid=' . $this->appId,
            'secret=' . $this->secret,
            'code=' . $this->code,
            'grant_type=authorization_code'
        ];

        return self::$readUrlPrefix . join('&', $queryParam);
    }

    /**
     * 获取用户公开信息
     *
     * @param string $token
     * @param string $openId
     * @return string
     */
    protected function generateInfoUrl($token, $openId)
    {
        $queryParam = [
            'access_token=' . $token,
            'openid=' . $openId
        ];

        return self::$infoUrlPrefix . join('&', $queryParam);
    }
}