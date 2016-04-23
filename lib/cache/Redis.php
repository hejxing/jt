<?php
/**
 * @Copyright jentian.com
 * Auth: ax@jentian.com
 * Create: 2016/4/20 16:16
 */

namespace jt\lib\cache;


class Redis extends \Redis
{
    private static $saver = null;

    public static function create()
    {
        if (self::$saver === null) {
            self::$saver = new \Redis();
            self::$saver->pconnect(\Config::REDIS['host'], \Config::REDIS['port'], \Config::REDIS['time_out']);
        }

        return self::$saver;
    }
}