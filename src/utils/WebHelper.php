<?php
/**
 * Created by ax@csmall.com.
 * Date: 2015/7/14 15:25
 *
 *
 */

namespace jt\utils;


use jt\Bootstrap;

class WebHelper
{
    /**
     * 返回相差的时间，如果超过一天的就直接显示日期
     *
     * @param string|int|float $time 要求为秒或毫秒 智能判定
     * @return string
     */
    public static function timeDiff($time)
    {
        if(is_numeric($time)){// 认为是时间戳
            $timestamp = intval($time);
            if($timestamp > 100000000000){
                $timestamp = intval($timestamp / 1000);
            }
        }else{
            $timestamp = strtotime($time);
        }
        if(Bootstrap::$now - $timestamp < 86400){
            return intval((Bootstrap::$now - $timestamp) / 3600).' 小时前';
        }else{
            return date('Y-m-d h:i', $timestamp);
        }
    }
}