<?php
/**
 * @Copyright jentian.com
 * Auth: ax@jentian.com
 * Create: 2016/4/20 16:16
 */

namespace jt\lib\cache\provider;


class Redis extends \Redis
{
    public function __construct()
    {
        $this->pconnect(\Config::REDIS['host'], \Config::REDIS['port'], \Config::REDIS['time_out']);
    }
}