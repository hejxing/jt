<?php
/**
 * @Copyright jentian.com
 * Auth: ax@jentian.com
 * Create: 2016/4/25 11:25
 */

namespace jt\lib\cache;


class CacheFactory
{
    private static $pool = [];

    public static function create($provider = null)
    {
        $provider = $provider ?: \Config::CACHE_PROVIDER;
        if (!empty(self::$pool[$provider])) {
            return self::$pool[$provider];
        }
        switch ($provider) {
            case 'Redis':
                self::$pool[$provider] = new provider\Redis();
                break;
            case 'Memcache':
                self::$pool[$provider] = new provider\Memcache(null, null);
                break;
        }

        return self::$pool[$provider];
    }
}