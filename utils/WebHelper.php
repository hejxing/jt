<?php
/**
 * Created by ax@jentian.com.
 * Date: 2015/7/14 15:25
 *
 *
 */

namespace jt\utils;


class WebHelper
{
    /**
     * 返回相差的时间，如果超过一天的就直接显示日期
     *
     * @param $time 要求为秒或毫秒 智能判定
     * @return string
     */
    public static function timeDiff($time)
    {
        if (is_numeric($time)) {// 认为是时间戳
            $timeSteamp = intval($time);
            if ($timeSteamp > 100000000000) {
                $timeSteamp = intval($timeSteamp / 1000);
            }
        }else {
            $timeSteamp = strtotime($time);
        }
        if (\jt\Bootstrap::$now - $timeSteamp < 86400) {
            return intval((\jt\Bootstrap::$now - $timeSteamp) / 3600) . ' 小时前';
        }else {
            return date('Y-m-d h:i', $timeSteamp);
        }
    }
}