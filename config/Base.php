<?php
/**
 * Created by PhpStorm.
 * User: hejxing
 * Date: 2015/5/23
 * Time: 2:21
 */

namespace config;

abstract class Base{
	const TIME_ZONE            = 'Asia/Chongqing';
	const CHARSET              = 'UTF-8';
	const ACCEPT_MIME          = ['json', 'html'];
	const JSON_FORMAT          = JSON_UNESCAPED_UNICODE;
	const DEFAULT_AUTH_CHECKER = '\app\shop_wx\auth\User';

	const PAGE_SIZE = 20;

	const TPL_AUTO_LOAD       = true;
	const TPL_SUFFIX          = '.tpl';
	const TPL_CACHING         = true;
	const TPL_FORCE_COMPILE   = false;
	const TPL_DEBUGGING       = false;
	const TPL_PLUGINS         = [];
	const TPL_CACHE_LIFETIME  = 86400;
	const TPL_LEFT_DELIMITER  = '{{';
	const TPL_RIGHT_DELIMITER = '}}';

	const RUNTIME_PATH_ROOT = CORE_ROOT . '/runtime';
	const LOG_PATH_ROOT     = CORE_ROOT . '/log';
	const TPL_PATH_ROOT     = DOCUMENT_ROOT . '/template';

	const SESSION_NAME = 'session_id';

	public static $memcacheHost = '192.168.1.63';
}