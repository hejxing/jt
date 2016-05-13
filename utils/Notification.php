<?php
/**
 * @Copyright jentian.com
 * Auth: ax@jentian.com
 * Create: 2016/5/12 16:06
 */

namespace jt\utils;


class Notification
{
    /**
     * 添加消息、通知
     *
     * @param string $targetType 接收消息的对象类型
     * @param string $targetId 接收消息的对象ID
     * @param array  $data 通知内容
     * @return bool
     */
    public static function add($targetType, $targetId, $data)
    {
        return true;
    }

    public static function __init($class){
        if(__CLASS__ === $class){
            $config
        }
    }
}