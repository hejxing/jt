<?php
/**
 * Created by PhpStorm.
 * User: hejxing
 * Date: 2015/5/23
 * Time: 2:21
 */

namespace sys\config;

/**
 * 配置的基础文件，其它配置将基于此进行覆盖、扩展
 *
 * @package app\example\config
 */
abstract class Base extends \config\Base
{
    const CHARSET              = 'UTF-8';
    const ACCEPT_MIME          = ['json', 'html'];
    const JSON_FORMAT          = JSON_UNESCAPED_UNICODE;
    const DEFAULT_AUTH_CHECKER = 'auth';

    const TPL_AUTO_LOAD       = true;
    const TPL_SUFFIX          = '.tpl';
    const TPL_CACHING         = true;
    const TPL_DEBUGGING       = false;
    const TPL_PLUGINS         = [];
    const TPL_CACHE_LIFETIME  = 86400;
    const TPL_LEFT_DELIMITER  = '{{';
    const TPL_RIGHT_DELIMITER = '}}';
    const TPL_PATH_ROOT       = DOCUMENT_ROOT . '/template/default';

    public static $webDefaultData = [
        'title'       => '小银袋',
        'description' => '',
        'keywords'    => ''
    ];

    public static $memcacheHost = '192.168.1.63';
}