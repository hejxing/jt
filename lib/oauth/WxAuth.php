<?php

/**
 * Date: 15-3-25 00:23
 */

namespace jt\lib\oauth;

use jt\Responder;
use jt\utils\Helper;

class WxAuth
{
    /**
     * @var string 最新app用服务号
     */
    private $appId = '';
    /**
     * @var string
     */
    private $secret = '';
    /**
     * @var string
     */
    private $responseType = 'code';
    /**
     * @var string
     */
    private $state = 'enter';

    private static $authUrlPrefix   = 'https://open.weixin.qq.com/connect/oauth2/authorize?';
    private static $readUrlPrefix   = 'https://api.weixin.qq.com/sns/oauth2/access_token?';
    private static $infoUrlPrefix   = 'https://api.weixin.qq.com/sns/userinfo?';
    private static $ticketUrlPrefix = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket';

    /**
     * WxAuth constructor.
     *
     * @param $appId
     * @param $secret
     */
    public function __construct($appId, $secret, $responseType = 'code')
    {
        $this->appId  = $appId;
        $this->secret = $secret;
    }

    /**
     * 准备获取微信端身份识别码
     *
     * @param string $redirectUrl 成功后跳转页面
     */
    public function toGetAuthorize($redirectUrl)
    {
        Responder::redirect($this->generateAuthUrl('snsapi_base', $redirectUrl));
    }

    /**
     * 获取微信用户信息
     *
     * @param string $redirectUrl
     * @param string $state
     */
    public function toGetAccessToken($redirectUrl, $state = 'enter')
    {
        $this->state = $state;
        Responder::redirect(self::generateAuthUrl('snsapi_userinfo', $redirectUrl));
    }

    protected function generateAuthUrl($scope, $redirectUrl)
    {
        $queryParam = [
            'appid='.$this->appId,
            'redirect_uri='.$redirectUrl,
            'response_type='.$this->responseType,
            'scope='.$scope,
            'state='.$this->state,
            'connect_redirect=1'
        ];

        return self::$authUrlPrefix.join('&', $queryParam).'#wechat_redirect';
    }

    /**
     * 获取微信返回内容
     *
     * @param string $code
     * @return array
     */
    public function getAuthorize($code)
    {
        $resultString = file_get_contents($this->generateAccessTokenUrl($code));

        return json_decode($resultString, true);
    }

    /**
     * 生成获取微信返回内容的URL
     *
     * @param string $code
     * @return string
     */
    protected function generateAccessTokenUrl($code)
    {
        $queryParam = [
            'appid='.$this->appId,
            'secret='.$this->secret,
            'code='.$code,
            'grant_type=authorization_code'
        ];

        return self::$readUrlPrefix.join('&', $queryParam);
    }

    /**
     * 获取微信返回内容
     *
     * @param string $code
     * @return array
     */
    public function getUserInfo($code)
    {
        $token        = $this->getAuthorize($code);
        $resultString = file_get_contents(self::generateInfoUrl($token['access_token'], $token['openid']));

        return json_decode($resultString, true);
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
            'access_token='.$token,
            'openid='.$openId
        ];

        return self::$infoUrlPrefix.join('&', $queryParam);
    }

    /**
     * 获取调用JSAPI的门票
     *
     * @param string $code
     */
    public function getJsApiTicket($code)
    {
        $authorize    = $this->getAuthorize($code);
        $accessToken  = $authorize['access_token'];
        $resultString = file_get_contents(self::$ticketUrlPrefix."?access_token=$accessToken&type=jsapi");
        $result       = Helper::decodeJSON($resultString);

        return $result['ticket'];
    }
}

