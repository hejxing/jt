<?php
/**
 * @Copyright csmall.com
 * Auth: ax@csmall.com
 * Create: 2016/5/12 16:06
 */

namespace jt\utils;

use jt\Controller;
use jt\lib\cache\CacheFactory;

class Notification
{
    private static $channel = [];

    /**
     * 添加消息、通知
     *
     * @param string $targetType 接收消息的对象类型
     * @param string $targetId 接收消息的对象ID
     * @param string $task 任务类型
     * @param array  $data 通知内容
     * @return bool
     */
    public static function push($targetType, $targetId, $task, $data)
    {
        $cache = CacheFactory::create();

        return $cache->set($targetType.'_'.$targetId, serialize(['task' => $task, 'info' => $data]));
    }

    /**
     * 刷出事件、消息
     */
    public static function flush()
    {
        $cache = CacheFactory::create();
        $event = [];
        foreach(self::$channel as $c){
            $seek = $c[0].'_'.$c[1];
            $data = $cache->get($seek);
            if($data){
                $cache->del($seek);
                $event[] = unserialize($data);
            }
        }
        if(!empty($event)){
            Controller::current()->getAction()->header('event', $event);
        }
    }

    /**
     * 添加消息订阅者
     *
     * @param $targetType
     * @param $targetId
     */
    public static function subscriber($targetType, $targetId)
    {
        self::$channel[] = [$targetType, $targetId];
    }

    /**
     * 注册一个事件
     *
     * @param $class
     */
    public static function __init($class)
    {
        if(__CLASS__ === $class){
            Controller::current()->hook('render', [__CLASS__, 'flush']);
        }
    }

}