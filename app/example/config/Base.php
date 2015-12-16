<?php
/**
 * Created by PhpStorm.
 * User: hejxing
 * Date: 2015/5/23
 * Time: 2:21
 */

namespace app\example\config;

abstract class Base extends \config\Base
{
    const TIME_ZONE            = 'Asia/Chongqing';
    const CHARSET              = 'UTF-8';
    const ACCEPT_MIME          = ['html', 'json', 'xml'];
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

    public static $webDefaultData = [];
}