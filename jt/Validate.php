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
    const REGEX_UNIVERSE_MOBILE = '/^\+?(\d+[ -]?)(\d+[-]?\d+)+$/';

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
     * 检查是否是有效的手机号码
     *
     * @param $value
     * @return bool
     */
    public static function mobile($value)
    {
        if (preg_match(self::REGEX_UNIVERSE_MOBILE, $value)) {
            $value = str_replace('-', '', $value);
            $value = str_replace('+', '', $value);

            return strlen($value) === 11;
        }

        return false;
    }

    /**
     * 检查是否是有效的手机号码
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
    public static function idcard($value)
    {
        return true;
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
        return self::$type($value);
    }
}