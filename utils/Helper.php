<?php
/**
 * Created by PhpStorm.
 * User: 渐兴
 * Date: 15-4-3
 * Time: 22:13
 */

namespace jt\utils;

define('JT_CHAR_NUMBER', 1);
define('JT_CHAR_LOWERCASE', 2);
define('JT_CHAR_UPPERCASE', 4);
define('JT_CHAR_ZN_CH', 8);

class Helper
{
    /**
     * 生成UUID
     * 允许传入
     *
     * @param array  $default 默认的位置值
     * @param string $split 分隔符
     * @return string
     */
    public static function uuid(array $default = [], $split = '')
    {
        $partLength = [8, 4, 4, 4, 12];
        foreach ($partLength as $i => $length) {
            if (isset($default[$i])) {
                $default[$i] = str_pad(substr($default[$i], 0, $length), $length, '0', STR_PAD_LEFT);
            }else {
                $default[$i] = '';
                while (strlen($default[$i]) < $length) {
                    $default[$i] .= str_pad(base_convert(mt_rand(0, 65535), 10, 16), 4, '0', STR_PAD_LEFT);
                }
            }
        }
        ksort($default);

        return implode($split, $default);
    }

    /**
     * 生成密码
     *
     * @param string $pwd 密码明文
     * @param string $salt 扰乱因子
     * @return string
     */
    public static function encrypt($pwd, $salt)
    {
        return \md5($salt . $pwd . $salt);
    }

    /**
     * 删除索引数组的键，转换为普通数组
     *
     * @param array $array 要转换的索引数组
     * @return array 转换后的普通数组
     */
    public static function delArrayAssoc($array)
    {
        $newArr = [];
        foreach ($array as $value) {
            $newArr[] = $value;
        }

        return $newArr;
    }

    /**
     * 解析JSON为数组,如果字符串为空，返回空数组
     *
     * @param $json
     * @return mixed
     */
    public static function decodeJSON($json)
    {
        if (\is_string($json) && !empty($json)) {
            return \json_decode($json, true);
        }else {
            return [];
        }
    }

    /**
     * 平台类型
     *
     * @return string
     */
    public static function deviceType()
    {
        //获取USER AGENT
        $map   = [
            'pc'      => 'windows nt',
            'iphone'  => 'iphone',
            'ipad'    => 'ipad',
            'android' => 'android'
        ];
        $agent = \strtolower($_SERVER['HTTP_USER_AGENT']);
        foreach ($map as $type => $flag) {
            if (\strpos($agent, $flag) !== false) {
                return $type;
            }
        }

        return 'unknown';
    }

    /**
     * 判断是否为该终端
     *
     * @param string $flag MicroMessenger,
     *
     * @return bool
     */
    public static function isTheAgent($flag)
    {
        return (\strpos($_SERVER['HTTP_USER_AGENT'], $flag) !== false);
    }

    /**
     * 搜集列表所需的内容
     *
     * @param $map
     * @param $data
     * @return array
     */
    public static function mapList($map, $data)
    {
        $list = [];
        foreach ($map as $key) {
            if (isset($data[$key])) {
                $list[$key] = $data[$key];
            }
        }

        return $list;
    }

    /**
     * 获取客户端IP,检查代理(代理通常可伪造)
     *
     * @return string
     */
    private static function getClientIp()
    {
        $ip = null;
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                /* 取X-Forwarded-For中第一个非unknown的有效IP字符串 */
                foreach ($arr as $ip) {
                    $ip = trim($ip);
                    if ($ip != 'unknown') {
                        break;
                    }
                }
            }else {
                if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                    $ip = $_SERVER['HTTP_CLIENT_IP'];
                }
            }
        }else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $ip = getenv('HTTP_X_FORWARDED_FOR');
            }elseif (getenv('HTTP_CLIENT_IP')) {
                $ip = getenv('HTTP_CLIENT_IP');
            }
        }
        if ($ip === null) {
            $ip = self::getShakeIp();
        }

        return $ip;
    }

    private static function getShakeIp()
    {
        return $_SERVER['REMOTE_ADDR']??getenv('REMOTE_ADDR') ?: '0.0.0.0';
    }

    /**
     * 获取访客的IP地址
     *
     * @param bool $proxy 是否透过代理获取"真实IP"? 警告:该IP可以伪造
     * @param bool $long 返回的类型;true:将IP地址转换成整型返回;false:直接返回IP串
     * @return string||long
     */
    public static function getIp($proxy = true, $long = false)
    {
        if ($proxy) {
            /* 这类IP皆是可伪造的HTTP报文 */
            //此处为http报文,可伪造,不可靠
            $ip = self::getClientIp();
        }else {
            $ip = self::getShakeIp();
        }

        return $long ? ip2long($ip) : $ip;
    }
    public static function randString($length, $mask = JT_CHAR_NUMBER){

    }
}