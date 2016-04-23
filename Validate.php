<?php
/**
 * Created by PhpStorm.
 * User: 渐兴
 * Date: 15-4-26
 * Time: 15:12
 */

namespace jt;

class Validate
{
    const REGEX_EMAIL           = '/^[\w+\.]+@([\w-]+\.)+[\w]+$/';
    const REGEX_PHONE           = '/^\+?\d*(\([\d]+\))?([ -]{0,1}[\d]+)+$/';
    const REGEX_UNIVERSE_MOBILE = '/^(\(\+\d+\))?((?:\d+[ -]?\d+)+)$/';
    const REGEX_ID_CARD         = '/^(?:[\d -]{17}|[\d -]{14})[\dxX]{1}$/';
    const REGEX_ZH_CN           = '/^[\x{4e00}-\x{9fa5}]+$/u';

    /**
     * 检查是否是Email
     *
     * @param $value
     * @return bool
     */
    public static function email($value)
    {
        return (bool)preg_match(self::REGEX_EMAIL, $value);
    }

    /**
     * 检查是否是电话号码
     *
     * @param $value
     * @return bool
     */
    public static function phone($value)
    {
        return (bool)preg_match(self::REGEX_PHONE, $value);
    }

    /**
     * 检查是否是有效的手机号码（中国大陆手机号）
     *
     * @param      $value
     * @param bool $comb 是否整理手机格式
     * @return bool
     */
    public static function mobile($value, $comb = false)
    {
        $value = str_replace(' ', '', $value);
        if (preg_match(self::REGEX_UNIVERSE_MOBILE, $value, $matched)) {
            if ($matched[1] === '' || $matched[1] === '(+86)') {
                $matched[2] = str_replace('-', '', $matched[2]);

                if (strlen($matched[2]) === 11) {
                    return $comb ? $matched[2] : true;
                }
            }
        }

        return false;
    }

    /**
     * 检查是否是有效的手机号码(全球手机号)
     *
     * @param $value
     * @return bool
     */
    public static function universeMobile($value)
    {
        return (bool)preg_match(self::REGEX_UNIVERSE_MOBILE, $value);
    }

    /**
     * 检查是否是一个有效的身份证号码
     *
     * @param $value
     * @return bool
     */
    public static function identityCard($value)
    {
        $value = str_replace(' ', '', $value);
        if (!preg_match(self::REGEX_ID_CARD, $value)) {
            return false;
        }
        if (strlen($value) === 15) {
            return true;
        }

        $power = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        $ecc   = ['1', '0', 'x', '9', '8', '7', '6', '5', '4', '3', '2'];
        $sum   = 0;
        $chars = str_split(substr($value, 0, 17));
        foreach ($chars as $index => $v) {
            $sum += $power[$index] * $v;
        }
        $last = $ecc[$sum % 11];

        return $last === strtolower(substr($value, -1));
    }

    public static function number($value)
    {
        return \preg_match('/^\d+$/', $value) > 0;
    }

    public static function zh_cn($value)
    {
        return \preg_match(self::REGEX_ZH_CN, $value) > 0;
    }

    /**
     * 验证是否属于指定类型
     *
     * @param $value
     * @param $type
     * @return bool
     */
    public static function check($value, $type)
    {
        if (method_exists(__CLASS__, $type)) {
            return self::$type($value);
        }elseif (\preg_match('/\/.*\/\w*/', $type)) {//正则表达式
            return \preg_match($type, $value);
        }else {//错误的类型
            return null;
        }
    }
}