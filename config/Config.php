<?php
/**
 * Created by PhpStorm.
 * User: hejxing
 * Date: 2015/5/23
 * Time: 2:21
 */

namespace config;

class Config
{
    const TIME_ZONE            = 'Asia/Chongqing';
    const CHARSET              = 'UTF-8';
    const ACCEPT_MIME          = ['json', 'html'];
    const JSON_FORMAT          = JSON_UNESCAPED_UNICODE;
    const DEFAULT_AUTH_CHECKER = '\app\system\permission\Blocker';

    const RUNTIME_PATH_ROOT = PROJECT_ROOT . '/runtime';

    const NAMESPACE_PATH_MAP = [];

    const MEMCACHED = [
        'persistentId' => 'pool',
        'serverList'   => [
            ['127.0.0.1', 11211]
        ]
    ];

    const SESSION = [
        'handler' => 'Redis',
        'idSaver' => 'header,url',
        'idName' => 'ACCESS_TOKEN,token'
    ];
}

class EnumList
{
    const map = [];

    public static function getValues()
    {
        $values = [];
        foreach (static::map as $key => $name) {
            if (defined("static::{$key}")) {
                $values[$name] = constant("static::{$key}");
            }
        }

        return $values;
    }
}


class Template extends EnumList
{
    const map           = [
        'PATH_ROOT'       => 'pathRoot',
        'AUTO_LOAD'       => 'autoLoad',
        'BASE_DATA'       => 'baseData',
        'PLUGINS'         => 'plugins',
        'FORCE_COMPILE'   => 'force_compile',
        'DEBUGGING'       => 'debugging',
        'SUFFIX'          => 'suffix',
        'CACHING'         => 'caching',
        'CACHE_LIFETIME'  => 'cache_lifetime',
        'LEFT_DELIMITER'  => 'left_delimiter',
        'RIGHT_DELIMITER' => 'right_delimiter'
    ];
    const PATH_ROOT     = '';
    const BASE_DATA     = [];
    const FORCE_COMPILE = true;
    const DEBUGGING     = false;
}

class Memcache
{
    const PERSISTENT_ID = 'pool';
    const SERVER_LIST   = [
        ['127.0.0.1', 11211]
    ];
}

class Redis
{
    const HOST = '127.0.0.1';
    const PORT = '6379';
    const TIME_OUT = 0;
}