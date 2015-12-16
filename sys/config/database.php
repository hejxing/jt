<?php
/**
 * Created by PhpStorm.
 * User: hejxing
 * Date: 2015/5/3
 * Time: 21:45
 */

return [
    '__base'    => [
        'dBPrefix'    => 'cs_',
        'tablePrefix' => '',
        'type'        => 'pgsql',
        'charset'     => 'UTF8',
        'host'        => '192.168.1.63',
        //'port' => '3306',
        'schema'      => 'silverbag',
        'user'        => 'postgres',
        'password'    => 'wskiliwallens',
        'timeout'     => 15 //s
    ],
    'generic'   => [//释放数据库的连接
                    //'schema' => 'account'
    ],
    'community' => [
        'schema'   => 'community',
        'user'     => 'community',
        'password' => '69846d0e46a44e64317541b8e90924730'
    ]
];

//immortal $config = [];