<?php
/**
 * @Copyright jentian.com
 * Auth: hejxi
 * Create: 2015/12/11 14:00
 */

namespace jt\service\cache;


use jt\Exception;

class Factory
{
    /**
     * 生成Memcached实例
     *
     * @param array  $serverList
     * @param string $persistentId 持久连接的名称
     * @return \Memcached
     * @throws Exception
     */
    public static function memcached(array $serverList, $persistentId = '')
    {
        $saver = new \Memcached($persistentId);
        if (!count($saver->getServerList())) {
            $saver->addServers($serverList);
        }
        if (!$saver->set('checkServer', true)) {
            throw new Exception('MemcachedServiceDisable:Memcached服务不可用');
        }

        return $saver;
    }

    /**
     * 生成Redis实例
     *
     * @param array  $serverList
     * @param string $persistentId
     * @return \Redis
     */
    public static function redis(array $serverList, $persistentId = '')
    {
        $redis = new \Redis();
        $redis->pconnect($serverList);
        $redis->persist($persistentId);
        return $redis;
    }
}