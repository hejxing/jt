<?php
//CreateTime:2016-04-23 15:15:13
class Config
{
    const TIME_ZONE            = 'Asia/Shanghai';
    const CHARSET              = 'UTF-8';
    const ACCEPT_MIME          = [
        0 => 'json',
        1 => 'html',
    ];
    const JSON_FORMAT          = 384;
    const DEFAULT_AUTH_CHECKER = '\\auth\\Blocker';
    const NAMESPACE_PATH_MAP   = [
        'sys' => '/web/developer/ax/source/silverbag/api/..',
    ];
    const SMS_SIGNATURE        = '金猫银猫';
    const MAIL_FROM            = 'noreply<noreply@csmall.com>';
    const SESSION              = [
        'handler' => 'Redis',
        'idSaver' => 'Header,Url',
        'idName'  => 'ACCESS_TOKEN,token',
    ];

    const WECHAT_APP_PAY = [
        'appId' => '',
        'mchId' => '',
        'key' => '',
        'appSecret' => '',
        'sslCertPath' => ''
    ];

    const WECHAT_JS_API_PAY = [
        'appId' => '',
        'mchId' => '',
        'key' => '',
        'appSecret' => '',
        'sslCertPath' => ''
    ];

    const TEMPLATE             = [
        'pathRoot'      => '',
        'baseData'      =>
            [
            ],
        'force_compile' => true,
        'debugging'     => false,
    ];
    
    const CACHE_PROVIDER = 'Redis';
    const REDIS                = [
        'host'     => '127.0.0.1',
        'port'     => '6379',
        'time_out' => 0,
    ];
    const UPLOAD_ROOT          = '/data/uploadFile/silver_bag';
    const STATIC_HOST          = 'https://static_silver.csmall.com';
    const IMG_HOST             = 'http://static_silver.test.csmall.com';
}