<?php

/**
 * Created by ax@jentian.com.
 * Date: 2015/6/5 14:51
 *
 *
 */
use config\Config as Base;
use config\Redis;

class Config extends Base
{
    const JSON_FORMAT = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;
}

class RedisConfig extends Redis
{

}