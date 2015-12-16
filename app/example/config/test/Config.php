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

    const TPL_CACHING       = false;
    const TPL_FORCE_COMPILE = true;
}