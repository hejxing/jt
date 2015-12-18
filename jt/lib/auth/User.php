<?php
/**
 * Created by ax@jentian.com.
 * Date: 2015/6/5 14:39
 *
 *
 */
namespace jt\lib\auth;

use jt\Auth;
use jt\lib\cache\Memcache;
use jt\utils\Helper;

/**
 * 负责管理用户登录、身份认证
 *
 * @package jt\lib\auth
 */
class User extends Auth
{
    /**
     * 用户资料
     *
     * @type array
     */
    protected static $data = [];
    /**
     * 是否登录
     *
     * @type bool
     */
    protected static $isLogin = false;
    /**
     * 会员ID
     *
     * @type string
     */
    protected static $id = '';
    /**
     * 登录时用的账号
     *
     * @type string
     */
    protected static $account = '';
    /**
     * 用户姓
     *
     * @type string
     */
    protected static $familyName = '';
    /**
     * 用户名
     *
     * @type string
     */
    protected static $name = '';
    /**
     * token前缀
     *
     * @type string
     */
    protected static $tokenPrefix = '';
    /**
     * token值
     *
     * @type string
     */
    protected static $token = '';

    /**
     * 构建用户
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        self::$data    = $data;
        self::$id      = $data['id'];
        self::$account = $data['username'];
    }

    /**
     * 获取用户登录信息
     *
     * @param $token
     */
    public static function loadFromMemcached($token)
    {
        $saver = new Memcache();
        $data  = $saver->get('list_user_session_' . $token);
        if ($data && isset($data['websiteMember']) && isset($data['websiteMember']['id'])) {
            $data            = $data['websiteMember'];
            $data['account'] = $data['username'];
            unset($data['username']);
            self::setUserInfo($data, $token);
        }
    }

    /**
     * 保存登录会话
     *
     * @param array  $data
     * @param string $token
     */
    protected static function setUserInfo(array $data, $token)
    {
        self::$data       = $data;
        self::$id         = $data['id'];
        self::$account    = $data['username'];
        self::$familyName = $data['familyName'];
        self::$name       = $data['name'];
        if (!self::$name) {
            self::$name = self::$account;
        }
        self::$isLogin = true;
        \setcookie(\Config::SESSION_NAME, $token, 0, '/');
    }

    /**
     * 获取用户姓名，如果用户姓名不存在，则用登录账号替代
     *
     * @return string
     */
    public static function getName()
    {
        return static::$name;
    }

    /**
     * 是否登录了
     *
     * @return bool
     */
    public static function isLogin()
    {
        return static::$isLogin;
    }

    /**
     * 设置sessionID
     *
     * @return string
     */
    protected static function getSessionId()
    {
        if (isset($_COOKIE[\Config::SESSION_NAME])) {
            return $_COOKIE[\Config::SESSION_NAME];
        }

        return null;
    }

    /**
     * 获取登录用户的数据
     *
     * @return array
     */
    public static function getData()
    {
        return self::$data;
    }

    /**
     * 保存已登录的用户信息
     *
     * @param array $data
     * @return bool
     */
    public static function hold(array $data)
    {
        $token = Helper::uuid();
        $saver = new Memcache();
        if ($saver->set(self::genSeed($token), $data)) {
            self::setUserInfo($data, $token);

            return true;
        }

        return false;
    }

    /**
     * 生成保存的键名
     *
     * @param string $token
     * @return string
     */
    protected static function genSeed($token = null)
    {
        if ($token === null) {
            $token = Helper::uuid();
        }
        self::$token = $token;

        return static::$tokenPrefix . self::$token;
    }

    /**
     * 获取token
     *
     * @return mixed
     */
    public static function getToken()
    {
        return self::$token;
    }

    public function auth()
    {
        // TODO: Implement auth() method.
    }

    public function filter()
    {
        // TODO: Implement filter() method.
    }
}