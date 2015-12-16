<?php

/**
 * Created by ax@jentian.com.
 * Date: 2015/6/5 14:51
 *
 *
 */
namespace app\example;

use app\example\config\Base;

class Config extends Base
{
    const JSON_FORMAT = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;
    const ACCEPT_MIME = ['json', 'html'];

    const TPL_AUTO_LOAD = true;
    const TPL_CACHING   = false;

    const SESSION_NAME = 'sys_ses_id';

    public static $webDefaultOption = [
        'titleSuffix' => '-金猫银猫',
        'description' => '',
        'keywords'    => ''
    ];
}

define('WEB_NAME', '金猫银猫');

define('URL_PAGE', \jt\utils\Url::current());
define('URL_LOGIN', '/');