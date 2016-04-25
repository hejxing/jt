<?php

/**
 * @Copyright jentian.com
 * Auth: hejxi
 * Create: 2015/11/25 11:31
 */
namespace jt\lib\cache\provider;

/**
 * Memcache的轻度封装
 *
 * @package jt\lib\cache\provider
 */
class Memcache extends \Memcached
{
    public function __construct($persistent_id, $callback)
    {
        parent::__construct($persistent_id, $callback);
    }
}