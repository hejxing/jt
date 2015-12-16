<?php
/**
 * Created by PhpStorm.
 * User: 渐兴
 * Date: 15-4-3
 * Time: 22:13
 */

namespace jt\utils;


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
            if (!isset($default[$i])) {
                $default[$i] = '';
                while (strlen($default[$i]) < $length) {
                    $default[$i] .= base_convert(mt_rand(), 10, 16);
                }
            }
            $default[$i] = str_pad(substr($default[$i], 0, $length), $length, '0', STR_PAD_LEFT);
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
     * @param $flag
     *
     * @return bool
     */
    public static function isTheAgent($flag)
    {
        return (\strpos($_SERVER['HTTP_USER_AGENT'], $flag) !== false);
    }
}